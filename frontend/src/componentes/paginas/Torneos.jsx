import { useEffect, useMemo, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import EncabezadoSeccion from "../comunes/EncabezadoSeccion.jsx";
import "./Torneos.css";

const filtros = [
    { clave: "todos", texto: "Todos" },
    { clave: "publico", texto: "Públicos" },
    { clave: "privado", texto: "Privados" },
    { clave: "en_curso", texto: "En curso" },
    { clave: "finalizado", texto: "Finalizados" }
];

const estadoTexto = {
    inscripcion_abierta: "Inscripción abierta",
    en_curso: "En curso",
    finalizado: "Finalizado",
    cancelado: "Cancelado"
};

const Torneos = () => {
    const [torneos, setTorneos] = useState([]);
    const [equipos, setEquipos] = useState([]);
    const [filtro, setFiltro] = useState("todos");
    const [selecciones, setSelecciones] = useState({});
    const [codigos, setCodigos] = useState({});
    const [mensaje, setMensaje] = useState("");
    const [tipoMensaje, setTipoMensaje] = useState("error");
    const [cargando, setCargando] = useState(true);
    const { isAuth } = useAuth();
    const navigate = useNavigate();

    const cargarTorneos = async () => {
        try {
            const respuesta = await api.get("/torneos");
            setTorneos(Array.isArray(respuesta.data) ? respuesta.data : []);
        } catch {
            setTipoMensaje("error");
            setMensaje("No se han podido cargar los torneos.");
        } finally {
            setCargando(false);
        }
    };

    const cargarEquipos = async () => {
        if (!isAuth) return;

        try {
            const respuesta = await api.get("/mis-equipos");
            setEquipos(Array.isArray(respuesta.data) ? respuesta.data : []);
        } catch {
            setEquipos([]);
        }
    };

    useEffect(() => {
        cargarTorneos();
    }, []);

    useEffect(() => {
        cargarEquipos();
    }, [isAuth]);

    const torneosFiltrados = useMemo(() => {
        return torneos.filter((torneo) => {
            const estado = torneo.estado_torneo || torneo.estado;

            if (estado === "finalizado") {
                return filtro === "finalizado";
            }

            if (filtro === "todos") return true;
            if (filtro === "publico" || filtro === "privado") return torneo.privacidad === filtro;
            return estado === filtro;
        });
    }, [torneos, filtro]);

    const unirse = async (torneo) => {
        if (!isAuth) {
            setTipoMensaje("error");
            setMensaje("Debes iniciar sesion para inscribir un equipo.");
            navigate("/login");
            return;
        }

        const idEquipo = selecciones[torneo.id_torneo] || equipos[0]?.id_equipo;
        if (!idEquipo) {
            setTipoMensaje("error");
            setMensaje("Primero necesitas crear un equipo.");
            return;
        }

        try {
            const respuesta = await api.post(`/torneos/${torneo.id_torneo}/unirse`, {
                id_equipo: idEquipo,
                codigo_acceso: codigos[torneo.id_torneo] || ""
            });
            setTipoMensaje("exito");
            setMensaje(respuesta.data?.mensaje || "Equipo inscrito correctamente.");
            cargarTorneos();
        } catch (error) {
            setTipoMensaje("error");
            setMensaje(error.response?.data?.mensaje || "No se ha podido inscribir el equipo.");
        }
    };

    return (
        <main className="inicio torneos-page">
            <EncabezadoSeccion
                titulo="Torneos"
                descripcion="Explora, crea y participa en torneos organizados por la comunidad."
                accion={<Link to="/torneos/crear">Crear torneo</Link>}
            />

            {mensaje && <p className={`mensaje ${tipoMensaje === "exito" ? "mensaje-exito" : "mensaje-error"}`}>{mensaje}</p>}

            <section className="torneos-toolbar">
                <div className="torneos-filtros">
                    {filtros.map((item) => (
                        <button
                            key={item.clave}
                            type="button"
                            className={filtro === item.clave ? "activo" : ""}
                            onClick={() => setFiltro(item.clave)}
                        >
                            {item.texto}
                        </button>
                    ))}
                </div>
            </section>

            <section className="torneos-lista">
                {cargando && <p className="torneo-empty">Cargando torneos...</p>}

                {!cargando && torneosFiltrados.length === 0 && (
                    <div className="torneo-empty">
                        <h2>No hay torneos con este filtro</h2>
                        <p>Crea uno nuevo o prueba con otro estado.</p>
                    </div>
                )}

                {torneosFiltrados.map((torneo) => {
                    const estado = torneo.estado_torneo || torneo.estado || "inscripcion_abierta";
                    const inscritos = torneo.equipos_count ?? 0;
                    const lleno = inscritos >= Number(torneo.max_equipos || 0);
                    const puedeUnirse = estado === "inscripcion_abierta" && !lleno;

                    return (
                        <article className="torneo-card" key={torneo.id_torneo}>
                            <div className="torneo-card-main">
                                <div className="torneo-card-title">
                                    <span className={`torneo-pill ${torneo.privacidad === "privado" ? "privado" : ""}`}>
                                        {torneo.privacidad === "privado" ? "Privado" : "Público"}
                                    </span>
                                    <h2>{torneo.nombre_torneo}</h2>
                                </div>
                                <p>{torneo.descripcion || "Torneo sin descripción todavía."}</p>
                                <div className="torneo-meta-grid">
                                    <span>{torneo.tipo_torneo || "eliminatoria"}</span>
                                    <span>{torneo.tipo_futbol || "7v7"}</span>
                                    <span>{estadoTexto[estado] || estado}</span>
                                    <span>{torneo.fecha_inicio || "Sin fecha"}</span>
                                </div>
                            </div>

                            <div className="torneo-card-side">
                                <div className="torneo-cupo">
                                    <strong>{inscritos}/{torneo.max_equipos || 0}</strong>
                                    <span>equipos</span>
                                </div>

                                {puedeUnirse && isAuth && (
                                    <div className="torneo-unirse-box">
                                        <select
                                            value={selecciones[torneo.id_torneo] || equipos[0]?.id_equipo || ""}
                                            onChange={(e) => setSelecciones({ ...selecciones, [torneo.id_torneo]: e.target.value })}
                                        >
                                            {equipos.length === 0 && <option value="">Sin equipos</option>}
                                            {equipos.map((equipo) => (
                                                <option key={equipo.id_equipo} value={equipo.id_equipo}>
                                                    {equipo.nombre_equipo}
                                                </option>
                                            ))}
                                        </select>
                                        {torneo.privacidad === "privado" && (
                                            <input
                                                value={codigos[torneo.id_torneo] || ""}
                                                onChange={(e) => setCodigos({ ...codigos, [torneo.id_torneo]: e.target.value })}
                                                placeholder="Codigo"
                                            />
                                        )}
                                    </div>
                                )}

                                <div className="torneo-acciones">
                                    <button type="button" onClick={() => navigate(`/torneos/${torneo.id_torneo}`)}>
                                        Ver torneo
                                    </button>
                                    {puedeUnirse && (
                                        <button type="button" className="secundario" onClick={() => unirse(torneo)}>
                                            Unirse
                                        </button>
                                    )}
                                </div>
                            </div>
                        </article>
                    );
                })}
            </section>
        </main>
    );
};

export default Torneos;
