import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import "./Inicio.css";

const UnirseEquipo = () => {
    const [equipos, setEquipos] = useState([]);
    const [misEquipos, setMisEquipos] = useState([]);
    const [busqueda, setBusqueda] = useState("");
    const [mensaje, setMensaje] = useState("");
    const [cargando, setCargando] = useState(true);
    const [usuarioActivo, setUsuarioActivo] = useState(null);
    const { isAuth, usuario } = useAuth();
    const navigate = useNavigate();

    const cargarDatos = async () => {
        if (!isAuth) {
            setCargando(false);
            return;
        }

        try {
            const [respuestaPerfil, respuestaEquipos, respuestaMisEquipos] = await Promise.all([
                api.get("/perfil"),
                api.get("/equipos"),
                api.get("/mis-equipos")
            ]);

            setUsuarioActivo(respuestaPerfil.data);
            setEquipos(Array.isArray(respuestaEquipos.data) ? respuestaEquipos.data : []);
            setMisEquipos(Array.isArray(respuestaMisEquipos.data) ? respuestaMisEquipos.data : []);
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se han podido cargar los equipos.");
        } finally {
            setCargando(false);
        }
    };

    useEffect(() => {
        cargarDatos();
    }, [isAuth]);

    const unirse = async (idEquipo) => {
        if (!isAuth) {
            navigate("/login");
            return;
        }

        try {
            const respuesta = await api.post(`/equipos/${idEquipo}/unirse`);
            const usuarioAccion = respuesta.data?.usuario?.nombre_usuario || usuarioActivo?.nombre_usuario || usuario?.nombre_usuario;
            setMensaje(`${respuesta.data?.mensaje || "Te has unido al equipo correctamente."}${usuarioAccion ? ` (${usuarioAccion})` : ""}`);
            cargarDatos();
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido unir al equipo.");
        }
    };

    const idsMisEquipos = misEquipos.map((equipo) => Number(equipo.id_equipo));
    const texto = busqueda.trim().toLowerCase();
    const equiposDisponibles = equipos
        .filter((equipo) => !idsMisEquipos.includes(Number(equipo.id_equipo)))
        .filter((equipo) => !texto || [equipo.nombre_equipo, equipo.descripcion].filter(Boolean).join(" ").toLowerCase().includes(texto));

    return (
        <main className="inicio equipos-page">
            <section className="equipos-hero equipos-hero-compacto">
                <div>
                    <span className="eyebrow-equipo">Mercado de equipos</span>
                    <h1>Unirse a equipo</h1>
                    <p>Encuentra un grupo y entra si todavía no perteneces a él.</p>
                    {(usuarioActivo?.nombre_usuario || usuario?.nombre_usuario) && (
                        <span className="usuario-activo-equipo">Cuenta activa: @{usuarioActivo?.nombre_usuario || usuario?.nombre_usuario}</span>
                    )}
                </div>
                <Link to="/equipos" className="volver-equipos">Volver</Link>
            </section>

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
