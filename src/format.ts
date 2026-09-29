const moneyFmt = new Intl.NumberFormat('es-ES', {
  style: 'currency',
  currency: 'EUR',
  maximumFractionDigits: 2,
})

const qtyFmt = new Intl.NumberFormat('es-ES', {
  maximumFractionDigits: 2,
})

const dateFmt = new Intl.DateTimeFormat('es-ES', {
  day: 'numeric',
  month: 'short',
})

const dateLongFmt = new Intl.DateTimeFormat('es-ES', {
  weekday: 'short',
  day: 'numeric',
  month: 'short',
})

export function formatMoney(value: number): string {
  return moneyFmt.format(value)
}

export function formatQty(value: number): string {
  return qtyFmt.format(value)
}

export function formatDate(isoDate: string): string {
  return dateFmt.format(parseLocalDate(isoDate))
}

export function formatDateLong(isoDate: string): string {
  return dateLongFmt.format(parseLocalDate(isoDate))
}

export function todayIso(): string {
  const d = new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

export function parseLocalDate(isoDate: string): Date {
  const [y, m, d] = isoDate.split('-').map(Number)
  return new Date(y, (m ?? 1) - 1, d ?? 1)
}

export function addDays(isoDate: string, days: number): string {
  const date = parseLocalDate(isoDate)
  date.setDate(date.getDate() + days)
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

export function monthKey(isoDate: string): string {
  return isoDate.slice(0, 7)
}

export function formatMonth(key: string): string {
  const [y, m] = key.split('-').map(Number)
  return new Intl.DateTimeFormat('es-ES', { month: 'long', year: 'numeric' }).format(
    new Date(y, (m ?? 1) - 1, 1),
  )
}

export function uid(): string {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID()
  }
  return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`
}
