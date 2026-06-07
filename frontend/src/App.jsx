import './App.css';
import Cabecera from './componentes/estructura/Cabecera.jsx';
import Contenido from './componentes/estructura/Contenido.jsx';
import Footer from './componentes/estructura/Footer.jsx';
import { BrowserRouter } from "react-router-dom";

// Archivo propio del frontend de Drafty.
function App() {

  // Vista que se muestra al usuario.
  return (
    <BrowserRouter>
      <Cabecera />
      <Contenido />
      <Footer />
    </BrowserRouter>
  )
}

export default App
