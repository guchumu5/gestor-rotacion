import { useState } from 'react'
import { useStore } from '../store'

export function Directory() {
  const {
    state,
    addClient,
    renameClient,
    setClientActive,
    deleteClient,
    addProduct,
    renameProduct,
    setProductActive,
    deleteProduct,
    logout,
  } = useStore()
  const [clientName, setClientName] = useState('')
  const [productName, setProductName] = useState('')
  const [editingClient, setEditingClient] = useState<string | null>(null)
  const [editingProduct, setEditingProduct] = useState<string | null>(null)
  const [draft, setDraft] = useState('')
  const [notice, setNotice] = useState('')

  function flash(message: string) {
    setNotice(message)
  }

  return (
    <section className="page">
      <header className="page-head">
        <p className="eyebrow">Fichas</p>
        <h1>Clientes y productos</h1>
      </header>

      {notice ? <p className="form-ok">{notice}</p> : null}

      <section className="block">
        <h2>Clientes</h2>
        <form
          className="inline-create"
          onSubmit={(e) => {
            e.preventDefault()
            if (!clientName.trim()) return
            addClient(clientName)
            setClientName('')
            flash('Cliente creado.')
          }}
        >
          <input
            value={clientName}
            onChange={(e) => setClientName(e.target.value)}
            placeholder="Nuevo cliente"
          />
          <button className="btn-secondary" type="submit">
            Añadir
          </button>
        </form>
        <ul className="dir-list">
          {state.clients.map((client) => (
            <li key={client.id} className={!client.active ? 'inactive' : undefined}>
              {editingClient === client.id ? (
                <form
                  className="inline-create"
                  onSubmit={(e) => {
                    e.preventDefault()
                    renameClient(client.id, draft)
                    setEditingClient(null)
                    flash('Cliente actualizado.')
                  }}
                >
                  <input value={draft} onChange={(e) => setDraft(e.target.value)} autoFocus />
                  <button className="btn-secondary" type="submit">
                    Guardar
                  </button>
                </form>
              ) : (
                <>
                  <div>
                    <strong>{client.name}</strong>
                    <p>{client.active ? 'Activo' : 'Oculto en el registro'}</p>
                  </div>
                  <div className="dir-actions">
                    <button
                      type="button"
                      className="link-btn"
                      onClick={() => {
                        setEditingClient(client.id)
                        setDraft(client.name)
                      }}
                    >
                      Renombrar
                    </button>
                    <button
                      type="button"
                      className="link-btn"
                      onClick={() => setClientActive(client.id, !client.active)}
                    >
                      {client.active ? 'Ocultar' : 'Activar'}
                    </button>
                    <button
                      type="button"
                      className="link-btn danger"
                      onClick={() => {
                        const result = deleteClient(client.id)
                        if (!result.ok) {
                          setClientActive(client.id, false)
                          flash('Tiene historial: se ha ocultado para no perder datos.')
                          return
                        }
                        flash('Cliente eliminado.')
                      }}
                    >
                      Borrar
                    </button>
                  </div>
                </>
              )}
            </li>
          ))}
        </ul>
      </section>

      <section className="block">
        <h2>Productos</h2>
        <form
          className="inline-create"
          onSubmit={(e) => {
            e.preventDefault()
            if (!productName.trim()) return
            addProduct(productName)
            setProductName('')
            flash('Producto creado.')
          }}
        >
          <input
            value={productName}
            onChange={(e) => setProductName(e.target.value)}
            placeholder="Nuevo producto"
          />
          <button className="btn-secondary" type="submit">
            Añadir
          </button>
        </form>
        <ul className="dir-list">
          {state.products.map((product) => (
            <li key={product.id} className={!product.active ? 'inactive' : undefined}>
              {editingProduct === product.id ? (
                <form
                  className="inline-create"
                  onSubmit={(e) => {
                    e.preventDefault()
                    renameProduct(product.id, draft)
                    setEditingProduct(null)
                    flash('Producto actualizado.')
                  }}
                >
                  <input value={draft} onChange={(e) => setDraft(e.target.value)} autoFocus />
                  <button className="btn-secondary" type="submit">
                    Guardar
                  </button>
                </form>
              ) : (
                <>
                  <div>
                    <strong>{product.name}</strong>
                    <p>{product.active ? 'Activo' : 'Oculto en el registro'}</p>
                  </div>
                  <div className="dir-actions">
                    <button
                      type="button"
                      className="link-btn"
                      onClick={() => {
                        setEditingProduct(product.id)
                        setDraft(product.name)
                      }}
                    >
                      Renombrar
                    </button>
                    <button
                      type="button"
                      className="link-btn"
                      onClick={() => setProductActive(product.id, !product.active)}
                    >
                      {product.active ? 'Ocultar' : 'Activar'}
                    </button>
                    <button
                      type="button"
                      className="link-btn danger"
                      onClick={() => {
                        const result = deleteProduct(product.id)
                        if (!result.ok) {
                          setProductActive(product.id, false)
                          flash('Tiene historial: se ha ocultado para no perder datos.')
                          return
                        }
                        flash('Producto eliminado.')
                      }}
                    >
                      Borrar
                    </button>
                  </div>
                </>
              )}
            </li>
          ))}
        </ul>
      </section>

      <button
        type="button"
        className="btn-ghost"
        onClick={() => {
          logout()
          window.location.reload()
        }}
      >
        Cerrar sesión
      </button>
    </section>
  )
}
