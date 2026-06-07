import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.jsx'
import { ProveedorAuth } from "./contextos/ProveedorAuth.jsx";

// Archivo propio del frontend de Drafty.
createRoot(document.getElementById('root')).render(
  <StrictMode>
    <ProveedorAuth>
      <App />
    </ProveedorAuth>
  </StrictMode>,
)
