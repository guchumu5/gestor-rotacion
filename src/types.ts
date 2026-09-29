export type Client = {
  id: string
  name: string
  active: boolean
  createdAt: string
}

export type Product = {
  id: string
  name: string
  active: boolean
  createdAt: string
}

export type Movement = {
  id: string
  clientId: string
  productId: string
  quantity: number
  amount: number
  note: string
  date: string
  createdAt: string
}

export type AppState = {
  clients: Client[]
  products: Product[]
  movements: Movement[]
}

export type TabId = 'resumen' | 'registrar' | 'historial' | 'fichas'
