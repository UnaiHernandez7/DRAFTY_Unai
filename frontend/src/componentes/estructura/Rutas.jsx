import { Fragment } from "react";
import { Navigate, Routes, Route } from "react-router-dom";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";

// Páginas
import Inicio from "../paginas/Inicio.jsx";
import Login from "../paginas/Login.jsx";
import Registro from "../paginas/Registro.jsx";
import VerificarCodigo from "../paginas/VerificarCodigo.jsx";
import SalaPartido from "../paginas/SalaPartido.jsx";
import CrearPartido from "../paginas/CrearPartido.jsx";
import MisPartidos from "../paginas/MisPartidos.jsx";
import Perfil from "../paginas/Perfil.jsx";
import PerfilUsuario from "../paginas/PerfilUsuario.jsx";
import Competitivo from "../paginas/Competitivo.jsx";
import Equipos from "../paginas/Equipos.jsx";
import CrearEquipo from "../paginas/CrearEquipo.jsx";
import UnirseEquipo from "../paginas/UnirseEquipo.jsx";
import DetalleEquipo from "../paginas/DetalleEquipo.jsx";
import Amigos from "../paginas/Amigos.jsx";
import Torneos from "../paginas/Torneos.jsx";
import CrearTorneo from "../paginas/CrearTorneo.jsx";
import DetalleTorneo from "../paginas/DetalleTorneo.jsx";
import Admin from "../paginas/Admin.jsx";
import Error from "../paginas/Error.jsx";

// Archivo propio del frontend de Drafty.
const RutaProtegida = ({ children }) => {
    const { isAuth, cargandoAuth } = useAuth();

    if (cargandoAuth) {
        return <main className="inicio"><p className="estado">Cargando sesión...</p></main>;
    }

    return isAuth ? children : <Navigate to="/login" replace />;
};

// Funcion auxiliar usada por este componente.
const RutaPublica = ({ children }) => {
    const { isAuth, cargandoAuth } = useAuth();

    if (cargandoAuth) {
        return <main className="inicio"><p className="estado">Cargando sesión...</p></main>;
    }

    return isAuth ? <Navigate to="/" replace /> : children;
};

// Funcion auxiliar usada por este componente.
const Rutas = () => {
    // Vista que se muestra al usuario.
    return (
        <Fragment>
            <Routes>
                <Route path="/login" element={<RutaPublica><Login /></RutaPublica>} />
                <Route path="/registro" element={<RutaPublica><Registro /></RutaPublica>} />
                <Route path="/verificar-codigo" element={<RutaPublica><VerificarCodigo /></RutaPublica>} />

                <Route path="/" element={<RutaProtegida><Inicio /></RutaProtegida>} />
                <Route path="/partidos" element={<Navigate to="/" replace />} />
                <Route path="/crear-partido" element={<RutaProtegida><CrearPartido /></RutaProtegida>} />
                <Route path="/mis-partidos" element={<RutaProtegida><MisPartidos /></RutaProtegida>} />
                <Route path="/partidos/:id/sala" element={<RutaProtegida><SalaPartido /></RutaProtegida>} />

                <Route path="/perfil" element={<RutaProtegida><Perfil /></RutaProtegida>} />
                <Route path="/usuarios/:id/perfil" element={<RutaProtegida><PerfilUsuario /></RutaProtegida>} />
                <Route path="/competitivo" element={<RutaProtegida><Competitivo /></RutaProtegida>} />
                <Route path="/equipos" element={<RutaProtegida><Equipos /></RutaProtegida>} />
                <Route path="/equipos/crear" element={<RutaProtegida><CrearEquipo /></RutaProtegida>} />
                <Route path="/equipos/unirse" element={<RutaProtegida><UnirseEquipo /></RutaProtegida>} />
                <Route path="/equipos/:id" element={<RutaProtegida><DetalleEquipo /></RutaProtegida>} />
                <Route path="/amigos" element={<RutaProtegida><Amigos /></RutaProtegida>} />
                <Route path="/torneos" element={<RutaProtegida><Torneos /></RutaProtegida>} />
                <Route path="/torneos/crear" element={<RutaProtegida><CrearTorneo /></RutaProtegida>} />
                <Route path="/torneos/:id" element={<RutaProtegida><DetalleTorneo /></RutaProtegida>} />

                <Route path="/admin" element={<RutaProtegida><Admin /></RutaProtegida>} />

                <Route path="/*" element={<RutaProtegida><Error /></RutaProtegida>} />
            </Routes>
        </Fragment>
    );
};

export default Rutas;
