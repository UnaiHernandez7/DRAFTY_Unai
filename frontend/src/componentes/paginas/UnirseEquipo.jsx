import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import EncabezadoSeccion from "../comunes/EncabezadoSeccion.jsx";
import "./Inicio.css";

// Archivo propio del frontend de Drafty.
const UnirseEquipo = () => {
    // Estado que guarda informacion de la pantalla.
    const [equipos, setEquipos] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [misEquipos, setMisEquipos] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [busqueda, setBusqueda] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [mensaje, setMensaje] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [cargando, setCargando] = useState(true);
    const { isAuth } = useAuth();
    // Dato usado para pintar esta pantalla.
    const navigate = useNavigate();

    // Funcion que llama al servidor y actualiza la pantalla.
    const cargarDatos = async () => {
        if (!isAuth) {
            setCargando(false);
            return;
        }

        try {
            // Solo cargamos listas; no hace falta mostrar la cuenta activa.
            const [respuestaEquipos, respuestaMisEquipos] = await Promise.all([
                api.get("/equipos"),
                api.get("/mis-equipos")
            ]);

            setEquipos(Array.isArray(respuestaEquipos.data) ? respuestaEquipos.data : []);
            setMisEquipos(Array.isArray(respuestaMisEquipos.data) ? respuestaMisEquipos.data : []);
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se han podido cargar los equipos.");
        } finally {
            setCargando(false);
        }
    };

    // Efecto que se ejecuta cuando cambian los datos indicados.
    useEffect(() => {
        cargarDatos();
    }, [isAuth]);

    // Funcion que llama al servidor y actualiza la pantalla.
    const unirse = async (idEquipo) => {
        if (!isAuth) {
            navigate("/login");
            return;
        }

        try {
            // Dato usado para pintar esta pantalla.
            const respuesta = await api.post(`/equipos/${idEquipo}/unirse`);
            // Mensaje simple sin repetir el usuario activo.
            setMensaje(respuesta.data?.mensaje || "Te has unido al equipo correctamente.");
            cargarDatos();
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido unir al equipo.");
        }
    };

    // Dato usado para pintar esta pantalla.
    const idsMisEquipos = misEquipos.map((equipo) => Number(equipo.id_equipo));
    // Dato usado para pintar esta pantalla.
    const texto = busqueda.trim().toLowerCase();
    // Dato usado para pintar esta pantalla.
    const equiposDisponibles = equipos
        .filter((equipo) => !idsMisEquipos.includes(Number(equipo.id_equipo)))
        .filter((equipo) => !texto || [equipo.nombre_equipo, equipo.descripcion].filter(Boolean).join(" ").toLowerCase().includes(texto));

    // Vista que se muestra al usuario.
    return (
        <main className="inicio equipos-page">
            <EncabezadoSeccion
                titulo="Unirse a equipo"
                descripcion="Encuentra un grupo y solicita entrar si todavía no perteneces a él."
                accion={<Link to="/equipos">Volver</Link>}
            />

            {mensaje && <p className="mensaje">{mensaje}</p>}

            <section className="panel-equipos">
                <div className="buscador-equipos">
                    <input
                        type="search"
                        value={busqueda}
                        onChange={(e) => setBusqueda(e.target.value)}
                        placeholder="Buscar por nombre o descripción"
                    />
                </div>

                {cargando && <p className="estado">Cargando equipos...</p>}

                {!cargando && equiposDisponibles.length === 0 && (
                    <p className="estado">No hay equipos disponibles con esa busqueda.</p>
                )}

                <div className="grid-equipos">
                    {equiposDisponibles.map((equipo) => (
                        <article className="card-equipo" key={equipo.id_equipo}>
                            <div className="card-equipo-top">
                                <div>
                                    <span className="badge-equipo">Equipo abierto</span>
                                    <h3>Equipo: {equipo.nombre_equipo}</h3>
                                </div>
                                <strong>{equipo.jugadores_count ?? 0}</strong>
                            </div>
                            <p>{equipo.descripcion || "Sin descripción"}</p>
                            <button type="button" onClick={() => unirse(equipo.id_equipo)}>
                                Unirse a este equipo
                            </button>
                        </article>
                    ))}
                </div>
            </section>
        </main>
    );
};

export default UnirseEquipo;
