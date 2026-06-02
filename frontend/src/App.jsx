import './App.css';
import Cabecera from './componentes/estructura/Cabecera.jsx';
import Contenido from './componentes/estructura/Contenido.jsx';
import Footer from './componentes/estructura/Footer.jsx';
import { BrowserRouter } from "react-router-dom";

function App() {

  return (
    <BrowserRouter>
      <Cabecera />
      <Contenido />
      <Footer />
    </BrowserRouter>
  )
}

export default App
