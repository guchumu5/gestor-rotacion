import { useMemo, useState, type FormEvent } from 'react'
import { todayIso } from '../format'
import { useStore } from '../store'

type AddMovementProps = {
  onSaved: () => void
}

function parseNumber(value: string): number {
  const normalized = value.replace(',', '.').trim()
  const n = Number(normalized)
  return Number.isFinite(n) ? n : NaN
}

export function AddMovement({ onSaved }: AddMovementProps) {
  const { state, addClient, addMovement } = useStore()
  const activeClients = useMemo(
    () => state.clients.filter((c) => c.active),
    [state.clients],
  )
  const activeProducts = useMemo(
    () => state.products.filter((p) => p.active),
    [state.products],
  )

  const [clientId, setClientId] = useState(activeClients[0]?.id ?? '')
  const [creatingClient, setCreatingClient] = useState(false)
  const [newClientName, setNewClientName] = useState('')
  const [productId, setProductId] = useState(activeProducts[0]?.id ?? '')
  const [quantity, setQuantity] = useState('1')
  const [amount, setAmount] = useState('')
  const [date, setDate] = useState(todayIso())
  const [note, setNote] = useState('')
  const [showNote, setShowNote] = useState(false)
  const [error, setError] = useState('')
  const [saved, setSaved] = useState(false)

  function bumpQty(delta: number) {
    const current = parseNumber(quantity)
    const next = Math.max(1, (Number.isFinite(current) ? current : 1) + delta)
    setQuantity(String(next))
  }

  function handleCreateClient() {
    const name = newClientName.trim()
    if (!name) {
      setError('Pon un nombre al cliente nuevo.')
      return
    }
    const client = addClient(name)
    setClientId(client.id)
    setCreatingClient(false)
    setNewClientName('')
    setError('')
  }

  function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setSaved(false)

    const qty = parseNumber(quantity)
    const money = parseNumber(amount)
    if (!clientId) {
      setError('Elige o crea un cliente.')
      return
    }
    if (!productId) {
      setError('Elige un producto.')
      return
    }
    if (!Number.isFinite(qty) || qty <= 0) {
      setError('La cantidad tiene que ser mayor que 0.')
      return
    }
    if (!Number.isFinite(money) || money < 0) {
      setError('Indica el importe total cobrado.')
      return
    }

    addMovement({
      clientId,
      productId,
      quantity: qty,
      amount: money,
      note,
      date,
    })
    setAmount('')
    setQuantity('1')
    setNote('')
    setShowNote(false)
    setError('')
    setSaved(true)
    onSaved()
  }

  return (
    <section className="page">
      <header className="page-head">
        <p className="eyebrow">Nuevo</p>
        <h1>Registrar movimiento</h1>
      </header>

      <form className="stack-form" onSubmit={handleSubmit}>
        <fieldset>
          <legend>Cliente</legend>
          <div className="chip-row">
            {activeClients.map((client) => (
              <button
                key={client.id}
                type="button"
                className={clientId === client.id && !creatingClient ? 'chip on' : 'chip'}
                onClick={() => {
                  setClientId(client.id)
                  setCreatingClient(false)
                }}
              >
                {client.name}
              </button>
            ))}
            <button
              type="button"
              className={creatingClient ? 'chip on' : 'chip chip-add'}
              onClick={() => {
                setCreatingClient(true)
                setClientId('')
              }}
            >
              + Nuevo
            </button>
          </div>
          {creatingClient ? (
            <div className="inline-create">
              <input
                value={newClientName}
                onChange={(e) => setNewClientName(e.target.value)}
                placeholder="Nombre del cliente"
                autoFocus
              />
              <button type="button" className="btn-secondary" onClick={handleCreateClient}>
                Crear
              </button>
            </div>
          ) : null}
        </fieldset>

        <fieldset>
          <legend>Producto</legend>
          <div className="chip-row">
            {activeProducts.map((product) => (
              <button
                key={product.id}
                type="button"
                className={productId === product.id ? 'chip on' : 'chip'}
                onClick={() => setProductId(product.id)}
              >
                {product.name}
              </button>
            ))}
          </div>
        </fieldset>

        <div className="field">
          <label htmlFor="qty">Cantidad</label>
          <div className="stepper">
            <button type="button" onClick={() => bumpQty(-1)} aria-label="Quitar uno">
              −
            </button>
            <input
              id="qty"
              inputMode="decimal"
              value={quantity}
              onChange={(e) => setQuantity(e.target.value)}
            />
            <button type="button" onClick={() => bumpQty(1)} aria-label="Añadir uno">
              +
            </button>
          </div>
        </div>

        <div className="field">
          <label htmlFor="amount">Importe total cobrado</label>
          <input
            id="amount"
            inputMode="decimal"
            value={amount}
            onChange={(e) => setAmount(e.target.value)}
            placeholder="0,00"
          />
        </div>

        <div className="field">
          <label htmlFor="date">Fecha</label>
          <input id="date" type="date" value={date} onChange={(e) => setDate(e.target.value)} />
        </div>

        {showNote ? (
          <div className="field">
            <label htmlFor="note">Nota (opcional)</label>
            <textarea
              id="note"
              rows={2}
              value={note}
              onChange={(e) => setNote(e.target.value)}
              placeholder="Algo que quieras recordar"
            />
          </div>
        ) : (
          <button type="button" className="link-btn" onClick={() => setShowNote(true)}>
            Añadir nota
          </button>
        )}

        {error ? <p className="form-error">{error}</p> : null}
        {saved ? <p className="form-ok">Movimiento guardado.</p> : null}

        <button className="btn-primary btn-xl" type="submit">
          Guardar movimiento
        </button>
      </form>
    </section>
  )
}
