import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import EncabezadoSeccion from "../comunes/EncabezadoSeccion.jsx";
import "./Inicio.css";

// Archivo propio del frontend de Drafty.
const CrearEquipo = () => {
    // Estado que guarda informacion de la pantalla.
    const [nombreEquipo, setNombreEquipo] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [descripcion, setDescripcion] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [privacidad, setPrivacidad] = useState("publico");
    // Estado que guarda informacion de la pantalla.
    const [mensaje, setMensaje] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [guardando, setGuardando] = useState(false);
    const { isAuth } = useAuth();
    // Dato usado para pintar esta pantalla.
    const navigate = useNavigate();

    // Funcion que llama al servidor y actualiza la pantalla.
    const crearEquipo = async (e) => {
        e.preventDefault();

        if (!isAuth) {
            navigate("/login");
            return;
        }

        try {
            setGuardando(true);
            setMensaje("");
            // Dato usado para pintar esta pantalla.
            const respuesta = await api.post("/equipos", {
                nombre_equipo: nombreEquipo,
                descripcion,
                privacidad
            });

            navigate(`/equipos/${respuesta.data.id_equipo}`);
        } catch (error) {
            // Dato usado para pintar esta pantalla.
            const errores = error.response?.data?.errors;
            // Dato usado para pintar esta pantalla.
            const primerError = errores ? Object.values(errores).flat()[0] : null;
            setMensaje(primerError || error.response?.data?.mensaje || "No se ha podido crear el equipo.");
        } finally {
            setGuardando(false);
        }
    };

    // Vista que se muestra al usuario.
    return (
        <main className="inicio equipos-page">
            <EncabezadoSeccion
                titulo="Crear equipo"
                descripcion="Define un equipo nuevo y empieza a invitar jugadores."
                accion={<Link to="/equipos">Volver</Link>}
            />

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
