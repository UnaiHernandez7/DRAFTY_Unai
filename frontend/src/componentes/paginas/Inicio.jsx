import { useEffect, useState } from "react";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import { useNavigate } from "react-router-dom";
import PartidoLista from "./PartidoLista.jsx";
import logotipoDrafty from "../../img/logotipo_drafty.svg";
import "./Inicio.css";

// Archivo propio del frontend de Drafty.
const obtenerCapacidad = (partidoOTipo = "") => {
    if (typeof partidoOTipo === "object" && partidoOTipo !== null) {
        // Dato usado para pintar esta pantalla.
        const capacidadPorTipo = obtenerCapacidad(partidoOTipo.tipo_futbol);
        // Dato usado para pintar esta pantalla.
        const capacidadGuardada = partidoOTipo.plazas_totales_calculadas || partidoOTipo.plazas_totales || 0;

        return Math.max(capacidadGuardada, capacidadPorTipo);
    }

    // Dato usado para pintar esta pantalla.
    const tipo = String(partidoOTipo || "").toLowerCase();

    if (tipo.includes("5v5") || tipo.includes("sala")) {
        return 14;
    }

    if (tipo.includes("7")) {
        return 20;
    }

    return 26;
};

// Dato usado para pintar esta pantalla.
const formacionesPorTipo = {
    futbol11: {
        "4-3-3": ["POR", "LI", "DFC", "DFC", "LD", "MC", "MCD", "MC", "EI", "DC", "ED"],
        "4-3-1-2": ["POR", "LI", "DFC", "DFC", "LD", "MC", "MCD", "MC", "MCO", "DC", "DC"]
    },
    futbol7: {
        "3-3-1": ["POR", "DFC", "DFC", "DFC", "MC", "MC", "MC", "DC"],
        "2-3-2": ["POR", "DFC", "DFC", "MC", "MC", "MC", "DC", "DC"]
    },
    sala: {
        "1-2-1": ["POR", "DFC", "ALA", "ALA", "PIV"],
        "2-1-1": ["POR", "DFC", "DFC", "ALA", "PIV"]
    }
};

// Funcion auxiliar usada por este componente.
const obtenerTipoFormacion = (tipoFutbol = "") => {
    // Dato usado para pintar esta pantalla.
    const tipo = tipoFutbol.toLowerCase();

    if (tipo.includes("5v5") || tipo.includes("sala")) return "sala";
    if (tipo.includes("7")) return "futbol7";

    return "futbol11";
};

// Funcion auxiliar usada por este componente.
const convertirPosicion = (texto = "") => {
    // Dato usado para pintar esta pantalla.
    const posicion = texto.toLowerCase();

    if (posicion.includes("port")) return ["POR"];
    if (posicion.includes("central") || posicion.includes("def")) return ["DFC", "LI", "LD"];
    if (posicion.includes("medio") || posicion.includes("mc")) return ["MC", "MCD", "MCO", "ALA"];
    if (posicion.includes("del") || posicion.includes("dc")) return ["DC", "PIV", "EI", "ED"];
    if (posicion.includes("supl")) return ["SUP"];

    return [texto.toUpperCase()];
};

// Funcion auxiliar usada por este componente.
const normalizarTexto = (texto = "") => (
    texto
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .trim()
        .toLowerCase()
);

// Funcion auxiliar usada por este componente.
const contarPosiciones = (posiciones, buscadas) => (
    posiciones.filter((posicion) => buscadas.includes(posicion)).length
);

// Funcion auxiliar usada por este componente.
const hayHuecoParaPosicion = (partido, filtroPosicion) => {
    if (!filtroPosicion) {
        return true;
    }

    // Dato usado para pintar esta pantalla.
    const posicionesBuscadas = convertirPosicion(filtroPosicion);
    // Dato usado para pintar esta pantalla.
    const participantesConfirmados = (partido.usuarios || [])
        .filter((jugador) => jugador.pivot?.estado_participacion !== "pendiente");

    if (posicionesBuscadas.includes("SUP")) {
        return ["Equipo A", "Equipo B"].some((equipo) => (
            participantesConfirmados.filter((jugador) => (
                jugador.pivot?.equipo_asignado === equipo &&
                jugador.pivot?.posicion_asignada === "SUP"
            )).length < 2
        ));
    }

    // Dato usado para pintar esta pantalla.
    const tipoFormacion = obtenerTipoFormacion(partido.tipo_futbol);
    // Dato usado para pintar esta pantalla.
    const formaciones = formacionesPorTipo[tipoFormacion];
    // Dato usado para pintar esta pantalla.
    const formacionLocal = formaciones[partido.formacion_local] ? partido.formacion_local : Object.keys(formaciones)[0];
    // Dato usado para pintar esta pantalla.
    const formacionVisitante = formaciones[partido.formacion_visitante] ? partido.formacion_visitante : Object.keys(formaciones)[0];

    return [
        ["Equipo A", formaciones[formacionLocal]],
        ["Equipo B", formaciones[formacionVisitante]]
    ].some(([equipo, posicionesFormacion]) => {
        // Dato usado para pintar esta pantalla.
        const huecos = contarPosiciones(posicionesFormacion, posicionesBuscadas);
        // Dato usado para pintar esta pantalla.
        const ocupadas = participantesConfirmados.filter((jugador) => (
            jugador.pivot?.equipo_asignado === equipo &&
            posicionesBuscadas.includes(jugador.pivot?.posicion_asignada)
        )).length;

        return ocupadas < huecos;
    });
};

// Funcion auxiliar usada por este componente.
const Inicio = () => {
    // Estado que guarda informacion de la pantalla.
    const [partidos, setPartidos] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [mensaje, setMensaje] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [cargando, setCargando] = useState(true);
    // Estado que guarda informacion de la pantalla.
    const [error, setError] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [partidosUnidos, setPartidosUnidos] = useState([]);
    // Por defecto se muestran todos para no depender de permisos de ubicacion.
    const [filtros, setFiltros] = useState({ tipo: "", nivel: "", posicion: "", codigo: "", proximidad: "todos", radio: "25" });
    // Estado que guarda informacion de la pantalla.
    const [ubicacionUsuario, setUbicacionUsuario] = useState(null);
    // Estado que guarda informacion de la pantalla.
    const [estadoUbicacion, setEstadoUbicacion] = useState("pendiente");
    const { token, usuario } = useAuth();
    // Dato usado para pintar esta pantalla.
    const navigate = useNavigate();

    // Funcion que llama al servidor y actualiza la pantalla.
    const cargarPartidos = async () => {
        try {
            setCargando(true);
            setError("");

            // Dato usado para pintar esta pantalla.
            const respuesta = await api.get("/partidos/cercanos", {
                params: {
                    // El backend decide si ordena por cercania, ciudad o todos.
                    modo: filtros.proximidad,
                    radio: ["cerca", "desde-ciudad"].includes(filtros.proximidad) ? filtros.radio : undefined,
                    ...(usuario?.ciudad ? { ciudad: usuario.ciudad } : {}),
                    ...(filtros.proximidad === "cerca" && ubicacionUsuario ? { latitud: ubicacionUsuario.latitud, longitud: ubicacionUsuario.longitud } : {})
                }
            });
            // Dato usado para pintar esta pantalla.
            const listaPartidos = (Array.isArray(respuesta.data) ? respuesta.data : [])
                .filter((partido) => !partido.es_competitivo && normalizarTexto(partido.nivel) !== "competitivo");

            setPartidos(listaPartidos);

            if (token) {
                try {
                    // Dato usado para pintar esta pantalla.
                    const respuestaUnidos = await api.get("/mis-partidos");
                    // Dato usado para pintar esta pantalla.
                    const idsUnidos = Array.isArray(respuestaUnidos.data)
                        ? respuestaUnidos.data.map((item) => Number(item.id_partido ?? item)).filter(Boolean)
                        : [];

                    setPartidosUnidos(idsUnidos);
                } catch {
                    setPartidosUnidos([]);
                }
            }
        } catch {
            setError("No se han podido cargar los partidos.");
        } finally {
            setCargando(false);
        }
    };

    // Efecto que se ejecuta cuando cambian los datos indicados.
    useEffect(() => {
        cargarPartidos();
        // eslint-disable-next-line react-hooks/set-state-in-effect
    }, [token, usuario?.ciudad, ubicacionUsuario?.latitud, ubicacionUsuario?.longitud, filtros.proximidad, filtros.radio]);

    // Efecto que se ejecuta cuando cambian los datos indicados.
    useEffect(() => {
        if (!navigator.geolocation) {
            setEstadoUbicacion("no-disponible");
            return;
        }

        setEstadoUbicacion("solicitando");
        navigator.geolocation.getCurrentPosition(
            (posicion) => {
                setUbicacionUsuario({
                    latitud: Number(posicion.coords.latitude.toFixed(7)),
                    longitud: Number(posicion.coords.longitude.toFixed(7))
                });
                setEstadoUbicacion("activa");
            },
            () => setEstadoUbicacion("denegada")
        );
    }, []);

    // Funcion que llama al servidor y actualiza la pantalla.
    const unirseAPartido = async (idPartido) => {
        if (!token) {
            setMensaje("Debes iniciar sesión para unirte.");
            return;
        }

        // Dato usado para pintar esta pantalla.
        const idNumerico = Number(idPartido);

        if (!idNumerico) {
            setMensaje("No se ha encontrado la sala de este partido.");
            return;
        }

        if (partidosUnidos.includes(idNumerico)) {
            navigate(`/partidos/${idNumerico}/sala`);
            return;
        }

        try {
            // Dato usado para pintar esta pantalla.
            const respuesta = await api.post(`/partidos/${idNumerico}/unirse`);
            // Dato usado para pintar esta pantalla.
            const partidoActualizado = respuesta.data.partido;

            setPartidosUnidos((actuales) => (
                actuales.includes(idNumerico) ? actuales : [...actuales, idNumerico]
            ));

            if (partidoActualizado) {
                setPartidos((actuales) => actuales.map((partido) => (
                    partido.id_partido === idNumerico ? partidoActualizado : partido
                )));
            }

            // Dato usado para pintar esta pantalla.
            const idSala = respuesta.data.id_partido || partidoActualizado?.id_partido || idNumerico;
            navigate(`/partidos/${idSala}/sala`);
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido unir al partido.");
        }
    };

    // Funcion que llama al servidor y actualiza la pantalla.
    const unirseConCodigo = async (e) => {
        e.preventDefault();

        if (!token) {
            navigate("/login");
            return;
        }

        try {
            // Dato usado para pintar esta pantalla.
            const respuesta = await api.post(`/partidos/codigo/${filtros.codigo}/unirse`);
            // Dato usado para pintar esta pantalla.
            const idSala = respuesta.data.id_partido || respuesta.data.partido?.id_partido;

            if (!idSala) {
                setMensaje("No se ha encontrado la sala de este partido.");
                return;
            }

            navigate(`/partidos/${idSala}/sala`);
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "Código no válido.");
        }
    };

    // Dato usado para pintar esta pantalla.
    const partidosFiltrados = partidos.filter((partido) => {
        // Dato usado para pintar esta pantalla.
        const coincideTipo = !filtros.tipo || normalizarTexto(partido.tipo_futbol) === normalizarTexto(filtros.tipo);
        // Dato usado para pintar esta pantalla.
        const coincideNivel = !filtros.nivel || normalizarTexto(partido.nivel) === normalizarTexto(filtros.nivel);
        // Dato usado para pintar esta pantalla.
        const necesitaPosicion = hayHuecoParaPosicion(partido, filtros.posicion);

        return coincideTipo && coincideNivel && necesitaPosicion;
    });

    // Vista que se muestra al usuario.
    return (
        <main className="inicio">
            <section className="portada inicio-hero">
                <div className="inicio-hero-contenido">
                    <span className="inicio-hero-kicker">Fútbol organizado</span>
                    <div className="inicio-hero-logo" aria-label="DRAFTY">
                        <img src={logotipoDrafty} alt="DRAFTY" />
                    </div>
                    <p className="inicio-hero-subtitulo">
                        La forma más fácil de encontrar, organizar y competir en partidos de fútbol.
                    </p>
                    <p className="inicio-hero-descripcion">
                        Encuentra partidos cerca de ti, crea tus propios partidos, compite en torneos, forma equipos y mejora tu ranking dentro de la comunidad DRAFTY.
                    </p>

                    <div className="inicio-hero-indicadores" aria-label="Modos principales de DRAFTY">
                        <span>Partidos</span>
                        <span>Torneos</span>
                        <span>Competitivo</span>
                        <span>Equipos</span>
                        <span>Cercanía</span>
                    </div>
                </div>

                <div className="inicio-hero-visual" aria-hidden="true">
                    <div className="inicio-cancha">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <div className="inicio-hero-stat">
                        <span>Disponibles</span>
                        <strong>{partidosFiltrados.length}</strong>
                    </div>
                </div>
            </section>

            {mensaje && <p className="mensaje">{mensaje}</p>}
            {error && <p className="mensaje mensaje-error">{error}</p>}
            {filtros.proximidad === "cerca" && estadoUbicacion !== "activa" && (
                <p className="mensaje mensaje-error">
                    Para buscar cerca de ti con precisión tienes que permitir la ubicación del navegador.
                </p>
            )}
            {cargando && <p className="estado">Cargando partidos...</p>}

            <section className="panel-admin">
                <h2>Buscar partido</h2>
                <form className="formulario-admin" onSubmit={unirseConCodigo}>
                    <label>
                        Proximidad
                        <select value={filtros.proximidad} onChange={(e) => setFiltros({ ...filtros, proximidad: e.target.value })}>
                            <option value="cerca">Cerca de mí</option>
                            <option value="desde-ciudad">Desde mi ciudad</option>
                            <option value="todos">Todos</option>
                        </select>
                    </label>
                    <label>
                        Radio
                        <select value={filtros.radio} disabled={!["cerca", "desde-ciudad"].includes(filtros.proximidad)} onChange={(e) => setFiltros({ ...filtros, radio: e.target.value })}>
                            <option value="5">5 km</option>
                            <option value="10">10 km</option>
                            <option value="25">25 km</option>
                            <option value="50">50 km</option>
                        </select>
                    </label>
                    <label>
                        Tipo
                        <select value={filtros.tipo} onChange={(e) => setFiltros({ ...filtros, tipo: e.target.value })}>
                            <option value="">Todos</option>
                            <option>5v5</option>
                            <option>7v7</option>
                            <option>11v11</option>
                        </select>
                    </label>
                    <label>
                        Nivel
                        <select value={filtros.nivel} onChange={(e) => setFiltros({ ...filtros, nivel: e.target.value })}>
                            <option value="">Todos</option>
                            <option>Casual</option>
                            <option>Intermedio</option>
                            <option>Alto</option>
                        </select>
                    </label>
                    <label>
                        Posición buscada
                        <select value={filtros.posicion} onChange={(e) => setFiltros({ ...filtros, posicion: e.target.value })}>
                            <option value="">Todas</option>
                            <option value="Portero">Portero</option>
                            <option value="Central">Defensa / Central</option>
                            <option value="Mediocentro">Mediocentro</option>
                            <option value="Delantero">Delantero</option>
                            <option value="Suplente">Suplente</option>
                        </select>
                    </label>
                    <label className="campo-codigo-privado">
                        <span>Código privado</span>
                        <div className="codigo-privado-control">
                            <input
                                value={filtros.codigo}
                                onChange={(e) => setFiltros({ ...filtros, codigo: e.target.value })}
                                placeholder="ABC123"
                                aria-label="Código privado"
                            />
                            <button type="submit">Entrar con código</button>
                        </div>
                    </label>
                </form>
            </section>

            {!cargando && !error && partidosFiltrados.length === 0 && (
                <p className="estado">No hay partidos disponibles ahora mismo.</p>
            )}

            {!cargando && !error && partidosFiltrados.length > 0 && (
                <section className="lista-partidos lista-partidos-inicio">
                    {partidosFiltrados.map((partido) => {
                        // Dato usado para pintar esta pantalla.
                        const estaUnido = partidosUnidos.includes(Number(partido.id_partido));
                        // Dato usado para pintar esta pantalla.
                        const estaCancelado = partido.estado === "cancelado";
                        // Dato usado para pintar esta pantalla.
                        const capacidad = obtenerCapacidad(partido);
                        // Dato usado para pintar esta pantalla.
                        const ocupadas = partido.usuarios_count || 0;
                        // Dato usado para pintar esta pantalla.
                        const plazasLibres = typeof partido.plazas_disponibles === "number"
                            ? partido.plazas_disponibles
                            : Math.max(0, capacidad - ocupadas);
                        // Dato usado para pintar esta pantalla.
                        const estaCompleto = capacidad > 0 && plazasLibres <= 0;

                        // Vista que se muestra al usuario.
                        return (
                            <PartidoLista
                                key={partido.id_partido}
                                partido={partido}
                                estaUnido={estaUnido}
                                estaCompleto={estaCompleto || estaCancelado}
                                onAccion={unirseAPartido}
                            />
                        );
                    })}
                </section>
            )}
        </main>
    );
};

export default Inicio;
