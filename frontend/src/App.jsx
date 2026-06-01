import './App.css';
import Cabecera from './componentes/estructura/Cabecera.jsx';
import Contenido from './componentes/estructura/Contenido.jsx';
import { BrowserRouter } from "react-router-dom";

function App() {

  return (
    <BrowserRouter>
      <Cabecera />
      <Contenido />
    </BrowserRouter>
  )
}

export default App
