import { useState } from 'react'
import { AddMovement } from './screens/AddMovement'
import { Dashboard } from './screens/Dashboard'
import { Directory } from './screens/Directory'
import { History } from './screens/History'
import { Login } from './screens/Login'
import { readSessionAuth, StoreProvider } from './store'
import type { TabId } from './types'

function Shell() {
  const [tab, setTab] = useState<TabId>('resumen')

  return (
    <div className="app-shell">
      <main className="app-main">
        {tab === 'resumen' ? <Dashboard onGo={setTab} /> : null}
        {tab === 'registrar' ? <AddMovement onSaved={() => setTab('resumen')} /> : null}
        {tab === 'historial' ? <History onGo={setTab} /> : null}
        {tab === 'fichas' ? <Directory /> : null}
      </main>
      <nav className="tabbar" aria-label="Secciones">
        <button
          type="button"
          className={tab === 'resumen' ? 'tab on' : 'tab'}
          onClick={() => setTab('resumen')}
        >
          <span>Resumen</span>
        </button>
        <button
          type="button"
          className={tab === 'registrar' ? 'tab tab-cta on' : 'tab tab-cta'}
          onClick={() => setTab('registrar')}
        >
          <span>Registrar</span>
        </button>
        <button
          type="button"
          className={tab === 'historial' ? 'tab on' : 'tab'}
          onClick={() => setTab('historial')}
        >
          <span>Historial</span>
        </button>
        <button
          type="button"
          className={tab === 'fichas' ? 'tab on' : 'tab'}
          onClick={() => setTab('fichas')}
        >
          <span>Fichas</span>
        </button>
      </nav>
    </div>
  )
}

export default function App() {
  const [authed, setAuthed] = useState(() => readSessionAuth())

  if (!authed) {
    return <Login onSuccess={() => setAuthed(true)} />
  }

  return (
    <StoreProvider>
      <Shell />
    </StoreProvider>
  )
}
