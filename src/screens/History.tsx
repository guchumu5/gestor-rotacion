import { formatDateLong, formatMoney, formatQty } from '../format'
import { findClient, findProduct } from '../stats'
import { useStore } from '../store'
import type { TabId } from '../types'

type HistoryProps = {
  onGo: (tab: TabId) => void
}

export function History({ onGo }: HistoryProps) {
  const { state, deleteMovement } = useStore()
  const movements = [...state.movements].sort((a, b) => {
    if (a.date === b.date) return b.createdAt.localeCompare(a.createdAt)
    return b.date.localeCompare(a.date)
  })

  if (movements.length === 0) {
    return (
      <section className="page">
        <header className="page-head">
          <p className="eyebrow">Historial</p>
          <h1>Movimientos</h1>
        </header>
        <div className="empty-card">
          <strong>Nada que mostrar todavía</strong>
          <p>Cuando registres un pedido aparecerá aquí, del más reciente al más antiguo.</p>
          <button className="btn-primary" type="button" onClick={() => onGo('registrar')}>
            Registrar ahora
          </button>
        </div>
      </section>
    )
  }

  return (
    <section className="page">
      <header className="page-head">
        <p className="eyebrow">Historial</p>
        <h1>{movements.length} movimientos</h1>
      </header>
      <ul className="history-list">
        {movements.map((mv) => {
          const client = findClient(state, mv.clientId)
          const product = findProduct(state, mv.productId)
          return (
            <li key={mv.id} className="history-card">
              <div className="history-top">
                <strong>{client?.name ?? 'Cliente'}</strong>
                <em>{formatMoney(mv.amount)}</em>
              </div>
              <p>
                {product?.name ?? 'Producto'} · {formatQty(mv.quantity)} uds. ·{' '}
                {formatDateLong(mv.date)}
              </p>
              {mv.note ? <p className="note">{mv.note}</p> : null}
              <button
                type="button"
                className="link-btn danger"
                onClick={() => {
                  if (window.confirm('¿Borrar este movimiento?')) deleteMovement(mv.id)
                }}
              >
                Borrar
              </button>
            </li>
          )
        })}
      </ul>
    </section>
  )
}
