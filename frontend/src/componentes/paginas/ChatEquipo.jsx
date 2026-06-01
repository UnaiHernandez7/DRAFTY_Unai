import { useEffect, useState } from "react";
import api from "../../api/api.js";

const formatearFecha = (fecha) => {
    if (!fecha) return "";
    return new Date(fecha).toLocaleString("es-ES", {
        day: "2-digit",
        month: "2-digit",
        hour: "2-digit",
        minute: "2-digit"
    });
};

const ChatEquipo = ({ idEquipo }) => {
    const [mensajes, setMensajes] = useState([]);
    const [mensaje, setMensaje] = useState("");
    const [error, setError] = useState("");
    const [enviando, setEnviando] = useState(false);

    const cargarMensajes = async () => {
        try {
            const respuesta = await api.get(`/equipos/${idEquipo}/mensajes`);
            setMensajes(Array.isArray(respuesta.data) ? respuesta.data : []);
        } catch (err) {
            setError(err.response?.data?.mensaje || "No se ha podido cargar el chat.");
        }
    };

    useEffect(() => {
        cargarMensajes();
    }, [idEquipo]);

    const enviarMensaje = async (e) => {
        e.preventDefault();

        if (!mensaje.trim()) {
            return;
        }

        try {
            setEnviando(true);
            setError("");
            await api.post(`/equipos/${idEquipo}/mensajes`, { mensaje });
            setMensaje("");
            await cargarMensajes();
        } catch (err) {
            setError(err.response?.data?.mensaje || "No se ha podido enviar el mensaje.");
        } finally {
            setEnviando(false);
        }
    };

    return (
        <section className="bloque-detalle-equipo chat-equipo">
            <div className="cabecera-bloque-equipo">
                <h2>Chat interno</h2>
                <span>{mensajes.length} mensajes</span>
            </div>

            {error && <p className="mensaje mensaje-error">{error}</p>}

            <div className="mensajes-equipo">
                {mensajes.length === 0 && <p className="estado">Todavía no hay mensajes.</p>}
                {mensajes.map((item) => (
                    <div className="mensaje-equipo" key={item.id_mensaje}>
                        <div>
                            <strong>{item.usuario?.nombre_usuario || item.usuario?.nombre || "Usuario"}</strong>
                            <span>{formatearFecha(item.created_at)}</span>
                        </div>
                        <p>{item.mensaje}</p>
                    </div>
                ))}
            </div>

            <form className="form-chat-equipo" onSubmit={enviarMensaje}>
                <input
                    value={mensaje}
                    onChange={(e) => setMensaje(e.target.value)}
                    placeholder="Escribe un mensaje para tu equipo"
                    maxLength={1000}
                />
                <button type="submit" disabled={enviando || !mensaje.trim()}>
                    Enviar
                </button>
            </form>
        </section>
    );
};

export default ChatEquipo;
