import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import "./Inicio.css";

const CrearEquipo = () => {
    const [nombreEquipo, setNombreEquipo] = useState("");
    const [descripcion, setDescripcion] = useState("");
    const [privacidad, setPrivacidad] = useState("publico");
    const [mensaje, setMensaje] = useState("");
    const [guardando, setGuardando] = useState(false);
    const { isAuth } = useAuth();
    const navigate = useNavigate();

    const crearEquipo = async (e) => {
        e.preventDefault();

        if (!isAuth) {
            navigate("/login");
            return;
        }

        try {
            setGuardando(true);
            setMensaje("");
            const respuesta = await api.post("/equipos", {
                nombre_equipo: nombreEquipo,
                descripcion,
                privacidad
            });

            navigate(`/equipos/${respuesta.data.id_equipo}`);
        } catch (error) {
            const errores = error.response?.data?.errors;
            const primerError = errores ? Object.values(errores).flat()[0] : null;
            setMensaje(primerError || error.response?.data?.mensaje || "No se ha podido crear el equipo.");
        } finally {
            setGuardando(false);
        }
    };

    return (
        <main className="inicio equipos-page">
            <section className="equipos-hero equipos-hero-compacto">
                <div>
                    <span className="eyebrow-equipo">Nuevo equipo</span>
                    <h1>Crear equipo</h1>
                    <p>El creador se añade automáticamente como capitán principal.</p>
                </div>
                <Link to="/equipos" className="volver-equipos">Volver</Link>
            </section>

            {mensaje && <p className="mensaje mensaje-error">{mensaje}</p>}

            <section className="form-equipo-shell">
                <form className="form-equipo" onSubmit={crearEquipo}>
                    <label>
                        Nombre del equipo
                        <input
                            value={nombreEquipo}
                            onChange={(e) => setNombreEquipo(e.target.value)}
                            placeholder="Ej. Drafty United"
                            maxLength={255}
                            required
                        />
                    </label>
                    <label>
                        Descripción
                        <textarea
                            value={descripcion}
                            onChange={(e) => setDescripcion(e.target.value)}
                            placeholder="Estilo de juego, ciudad, horarios..."
                            rows="5"
                        />
                    </label>
                    <label>
                        Privacidad
                        <select value={privacidad} onChange={(e) => setPrivacidad(e.target.value)}>
                            <option value="publico">Público - cualquiera puede solicitar unirse</option>
                            <option value="privado">Privado - solo por invitación</option>
                        </select>
                    </label>
                    <button type="submit" disabled={guardando}>
                        {guardando ? "Creando..." : "Crear equipo"}
                    </button>
                </form>
            </section>
        </main>
    );
};

export default CrearEquipo;
