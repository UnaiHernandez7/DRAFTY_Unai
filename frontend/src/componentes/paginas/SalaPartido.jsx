import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import EncabezadoSeccion from "../comunes/EncabezadoSeccion.jsx";
import MapaSalaPartido from "./MapaSalaPartido.jsx";
import ResultadoPartido from "./ResultadoPartido.jsx";
import VotacionMVP from "./VotacionMVP.jsx";
import ValoracionesPartido from "./ValoracionesPartido.jsx";
import "./Inicio.css";
import "./PostPartido.css";

const formacionesPorTipo = {
    futbol11: {
        "4-3-3": [
        { posicion: "POR", x: 50, y: 90 },
        { posicion: "LI", x: 18, y: 72 },
        { posicion: "DFC", x: 39, y: 76 },
        { posicion: "DFC", x: 61, y: 76 },
        { posicion: "LD", x: 82, y: 72 },
        { posicion: "MC", x: 28, y: 52 },
        { posicion: "MCD", x: 50, y: 58 },
        { posicion: "MC", x: 72, y: 52 },
        { posicion: "EI", x: 22, y: 25 },
        { posicion: "DC", x: 50, y: 18 },
        { posicion: "ED", x: 78, y: 25 }
    ],
        "4-3-1-2": [
        { posicion: "POR", x: 50, y: 90 },
        { posicion: "LI", x: 18, y: 72 },
        { posicion: "DFC", x: 39, y: 76 },
        { posicion: "DFC", x: 61, y: 76 },
        { posicion: "LD", x: 82, y: 72 },
        { posicion: "MC", x: 28, y: 55 },
        { posicion: "MCD", x: 50, y: 60 },
        { posicion: "MC", x: 72, y: 55 },
        { posicion: "MCO", x: 50, y: 39 },
        { posicion: "DC", x: 38, y: 20 },
        { posicion: "DC", x: 62, y: 20 }
        ],
        "4-4-2": [
        { posicion: "POR", x: 50, y: 90 },
        { posicion: "LI", x: 18, y: 72 },
        { posicion: "DFC", x: 39, y: 76 },
        { posicion: "DFC", x: 61, y: 76 },
        { posicion: "LD", x: 82, y: 72 },
        { posicion: "MI", x: 18, y: 50 },
        { posicion: "MC", x: 39, y: 55 },
        { posicion: "MC", x: 61, y: 55 },
        { posicion: "MD", x: 82, y: 50 },
        { posicion: "DC", x: 38, y: 20 },
        { posicion: "DC", x: 62, y: 20 }
        ],
        "3-5-2": [
        { posicion: "POR", x: 50, y: 90 },
        { posicion: "DFC", x: 28, y: 74 },
        { posicion: "DFC", x: 50, y: 78 },
        { posicion: "DFC", x: 72, y: 74 },
        { posicion: "CAI", x: 15, y: 48 },
        { posicion: "MCD", x: 35, y: 55 },
        { posicion: "MC", x: 50, y: 48 },
        { posicion: "MCD", x: 65, y: 55 },
        { posicion: "CAD", x: 85, y: 48 },
        { posicion: "DC", x: 38, y: 20 },
        { posicion: "DC", x: 62, y: 20 }
        ],
        "4-2-3-1": [
        { posicion: "POR", x: 50, y: 90 },
        { posicion: "LI", x: 18, y: 72 },
        { posicion: "DFC", x: 39, y: 76 },
        { posicion: "DFC", x: 61, y: 76 },
        { posicion: "LD", x: 82, y: 72 },
        { posicion: "MCD", x: 38, y: 57 },
        { posicion: "MCD", x: 62, y: 57 },
        { posicion: "EI", x: 24, y: 35 },
        { posicion: "MCO", x: 50, y: 38 },
        { posicion: "ED", x: 76, y: 35 },
        { posicion: "DC", x: 50, y: 18 }
        ]
    },
    futbol7: {
        "3-3-1": [
            { posicion: "POR", x: 50, y: 88 },
            { posicion: "DFC", x: 28, y: 68 },
            { posicion: "DFC", x: 50, y: 72 },
            { posicion: "DFC", x: 72, y: 68 },
            { posicion: "MC", x: 25, y: 48 },
            { posicion: "MC", x: 50, y: 52 },
            { posicion: "MC", x: 75, y: 48 },
            { posicion: "DC", x: 50, y: 22 }
        ],
        "2-3-2": [
            { posicion: "POR", x: 50, y: 88 },
            { posicion: "DFC", x: 35, y: 68 },
            { posicion: "DFC", x: 65, y: 68 },
            { posicion: "MC", x: 25, y: 48 },
            { posicion: "MC", x: 50, y: 52 },
            { posicion: "MC", x: 75, y: 48 },
            { posicion: "DC", x: 38, y: 22 },
            { posicion: "DC", x: 62, y: 22 }
        ],
        "3-2-2": [
            { posicion: "POR", x: 50, y: 88 },
            { posicion: "DFC", x: 28, y: 68 },
            { posicion: "DFC", x: 50, y: 72 },
            { posicion: "DFC", x: 72, y: 68 },
            { posicion: "MC", x: 38, y: 48 },
            { posicion: "MC", x: 62, y: 48 },
            { posicion: "DC", x: 38, y: 22 },
            { posicion: "DC", x: 62, y: 22 }
        ],
        "2-4-1": [
            { posicion: "POR", x: 50, y: 88 },
            { posicion: "DFC", x: 35, y: 68 },
            { posicion: "DFC", x: 65, y: 68 },
            { posicion: "MI", x: 18, y: 48 },
            { posicion: "MCD", x: 40, y: 52 },
            { posicion: "MCD", x: 60, y: 52 },
            { posicion: "MD", x: 82, y: 48 },
            { posicion: "DC", x: 50, y: 22 }
        ],
        "2-1-3-1": [
            { posicion: "POR", x: 50, y: 88 },
            { posicion: "DFC", x: 35, y: 70 },
            { posicion: "DFC", x: 65, y: 70 },
            { posicion: "MCD", x: 50, y: 56 },
            { posicion: "EI", x: 25, y: 42 },
            { posicion: "MCO", x: 50, y: 40 },
            { posicion: "ED", x: 75, y: 42 },
            { posicion: "DC", x: 50, y: 20 }
        ]
    },
    sala: {
        "1-2-1": [
            { posicion: "POR", x: 50, y: 88 },
            { posicion: "DFC", x: 50, y: 66 },
            { posicion: "ALA", x: 30, y: 45 },
            { posicion: "ALA", x: 70, y: 45 },
            { posicion: "PIV", x: 50, y: 22 }
        ],
        "2-1-1": [
            { posicion: "POR", x: 50, y: 88 },
            { posicion: "DFC", x: 35, y: 62 },
            { posicion: "DFC", x: 65, y: 62 },
            { posicion: "ALA", x: 50, y: 43 },
            { posicion: "PIV", x: 50, y: 22 }
        ],
        "2-2": [
            { posicion: "POR", x: 50, y: 88 },
            { posicion: "DFC", x: 35, y: 62 },
            { posicion: "DFC", x: 65, y: 62 },
            { posicion: "PIV", x: 35, y: 28 },
            { posicion: "PIV", x: 65, y: 28 }
        ],
        "1-1-2": [
            { posicion: "POR", x: 50, y: 88 },
            { posicion: "DFC", x: 50, y: 66 },
            { posicion: "ALA", x: 50, y: 46 },
            { posicion: "PIV", x: 35, y: 22 },
            { posicion: "PIV", x: 65, y: 22 }
        ]
    }
};

const obtenerTipoFormacion = (tipoFutbol = "") => {
    const tipo = String(tipoFutbol || "").toLowerCase();

    if (tipo.includes("5v5") || tipo.includes("sala")) {
        return "sala";
    }

    if (tipo.includes("7")) {
        return "futbol7";
    }

    return "futbol11";
};

const obtenerCapacidad = (partidoOTipo = "") => {
    if (typeof partidoOTipo === "object" && partidoOTipo !== null) {
        const capacidadPorTipo = obtenerCapacidad(partidoOTipo.tipo_futbol);
        const capacidadGuardada = partidoOTipo.plazas_totales_calculadas ?? partidoOTipo.plazas_totales ?? 0;

        return Math.max(capacidadGuardada, capacidadPorTipo);
    }

    const tipo = String(partidoOTipo || "").toLowerCase();

    if (tipo.includes("5v5") || tipo.includes("sala")) {
        return 14;
    }

    if (tipo.includes("7")) {
        return 20;
    }

    return 26;
};

const obtenerFormacionesSeguras = (tipoFutbol) => (
    formacionesPorTipo[obtenerTipoFormacion(tipoFutbol)] || formacionesPorTipo.futbol11
);

const partidoHaEmpezado = (partido) => {
    if (!partido?.fecha || !partido?.hora) {
        return false;
    }

    return new Date(`${partido.fecha}T${partido.hora}`) <= new Date();
};

const esCompetitivo = (partido) => Boolean(partido?.es_competitivo) || partido?.nivel === "Competitivo";
const posicionesInvitacion = ["Portero", "Defensa", "Mediocentro", "Delantero"];

const SalaPartido = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const { usuario, token, isAdmin } = useAuth();
    const [partido, setPartido] = useState(null);
    const [datosAdmin, setDatosAdmin] = useState({
        titulo: "",
        fecha: "",
        hora: "",
        tipo_futbol: "",
        nivel: "",
        estado: "",
        plazas_totales: ""
    });
    const [mensajes, setMensajes] = useState([]);
    const [nuevoMensaje, setNuevoMensaje] = useState("");
    const [mensaje, setMensaje] = useState("");
    const [cargando, setCargando] = useState(true);
    const [posicionInvitacion, setPosicionInvitacion] = useState("Portero");
    const [candidatosInvitacion, setCandidatosInvitacion] = useState([]);
    const [cargandoCandidatos, setCargandoCandidatos] = useState(false);
    const [invitandoId, setInvitandoId] = useState(null);

    const cargarSala = async () => {
        if (!id || id === "undefined" || Number.isNaN(Number(id))) {
            setCargando(false);
            setPartido(null);
            setMensaje("No se ha encontrado la sala de este partido.");
            return;
        }

        try {
            setCargando(true);
            setMensaje("");
            const respuesta = await api.get(`/partidos/${id}/sala`);
            setPartido(respuesta.data);
            setDatosAdmin({
                titulo: respuesta.data.titulo || "",
                fecha: respuesta.data.fecha || "",
                hora: respuesta.data.hora || "",
                tipo_futbol: respuesta.data.tipo_futbol || "",
                nivel: respuesta.data.nivel || "",
                estado: respuesta.data.estado || "",
                plazas_totales: respuesta.data.plazas_totales || ""
            });

            try {
                const respuestaMensajes = await api.get(`/partidos/${id}/mensajes`);
                const datosMensajes = Array.isArray(respuestaMensajes.data)
                    ? respuestaMensajes.data
                    : respuestaMensajes.data?.value;

                setMensajes(Array.isArray(datosMensajes) ? datosMensajes : []);
            } catch {
                setMensajes([]);
            }
        } catch (error) {
            setPartido(null);
            setMensaje(error.response?.data?.mensaje || "No se ha podido cargar la sala del partido.");
        } finally {
            setCargando(false);
        }
    };

    useEffect(() => {
        if (!token) {
            setCargando(false);
            navigate("/login");
            return;
        }

        cargarSala();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [id, token]);

    useEffect(() => {
        if (!partido || esCompetitivo(partido)) {
            setCandidatosInvitacion([]);
            return;
        }

        cargarCandidatosPorPosicion(posicionInvitacion);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [partido?.id_partido, posicionInvitacion]);

    const cambiarPosicion = async (nuevoEquipo, nuevaPosicion) => {
        try {
            await api.patch(`/partidos/${id}/posicion`, {
                equipo_asignado: nuevoEquipo,
                posicion_asignada: nuevaPosicion
            });
            setMensaje(`Alineación actualizada: ${nuevaPosicion}.`);
            cargarSala();
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido cambiar la posición.");
        }
    };

    const cambiarFormacion = async (equipo, nuevaFormacion) => {
        try {
            await api.patch(`/partidos/${id}/formacion`, {
                equipo_asignado: equipo,
                formacion: nuevaFormacion
            });
            setMensaje(`Formación ${nuevaFormacion} guardada.`);
            cargarSala();
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido cambiar la formación.");
        }
    };

    const salirDeLaSala = async () => {
        try {
            const respuesta = await api.post(`/partidos/${id}/salir`);

            if (respuesta.data?.partido_eliminado) {
                setMensaje("Has salido. La sala se ha eliminado porque estaba vacia.");
            }

            navigate("/");
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido salir del partido.");
        }
    };

    const guardarDatosAdmin = async (e) => {
        e.preventDefault();

        try {
            await api.patch(`/partidos/${id}`, datosAdmin);
            setMensaje("Partido actualizado correctamente.");
            cargarSala();
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido actualizar el partido.");
        }
    };

    const enviarMensaje = async (e) => {
        e.preventDefault();

        if (!nuevoMensaje.trim()) {
            return;
        }

        try {
            await api.post(`/partidos/${id}/mensajes`, { mensaje: nuevoMensaje });
            setNuevoMensaje("");
            cargarSala();
        } catch {
            setMensaje("No se ha podido enviar el mensaje.");
        }
    };

    const cargarCandidatosPorPosicion = async (posicion = posicionInvitacion) => {
        if (!id || !partido || esCompetitivo(partido)) {
            return;
        }

        try {
            setCargandoCandidatos(true);
            const respuesta = await api.get(`/partidos/${id}/candidatos-posicion`, {
                params: { posicion }
            });
            setCandidatosInvitacion(Array.isArray(respuesta.data) ? respuesta.data : []);
        } catch (error) {
            setCandidatosInvitacion([]);
            setMensaje(error.response?.data?.mensaje || "No se han podido cargar jugadores para invitar.");
        } finally {
            setCargandoCandidatos(false);
        }
    };

    const invitarPorPosicion = async (jugador) => {
        try {
            setInvitandoId(jugador.id_usuario);
            const respuesta = await api.post(`/partidos/${id}/invitar-posicion/${jugador.id_usuario}`, {
                posicion: posicionInvitacion
            });
            setMensaje(respuesta.data?.mensaje || "Invitación enviada correctamente.");
            await cargarCandidatosPorPosicion(posicionInvitacion);
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido enviar la invitación.");
        } finally {
            setInvitandoId(null);
        }
    };

    const participantes = partido?.usuarios || [];
    const miJugador = participantes.find((jugador) => jugador.id_usuario === usuario?.id_usuario);
    const miEquipo = miJugador?.pivot?.equipo_asignado;
    const plazasOcupadas = participantes.length;
    const formaciones = obtenerFormacionesSeguras(partido?.tipo_futbol);
    const capacidadTotal = obtenerCapacidad(partido);
    const jugadoresPorEquipo = (equipo) => participantes.filter((jugador) => jugador.pivot?.equipo_asignado === equipo);
    const capitanLocal = participantes.find((jugador) => (
        jugador.pivot?.equipo_asignado === "Equipo A" && jugador.pivot?.es_capitan
    ));
    const capitanVisitante = participantes.find((jugador) => (
        jugador.pivot?.equipo_asignado === "Equipo B" && jugador.pivot?.es_capitan
    ));
    const esPartidoCompetitivo = esCompetitivo(partido);
    const ventanaPostPartidoActiva = Boolean(partido?.ventana_resultado_abierta) && partido?.estado !== "cancelado";
    const alineacionBloqueada = partidoHaEmpezado(partido);
    const puedoGestionarResultado = ventanaPostPartidoActiva
        && partido?.resultado?.estado_resultado !== "cerrado"
        && Boolean(miJugador?.pivot?.es_capitan);
    const puedoVotarMvp = ventanaPostPartidoActiva && Boolean(miJugador);
    const puedoValorarJugadores = ventanaPostPartidoActiva && Boolean(miJugador?.pivot?.es_capitan);
    const mostrarPostPartido = puedoGestionarResultado || puedoVotarMvp || puedoValorarJugadores;
    const totalTarjetasPostPartido = [puedoGestionarResultado, puedoVotarMvp, puedoValorarJugadores]
        .filter(Boolean)
        .length;

    const buscarJugadorLibre = (equipo, posicion, usados) => {
        const jugador = participantes.find((jugador) => (
            jugador.pivot?.equipo_asignado === equipo &&
            jugador.pivot?.posicion_asignada === posicion &&
            !usados.includes(jugador.id_usuario)
        ));

        if (jugador) {
            usados.push(jugador.id_usuario);
        }

        return jugador;
    };

    const pintarCampo = (equipo, titulo) => {
        const jugadores = jugadoresPorEquipo(equipo);
        const jugadoresPintados = [];
        const suplentesPintados = [];
        const formacionGuardada = equipo === "Equipo A"
            ? partido?.formacion_local || "4-3-3"
            : partido?.formacion_visitante || "4-3-3";
        const formacionEquipo = formaciones[formacionGuardada]
            ? formacionGuardada
            : Object.keys(formaciones)[0];
        const posicionesFormacion = formaciones[formacionEquipo] || Object.values(formaciones)[0] || [];
        const soyCapitanDeEsteEquipo = miEquipo === equipo && miJugador?.pivot?.es_capitan;
        const puedeCambiarFormacion = soyCapitanDeEsteEquipo && !alineacionBloqueada;
        const suplentes = jugadores.filter((jugador) => jugador.pivot?.posicion_asignada === "SUP");

        return (
            <article className="equipo-alineacion">
                <div className="cabecera-alineacion">
                    <h2>{titulo}</h2>
                    <span>{formacionEquipo}</span>
                </div>

                <div className={`botones-formacion ${soyCapitanDeEsteEquipo ? "" : "botones-formacion-ocultos"}`}>
                    {Object.keys(formaciones).map((nombreFormacion) => (
                        <button
                            type="button"
                            key={`${equipo}-${nombreFormacion}`}
                            className={formacionEquipo === nombreFormacion ? "formacion-activa" : ""}
                            onClick={() => cambiarFormacion(equipo, nombreFormacion)}
                            disabled={!puedeCambiarFormacion}
                            title={alineacionBloqueada ? "El partido ya ha empezado" : ""}
                        >
                            {nombreFormacion}
                        </button>
                    ))}
                </div>
                {soyCapitanDeEsteEquipo && alineacionBloqueada && (
                    <p className="aviso-alineacion-bloqueada">El partido ya ha empezado. La alineación está bloqueada.</p>
                )}

                <div className="campo-alineacion">
                    {posicionesFormacion.map((zona, index) => {
                        const jugador = buscarJugadorLibre(equipo, zona.posicion, jugadoresPintados);
                        const estoyAqui = jugador?.id_usuario === usuario?.id_usuario;

                        return (
                            <button
                                type="button"
                                key={`${equipo}-${zona.posicion}-${index}`}
                                className={`zona-campo ${estoyAqui ? "mi-zona" : ""}`}
                                style={{ left: `${zona.x}%`, top: `${zona.y}%` }}
                                onClick={() => cambiarPosicion(equipo, zona.posicion)}
                                disabled={Boolean(jugador) || alineacionBloqueada}
                                title={alineacionBloqueada ? "El partido ya ha empezado" : ""}
                            >
                                <span>{zona.posicion}</span>
                                <strong>{jugador?.nombre_usuario || "Libre"}</strong>
                                {esPartidoCompetitivo && jugador && (
                                    <small>{jugador.competitivo?.rango || "Bronce 1"}</small>
                                )}
                            </button>
                        );
                    })}
                </div>
                <div className="banquillo">
                    <h3>Suplentes</h3>
                    {[0, 1].map((indice) => {
                        const jugador = suplentes.find((jugador) => !suplentesPintados.includes(jugador.id_usuario));

                        if (jugador) {
                            suplentesPintados.push(jugador.id_usuario);
                        }

                        return (
                            <button
                                type="button"
                                key={`${equipo}-suplente-${indice}`}
                                className={`suplente ${jugador?.id_usuario === usuario?.id_usuario ? "mi-zona" : ""}`}
                                onClick={() => cambiarPosicion(equipo, "SUP")}
                                disabled={alineacionBloqueada}
                                title={alineacionBloqueada ? "El partido ya ha empezado" : ""}
                            >
                                <span>SUP</span>
                                <strong>{jugador?.nombre_usuario || "Libre"}</strong>
                                {esPartidoCompetitivo && jugador && (
                                    <small>{jugador.competitivo?.rango || "Bronce 1"}</small>
                                )}
                            </button>
                        );
                    })}
                </div>
                <p className="jugadores-equipo">
                    Jugadores: {jugadores.length}
                </p>
            </article>
        );
    };

    return (
        <main className="inicio">
            <EncabezadoSeccion
                titulo={partido?.titulo || "Sala del partido"}
                descripcion="Alineaciones, posiciones, ubicación y chat del partido."
            />

            {mensaje && <p className="mensaje">{mensaje}</p>}
            {cargando && <p className="estado">Cargando sala...</p>}

            {!cargando && partido && (
                <>
                    <section className="info-partido-sala">
                        <div className="info-principal">
                            <h2>{partido.titulo}</h2>
                            <p>{partido.tipo_futbol || "Tipo de fútbol sin definir"} · {partido.nivel || "Nivel sin definir"}</p>
                        </div>

                        <div className="datos-partido">
                            <div>
                                <span>Fecha</span>
                                <strong>{partido.fecha || "Sin fecha"}</strong>
                            </div>
                            <div>
                                <span>Hora</span>
                                <strong>{partido.hora || "Sin hora"}</strong>
                            </div>
                            <div>
                                <span>Estado</span>
                                <strong>{partido.estado || "Sin estado"}</strong>
                            </div>
                            <div>
                                <span>Plazas</span>
                                <strong>{plazasOcupadas}/{capacidadTotal}</strong>
                            </div>
                        </div>

                        {!partido.es_publico && partido.codigo_acceso && (
                            <div className="codigo-privado">
                                <span>Código privado</span>
                                <strong>{partido.codigo_acceso}</strong>
                            </div>
                        )}

                        <div className="resumen-equipos">
                            <div>
                                <span>Capitán Equipo A</span>
                                <strong>{capitanLocal?.nombre_usuario || "Sin capitán"}</strong>
                            </div>
                            <div>
                                <span>Capitán Equipo B</span>
                                <strong>{capitanVisitante?.nombre_usuario || "Sin capitán"}</strong>
                            </div>
                            <div>
                                <span>Tu equipo</span>
                                <strong>{miEquipo || "Sin equipo"}</strong>
                            </div>
                            <div>
                                <span>Tu rol</span>
                                <strong>{miJugador?.pivot?.es_capitan ? "Capitán" : "Jugador"}</strong>
                            </div>
                        </div>

                        <div className="resultado-partido">
                            <div className="marcador">
                                <span>Equipo A</span>
                                <strong>{partido.resultado?.goles_local ?? partido.goles_equipo_a ?? "-"}</strong>
                                <small>-</small>
                                <strong>{partido.resultado?.goles_visitante ?? partido.goles_equipo_b ?? "-"}</strong>
                                <span>Equipo B</span>
                            </div>

                        </div>

                        {/*
                            <form className="formulario-resultado" onSubmit={guardarResultado}>
                                <label>
                                    Goles Equipo A
                                    <input
                                        type="number"
                                        min="0"
                                        value={resultado.goles_equipo_a}
                                        onChange={(e) => setResultado({ ...resultado, goles_equipo_a: e.target.value })}
                                    />
                                </label>

                                <label>
                                    Goles Equipo B
                                    <input
                                        type="number"
                                        min="0"
                                        value={resultado.goles_equipo_b}
                                        onChange={(e) => setResultado({ ...resultado, goles_equipo_b: e.target.value })}
                                    />
                                </label>

                                <div className="lista-goleadores">
                                    {parsearGoleadores(resultado.goleadores).length === 0 ? (
                                        <p>Todavía no has añadido goleadores.</p>
                                    ) : (
                                        parsearGoleadores(resultado.goleadores).map((item) => (
                                            <div className="goleador-item" key={`${item.nombre}-${item.equipo}`}>
                                                <strong>{item.nombre}</strong>
                                                <small>{item.equipo || "Sin equipo"}</small>
                                                <span>{item.goles} {item.goles === 1 ? "gol" : "goles"}</span>
                                                <button type="button" onClick={() => quitarGoleador(item)}>
                                                    Quitar
                                                </button>
                                            </div>
                                        ))
                                    )}
                                </div>

                                <div className="selector-goleador">
                                    <label>
                                        Jugador
                                        <select
                                            value={nuevoGoleador.id_usuario}
                                            onChange={(e) => setNuevoGoleador({ ...nuevoGoleador, id_usuario: e.target.value })}
                                        >
                                            <option value="">Selecciona jugador</option>
                                            {participantes.map((jugador) => (
                                                <option value={jugador.id_usuario} key={jugador.id_usuario}>
                                                    {jugador.nombre_usuario || jugador.nombre} - {jugador.pivot?.equipo_asignado || "Sin equipo"}
                                                </option>
                                            ))}
                                        </select>
                                    </label>

                                    <label>
                                        Goles
                                        <input
                                            type="number"
                                            min="1"
                                            value={nuevoGoleador.goles}
                                            onChange={(e) => setNuevoGoleador({ ...nuevoGoleador, goles: e.target.value })}
                                        />
                                    </label>

                                    <button type="button" onClick={agregarGoleador}>
                                        Añadir goleador
                                    </button>
                                </div>

                                <button type="submit">Guardar resultado</button>
                            </form>
                        */}
                    </section>

                    <section className="ubicacion-sala">
                        <div className="info-principal">
                            <h2>Ubicación del partido</h2>
                            <p>{partido.campo?.nombre_campo || partido.campo?.nombre || "Campo por confirmar"}</p>
                        </div>

                        <div className="datos-partido datos-ubicacion-sala">
                            <div>
                                <span>Dirección</span>
                                <strong>{partido.campo?.direccion || "Sin dirección"}</strong>
                            </div>
                            <div>
                                <span>Ciudad</span>
                                <strong>{partido.campo?.ciudad || "Sin ciudad"}</strong>
                            </div>
                            <div>
                                <span>Provincia</span>
                                <strong>{partido.campo?.provincia || "Sin provincia"}</strong>
                            </div>
                        </div>

                        <MapaSalaPartido campo={partido.campo} />
                    </section>

                    {mostrarPostPartido && (
                        <section className={`post-partido-grid ${totalTarjetasPostPartido === 1 ? "post-partido-grid-unico" : ""}`}>
                            {puedoGestionarResultado && (
                                <ResultadoPartido
                                    partido={partido}
                                    participantes={participantes}
                                    miJugador={miJugador}
                                    onCambio={cargarSala}
                                />
                            )}
                            {puedoVotarMvp && (
                                <VotacionMVP
                                    partido={partido}
                                    participantes={participantes}
                                    usuario={usuario}
                                />
                            )}
                            {puedoValorarJugadores && (
                                <ValoracionesPartido
                                    partido={partido}
                                    participantes={participantes}
                                    miJugador={miJugador}
                                />
                            )}
                        </section>
                    )}

                    {isAdmin && (
                        <section className="panel-admin">
                            <h2>Editar partido</h2>
                            <form className="formulario-admin" onSubmit={guardarDatosAdmin}>
                                <label>
                                    Titulo
                                    <input value={datosAdmin.titulo} onChange={(e) => setDatosAdmin({ ...datosAdmin, titulo: e.target.value })} />
                                </label>

                                <label>
                                    Fecha
                                    <input type="date" value={datosAdmin.fecha} onChange={(e) => setDatosAdmin({ ...datosAdmin, fecha: e.target.value })} />
                                </label>

                                <label>
                                    Hora
                                    <input type="time" value={datosAdmin.hora} onChange={(e) => setDatosAdmin({ ...datosAdmin, hora: e.target.value })} />
                                </label>

                                <label>
                                    Tipo de fútbol
                                    <select value={datosAdmin.tipo_futbol} onChange={(e) => setDatosAdmin({ ...datosAdmin, tipo_futbol: e.target.value })}>
                                        <option>Fútbol sala</option>
                                        <option>Fútbol 7</option>
                                        <option>Fútbol 11</option>
                                    </select>
                                </label>

                                <label>
                                    Nivel
                                    <select value={datosAdmin.nivel} onChange={(e) => setDatosAdmin({ ...datosAdmin, nivel: e.target.value })}>
                                        <option>Principiante</option>
                                        <option>Intermedio</option>
                                        <option>Avanzado</option>
                                        <option>Competitivo</option>
                                    </select>
                                </label>

                                <label>
                                    Estado
                                    <select value={datosAdmin.estado} onChange={(e) => setDatosAdmin({ ...datosAdmin, estado: e.target.value })}>
                                        <option value="abierto">abierto</option>
                                        <option value="completo">completo</option>
                                        <option value="cancelado">cancelado</option>
                                    </select>
                                </label>

                                <label>
                                    Plazas totales
                                    <input
                                        type="number"
                                        value={datosAdmin.plazas_totales}
                                        onChange={(e) => setDatosAdmin({ ...datosAdmin, plazas_totales: e.target.value })}
                                    />
                                </label>

                                <div className="acciones-admin">
                                    <button type="submit">Guardar cambios</button>
                                </div>
                            </form>
                        </section>
                    )}

                    <section className="panel-posicion">
                        <div className="cabecera-sala">
                            <h2>Tu puesto</h2>
                            <button type="button" onClick={salirDeLaSala}>
                                Salir de la sala
                            </button>
                        </div>

                        <p>
                            Ahora juegas de <strong>{miJugador?.pivot?.posicion_asignada || "sin posición"}</strong>.
                            {miJugador?.pivot?.es_capitan
                                ? " Eres capitán: puedes cambiar la formación de tu equipo y moverte a cualquier puesto."
                                : " Puedes cambiarte de puesto o de equipo pulsando en el campo."}
                        </p>
                    </section>

                    {!esPartidoCompetitivo && miJugador && (
                        <section className="panel-invitaciones-posicion">
                            <div className="cabecera-bloque-partidos">
                                <div>
                                    <h2 className="titulo-invitaciones-posicion">Invitar por posición</h2>
                                    <p>Encuentra jugadores según su posición favorita y envíales una invitación a esta sala.</p>
                                </div>
                            </div>

                            <div className="selector-invitacion-posicion">
                                <label>
                                    Posición que necesitas
                                    <select
                                        value={posicionInvitacion}
                                        onChange={(e) => setPosicionInvitacion(e.target.value)}
                                    >
                                        {posicionesInvitacion.map((posicion) => (
                                            <option key={posicion} value={posicion}>{posicion}</option>
                                        ))}
                                    </select>
                                </label>
                                <button type="button" onClick={() => cargarCandidatosPorPosicion(posicionInvitacion)} disabled={cargandoCandidatos}>
                                    {cargandoCandidatos ? "Buscando..." : "Buscar jugadores"}
                                </button>
                            </div>

                            {cargandoCandidatos && <p className="estado">Buscando jugadores...</p>}

                            {!cargandoCandidatos && candidatosInvitacion.length === 0 && (
                                <p className="estado">No hay jugadores disponibles con esa posición favorita.</p>
                            )}

                            {!cargandoCandidatos && candidatosInvitacion.length > 0 && (
                                <div className="lista-candidatos-posicion">
                                    {candidatosInvitacion.map((jugador) => (
                                        <article className="candidato-posicion-card" key={jugador.id_usuario}>
                                            <div className="avatar-amigo avatar-amigo-lista">
                                                {jugador.foto_perfil ? (
                                                    <img src={jugador.foto_perfil} alt={jugador.nombre_usuario || jugador.nombre} />
                                                ) : (
                                                    <span>{(jugador.nombre_usuario || jugador.nombre || "D").slice(0, 1).toUpperCase()}</span>
                                                )}
                                            </div>
                                            <div className="datos-candidato-posicion">
                                                <strong>{jugador.nombre_usuario || jugador.nombre}</strong>
                                                <span>{jugador.ciudad || "Sin ciudad"}</span>
                                                <small>{jugador.posiciones_favoritas || "Sin posición favorita"}</small>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={() => invitarPorPosicion(jugador)}
                                                disabled={invitandoId === jugador.id_usuario}
                                            >
                                                {invitandoId === jugador.id_usuario ? "Enviando..." : "Invitar"}
                                            </button>
                                        </article>
                                    ))}
                                </div>
                            )}
                        </section>
                    )}

                    <section className="chat-partido">
                        <h2>Chat del partido</h2>
                        <div className="mensajes-chat">
                            {mensajes.length === 0 && <p className="estado">Todavía no hay mensajes.</p>}
                            {mensajes.map((item) => (
                                <div className="mensaje-chat" key={item.id_mensaje}>
                                    <strong>{item.usuario?.nombre_usuario || "Usuario"}</strong>
                                    <p>{item.mensaje}</p>
                                </div>
                            ))}
                        </div>
                        <form className="formulario-chat" onSubmit={enviarMensaje}>
                            <input
                                value={nuevoMensaje}
                                onChange={(e) => setNuevoMensaje(e.target.value)}
                                placeholder="Escribe un mensaje..."
                            />
                            <button type="submit">Enviar</button>
                        </form>
                    </section>

                    <section className="alineaciones">
                        {pintarCampo("Equipo A", "Equipo A")}
                        {pintarCampo("Equipo B", "Equipo B")}
                    </section>
                </>
            )}
        </main>
    );
};

export default SalaPartido;
