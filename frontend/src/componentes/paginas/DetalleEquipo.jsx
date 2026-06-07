import { useEffect, useMemo, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import EncabezadoSeccion from "../comunes/EncabezadoSeccion.jsx";
import ChatEquipo from "./ChatEquipo.jsx";
import "./Inicio.css";

// Archivo propio del frontend de Drafty.
const finalizado = (partido) => (
    ["finalizado", "cancelado"].includes(partido.estado) ||
    partido.goles_equipo_a != null ||
    partido.goles_equipo_b != null
);

// Funcion auxiliar usada por este componente.
const formatearRol = (rol) => {
    // Dato usado para pintar esta pantalla.
    const roles = {
        capitan: "Capitán",
        jugador: "Jugador",
        invitado: "Invitado"
    };

    return roles[rol] || "Jugador";
};

// Funcion auxiliar usada por este componente.
const DetalleEquipo = () => {
    const { id } = useParams();
    // Dato usado para pintar esta pantalla.
    const navigate = useNavigate();
    const { isAuth, usuario } = useAuth();
    // Estado que guarda informacion de la pantalla.
    const [equipo, setEquipo] = useState(null);
    // Estado que guarda informacion de la pantalla.
    const [partidos, setPartidos] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [historial, setHistorial] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [torneosGanados, setTorneosGanados] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [ranking, setRanking] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [mensaje, setMensaje] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [cargando, setCargando] = useState(true);

    // Funcion que llama al servidor y actualiza la pantalla.
    const cargarDetalle = async () => {
        if (!isAuth) {
            setCargando(false);
            return;
        }

        try {
            const [respuestaEquipo, respuestaPartidos, respuestaHistorial, respuestaTorneosGanados, respuestaRanking] = await Promise.all([
                api.get(`/equipos/${id}`),
                api.get(`/equipos/${id}/partidos`),
                api.get(`/equipos/${id}/historial`),
                api.get(`/equipos/${id}/torneos-ganados`),
                api.get(`/equipos/${id}/ranking`)
            ]);

            setEquipo(respuestaEquipo.data);
            setPartidos(Array.isArray(respuestaPartidos.data) ? respuestaPartidos.data : []);
            setHistorial(Array.isArray(respuestaHistorial.data) ? respuestaHistorial.data : []);
            setTorneosGanados(Array.isArray(respuestaTorneosGanados.data) ? respuestaTorneosGanados.data : []);
            setRanking(Array.isArray(respuestaRanking.data) ? respuestaRanking.data : []);
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido cargar el equipo.");
        } finally {
            setCargando(false);
        }
    };

    // Efecto que se ejecuta cuando cambian los datos indicados.
    useEffect(() => {
        cargarDetalle();
    }, [id, isAuth]);

    // Dato usado para pintar esta pantalla.
    const jugadores = useMemo(() => (
        equipo?.usuarios?.filter((jugador) => (
            jugador.pivot?.rol_en_equipo !== "invitado" &&
            (!jugador.pivot?.estado || jugador.pivot.estado === "activo")
        )) || []
    ), [equipo]);
    // Dato usado para pintar esta pantalla.
    const partidosTorneoHistorial = useMemo(() => (
        torneosGanados.flatMap((torneo) => (
            (torneo.partidos_bracket || []).map((partido) => ({
                ...partido,
                torneo_nombre: torneo.nombre_torneo,
                torneo_fecha: torneo.fecha_fin || torneo.fecha_inicio,
                torneo_campeon: torneo.campeon
            }))
        ))
    ), [torneosGanados]);

    // Dato usado para pintar esta pantalla.
    const miRol = jugadores.find((jugador) => Number(jugador.id_usuario) === Number(usuario?.id_usuario))?.pivot?.rol_en_equipo || "jugador";
    // Dato usado para pintar esta pantalla.
    const puedoGestionarMiembros = miRol === "capitan" || Number(equipo?.id_creador) === Number(usuario?.id_usuario);

    // Funcion que llama al servidor y actualiza la pantalla.
    const cambiarRolMiembro = async (idUsuario, rol) => {
        try {
            // Dato usado para pintar esta pantalla.
            const respuesta = await api.patch(`/equipos/${id}/jugadores/${idUsuario}/rol`, {
                rol_en_equipo: rol
            });
            setMensaje(respuesta.data?.mensaje || "Rol actualizado.");
            cargarDetalle();
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido actualizar el rol.");
        }
    };

    // Funcion que llama al servidor y actualiza la pantalla.
    const expulsarMiembro = async (idUsuario) => {
        try {
            // Dato usado para pintar esta pantalla.
            const respuesta = await api.delete(`/equipos/${id}/jugadores/${idUsuario}`);
            setMensaje(respuesta.data?.mensaje || "Jugador expulsado.");
            cargarDetalle();
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido expulsar al jugador.");
        }
    };

    // Funcion que llama al servidor y actualiza la pantalla.
    const unirseAPartido = async (idPartido) => {
        if (!isAuth) {
            navigate("/login");
            return;
        }

        try {
            // Dato usado para pintar esta pantalla.
            const respuesta = await api.post(`/equipos/${id}/partidos/${idPartido}/unirse`);
            setMensaje(respuesta.data?.mensaje || "Te has unido al partido.");
            cargarDetalle();
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido unir al partido.");
        }
    };

    // Funcion que llama al servidor y actualiza la pantalla.
    const abandonarEquipo = async () => {
        try {
            // Dato usado para pintar esta pantalla.
            const respuesta = await api.post(`/equipos/${id}/salir`);
            setMensaje(respuesta.data?.mensaje || "Has abandonado el equipo.");
            navigate("/equipos");
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido abandonar el equipo.");
        }
    };

    if (!isAuth) {
        // Vista que se muestra al usuario.
        return (
            <main className="inicio equipos-page">
                <EncabezadoSeccion
                    titulo="Equipo"
                    descripcion="Inicia sesión para ver los detalles del equipo."
                    accion={<button type="button" onClick={() => navigate("/login")}>Iniciar sesión</button>}
                />
            </main>
        );
    }

    if (cargando) {
        return <main className="inicio equipos-page"><p className="estado">Cargando equipo...</p></main>;
    }

    if (!equipo) {
        // Vista que se muestra al usuario.
        return (
            <main className="inicio equipos-page">
                {mensaje && <p className="mensaje mensaje-error">{mensaje}</p>}
                <Link to="/equipos" className="volver-equipos">Volver a equipos</Link>
            </main>
        );
    }

    // Vista que se muestra al usuario.
    return (
        <main className="inicio equipos-page">
            <EncabezadoSeccion
                titulo={equipo.nombre_equipo}
                descripcion={equipo.descripcion || "Sin descripción"}
                accion={(
                    <>
                        <Link to="/equipos">Volver</Link>
                        <button type="button" className="boton-abandonar-equipo" onClick={abandonarEquipo}>
                            Abandonar equipo
                        </button>
                    </>
                )}
            />

            <div className="resumen-detalle-equipo">
                <div><span>Jugadores</span><strong>{equipo.jugadores_count ?? jugadores.length}</strong></div>
                <div><span>Tu rol</span><strong>{formatearRol(miRol)}</strong></div>
                <div><span>Acceso</span><strong>{equipo.privacidad === "privado" ? "Privado" : "Público"}</strong></div>
                <div><span>Creado</span><strong>{equipo.fecha_creacion || "Sin fecha"}</strong></div>
            </div>

            {mensaje && <p className="mensaje">{mensaje}</p>}

            <section className="bloque-detalle-equipo">
                <div className="cabecera-bloque-equipo">
                    <h2>Información general</h2>
                </div>
                <div className="info-grid-equipo">
                    <div><span>Nombre</span><strong>{equipo.nombre_equipo}</strong></div>
                    <div><span>Descripción</span><strong>{equipo.descripcion || "Sin descripción"}</strong></div>
                    <div><span>Capitán principal</span><strong>{equipo.creador?.nombre_usuario || "Sin capitán"}</strong></div>
                    <div><span>Acceso</span><strong>{equipo.privacidad === "privado" ? "Solo por invitación" : "Público"}</strong></div>
                    <div><span>Jugadores</span><strong>{jugadores.length}</strong></div>
                </div>
            </section>

            <section className="bloque-detalle-equipo">
                <div className="cabecera-bloque-equipo">
                    <h2>Jugadores</h2>
                    <span>{jugadores.length} activos</span>
                </div>
                <div className="grid-jugadores-equipo">
                    {jugadores.map((jugador) => {
                        // Dato usado para pintar esta pantalla.
                        const esCapitan = jugador.pivot?.rol_en_equipo === "capitan";
                        // Dato usado para pintar esta pantalla.
                        const esCreador = Number(jugador.id_usuario) === Number(equipo.id_creador);
                        // Dato usado para pintar esta pantalla.
                        const puedoEditarJugador = puedoGestionarMiembros && !esCreador && Number(jugador.id_usuario) !== Number(usuario?.id_usuario);
                        // Vista que se muestra al usuario.
                        return (
                            <article className="jugador-equipo-card" key={jugador.id_usuario}>
                                <div className="avatar-amigo">
                                    {jugador.foto_perfil ? (
                                        <img src={jugador.foto_perfil} alt={jugador.nombre_usuario} />
                                    ) : (
                                        <span>{(jugador.nombre_usuario || jugador.nombre || "D").slice(0, 1).toUpperCase()}</span>
                                    )}
                                </div>
                                <div>
                                    <strong>{jugador.nombre} {jugador.apellido}</strong>
                                    <p>@{jugador.nombre_usuario}</p>
                                    <small>{jugador.posiciones_favoritas || "Sin posición favorita"}</small>
                                </div>
                                <span className={esCapitan ? "badge-capitan" : "badge-equipo"}>
                                    {esCreador ? "Creador" : formatearRol(jugador.pivot?.rol_en_equipo)}
                                </span>
                                {puedoEditarJugador && (
                                    <div className="acciones-miembro-equipo">
                                        <button
                                            type="button"
                                            onClick={() => cambiarRolMiembro(jugador.id_usuario, esCapitan ? "jugador" : "capitan")}
                                        >
                                            {esCapitan ? "Hacer jugador" : "Hacer capitán"}
                                        </button>
                                        <button
                                            type="button"
                                            className="boton-expulsar-miembro"
                                            onClick={() => expulsarMiembro(jugador.id_usuario)}
                                        >
                                            Expulsar
                                        </button>
                                    </div>
                                )}
                            </article>
                        );
                    })}
                </div>
            </section>

            <section className="bloque-detalle-equipo">
                <div className="cabecera-bloque-equipo">
                    <h2>Partidos del equipo</h2>
                    <span>{partidos.length} próximos</span>
                </div>
                <div className="lista-partidos-equipo">
                    {partidos.length === 0 && <p className="estado">No hay partidos activos para este equipo.</p>}
                    {partidos.map((partido) => (
                        <article className="fila-partido-equipo" key={partido.id_partido}>
                            <div>
                                <strong>{partido.titulo}</strong>
                                <p>{partido.fecha || "Sin fecha"} {partido.hora || ""} - {partido.campo?.nombre_campo || "Campo sin asignar"}</p>
                            </div>
                            <div className="chips-partido-equipo">
                                <span>{partido.nivel || "Casual"}</span>
                                <span>{partido.estado || "abierto"}</span>
                                <span>{partido.usuarios_count ?? 0}/{partido.plazas_totales || "-"}</span>
                            </div>
                            <button type="button" disabled={finalizado(partido)} onClick={() => unirseAPartido(partido.id_partido)}>
                                Unirse
                            </button>
                        </article>
                    ))}
                </div>
            </section>

            <section className="bloque-detalle-equipo">
                <div className="cabecera-bloque-equipo">
                    <h2>Historial</h2>
                    <span>{historial.length} partidos · {partidosTorneoHistorial.length} partidos de torneo</span>
                </div>
                <div className="lista-partidos-equipo">
                    {historial.length === 0 && partidosTorneoHistorial.length === 0 && <p className="estado">Todavía no hay resultados registrados.</p>}
                    {partidosTorneoHistorial.map((partido) => (
                        <article className="fila-partido-equipo torneo-ganado-equipo" key={`torneo-partido-${partido.id_torneo_partido}`}>
                            <div>
                                <strong>{partido.torneo_nombre}</strong>
                                <p>
                                    {partido.torneo_campeon ? "Campeones" : "Torneo jugado"} · {partido.ronda} · {partido.torneo_fecha || "Sin fecha"}
                                </p>
                            </div>
                            <div className="resultado-equipo titulo-torneo-equipo resultado-torneo-detallado">
                                <span>{partido.equipo_local?.nombre_equipo || "Local"}</span>
                                <strong>{partido.goles_local ?? "-"} : {partido.goles_visitante ?? "-"}</strong>
                                <span>{partido.equipo_visitante?.nombre_equipo || "Visitante"}</span>
                            </div>
                        </article>
                    ))}
                    {historial.map((partido) => (
                        <article className="fila-partido-equipo" key={partido.id_partido}>
                            <div>
                                <strong>{partido.titulo}</strong>
                                <p>{partido.fecha || "Sin fecha"} - {partido.campo?.nombre_campo || "Campo sin asignar"}</p>
                            </div>
                            <div className="resultado-equipo">
                                {partido.goles_equipo_a ?? "-"} : {partido.goles_equipo_b ?? "-"}
                            </div>
                        </article>
                    ))}
                </div>
            </section>

            <section className="bloque-detalle-equipo">
                <div className="cabecera-bloque-equipo">
                    <h2>Ranking interno</h2>
                    <span>estadísticas de equipo</span>
                </div>
                <div className="tabla-ranking-equipo">
                    <div className="fila-ranking-equipo cabecera-ranking-equipo">
                        <span>Jugador</span>
                        <span>PJ</span>
                        <span>Goles</span>
                        <span>PC</span>
                    </div>
                    {ranking.length === 0 && <p className="estado">Todavía no hay estadísticas de equipo.</p>}
                    {ranking.map((item) => (
                        <div className="fila-ranking-equipo" key={item.id_estadistica_equipo_usuario}>
                            <span>{item.usuario?.nombre_usuario || "Usuario"}</span>
                            <span>{item.partidos_jugados}</span>
                            <span>{item.goles}</span>
                            <span>{item.porterias_cero}</span>
                        </div>
                    ))}
                </div>
            </section>

            <ChatEquipo idEquipo={id} />
        </main>
    );
};

export default DetalleEquipo;
