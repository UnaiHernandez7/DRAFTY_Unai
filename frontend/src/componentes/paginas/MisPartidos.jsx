import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import EncabezadoSeccion from "../comunes/EncabezadoSeccion.jsx";
import HistorialPartidos from "./HistorialPartidos.jsx";
import PartidoLista from "./PartidoLista.jsx";
import "./Inicio.css";
import "./MisPartidos.css";

// Archivo propio del frontend de Drafty.
const MisPartidos = () => {
    // Estado que guarda informacion de la pantalla.
    const [partidos, setPartidos] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [mensaje, setMensaje] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [cargando, setCargando] = useState(true);
    const { token } = useAuth();
    // Dato usado para pintar esta pantalla.
    const navigate = useNavigate();

    // Efecto que se ejecuta cuando cambian los datos indicados.
    useEffect(() => {
        // Funcion que llama al servidor y actualiza la pantalla.
        const cargarMisPartidos = async () => {
            if (!token) {
                navigate("/login");
                return;
            }

            try {
                setMensaje("");
                setCargando(true);

                // Dato usado para pintar esta pantalla.
                const respuesta = await api.get("/mis-partidos");
                setPartidos(Array.isArray(respuesta.data) ? respuesta.data : []);
            } catch {
                setMensaje("No se han podido cargar tus partidos.");
            } finally {
                setCargando(false);
            }
        };

        cargarMisPartidos();
    }, [token, navigate]);

    // Funcion auxiliar usada por este componente.
    const verSala = (idPartido) => {
        navigate(`/partidos/${idPartido}/sala`);
    };

    // Vista que se muestra al usuario.
    return (
        <main className="inicio mis-partidos-page">
            <EncabezadoSeccion
                titulo="Mis partidos"
                descripcion="Consulta y gestiona todos los partidos en los que participas."
            />

            {mensaje && <p className="mensaje mensaje-error">{mensaje}</p>}
            {cargando && <p className="estado">Cargando tus partidos...</p>}

            {!cargando && partidos.length === 0 && (
                <section className="estado-vacio-partidos estado-vacio-mis-partidos">
                    <h2>Todavía no tienes partidos activos</h2>
                    <p>Busca una sala disponible y cuando te unas aparecerá aquí con este formato.</p>
                    <button type="button" onClick={() => navigate("/")}>
                        Buscar partidos
                    </button>
                </section>
            )}

            {!cargando && partidos.length > 0 && (
                <section className="bloque-mis-partidos bloque-mis-partidos-lista">
                    <div className="cabecera-bloque-partidos">
                        <div>
                            <h2>Mis partidos activos</h2>
                            <p>{partidos.length} partidos disponibles para consultar</p>
                        </div>
                        <span>En curso</span>
                    </div>

                    <div className="lista-partidos lista-partidos-inicio lista-mis-partidos">
                        {partidos.map((partido) => (
                            <PartidoLista
                                key={partido.id_partido}
                                partido={partido}
                                estaUnido
                                estaCompleto={false}
                                textoAccion="Ver sala"
                                mostrarPlazasOcupadas
                                onAccion={verSala}
                            />
                        ))}
                    </div>
                </section>
            )}

            <HistorialPartidos />
        </main>
    );
};

export default MisPartidos;
