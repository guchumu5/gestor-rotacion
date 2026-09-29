import { useState, type FormEvent } from 'react'
import { authenticate } from '../store'

type LoginProps = {
  onSuccess: () => void
}

export function Login({ onSuccess }: LoginProps) {
  const [value, setValue] = useState('')
  const [error, setError] = useState('')

  function handleSubmit(event: FormEvent) {
    event.preventDefault()
    if (authenticate(value)) {
      setError('')
      onSuccess()
      return
    }
    setError('Contraseña incorrecta. Prueba de nuevo.')
  }

  return (
    <div className="login-shell">
      <div className="login-mark" aria-hidden="true">
        G
      </div>
      <h1>Gestor de rotación</h1>
      <p className="login-lead">
        Anota quién compra, cuándo y cuánto. En segundos sabrás cuánto preparar.
      </p>
      <form className="login-card" onSubmit={handleSubmit}>
        <label htmlFor="access">Contraseña</label>
        <input
          id="access"
          type="password"
          autoComplete="current-password"
          inputMode="text"
          value={value}
          onChange={(e) => {
            setValue(e.target.value)
            if (error) setError('')
          }}
          placeholder="Introduce el acceso"
          autoFocus
        />
        {error ? <p className="form-error">{error}</p> : null}
        <button className="btn-primary" type="submit">
          Entrar
        </button>
      </form>
    </div>
  )
}
