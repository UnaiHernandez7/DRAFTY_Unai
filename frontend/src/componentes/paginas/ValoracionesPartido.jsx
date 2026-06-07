import { useState } from "react";
import api from "../../api/api.js";

// Archivo propio del frontend de Drafty.
const nombreJugador = (jugador) => jugador?.nombre_usuario || jugador?.nombre || "Jugador";

// Funcion auxiliar usada por este componente.
const balones = (cantidad) => "⚽".repeat(Number(cantidad || 0));

// Funcion auxiliar usada por este componente.
const ValoracionesPartido = ({ partido, participantes, miJugador }) => {
    // Estado que guarda informacion de la pantalla.
    const [formulario, setFormulario] = useState({
        id_usuario_valorado: "",
        puntuacion: 5,
        comentario: ""
    });
    // Estado que guarda informacion de la pantalla.
    const [mensaje, setMensaje] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [guardando, setGuardando] = useState(false);
    // Dato usado para pintar esta pantalla.
    const soyCapitan = Boolean(miJugador?.pivot?.es_capitan);
    // Dato usado para pintar esta pantalla.
    const puedeValorar = soyCapitan && partido?.estado !== "cancelado" && Boolean(partido?.ventana_resultado_abierta);

    // Funcion que llama al servidor y actualiza la pantalla.
    const enviarValoracion = async (e) => {
        e.preventDefault();
        setMensaje("");

        if (!formulario.id_usuario_valorado) {
            setMensaje("Selecciona el jugador que quieres valorar.");
            return;
        }

        try {
            setGuardando(true);
            await api.post(`/partidos/${partido.id_partido}/valoraciones`, formulario);
            setMensaje("Valoración guardada correctamente.");
            setFormulario({ id_usuario_valorado: "", puntuacion: 5, comentario: "" });
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido guardar la valoración.");
        } finally {
            setGuardando(false);
        }
    };

    // Vista que se muestra al usuario.
    return (
        <article className="post-card">
            <div className="post-card-cabecera">
                <div>
                    <span className="post-etiqueta">Capitanes</span>
                    <h2>Valoraciones</h2>
                </div>
            </div>

            {mensaje && <p className="post-mensaje">{mensaje}</p>}

            {puedeValorar ? (
                <form className="post-form post-form-simple" onSubmit={enviarValoracion}>
                    <label>
                        Jugador
                        <select
                            value={formulario.id_usuario_valorado}
                            onChange={(e) => setFormulario({ ...formulario, id_usuario_valorado: e.target.value })}
                        >
                            <option value="">Selecciona jugador</option>
                            {participantes.map((jugador) => (
                                <option value={jugador.id_usuario} key={jugador.id_usuario}>
                                    {nombreJugador(jugador)} - {jugador.pivot?.equipo_asignado || "Sin equipo"}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label>
                        Puntuacion
                        <select
                            value={formulario.puntuacion}
                            onChange={(e) => setFormulario({ ...formulario, puntuacion: Number(e.target.value) })}
                        >
                            {[1, 2, 3, 4, 5].map((numero) => (
                                <option value={numero} key={numero}>
                                    {numero} {balones(numero)}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label>
                        Comentario
                        <textarea
                            value={formulario.comentario}
                            onChange={(e) => setFormulario({ ...formulario, comentario: e.target.value })}
                            placeholder="Opcional"
                            rows="3"
                        />
                    </label>
                    <button type="submit" disabled={guardando}>Guardar valoración</button>
                </form>
            ) : (
                <p className="post-ayuda">Solo los capitanes pueden valorar durante la ventana de 24 horas.</p>
            )}
        </article>
    );
};

export default ValoracionesPartido;
