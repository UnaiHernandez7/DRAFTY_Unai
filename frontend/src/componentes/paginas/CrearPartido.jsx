import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import EncabezadoSeccion from "../comunes/EncabezadoSeccion.jsx";
import SelectorMapa from "./SelectorMapa.jsx";
import "./Inicio.css";
import "./CrearPartido.css";

const tiposPartido = {
    "5v5": {
        etiqueta: "Fútbol 5v5",
        jugadores: 10,
        plazas: 14,
        descripcion: "Partido rápido, perfecto para pista o campo pequeño."
    },
    "7v7": {
        etiqueta: "Fútbol 7v7",
        jugadores: 16,
        plazas: 20,
        descripcion: "Formato equilibrado para partidos competitivos."
    },
    "11v11": {
        etiqueta: "Fútbol 11v11",
        jugadores: 22,
        plazas: 26,
        descripcion: "Formato completo, once contra once."
    }
};

const estadoInicial = {
    titulo: "",
    descripcion: "",
    fecha: "",
    hora: "",
    tipo_futbol: "7v7",
    nivel: "Intermedio",
    es_publico: true,
    campo_nombre_campo: "",
    campo_direccion: "",
    campo_ciudad: "",
    campo_provincia: "",
    campo_codigo_postal: "",
    campo_latitud: "",
    campo_longitud: "",
    id_equipo_local: "",
    id_equipo_visitante: ""
};

const hoy = () => {
    const ahora = new Date();
    const year = ahora.getFullYear();
    const month = String(ahora.getMonth() + 1).padStart(2, "0");
    const day = String(ahora.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
};
const horaActual = () => {
    const ahora = new Date();

    return `${String(ahora.getHours()).padStart(2, "0")}:${String(ahora.getMinutes()).padStart(2, "0")}`;
};

const CrearPartido = () => {
    const navigate = useNavigate();
    const { token } = useAuth();
    const [formulario, setFormulario] = useState(estadoInicial);
    const [equipos, setEquipos] = useState([]);
    const [mensaje, setMensaje] = useState("");
    const [exito, setExito] = useState("");
    const [errores, setErrores] = useState({});
    const [cargando, setCargando] = useState(false);
    const [buscandoDireccion, setBuscandoDireccion] = useState(false);

    const tipoActual = tiposPartido[formulario.tipo_futbol];
    const coordenadas = formulario.campo_latitud && formulario.campo_longitud
        ? { latitud: Number(formulario.campo_latitud), longitud: Number(formulario.campo_longitud) }
        : null;

    useEffect(() => {
        if (!token) {
            navigate("/login");
            return;
        }

        const cargarEquipos = async () => {
            try {
                const respuesta = await api.get("/mis-equipos");
                setEquipos(Array.isArray(respuesta.data) ? respuesta.data : []);
            } catch {
                setEquipos([]);
            }
        };

        cargarEquipos();
    }, [token, navigate]);

    const cambiarCampo = (campo, valor) => {
        setErrores((actuales) => ({ ...actuales, [campo]: false }));
        setFormulario((actual) => {
            if (campo === "fecha" && valor === hoy() && actual.hora && actual.hora < horaActual()) {
                return { ...actual, fecha: valor, hora: "" };
            }

            if (campo === "hora" && actual.fecha === hoy() && valor < horaActual()) {
                setErrores((actuales) => ({ ...actuales, hora: true }));
                setMensaje("No puedes elegir una hora anterior a la actual.");
                return actual;
            }

            return { ...actual, [campo]: valor };
        });
    };

    const obtenerDireccionDesdeCoordenadas = async (latitud, longitud) => {
        try {
            setBuscandoDireccion(true);

            const respuesta = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latitud}&lon=${longitud}&addressdetails=1`
            );

            if (!respuesta.ok) {
                return null;
            }

            const datos = await respuesta.json();
            const direccion = datos.address || {};
            const via = [direccion.road, direccion.house_number].filter(Boolean).join(" ");

            return {
                direccion: via || datos.display_name || "",
                ciudad: direccion.city || direccion.town || direccion.village || direccion.municipality || "",
                provincia: direccion.province || direccion.county || direccion.state || "",
                codigoPostal: direccion.postcode || ""
            };
        } catch {
            return null;
        } finally {
            setBuscandoDireccion(false);
        }
    };

    const seleccionarUbicacion = async ({ latitud, longitud }) => {
        setErrores((actuales) => ({ ...actuales, campo_latitud: false, campo_longitud: false }));
        setFormulario((actual) => ({
            ...actual,
            campo_latitud: latitud,
            campo_longitud: longitud
        }));

        const datosUbicacion = await obtenerDireccionDesdeCoordenadas(latitud, longitud);

        if (!datosUbicacion) {
            return;
        }

        setFormulario((actual) => ({
            ...actual,
            campo_direccion: datosUbicacion?.direccion || actual.campo_direccion,
            campo_ciudad: datosUbicacion?.ciudad || actual.campo_ciudad,
            campo_provincia: datosUbicacion?.provincia || actual.campo_provincia,
            campo_codigo_postal: datosUbicacion?.codigoPostal || actual.campo_codigo_postal
        }));
    };

    const usarMiUbicacion = () => {
        if (!navigator.geolocation) {
            setMensaje("Tu navegador no permite obtener la ubicación.");
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (posicion) => seleccionarUbicacion({
                latitud: Number(posicion.coords.latitude.toFixed(7)),
                longitud: Number(posicion.coords.longitude.toFixed(7))
            }),
            () => setMensaje("No se ha podido obtener tu ubicación. Puedes clicar directamente en el mapa.")
        );
    };

    const validar = () => {
        const nuevosErrores = {};

        ["titulo", "fecha", "hora", "tipo_futbol", "nivel", "campo_nombre_campo", "campo_direccion", "campo_ciudad", "campo_provincia"].forEach((campo) => {
            if (!formulario[campo]) nuevosErrores[campo] = true;
        });

        if (formulario.fecha && formulario.fecha < hoy()) {
            nuevosErrores.fecha = true;
        }

        if (formulario.fecha === hoy() && formulario.hora && formulario.hora < horaActual()) {
            nuevosErrores.hora = true;
        }

        if (!formulario.campo_latitud || !formulario.campo_longitud) {
            nuevosErrores.campo_latitud = true;
            nuevosErrores.campo_longitud = true;
        }

        setErrores(nuevosErrores);
        return Object.keys(nuevosErrores).length === 0;
    };

    const crearPartido = async (e) => {
        e.preventDefault();

        if (!validar()) {
            setMensaje("Revisa los campos marcados. Si el partido es hoy, la hora no puede ser anterior a la actual.");
            return;
        }

        try {
            setCargando(true);
            setMensaje("");
            setExito("");

            const payload = {
                titulo: formulario.titulo,
                descripcion: formulario.descripcion,
                fecha: formulario.fecha,
                hora: formulario.hora,
                tipo_futbol: formulario.tipo_futbol,
                nivel: formulario.nivel,
                es_publico: formulario.es_publico,
                ubicacion_modo: "manual",
                campo_nombre_campo: formulario.campo_nombre_campo,
                campo_direccion: formulario.campo_direccion,
                campo_ciudad: formulario.campo_ciudad,
                campo_provincia: formulario.campo_provincia,
                campo_codigo_postal: formulario.campo_codigo_postal || null,
                campo_latitud: Number(formulario.campo_latitud),
                campo_longitud: Number(formulario.campo_longitud),
                id_equipo_local: formulario.id_equipo_local || null,
                id_equipo_visitante: formulario.id_equipo_visitante || null
            };

            const respuesta = await api.post("/partidos", payload);
            setExito("Partido creado correctamente. Entrando en la sala...");
            navigate(`/partidos/${respuesta.data.id_partido}/sala`);
        } catch (error) {
            const erroresApi = error.response?.data?.errors || {};
            const primerError = Object.values(erroresApi).flat()[0];

            setMensaje(primerError || error.response?.data?.mensaje || "No se ha podido crear el partido.");
        } finally {
            setCargando(false);
        }
    };

    return (
        <main className="inicio crear-partido-page">
            <EncabezadoSeccion
                titulo="Crear partido"
                descripcion="Organiza una sala, define el formato y marca la ubicación exacta del partido."
            />

            {mensaje && <p className="mensaje mensaje-error">{mensaje}</p>}
            {exito && <p className="mensaje">{exito}</p>}

            <form className="crear-partido-shell" onSubmit={crearPartido}>
                <section className="crear-partido-bloque">
                    <div className="crear-partido-bloque-header">
                        <span>01</span>
                        <div>
                            <h2>Datos básicos</h2>
                            <p>Nombre visible, descripción y privacidad.</p>
                        </div>
                    </div>

                    <div className="crear-partido-grid">
                        <label className="campo-doble">
                            Título del partido
                            <input className={errores.titulo ? "campo-error" : ""} value={formulario.titulo} onChange={(e) => cambiarCampo("titulo", e.target.value)} placeholder="Partido del sábado" />
                        </label>

                        <label>
                            Privacidad
                            <select value={formulario.es_publico ? "publico" : "privado"} onChange={(e) => cambiarCampo("es_publico", e.target.value === "publico")}>
                                <option value="publico">Público</option>
                                <option value="privado">Privado con código</option>
                            </select>
                        </label>

                        <label className="campo-completo">
                            Descripción opcional
                            <textarea value={formulario.descripcion} onChange={(e) => cambiarCampo("descripcion", e.target.value)} placeholder="Ritmo, normas, material necesario..." />
                        </label>
                    </div>
                </section>

                <section className="crear-partido-bloque">
                    <div className="crear-partido-bloque-header">
                        <span>02</span>
                        <div>
                            <h2>Fecha y formato</h2>
                            <p>Las plazas se calculan solas según el tipo de partido.</p>
                        </div>
                    </div>

                    <div className="crear-partido-grid">
                        <label>
                            Fecha
                            <input className={errores.fecha ? "campo-error" : ""} type="date" min={hoy()} value={formulario.fecha} onChange={(e) => cambiarCampo("fecha", e.target.value)} />
                        </label>

                        <label>
                            Hora
                            <input
                                className={errores.hora ? "campo-error" : ""}
                                type="time"
                                min={formulario.fecha === hoy() ? horaActual() : undefined}
                                value={formulario.hora}
                                onChange={(e) => cambiarCampo("hora", e.target.value)}
                            />
                        </label>

                        <label>
                            Tipo de partido
                            <select value={formulario.tipo_futbol} onChange={(e) => cambiarCampo("tipo_futbol", e.target.value)}>
                                {Object.entries(tiposPartido).map(([valor, tipo]) => (
                                    <option key={valor} value={valor}>{tipo.etiqueta}</option>
                                ))}
                            </select>
                        </label>

                        <label>
                            Nivel
                            <select value={formulario.nivel} onChange={(e) => cambiarCampo("nivel", e.target.value)}>
                                <option>Casual</option>
                                <option>Intermedio</option>
                                <option>Alto</option>
                            </select>
                        </label>
                    </div>

                    <div className="tipo-partido-preview">
                        <strong>{tipoActual.etiqueta}</strong>
                        <span>Este partido necesita {tipoActual.jugadores} titulares y tiene {tipoActual.plazas} plazas con suplentes.</span>
                        <small>{tipoActual.descripcion}</small>
                    </div>
                </section>

                <section className="crear-partido-bloque">
                    <div className="crear-partido-bloque-header">
                        <span>03</span>
                        <div>
                            <h2>Ubicación en mapa</h2>
                            <p>Clica directamente sobre el punto donde se jugará el partido.</p>
                        </div>
                    </div>

                    <div className={errores.campo_latitud || errores.campo_longitud ? "mapa-error" : ""}>
                        <SelectorMapa coordenadas={coordenadas} onSeleccionar={seleccionarUbicacion} />
                    </div>

                    <div className="ubicacion-mapa-info">
                        <button type="button" onClick={usarMiUbicacion}>Usar mi ubicación</button>
                        <span>
                            {buscandoDireccion
                                ? "Buscando dirección aproximada..."
                                : coordenadas
                                ? `Ubicación seleccionada: ${coordenadas.latitud}, ${coordenadas.longitud}`
                                : "Todavía no has seleccionado ningún punto."}
                        </span>
                    </div>

                    <div className="crear-partido-grid ubicacion-manual-grid">
                        <label>
                            Nombre del lugar o campo
                            <input className={errores.campo_nombre_campo ? "campo-error" : ""} value={formulario.campo_nombre_campo} onChange={(e) => cambiarCampo("campo_nombre_campo", e.target.value)} />
                        </label>
                        <label>
                            Dirección
                            <input className={errores.campo_direccion ? "campo-error" : ""} value={formulario.campo_direccion} onChange={(e) => cambiarCampo("campo_direccion", e.target.value)} />
                        </label>
                        <label>
                            Ciudad
                            <input className={errores.campo_ciudad ? "campo-error" : ""} value={formulario.campo_ciudad} onChange={(e) => cambiarCampo("campo_ciudad", e.target.value)} />
                        </label>
                        <label>
                            Provincia
                            <input className={errores.campo_provincia ? "campo-error" : ""} value={formulario.campo_provincia} onChange={(e) => cambiarCampo("campo_provincia", e.target.value)} />
                        </label>
                        <label>
                            Código postal
                            <input value={formulario.campo_codigo_postal} onChange={(e) => cambiarCampo("campo_codigo_postal", e.target.value)} />
                        </label>
                    </div>
                </section>

                <section className="crear-partido-bloque">
                    <div className="crear-partido-bloque-header">
                        <span>04</span>
                        <div>
                            <h2>Equipos opcionales</h2>
                            <p>Vincula el partido a tus equipos si quieres.</p>
                        </div>
                    </div>

                    <div className="crear-partido-grid">
                        <label>
                            Equipo local
                            <select value={formulario.id_equipo_local} onChange={(e) => cambiarCampo("id_equipo_local", e.target.value)}>
                                <option value="">Sin equipo local</option>
                                {equipos.map((equipo) => (
                                    <option key={equipo.id_equipo} value={equipo.id_equipo}>{equipo.nombre_equipo}</option>
                                ))}
                            </select>
                        </label>
                        <label>
                            Equipo visitante
                            <select value={formulario.id_equipo_visitante} onChange={(e) => cambiarCampo("id_equipo_visitante", e.target.value)}>
                                <option value="">Sin equipo visitante</option>
                                {equipos.map((equipo) => (
                                    <option key={equipo.id_equipo} value={equipo.id_equipo}>{equipo.nombre_equipo}</option>
                                ))}
                            </select>
                        </label>
                    </div>
                </section>

                <div className="crear-partido-acciones">
                    <button type="submit" disabled={cargando}>
                        {cargando ? "Creando..." : "Crear partido"}
                    </button>
                </div>
            </form>
        </main>
    );
};

export default CrearPartido;
