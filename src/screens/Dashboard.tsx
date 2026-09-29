import { formatMoney, formatQty } from '../format'
import { computeStats, findClient, findProduct } from '../stats'
import { useStore } from '../store'
import type { TabId } from '../types'

type DashboardProps = {
  onGo: (tab: TabId) => void
}

function deltaClass(value: number | null): string {
  if (value == null) return 'muted'
  if (value > 2) return 'up'
  if (value < -2) return 'down'
  return 'muted'
}

function formatDelta(value: number | null): string {
  if (value == null) return 'Sin periodo anterior'
  const abs = Math.abs(value).toFixed(0)
  if (value > 0) return `+${abs}% vs 30 días previos`
  if (value < 0) return `−${abs}% vs 30 días previos`
  return 'Igual que los 30 días previos'
}

export function Dashboard({ onGo }: DashboardProps) {
  const { state } = useStore()
  const stats = computeStats(state)

  if (stats.movementCount === 0) {
    return (
      <section className="page">
        <header className="page-head">
          <p className="eyebrow">Resumen</p>
          <h1>Tu rotación, clara</h1>
        </header>
        <div className="empty-card">
          <strong>Aún no hay pedidos</strong>
          <p>
            Pulsa <em>Registrar</em> y anota el primer movimiento: cliente, producto, cantidad e
            importe. En cuanto tengas unos cuantos, aquí verás medias, mejores clientes y cuánto
            producto conviene tener listo.
          </p>
          <button className="btn-primary" type="button" onClick={() => onGo('registrar')}>
            Registrar movimiento
          </button>
        </div>
      </section>
    )
  }

  return (
    <section className="page">
      <header className="page-head">
        <p className="eyebrow">Resumen</p>
        <h1>Pulso de la rotación</h1>
      </header>

      <div className="kpi-grid">
        <article className="kpi kpi-main">
          <p>Media mensual</p>
          <strong>{formatQty(stats.monthlyAvgQuantity)} uds.</strong>
          <span>{formatMoney(stats.monthlyAvgAmount)} · {stats.monthCount || 1} mes(es)</span>
        </article>
        <article className="kpi">
          <p>Media semanal</p>
          <strong>{formatQty(stats.weeklyAvgQuantity)}</strong>
          <span>{formatMoney(stats.weeklyAvgAmount)}</span>
        </article>
        <article className="kpi">
          <p>Últimos 30 días</p>
          <strong>{formatMoney(stats.last30.amount)}</strong>
          <span className={deltaClass(stats.amountDeltaPct)}>
            {formatDelta(stats.amountDeltaPct)}
          </span>
        </article>
      </div>

      <article className="hint-card">
        <p className="eyebrow">Preparación</p>
        <h2>Ten listos unos {formatQty(Math.ceil(stats.stockHintQuantity))} uds. al mes</h2>
        <p>
          Es la media de volumen según tus movimientos. Si un mes viene flojo, baja; si hay pico,
          usa este número como suelo.
        </p>
      </article>

      <section className="block">
        <h2>Mejores por importe</h2>
        <ul className="rank-list">
          {stats.bestByAmount.map((row, index) => (
            <li key={row.client.id}>
              <span className="rank">{index + 1}</span>
              <div>
                <strong>{row.client.name}</strong>
                <p>
                  {formatMoney(row.amount)} · {row.count} pedidos
                </p>
              </div>
            </li>
          ))}
        </ul>
      </section>

      <section className="block">
        <h2>Mejores por volumen</h2>
        <ul className="rank-list">
          {stats.bestByQuantity.map((row, index) => (
            <li key={row.client.id}>
              <span className="rank">{index + 1}</span>
              <div>
                <strong>{row.client.name}</strong>
                <p>
                  {formatQty(row.quantity)} uds. · {row.count} pedidos
                </p>
              </div>
            </li>
          ))}
        </ul>
      </section>

      <section className="block">
        <h2>Qué pide cada uno</h2>
        <ul className="avg-list">
          {stats.clientAverages.map((row) => (
            <li key={row.client.id}>
              <strong>{row.client.name}</strong>
              <span>
                suele {formatQty(row.avgQuantity)} uds. · {formatMoney(row.avgAmount)}
              </span>
            </li>
          ))}
        </ul>
      </section>

      <section className="block">
        <h2>Mezcla de producto</h2>
        <ul className="mix-list">
          {stats.productMix.map((row) => (
            <li key={row.product.id}>
              <div className="mix-row">
                <strong>{row.product.name}</strong>
                <span>{Math.round(row.share * 100)}%</span>
              </div>
              <div className="bar">
                <i style={{ width: `${Math.max(row.share * 100, row.quantity ? 4 : 0)}%` }} />
              </div>
              <p>
                {formatQty(row.quantity)} uds. · {formatMoney(row.amount)}
              </p>
            </li>
          ))}
        </ul>
      </section>

      <section className="block">
        <div className="block-head">
          <h2>Últimos movimientos</h2>
          <button className="link-btn" type="button" onClick={() => onGo('historial')}>
            Ver todos
          </button>
        </div>
        <ul className="mv-list">
          {stats.recent.map((mv) => {
            const client = findClient(state, mv.clientId)
            const product = findProduct(state, mv.productId)
            return (
              <li key={mv.id}>
                <div>
                  <strong>{client?.name ?? 'Cliente'}</strong>
                  <p>
                    {product?.name ?? 'Producto'} · {formatQty(mv.quantity)} uds.
                  </p>
                </div>
                <em>{formatMoney(mv.amount)}</em>
              </li>
            )
          })}
        </ul>
      </section>
    </section>
  )
}
