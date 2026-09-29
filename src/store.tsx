import {
  createContext,
  useCallback,
  useContext,
  useMemo,
  useState,
  type ReactNode,
} from 'react'
import { todayIso, uid } from './format'
import type { AppState, Client, Movement, Product } from './types'

const STORAGE_KEY = 'gestor-rotacion:v1'
const AUTH_KEY = 'gestor-rotacion:session'
const ACCESS_PHRASE = 'hola'

function nowIso(): string {
  return new Date().toISOString()
}

function seedState(): AppState {
  const createdAt = nowIso()
  return {
    clients: [
      { id: uid(), name: 'SanPablo', active: true, createdAt },
      { id: uid(), name: 'Mecanico', active: true, createdAt },
      { id: uid(), name: 'Fr', active: true, createdAt },
    ],
    products: [
      { id: uid(), name: 'Rojo', active: true, createdAt },
      { id: uid(), name: 'Ches', active: true, createdAt },
      { id: uid(), name: 'Win', active: true, createdAt },
    ],
    movements: [],
  }
}

function isAppState(value: unknown): value is AppState {
  if (!value || typeof value !== 'object') return false
  const data = value as AppState
  return Array.isArray(data.clients) && Array.isArray(data.products) && Array.isArray(data.movements)
}

function loadState(): AppState {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return seedState()
    const parsed: unknown = JSON.parse(raw)
    if (!isAppState(parsed)) return seedState()
    return parsed
  } catch {
    return seedState()
  }
}

function persist(state: AppState) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(state))
}

export function readSessionAuth(): boolean {
  try {
    return sessionStorage.getItem(AUTH_KEY) === 'ok'
  } catch {
    return false
  }
}

export function checkAccessPhrase(value: string): boolean {
  return value.trim() === ACCESS_PHRASE
}

function writeSessionAuth() {
  sessionStorage.setItem(AUTH_KEY, 'ok')
}

function clearSessionAuth() {
  sessionStorage.removeItem(AUTH_KEY)
}

export type NewMovementInput = {
  clientId: string
  productId: string
  quantity: number
  amount: number
  note: string
  date: string
}

type StoreApi = {
  state: AppState
  addClient: (name: string) => Client
  renameClient: (id: string, name: string) => void
  setClientActive: (id: string, active: boolean) => void
  deleteClient: (id: string) => { ok: true } | { ok: false; reason: 'has-history' }
  addProduct: (name: string) => Product
  renameProduct: (id: string, name: string) => void
  setProductActive: (id: string, active: boolean) => void
  deleteProduct: (id: string) => { ok: true } | { ok: false; reason: 'has-history' }
  addMovement: (input: NewMovementInput) => Movement
  deleteMovement: (id: string) => void
  logout: () => void
}

const StoreContext = createContext<StoreApi | null>(null)

export function StoreProvider({ children }: { children: ReactNode }) {
  const [state, setState] = useState<AppState>(() => loadState())

  const update = useCallback((updater: (prev: AppState) => AppState) => {
    setState((prev) => {
      const next = updater(prev)
      persist(next)
      return next
    })
  }, [])

  const addClient = useCallback(
    (name: string) => {
      const client: Client = {
        id: uid(),
        name: name.trim(),
        active: true,
        createdAt: nowIso(),
      }
      update((prev) => ({ ...prev, clients: [...prev.clients, client] }))
      return client
    },
    [update],
  )

  const renameClient = useCallback(
    (id: string, name: string) => {
      const nextName = name.trim()
      if (!nextName) return
      update((prev) => ({
        ...prev,
        clients: prev.clients.map((c) => (c.id === id ? { ...c, name: nextName } : c)),
      }))
    },
    [update],
  )

  const setClientActive = useCallback(
    (id: string, active: boolean) => {
      update((prev) => ({
        ...prev,
        clients: prev.clients.map((c) => (c.id === id ? { ...c, active } : c)),
      }))
    },
    [update],
  )

  const deleteClient = useCallback(
    (id: string) => {
      const hasHistory = state.movements.some((mv) => mv.clientId === id)
      if (hasHistory) return { ok: false as const, reason: 'has-history' as const }
      update((prev) => ({ ...prev, clients: prev.clients.filter((c) => c.id !== id) }))
      return { ok: true as const }
    },
    [state.movements, update],
  )

  const addProduct = useCallback(
    (name: string) => {
      const product: Product = {
        id: uid(),
        name: name.trim(),
        active: true,
        createdAt: nowIso(),
      }
      update((prev) => ({ ...prev, products: [...prev.products, product] }))
      return product
    },
    [update],
  )

  const renameProduct = useCallback(
    (id: string, name: string) => {
      const nextName = name.trim()
      if (!nextName) return
      update((prev) => ({
        ...prev,
        products: prev.products.map((p) => (p.id === id ? { ...p, name: nextName } : p)),
      }))
    },
    [update],
  )

  const setProductActive = useCallback(
    (id: string, active: boolean) => {
      update((prev) => ({
        ...prev,
        products: prev.products.map((p) => (p.id === id ? { ...p, active } : p)),
      }))
    },
    [update],
  )

  const deleteProduct = useCallback(
    (id: string) => {
      const hasHistory = state.movements.some((mv) => mv.productId === id)
      if (hasHistory) return { ok: false as const, reason: 'has-history' as const }
      update((prev) => ({ ...prev, products: prev.products.filter((p) => p.id !== id) }))
      return { ok: true as const }
    },
    [state.movements, update],
  )

  const addMovement = useCallback(
    (input: NewMovementInput) => {
      const movement: Movement = {
        id: uid(),
        clientId: input.clientId,
        productId: input.productId,
        quantity: input.quantity,
        amount: input.amount,
        note: input.note.trim(),
        date: input.date || todayIso(),
        createdAt: nowIso(),
      }
      update((prev) => ({ ...prev, movements: [movement, ...prev.movements] }))
      return movement
    },
    [update],
  )

  const deleteMovement = useCallback(
    (id: string) => {
      update((prev) => ({ ...prev, movements: prev.movements.filter((mv) => mv.id !== id) }))
    },
    [update],
  )

  const logout = useCallback(() => {
    clearSessionAuth()
  }, [])

  const api = useMemo<StoreApi>(
    () => ({
      state,
      addClient,
      renameClient,
      setClientActive,
      deleteClient,
      addProduct,
      renameProduct,
      setProductActive,
      deleteProduct,
      addMovement,
      deleteMovement,
      logout,
    }),
    [
      state,
      addClient,
      renameClient,
      setClientActive,
      deleteClient,
      addProduct,
      renameProduct,
      setProductActive,
      deleteProduct,
      addMovement,
      deleteMovement,
      logout,
    ],
  )

  return <StoreContext.Provider value={api}>{children}</StoreContext.Provider>
}

export function useStore(): StoreApi {
  const ctx = useContext(StoreContext)
  if (!ctx) throw new Error('useStore debe usarse dentro de StoreProvider')
  return ctx
}

export function authenticate(phrase: string): boolean {
  if (!checkAccessPhrase(phrase)) return false
  writeSessionAuth()
  return true
}
