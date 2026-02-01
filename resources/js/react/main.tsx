import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App.tsx'
import AppV2 from './AppV2.tsx'
import './index.css'
import './i18n'

const kioskVersion = (window as any).tenant?.kiosk_version || 'v1';

ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    {kioskVersion === 'v2' ? <AppV2 /> : <App />}
  </React.StrictMode>,
)
