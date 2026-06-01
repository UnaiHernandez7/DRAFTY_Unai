import { useState } from "react";
import { useNavigate } from "react-router-dom";
import api from "../../api/api.js";
import SelectorMapa from "./SelectorMapa.jsx";
import "./Torneos.css";

const hoy = new Date().toISOString().slice(0, 10);

const CrearTorneo = () => {
    const navigate = useNavigate();
    const [mensaje, setMensaje] = useState("");
    const [formulario, setFormulario] = useState({
        nombre_torneo: "",
        descripcion: "",
        fecha_inicio: "",
        fecha_fin: "",
        tipo_futbol: "7v7",
        max_equipos: "8",
        privacidad: "publico",
        codigo_acceso: "",
        cuota_inscripcion: "",
        premio: "",
        nombre_lugar: "",
        direccion: "",
        ciudad: "",
        provincia: ""
    });
    const [coordenadas, setCoordenadas] = useState(null);

    const cambiar = (campo, valor) => {
        setFormulario((actual) => ({
            ...actual,
            [campo]: valor,
            codigo_acceso: campo === "privacidad" && valor === "publico" ? "" : campo === "codigo_acceso" ? valor : actual.codigo_acceso
        }));
    };

    const crear = async (e) => {
        e.preventDefault();

        try {
            const respuesta = await api.post("/torneos", {
                ...formulario,
                tipo_torneo: "eliminatoria",
                latitud: coordenadas?.latitud || null,
                longitud: coordenadas?.longitud || null,
                cuota_inscripcion: formulario.cuota_inscripcion || null,
                fecha_fin: formulario.fecha_fin || null
            });
            navigate(`/torneos/${respuesta.data.id_torneo}`);
        } catch (error) {
            const errores = error.response?.data?.errors;
            const primerError = errores ? Object.values(errores).flat()[0] : null;
            setMensaje(primerError || error.response?.data?.mensaje || "No se ha podido crear el torneo.");
        }
    };

    return (
        <main className="inicio torneos-page">
            <section className="torneos-hero">
                <div>
                    <span className="torneos-eyebrow">Nuevo torneo</span>
                    <h1>Crear torneo</h1>
                    <p>Define formato, plazas, privacidad y premio antes de abrir inscripciones.</p>
                </div>
                <button type="button" className="torneo-back" onClick={() => navigate("/torneos")}>Volver</button>
            </section>

            {mensaje && <p className="mensaje mensaje-error">{mensaje}</p>}

            <form className="torneo-form" onSubmit={crear}>
                <section className="torneo-form-panel">
                    <h2>Datos principales</h2>
                    <div className="torneo-form-grid">
                        <label>
                            Nombre del torneo
                            <input value={formulario.nombre_torneo} onChange={(e) => cambiar("nombre_torneo", e.target.value)} required />
                        </label>
                        <label>
                            Premio
                            <input value={formulario.premio} onChange={(e) => cambiar("premio", e.target.value)} placeholder="Opcional" />
                        </label>
                        <label className="campo-largo">
                            Descripción
                            <textarea value={formulario.descripcion} onChange={(e) => cambiar("descripcion", e.target.value)} rows="4" />
                        </label>
                    </div>
                </section>

                <section className="torneo-form-panel">
                    <h2>Formato</h2>
                    <div className="torneo-form-grid">
                        <div className="torneo-form-info">
                            <span>Tipo de torneo</span>
                            <strong>Eliminatoria</strong>
                            <small>Todos los torneos DRAFTY se juegan por cruces directos.</small>
                        </div>
                        <label>
                            Tipo de fútbol
                            <select value={formulario.tipo_futbol} onChange={(e) => cambiar("tipo_futbol", e.target.value)}>
                                <option value="5v5">5v5</option>
                                <option value="7v7">7v7</option>
                                <option value="11v11">11v11</option>
                            </select>
                        </label>
                        <label>
                            Máximo de equipos
                            <select value={formulario.max_equipos} onChange={(e) => cambiar("max_equipos", e.target.value)}>
                                <option value="4">4 equipos</option>
                                <option value="8">8 equipos</option>
                                <option value="16">16 equipos</option>
                            </select>
                        </label>
                    </div>
                </section>

                <section className="torneo-form-panel">
                    <h2>Fechas y acceso</h2>
                    <div className="torneo-form-grid">
                        <label>
                            Fecha de inicio
                            <input type="date" min={hoy} value={formulario.fecha_inicio} onChange={(e) => cambiar("fecha_inicio", e.target.value)} required />
                        </label>
                        <label>
                            Fecha de fin
                            <input type="date" min={formulario.fecha_inicio || hoy} value={formulario.fecha_fin} onChange={(e) => cambiar("fecha_fin", e.target.value)} />
                        </label>
                        <label>
                            Privacidad
                            <select value={formulario.privacidad} onChange={(e) => cambiar("privacidad", e.target.value)}>
                                <option value="publico">Público</option>
                                <option value="privado">Privado</option>
                            </select>
                        </label>
                        {formulario.privacidad === "privado" && (
                            <label>
                                Código de acceso
                                <input value={formulario.codigo_acceso} onChange={(e) => cambiar("codigo_acceso", e.target.value)} required />
                            </label>
                        )}
                        <label>
                            Cuota de inscripción
                            <input type="number" min="0" step="0.01" value={formulario.cuota_inscripcion} onChange={(e) => cambiar("cuota_inscripcion", e.target.value)} placeholder="0" />
                        </label>
                    </div>
                </section>

                <section className="torneo-form-panel">
                    <h2>Ubicación del torneo</h2>
                    <p className="torneo-form-help">Clica en el mapa para marcar donde se jugará el torneo.</p>
                    <SelectorMapa coordenadas={coordenadas} onSeleccionar={setCoordenadas} />
                    <div className="ubicacion-mapa-info torneo-map-info">
                        <span>
                            {coordenadas
                                ? `Ubicación seleccionada: ${coordenadas.latitud}, ${coordenadas.longitud}`
                                : "Todavía no has seleccionado ningún punto exacto."}
                        </span>
                    </div>
                    <div className="torneo-form-grid">
                        <label>
                            Nombre del lugar
                            <input value={formulario.nombre_lugar} onChange={(e) => cambiar("nombre_lugar", e.target.value)} placeholder="Campo municipal, polideportivo..." />
                        </label>
                        <label>
                            Dirección
                            <input value={formulario.direccion} onChange={(e) => cambiar("direccion", e.target.value)} />
                        </label>
                        <label>
                            Ciudad
                            <input value={formulario.ciudad} onChange={(e) => cambiar("ciudad", e.target.value)} />
                        </label>
                        <label>
                            Provincia
                            <input value={formulario.provincia} onChange={(e) => cambiar("provincia", e.target.value)} />
                        </label>
                    </div>
                </section>

                <div className="torneo-form-actions">
                    <button type="submit">Crear torneo</button>
                </div>
            </form>
        </main>
    );
};

export default CrearTorneo;
