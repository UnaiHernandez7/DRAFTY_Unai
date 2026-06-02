import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import EncabezadoSeccion from "../comunes/EncabezadoSeccion.jsx";
import PartidoLista from "./PartidoLista.jsx";
import "./Inicio.css";
import "./Competitivo.css";

const hoy = new Date().toISOString().slice(0, 10);

const gruposRangos = [
    { nombre: "Bronce", minimo: 0, maximo: 499, divisiones: 5 },
    { nombre: "Plata", minimo: 500, maximo: 999, divisiones: 5 },
    { nombre: "Oro", minimo: 1000, maximo: 1799, divisiones: 5 },
    { nombre: "Platino", minimo: 1800, maximo: 2799, divisiones: 5 },
    { nombre: "Diamante", minimo: 2800, maximo: 3999, divisiones: 3 },
    { nombre: "Elite", minimo: 4000, maximo: null, divisiones: 1 }
];

const rangosCompetitivos = gruposRangos.flatMap((grupo) => {
    if (grupo.maximo === null) {
        return [{ nombre: grupo.nombre, minimo: grupo.minimo, maximo: null, grupo: grupo.nombre }];
    }

    const puntosPorDivision = Math.ceil((grupo.maximo - grupo.minimo + 1) / grupo.divisiones);

    return Array.from({ length: grupo.divisiones }, (_, index) => {
        const minimo = grupo.minimo + index * puntosPorDivision;
        const maximo = Math.min(minimo + puntosPorDivision - 1, grupo.maximo);

        return {
            nombre: `${grupo.nombre} ${index + 1}`,
            minimo,
            maximo,
            grupo: grupo.nombre
        };
    });
});

const obtenerInfoRango = (puntos = 0) => {
    const indiceActual = rangosCompetitivos.findIndex((rango) => (
        puntos >= rango.minimo && (rango.maximo === null || puntos <= rango.maximo)
    ));
    const indiceSeguro = indiceActual === -1 ? 0 : indiceActual;
    const rangoActual = rangosCompetitivos[indiceSeguro];
    const proximoRango = rangosCompetitivos[indiceSeguro + 1] || null;

    return {
        indiceActual: indiceSeguro,
        rangoActual,
        proximoRango,
        faltan: proximoRango ? Math.max(proximoRango.minimo - puntos, 0) : 0
    };
};

const estadoRango = (indice, infoRango) => {
    if (indice < infoRango.indiceActual) return "Superado";
    if (indice === infoRango.indiceActual) return "Actual";
    if (indice === infoRango.indiceActual + 1) return "Próximo";
    return "Bloqueado";
};

const textoPuntos = (rango) => (
    rango.maximo === null ? `${rango.minimo}+` : `${rango.minimo} - ${rango.maximo}`
);

const rankingsConfig = [
    { clave: "goles", titulo: "Goles", unidad: "goles" },
    { clave: "porterias_cero", titulo: "Porterías a 0", unidad: "porterías" }
];

const modosFecha = [
    { clave: "hoy", texto: "Hoy" },
    { clave: "manana", texto: "Mañana" },
    { clave: "finde", texto: "Este fin de semana" },
    { clave: "fecha", texto: "Elegir fecha" },
    { clave: "aleatorio", texto: "Aleatorio" }
];

const radiosBusqueda = [5, 10, 25, 50];

const tiposBusqueda = [
    { clave: "todos", texto: "Todos" },
    { clave: "5v5", texto: "5vs5" },
    { clave: "7v7", texto: "7vs7" },
    { clave: "11v11", texto: "11vs11" }
];

const Competitivo = () => {
    const [perfilCompetitivo, setPerfilCompetitivo] = useState(null);
    const [mensaje, setMensaje] = useState("");
    const [tipoMensaje, setTipoMensaje] = useState("error");
    const [buscando, setBuscando] = useState(false);
    const [activando, setActivando] = useState(false);
    const [rankings, setRankings] = useState({ generales: {}, amigos: {} });
    const [filtrosBusqueda, setFiltrosBusqueda] = useState({
        modo_fecha: "aleatorio",
        fecha: hoy,
        proximidad: false,
        radio: "25",
        tipo_futbol: "todos"
    });
    const [resultados, setResultados] = useState([]);
    const { isAuth } = useAuth();
    const navigate = useNavigate();

    useEffect(() => {
        const cargarDatos = async () => {
            if (!isAuth) {
                setPerfilCompetitivo(null);
                return;
            }

            try {
                const [respuestaCompetitivo, respuestaRankings, respuestaRankingsAmigos] = await Promise.all([
                    api.get("/mi-competitivo"),
                    api.get("/competitivo-rankings"),
                    api.get("/competitivo-rankings/amigos")
                ]);

                setPerfilCompetitivo(respuestaCompetitivo.data);
                setRankings({
                    generales: respuestaRankings.data || {},
                    amigos: respuestaRankingsAmigos.data || {}
                });
            } catch {
                setPerfilCompetitivo(null);
            }
        };

        cargarDatos();
    }, [isAuth]);

    const puntos = perfilCompetitivo?.puntos_competitivos || 0;
    const infoRango = obtenerInfoRango(puntos);

    const buscarPartida = async () => {
        if (!isAuth) {
            navigate("/login");
            return;
        }

        if (!perfilCompetitivo?.activo) {
            setMensaje("Activa el modo competitivo antes de buscar partida.");
            setTipoMensaje("error");
            return;
        }

        try {
            setBuscando(true);
            setMensaje("");
            const fechaCola = filtrosBusqueda.modo_fecha === "manana"
                ? new Date(Date.now() + 86400000).toISOString().slice(0, 10)
                : filtrosBusqueda.modo_fecha === "fecha"
                ? filtrosBusqueda.fecha
                : hoy;

            const respuesta = await api.post("/competitivo/buscar-partida", {
                modo: "solo",
                tipo_futbol: filtrosBusqueda.tipo_futbol === "todos" ? "7v7" : filtrosBusqueda.tipo_futbol,
                fecha: fechaCola,
                proximidad: filtrosBusqueda.proximidad ? 1 : 0,
                radio: filtrosBusqueda.proximidad ? filtrosBusqueda.radio : undefined,
                id_equipo: null
            });

            navigate(`/partidos/${respuesta.data.id_partido}/sala`);
        } catch (error) {
            if (error.response?.data?.id_partido) {
                setTipoMensaje("error");
                setMensaje(error.response.data.mensaje || "Ya estás en una partida competitiva.");
                navigate(`/partidos/${error.response.data.id_partido}/sala`);
                return;
            }

            setTipoMensaje("error");
            setMensaje(error.response?.data?.mensaje || "No se ha podido encontrar una partida competitiva.");
        } finally {
            setBuscando(false);
        }
    };

    const cambiarFiltro = (campo, valor) => {
        setFiltrosBusqueda((actual) => ({ ...actual, [campo]: valor }));
    };

    const buscarCompetitivo = async () => {
        if (!isAuth) {
            navigate("/login");
            return;
        }

        if (!perfilCompetitivo?.activo) {
            setTipoMensaje("error");
            setMensaje("Activa el modo competitivo antes de buscar partidos.");
            return;
        }

        try {
            setBuscando(true);
            setMensaje("");
            const respuesta = await api.get("/competitivo/buscar", {
                params: {
                    ...filtrosBusqueda,
                    proximidad: filtrosBusqueda.proximidad ? 1 : 0,
                    fecha: filtrosBusqueda.modo_fecha === "fecha" ? filtrosBusqueda.fecha : undefined
                }
            });
            const lista = Array.isArray(respuesta.data) ? respuesta.data : [];
            setResultados(lista);
            if (lista.length === 0) {
                setTipoMensaje("error");
                setMensaje("No hay partidos competitivos con estos filtros.");
            }
        } catch (error) {
            setResultados([]);
            setTipoMensaje("error");
            setMensaje(error.response?.data?.mensaje || "No se han podido buscar partidos competitivos.");
        } finally {
            setBuscando(false);
        }
    };

    const unirsePartido = async (idPartido) => {
        if (!isAuth) {
            navigate("/login");
            return;
        }

        try {
            const respuesta = await api.post(`/partidos/${idPartido}/unirse`);
            setTipoMensaje("exito");
            setMensaje(respuesta.data?.mensaje || "Te has unido al partido correctamente.");
            await buscarCompetitivo();
        } catch (error) {
            if (error.response?.data?.id_partido) {
                navigate(`/partidos/${error.response.data.id_partido}/sala`);
                return;
            }
            setTipoMensaje("error");
            setMensaje(error.response?.data?.mensaje || "No se ha podido unir al partido.");
        }
    };

    const activarCompetitivo = async () => {
        if (!isAuth) {
            navigate("/login");
            return;
        }

        try {
            setActivando(true);
            setMensaje("");
            const respuesta = await api.post("/competitivo/activar");
            setPerfilCompetitivo(respuesta.data.competitivo);
            setTipoMensaje("exito");
            setMensaje(respuesta.data.mensaje || "Pago competitivo creado correctamente.");
        } catch (error) {
            setTipoMensaje("error");
            setMensaje(error.response?.data?.mensaje || "No se ha podido activar competitivo.");
        } finally {
            setActivando(false);
        }
    };

    return (
        <main className="inicio">
            <EncabezadoSeccion
                titulo="Competitivo"
                descripcion="Busca partidas equilibradas, mejora tu rango y compite en rankings."
            />

            {mensaje && <p className={`mensaje ${tipoMensaje === "exito" ? "mensaje-exito" : "mensaje-error"}`}>{mensaje}</p>}

            {!perfilCompetitivo?.activo && (
                <section className="info-partido-sala">
                    <div className="info-principal">
                        <h2>Plan premium competitivo</h2>
                        <p>Crea el pago del plan mensual. El acceso se activa cuando el pago quede confirmado.</p>
                    </div>

                    <div className="datos-partido">
                        <div><span>Precio</span><strong>{perfilCompetitivo?.precio_mensual || "3.99"} EUR / mes</strong></div>
                        <div><span>Estado</span><strong>Inactivo</strong></div>
                        <div><span>Rango inicial</span><strong>Bronce</strong></div>
                        <div><span>Pago</span><strong>{perfilCompetitivo?.estado_pago || "pendiente"}</strong></div>
                    </div>

                    <div className="acciones-admin acciones-plan-competitivo">
                        <button type="button" onClick={activarCompetitivo} disabled={activando}>
                            {activando ? "Creando pago..." : "Solicitar activacion"}
                        </button>
                    </div>
                </section>
            )}

            <section className="panel-admin">
                <h2>Buscar partida competitiva</h2>
                <div className="buscador-competitivo">
                    <div className="filtro-competitivo-card">
                        <span>Dia</span>
                        <div className="pills-competitivo">
                            {modosFecha.map((item) => (
                                <button
                                    key={item.clave}
                                    type="button"
                                    className={filtrosBusqueda.modo_fecha === item.clave ? "activo" : ""}
                                    onClick={() => cambiarFiltro("modo_fecha", item.clave)}
                                >
                                    {item.texto}
                                </button>
                            ))}
                        </div>
                        {filtrosBusqueda.modo_fecha === "fecha" && (
                            <input type="date" min={hoy} value={filtrosBusqueda.fecha} onChange={(e) => cambiarFiltro("fecha", e.target.value)} />
                        )}
                    </div>

                    <div className="filtro-competitivo-card">
                        <span>Proximidad</span>
                        <div className="pills-competitivo">
                            <button type="button" className={filtrosBusqueda.proximidad ? "activo" : ""} onClick={() => cambiarFiltro("proximidad", true)}>
                                Cerca de mi
                            </button>
                            <button type="button" className={!filtrosBusqueda.proximidad ? "activo" : ""} onClick={() => cambiarFiltro("proximidad", false)}>
                                Sin limite
                            </button>
                        </div>
                        {filtrosBusqueda.proximidad && (
                            <div className="pills-competitivo radio">
                                {radiosBusqueda.map((radio) => (
                                    <button
                                        key={radio}
                                        type="button"
                                        className={Number(filtrosBusqueda.radio) === radio ? "activo" : ""}
                                        onClick={() => cambiarFiltro("radio", String(radio))}
                                    >
                                        {radio} km
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="filtro-competitivo-card">
                        <span>Tipo de partido</span>
                        <div className="pills-competitivo">
                            {tiposBusqueda.map((item) => (
                                <button
                                    key={item.clave}
                                    type="button"
                                    className={filtrosBusqueda.tipo_futbol === item.clave ? "activo" : ""}
                                    onClick={() => cambiarFiltro("tipo_futbol", item.clave)}
                                >
                                    {item.texto}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="acciones-admin acciones-busqueda-competitiva">
                        <button type="button" disabled={buscando || !perfilCompetitivo?.activo} onClick={buscarCompetitivo}>
                            {!perfilCompetitivo?.activo ? "Activa competitivo" : buscando ? "Buscando..." : "Buscar partidos"}
                        </button>
                        <button type="button" className="boton-cola-rapida" disabled={buscando || !perfilCompetitivo?.activo} onClick={buscarPartida}>
                            Cola rápida
                        </button>
                    </div>

                    <div className="resultados-competitivo">
                        {resultados.map((partido) => (
                            <PartidoLista
                                key={partido.id_partido}
                                partido={partido}
                                mostrarPlazasOcupadas
                                textoAccion="Unirse"
                                onAccion={unirsePartido}
                                estaCompleto={(partido.plazas_disponibles ?? 0) <= 0}
                                accionesExtra={(
                                    <button type="button" className="boton-ver-sala-competitivo" onClick={() => navigate(`/partidos/${partido.id_partido}/sala`)}>
                                        Ver sala
                                    </button>
                                )}
                            />
                        ))}
                    </div>
                </div>
            </section>

            <section className="estado-competitivo-card">
                <div className="estado-competitivo-principal">
                    <span className={perfilCompetitivo?.activo ? "estado-activo" : "estado-inactivo"}>
                        {perfilCompetitivo?.activo ? "Competitivo activo" : "Competitivo no activo"}
                    </span>
                    <h2>{isAuth ? infoRango.rangoActual.nombre : "Sin sesión"}</h2>
                    <p>
                        {isAuth
                            ? infoRango.proximoRango
                                ? `${infoRango.faltan} puntos para llegar a ${infoRango.proximoRango.nombre}.`
                                : "Has alcanzado el rango maximo."
                            : "Inicia sesión para ver tu progreso competitivo."}
                    </p>
                </div>

                <div className="estado-competitivo-metricas">
                    <div><span>Puntos actuales</span><strong>{isAuth ? puntos : "-"}</strong></div>
                    <div><span>Próximo rango</span><strong>{isAuth ? infoRango.proximoRango?.nombre || "Máximo" : "-"}</strong></div>
                    <div><span>Faltan</span><strong>{isAuth ? infoRango.faltan : "-"}</strong></div>
                    <div><span>Partidos</span><strong>{perfilCompetitivo?.partidos_competitivos_jugados ?? 0}</strong></div>
                    <div><span>Ganados</span><strong>{perfilCompetitivo?.partidos_competitivos_ganados ?? 0}</strong></div>
                    <div><span>Perdidos</span><strong>{perfilCompetitivo?.partidos_competitivos_perdidos ?? 0}</strong></div>
                    <div><span>MVP</span><strong>{perfilCompetitivo?.mvps_competitivo ?? 0}</strong></div>
                    <div><span>Goles</span><strong>{perfilCompetitivo?.goles_competitivo ?? 0}</strong></div>
                    <div><span>Porterías a cero</span><strong>{perfilCompetitivo?.porterias_cero_competitivo ?? 0}</strong></div>
                </div>
            </section>

            <section className="rankings-competitivos-card">
                <div className="cabecera-tabla-rangos">
                    <div>
                        <h2>Rankings competitivos</h2>
                        <p>Solo cuentan las estadisticas del modo competitivo.</p>
                    </div>
                </div>

                <div className="bloques-rankings-competitivos">
                    <div>
                        <h3>General</h3>
                        <div className="grid-rankings-competitivos">
                            {rankingsConfig.map((ranking) => (
                                <RankingCompetitivo
                                    key={`general-${ranking.clave}`}
                                    titulo={ranking.titulo}
                                    unidad={ranking.unidad}
                                    datos={rankings.generales?.[ranking.clave] || []}
                                />
                            ))}
                        </div>
                    </div>

                    <div>
                        <h3>Solo amigos</h3>
                        <div className="grid-rankings-competitivos">
                            {rankingsConfig.map((ranking) => (
                                <RankingCompetitivo
                                    key={`amigos-${ranking.clave}`}
                                    titulo={ranking.titulo}
                                    unidad={ranking.unidad}
                                    datos={rankings.amigos?.[ranking.clave] || []}
                                />
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            <section className="tabla-rangos-card">
                <div className="cabecera-tabla-rangos">
                    <div>
                        <h2>Rangos competitivos</h2>
                        <p>Consulta todos los tramos y tu siguiente objetivo.</p>
                    </div>
                </div>

                <div className="tabla-rangos-competitivos">
                    <div className="fila-rango fila-rango-cabecera">
                        <span>Rango</span>
                        <span>Puntos minimos</span>
                        <span>Rango de puntos</span>
                        <span>Estado</span>
                    </div>

                    {rangosCompetitivos.map((rango, indice) => {
                        const estado = estadoRango(indice, infoRango);
                        const esActual = estado === "Actual";
                        const esProximo = estado === "Próximo";

                        return (
                            <div
                                className={`fila-rango ${esActual ? "fila-rango-actual" : ""} ${esProximo ? "fila-rango-proximo" : ""}`}
                                key={rango.nombre}
                            >
                                <div className="rango-nombre" data-label="Rango">
                                    <span className="marcador-rango">{esActual ? "*" : indice + 1}</span>
                                    <strong>{rango.nombre}</strong>
                                </div>
                                <span data-label="Puntos minimos">{rango.minimo}</span>
                                <span data-label="Rango de puntos">{textoPuntos(rango)}</span>
                                <div className="estado-rango" data-label="Estado">
                                    <span className={`etiqueta-rango etiqueta-rango-${estado.toLowerCase()}`}>
                                        {esActual ? "Tu rango" : esProximo ? "Próximo objetivo" : estado}
                                    </span>
                                    {esProximo && <small>{infoRango.faltan} puntos restantes</small>}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </section>

        </main>
    );
};

const RankingCompetitivo = ({ titulo, unidad, datos }) => (
    <article className="ranking-competitivo-card">
        <h4>{titulo}</h4>
        {datos.length === 0 && <p className="estado">Sin datos todavía.</p>}
        {datos.map((item, index) => (
            <div className={`fila-ranking-competitivo ${index >= 5 ? "fila-ranking-propia" : ""}`} key={`${titulo}-${item.id_usuario}`}>
                <span>{item.posicion || index + 1}</span>
                <div>
                    <strong>@{item.nombre_usuario || "usuario"}</strong>
                    <small>{item.rango || "Bronce 1"} - {item.puntos_competitivos ?? 0} pts</small>
                </div>
                <em>{item.valor ?? 0} {unidad}</em>
            </div>
        ))}
    </article>
);

export default Competitivo;
