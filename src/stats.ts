import { addDays, monthKey, parseLocalDate, todayIso } from './format'
import type { AppState, Client, Movement, Product } from './types'

export type ClientStat = {
  client: Client
  quantity: number
  amount: number
  count: number
  avgQuantity: number
  avgAmount: number
}

export type ProductStat = {
  product: Product
  quantity: number
  amount: number
  share: number
}

export type DashboardStats = {
  totalQuantity: number
  totalAmount: number
  movementCount: number
  monthCount: number
  monthlyAvgQuantity: number
  monthlyAvgAmount: number
  weeklyAvgQuantity: number
  weeklyAvgAmount: number
  last30: { quantity: number; amount: number; count: number }
  prev30: { quantity: number; amount: number; count: number }
  amountDeltaPct: number | null
  quantityDeltaPct: number | null
  bestByAmount: ClientStat[]
  bestByQuantity: ClientStat[]
  clientAverages: ClientStat[]
  productMix: ProductStat[]
  recent: Movement[]
  stockHintQuantity: number
}

function inRange(date: string, from: string, to: string): boolean {
  return date >= from && date <= to
}

function pctDelta(current: number, previous: number): number | null {
  if (previous === 0) return current === 0 ? 0 : null
  return ((current - previous) / previous) * 100
}

function clientStats(state: AppState, movements: Movement[]): ClientStat[] {
  const byId = new Map<string, ClientStat>()
  for (const client of state.clients) {
    byId.set(client.id, {
      client,
      quantity: 0,
      amount: 0,
      count: 0,
      avgQuantity: 0,
      avgAmount: 0,
    })
  }
  for (const mv of movements) {
    const row = byId.get(mv.clientId)
    if (!row) continue
    row.quantity += mv.quantity
    row.amount += mv.amount
    row.count += 1
  }
  return [...byId.values()]
    .map((row) => ({
      ...row,
      avgQuantity: row.count ? row.quantity / row.count : 0,
      avgAmount: row.count ? row.amount / row.count : 0,
    }))
    .filter((row) => row.count > 0)
}

export function computeStats(state: AppState): DashboardStats {
  const movements = [...state.movements].sort((a, b) => {
    if (a.date === b.date) return b.createdAt.localeCompare(a.createdAt)
    return b.date.localeCompare(a.date)
  })

  const totalQuantity = movements.reduce((sum, mv) => sum + mv.quantity, 0)
  const totalAmount = movements.reduce((sum, mv) => sum + mv.amount, 0)
  const months = new Set(movements.map((mv) => monthKey(mv.date)))
  const monthCount = Math.max(months.size, 1)
  const dates = movements.map((mv) => mv.date).sort()
  const spanDays = dates.length
    ? Math.max(
        1,
        Math.round(
          (parseLocalDate(dates[dates.length - 1]).getTime() -
            parseLocalDate(dates[0]).getTime()) /
            86400000,
        ) + 1,
      )
    : 1
  const weekCount = Math.max(spanDays / 7, 1)
  const weeklyAvgQuantity = totalQuantity / weekCount
  const weeklyAvgAmount = totalAmount / weekCount

  const today = todayIso()
  const last30From = addDays(today, -29)
  const prev30To = addDays(last30From, -1)
  const prev30From = addDays(prev30To, -29)

  const last30Movements = movements.filter((mv) => inRange(mv.date, last30From, today))
  const prev30Movements = movements.filter((mv) => inRange(mv.date, prev30From, prev30To))

  const last30 = {
    quantity: last30Movements.reduce((sum, mv) => sum + mv.quantity, 0),
    amount: last30Movements.reduce((sum, mv) => sum + mv.amount, 0),
    count: last30Movements.length,
  }
  const prev30 = {
    quantity: prev30Movements.reduce((sum, mv) => sum + mv.quantity, 0),
    amount: prev30Movements.reduce((sum, mv) => sum + mv.amount, 0),
    count: prev30Movements.length,
  }

  const allClientStats = clientStats(state, movements)
  const bestByAmount = [...allClientStats].sort((a, b) => b.amount - a.amount).slice(0, 5)
  const bestByQuantity = [...allClientStats].sort((a, b) => b.quantity - a.quantity).slice(0, 5)

  const qtyByProduct = new Map<string, { quantity: number; amount: number }>()
  for (const product of state.products) {
    qtyByProduct.set(product.id, { quantity: 0, amount: 0 })
  }
  for (const mv of movements) {
    const row = qtyByProduct.get(mv.productId) ?? { quantity: 0, amount: 0 }
    row.quantity += mv.quantity
    row.amount += mv.amount
    qtyByProduct.set(mv.productId, row)
  }

  const productMix: ProductStat[] = state.products
    .map((product) => {
      const row = qtyByProduct.get(product.id) ?? { quantity: 0, amount: 0 }
      return {
        product,
        quantity: row.quantity,
        amount: row.amount,
        share: totalQuantity ? row.quantity / totalQuantity : 0,
      }
    })
    .sort((a, b) => b.quantity - a.quantity)

  const monthlyAvgQuantity = totalQuantity / monthCount
  const monthlyAvgAmount = totalAmount / monthCount

  return {
    totalQuantity,
    totalAmount,
    movementCount: movements.length,
    monthCount: months.size,
    monthlyAvgQuantity,
    monthlyAvgAmount,
    weeklyAvgQuantity,
    weeklyAvgAmount,
    last30,
    prev30,
    amountDeltaPct: last30.count || prev30.count ? pctDelta(last30.amount, prev30.amount) : null,
    quantityDeltaPct:
      last30.count || prev30.count ? pctDelta(last30.quantity, prev30.quantity) : null,
    bestByAmount,
    bestByQuantity,
    clientAverages: [...allClientStats].sort((a, b) => b.avgAmount - a.avgAmount),
    productMix,
    recent: movements.slice(0, 6),
    stockHintQuantity: monthlyAvgQuantity,
  }
}

export function findClient(state: AppState, id: string): Client | undefined {
  return state.clients.find((c) => c.id === id)
}

export function findProduct(state: AppState, id: string): Product | undefined {
  return state.products.find((p) => p.id === id)
}
