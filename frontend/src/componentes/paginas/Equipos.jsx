import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import "./Inicio.css";

const formatearRol = (rol) => {
    const roles = {
        capitan: "Capitán",
        jugador: "Jugador",
        invitado: "Invitado"
    };

    return roles[rol] || "Jugador";
};

const Equipos = () => {
    const [equipos, setEquipos] = useState([]);
    const [mensaje, setMensaje] = useState("");
    const [cargando, setCargando] = useState(true);
    const { isAuth, usuario } = useAuth();
    const navigate = useNavigate();

    const cargarEquipos = async () => {
        if (!isAuth) {
            setCargando(false);
            return;
        }

        try {
            const respuesta = await api.get("/mis-equipos");
            setEquipos(Array.isArray(respuesta.data) ? respuesta.data : []);
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se han podido cargar tus equipos.");
        } finally {
            setCargando(false);
        }
    };

    useEffect(() => {
        cargarEquipos();
    }, [isAuth]);

    const rolUsuario = (equipo) => {
        const miembro = equipo.usuarios?.find((item) => Number(item.id_usuario) === Number(usuario?.id_usuario));
        return miembro?.pivot?.rol_en_equipo || equipo.pivot?.rol_en_equipo || "jugador";
    };

    const abandonarEquipo = async (idEquipo) => {
        try {
            const respuesta = await api.post(`/equipos/${idEquipo}/salir`);
            setMensaje(respuesta.data?.mensaje || "Has abandonado el equipo.");
            cargarEquipos();
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido abandonar el equipo.");
        }
    };

    if (!isAuth) {
        return (
            <main className="inicio equipos-page">
                <section className="equipos-hero">
                    <h1>Mis equipos</h1>
                    <p>Inicia sesión para crear equipos, unirte a grupos y competir con tu gente.</p>
                    <button type="button" onClick={() => navigate("/login")}>Iniciar sesión</button>
                </section>
            </main>
        );
    }

    return (
        <main className="inicio equipos-page">
            <section className="equipos-hero">
                <div>
                    <span className="eyebrow-equipo">DRAFTY Teams</span>
                    <h1>Mis equipos</h1>
                    <p>Crea, ficha jugadores, revisa partidos y habla con tu equipo desde un solo sitio.</p>
                </div>
                <div className="acciones-equipos-hero">
                    <Link to="/equipos/crear">Crear equipo</Link>
                    <Link to="/equipos/unirse" className="boton-secundario-equipo">Unirse a equipo</Link>
                </div>
            </section>

            {mensaje && <p className="mensaje mensaje-error">{mensaje}</p>}

            <section className="panel-equipos">
                <div className="cabecera-seccion-equipos">
                    <div>
                        <h2>Tus equipos</h2>
                        <p>{equipos.length} equipos activos</p>
                    </div>
                </div>

                {cargando && <p className="estado">Cargando equipos...</p>}

                {!cargando && equipos.length === 0 && (
                    <div className="estado-vacio-equipos">
                        <h3>Todavía no perteneces a ningún equipo</h3>
                        <p>Crea uno nuevo o únete a un equipo existente para empezar.</p>
                    </div>
                )}

                <div className="grid-equipos">
                    {equipos.map((equipo) => (
                        <article className="card-equipo" key={equipo.id_equipo}>
                            <div className="card-equipo-top">
                                <div className="titulo-card-equipo">
                                    <h3>{equipo.nombre_equipo}</h3>
                                    <span className={rolUsuario(equipo) === "capitan" ? "badge-capitan" : "badge-equipo"}>
                                        {formatearRol(rolUsuario(equipo))}
                                    </span>
                                    <span className={equipo.privacidad === "privado" ? "badge-equipo badge-equipo-privado" : "badge-equipo"}>
                                        {equipo.privacidad === "privado" ? "Privado" : "Público"}
                                    </span>
                                </div>
                            </div>

                            <p>{equipo.descripcion || "Sin descripción"}</p>

                            <div className="meta-equipo-card">
                                <span>Jugadores</span>
                                <strong>{equipo.jugadores_count ?? equipo.usuarios?.length ?? 0}</strong>
                                <span>Creado</span>
                                <strong>{equipo.fecha_creacion || "Sin fecha"}</strong>
                            </div>

                            <div className="acciones-card-equipo">
                                <button type="button" onClick={() => navigate(`/equipos/${equipo.id_equipo}`)}>
                                    Ver equipo
                                </button>
                                <button type="button" className="boton-abandonar-equipo" onClick={() => abandonarEquipo(equipo.id_equipo)}>
                                    Abandonar
                                </button>
                            </div>
                        </article>
                    ))}
                </div>
            </section>
        </main>
    );
};

export default Equipos;
