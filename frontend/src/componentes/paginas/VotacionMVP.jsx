import { useEffect, useState } from "react";
import api from "../../api/api.js";

// Archivo propio del frontend de Drafty.
const nombreJugador = (jugador) => jugador?.nombre_usuario || jugador?.nombre || "Jugador";

// Funcion auxiliar usada por este componente.
const VotacionMVP = ({ partido, participantes, usuario }) => {
    // Estado que guarda informacion de la pantalla.
    const [votos, setVotos] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [seleccionado, setSeleccionado] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [mensaje, setMensaje] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [guardando, setGuardando] = useState(false);
    // Dato usado para pintar esta pantalla.
    const soyParticipante = participantes.some((jugador) => Number(jugador.id_usuario) === Number(usuario?.id_usuario));
    // Dato usado para pintar esta pantalla.
    const puedeVotar = soyParticipante && partido?.estado !== "cancelado" && Boolean(partido?.ventana_resultado_abierta);

    // Funcion que llama al servidor y actualiza la pantalla.
    const cargarVotos = async () => {
        try {
            // Dato usado para pintar esta pantalla.
            const respuesta = await api.get(`/partidos/${partido.id_partido}/mvp`);
            setVotos(respuesta.data?.ranking || []);
        } catch {
            setVotos([]);
        }
    };

    // Efecto que se ejecuta cuando cambian los datos indicados.
    useEffect(() => {
        if (partido?.id_partido) {
            cargarVotos();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [partido?.id_partido]);

    // Funcion que llama al servidor y actualiza la pantalla.
    const votar = async (e) => {
        e.preventDefault();
        setMensaje("");

        if (!seleccionado) {
            setMensaje("Selecciona un jugador para votar al MVP.");
            return;
        }

        try {
            setGuardando(true);
            await api.post(`/partidos/${partido.id_partido}/mvp/votar`, {
                id_usuario_votado: seleccionado
            });
            setMensaje("Voto guardado correctamente.");
            setSeleccionado("");
            await cargarVotos();
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido guardar el voto.");
        } finally {
            setGuardando(false);
        }
    };

    // Vista que se muestra al usuario.
    return (
        <article className="post-card">
            <div className="post-card-cabecera">
                <div>
                    <span className="post-etiqueta">Votacion</span>
                    <h2>MVP</h2>
                </div>
            </div>

            {mensaje && <p className="post-mensaje">{mensaje}</p>}

            <div className="post-ranking">
                {votos.length === 0 ? (
                    <p className="estado">Todavía no hay votos para MVP.</p>
                ) : (
                    votos.map((item, index) => (
                        <div className="post-ranking-fila" key={item.id_usuario}>
                            <span>{index + 1}</span>
                            <strong>{item.usuario ? nombreJugador(item.usuario) : item.nombre_usuario || "Jugador"}</strong>
                            <small>{item.puntos ?? item.total} puntos</small>
                        </div>
                    ))
                )}
            </div>

            {puedeVotar ? (
                <form className="post-form post-form-simple" onSubmit={votar}>
                    <label>
                        Tu MVP
                        <select value={seleccionado} onChange={(e) => setSeleccionado(e.target.value)}>
                            <option value="">Selecciona jugador</option>
                            {participantes
                                .filter((jugador) => Number(jugador.id_usuario) !== Number(usuario?.id_usuario))
                                .map((jugador) => (
                                    <option value={jugador.id_usuario} key={jugador.id_usuario}>
                                        {nombreJugador(jugador)}
                                    </option>
                                ))}
                        </select>
                    </label>
                    <button type="submit" disabled={guardando}>Votar MVP</button>
                </form>
            ) : (
                <p className="post-ayuda">La votacion MVP solo esta disponible para participantes durante la ventana de 24 horas.</p>
            )}
        </article>
    );
};

export default VotacionMVP;
