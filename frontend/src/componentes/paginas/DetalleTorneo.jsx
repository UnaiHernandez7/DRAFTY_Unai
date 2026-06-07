import { useEffect, useMemo, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import EncabezadoSeccion from "../comunes/EncabezadoSeccion.jsx";
import BracketTorneo from "./BracketTorneo.jsx";
import MapaTorneo from "./MapaTorneo.jsx";
import RankingTorneo from "./RankingTorneo.jsx";
import "./Torneos.css";

// Archivo propio del frontend de Drafty.
const estadoTexto = {
    inscripcion_abierta: "Inscripción abierta",
    en_curso: "En curso",
    finalizado: "Finalizado",
    cancelado: "Cancelado"
};

// Funcion auxiliar usada por este componente.
const DetalleTorneo = () => {
    const { id } = useParams();
    // Dato usado para pintar esta pantalla.
    const navigate = useNavigate();
    const { isAuth, usuario, isAdmin } = useAuth();
    // Estado que guarda informacion de la pantalla.
    const [torneo, setTorneo] = useState(null);
    // Estado que guarda informacion de la pantalla.
    const [equipos, setEquipos] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [rankingGoles, setRankingGoles] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [idEquipo, setIdEquipo] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [codigo, setCodigo] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [mensaje, setMensaje] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [tipoMensaje, setTipoMensaje] = useState("error");
    // Estado que guarda informacion de la pantalla.
    const [cargando, setCargando] = useState(true);

    // Funcion que llama al servidor y actualiza la pantalla.
    const cargarDetalle = async () => {
        try {
            const [resTorneo, resGoles] = await Promise.all([
                api.get(`/torneos/${id}`),
                api.get(`/torneos/${id}/ranking-goles`).catch(() => ({ data: [] }))
            ]);
            setTorneo(resTorneo.data);
            setRankingGoles(Array.isArray(resGoles.data) ? resGoles.data : []);
        } catch {
            setTipoMensaje("error");
            setMensaje("No se ha podido cargar el torneo.");
        } finally {
            setCargando(false);
        }
    };

    // Funcion que llama al servidor y actualiza la pantalla.
    const cargarEquipos = async () => {
        if (!isAuth) return;
        try {
            // Dato usado para pintar esta pantalla.
            const respuesta = await api.get("/mis-equipos");
            // Dato usado para pintar esta pantalla.
            const lista = Array.isArray(respuesta.data) ? respuesta.data : [];
            setEquipos(lista);
            setIdEquipo(lista[0]?.id_equipo || "");
        } catch {
            setEquipos([]);
        }
    };

    // Efecto que se ejecuta cuando cambian los datos indicados.
    useEffect(() => {
        cargarDetalle();
    }, [id]);

    // Efecto que se ejecuta cuando cambian los datos indicados.
    useEffect(() => {
        cargarEquipos();
    }, [isAuth]);

    // Dato usado para pintar esta pantalla.
    const estado = torneo?.estado_torneo || torneo?.estado || "inscripcion_abierta";
    // Dato usado para pintar esta pantalla.
    const puedeEditar = useMemo(() => {
        return !!torneo && (isAdmin || Number(torneo.id_organizador) === Number(usuario?.id_usuario));
    }, [torneo, isAdmin, usuario]);

    // Funcion que llama al servidor y actualiza la pantalla.
    const unirse = async () => {
        if (!isAuth) {
            navigate("/login");
            return;
        }

        try {
            // Dato usado para pintar esta pantalla.
            const respuesta = await api.post(`/torneos/${id}/unirse`, {
                id_equipo: idEquipo,
                codigo_acceso: codigo
            });
            setTipoMensaje("exito");
            setMensaje(respuesta.data?.mensaje || "Equipo inscrito correctamente.");
            cargarDetalle();
        } catch (error) {
            setTipoMensaje("error");
            setMensaje(error.response?.data?.mensaje || "No se ha podido inscribir el equipo.");
        }
    };

    // Funcion que llama al servidor y actualiza la pantalla.
    const iniciar = async () => {
        try {
            // Dato usado para pintar esta pantalla.
            const respuesta = await api.post(`/torneos/${id}/iniciar`);
            setTipoMensaje("exito");
            setMensaje(respuesta.data?.mensaje || "Torneo iniciado.");
            cargarDetalle();
        } catch (error) {
            setTipoMensaje("error");
            setMensaje(error.response?.data?.mensaje || "No se ha podido iniciar el torneo.");
        }
    };

    // Funcion auxiliar usada por este componente.
    const manejarActualizacionBracket = (texto, tipo = "exito") => {
        setTipoMensaje(tipo);
        setMensaje(texto);
        cargarDetalle();
    };

    if (cargando) {
        // Vista que se muestra al usuario.
        return (
            <main className="inicio torneos-page">
                <p className="torneo-empty">Cargando torneo...</p>
            </main>
        );
    }

    if (!torneo) {
        // Vista que se muestra al usuario.
        return (
            <main className="inicio torneos-page">
                <p className="mensaje mensaje-error">{mensaje || "No se ha encontrado el torneo."}</p>
            </main>
        );
    }

    // Vista que se muestra al usuario.
    return (
        <main className="inicio torneos-page">
            <EncabezadoSeccion
                titulo={torneo.nombre_torneo}
                descripcion={torneo.descripcion || "Competición DRAFTY sin descripción."}
                accion={(
                    <>
                        <button type="button" onClick={() => navigate("/torneos")}>Volver</button>
                        <span className="torneo-hero-stats">
                            <strong>{torneo.equipos_count ?? torneo.equipos?.length ?? 0}/{torneo.max_equipos}</strong>
                            <span>{estadoTexto[estado] || estado}</span>
                        </span>
                    </>
                )}
            />

            {mensaje && <p className={`mensaje ${tipoMensaje === "exito" ? "mensaje-exito" : "mensaje-error"}`}>{mensaje}</p>}

            <section className="torneo-detail-grid">
                <article className="torneo-panel">
                    <h2>Información general</h2>
                    <div className="torneo-info-list">
                        <span>Organizador <b>{torneo.organizador?.nombre_usuario || torneo.organizador?.nombre || "DRAFTY"}</b></span>
                        <span>Inicio <b>{torneo.fecha_inicio || "Sin fecha"}</b></span>
                        <span>Fin <b>{torneo.fecha_fin || "Sin fecha"}</b></span>
                        <span>Tipo de fútbol <b>{torneo.tipo_futbol || "Sin definir"}</b></span>
                        <span>Privacidad <b>{torneo.privacidad}</b></span>
                        <span>Premio <b>{torneo.premio || "Sin premio"}</b></span>
                        <span>Cuota <b>{torneo.cuota_inscripcion || 0} EUR</b></span>
                    </div>
                </article>

                <article className="torneo-panel">
                    <h2>Inscripcion</h2>
                    {estado !== "inscripcion_abierta" ? (
                        <p>Las inscripciones no están abiertas ahora mismo.</p>
                    ) : (
                        <div className="torneo-join-detail">
                            <select value={idEquipo} onChange={(e) => setIdEquipo(e.target.value)}>
                                {equipos.length === 0 && <option value="">Sin equipos disponibles</option>}
                                {equipos.map((equipo) => (
                                    <option key={equipo.id_equipo} value={equipo.id_equipo}>{equipo.nombre_equipo}</option>
                                ))}
                            </select>
                            {torneo.privacidad === "privado" && (
                                <input value={codigo} onChange={(e) => setCodigo(e.target.value)} placeholder="Código privado" />
                            )}
                            <button type="button" onClick={unirse}>Unirse con equipo</button>
                        </div>
                    )}
                    {puedeEditar && estado === "inscripcion_abierta" && (
                        <button type="button" className="torneo-start" onClick={iniciar}>Iniciar torneo</button>
                    )}
                </article>
            </section>

            <section className="torneo-panel">
                <div className="torneo-panel-head">
                    <h2>Ubicación del torneo</h2>
                    <span>{torneo.ciudad || "Sin ciudad"}</span>
                </div>
                <div className="torneo-location-grid">
                    <div className="torneo-location-copy">
                        <strong>{torneo.nombre_lugar || "Lugar pendiente"}</strong>
                        <p>{torneo.direccion || "Sin dirección guardada"}</p>
                        <span>{[torneo.ciudad, torneo.provincia].filter(Boolean).join(", ") || "Sin ciudad/provincia"}</span>
                    </div>
                    <MapaTorneo torneo={torneo} />
                </div>
            </section>

            <section className="torneo-panel">
                <h2>Equipos inscritos</h2>
                <div className="torneo-teams-list">
                    {(torneo.equipos || []).map((equipo) => (
                        <div key={equipo.id_equipo} className="torneo-team-chip">
                            <strong>{equipo.nombre_equipo}</strong>
                            <span>{equipo.usuarios?.length || 0} jugadores</span>
                        </div>
                    ))}
                    {(torneo.equipos || []).length === 0 && <p>Todavía no hay equipos inscritos.</p>}
                </div>
            </section>

            <section className="torneo-panel">
                <div className="torneo-panel-head">
                    <h2>Bracket</h2>
                    <span>{torneo.partidos_bracket_count || torneo.partidos_bracket?.length || 0} cruces</span>
                </div>
                <BracketTorneo
                    partidos={torneo.partidos_bracket || []}
                    puedeEditar={puedeEditar}
                    onActualizado={manejarActualizacionBracket}
                />
            </section>

            <section className="torneo-rankings">
                <RankingTorneo titulo="Ranking de goles" datos={rankingGoles} campo="goles" />
            </section>
        </main>
    );
};

export default DetalleTorneo;
