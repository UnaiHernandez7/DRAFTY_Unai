import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import api from "../../api/api.js";

// Archivo propio del frontend de Drafty.
const obtenerCampo = (partido) => (
    partido.campo?.nombre || partido.campo?.nombre_campo || partido.campo?.ubicacion || "Sin campo"
);

// Funcion auxiliar usada por este componente.
const obtenerResultado = (partido) => {
    // Dato usado para pintar esta pantalla.
    const golesLocal = partido.resultado?.goles_local ?? partido.goles_equipo_a;
    // Dato usado para pintar esta pantalla.
    const golesVisitante = partido.resultado?.goles_visitante ?? partido.goles_equipo_b;

    if (golesLocal === null || golesLocal === undefined || golesVisitante === null || golesVisitante === undefined) {
        return "Sin resultado";
    }

    return `${golesLocal} - ${golesVisitante}`;
};

// Funcion auxiliar usada por este componente.
const obtenerMvp = (partido) => {
    // Dato usado para pintar esta pantalla.
    const votos = Array.isArray(partido.votos_mvp) ? partido.votos_mvp : [];

    if (votos.length === 0) {
        return "Sin MVP";
    }

    // Dato usado para pintar esta pantalla.
    const ranking = votos.reduce((acumulado, voto) => {
        // Dato usado para pintar esta pantalla.
        const id = voto.id_usuario_votado;
        // Dato usado para pintar esta pantalla.
        const nombre = voto.votado?.nombre_usuario || voto.votado?.nombre || "Jugador";
        // Dato usado para pintar esta pantalla.
        const peso = voto.peso_voto || 1;

        acumulado[id] = acumulado[id] || { nombre, puntos: 0 };
        acumulado[id].puntos += peso;

        return acumulado;
    }, {});

    return Object.values(ranking).sort((a, b) => b.puntos - a.puntos)[0]?.nombre || "Sin MVP";
};

// Funcion auxiliar usada por este componente.
const estaCancelado = (partido) => (
    String(partido.estado || "").trim().toLowerCase() === "cancelado"
);

// Funcion auxiliar usada por este componente.
const HistorialPartidos = () => {
    // Estado que guarda informacion de la pantalla.
    const [partidos, setPartidos] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [mensaje, setMensaje] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [cargando, setCargando] = useState(true);
    // Dato usado para pintar esta pantalla.
    const navigate = useNavigate();

    // Efecto que se ejecuta cuando cambian los datos indicados.
    useEffect(() => {
        // Funcion que llama al servidor y actualiza la pantalla.
        const cargarHistorial = async () => {
            try {
                setMensaje("");
                setCargando(true);

                // Dato usado para pintar esta pantalla.
                const respuesta = await api.get("/historial-partidos");
                // Dato usado para pintar esta pantalla.
                const historial = Array.isArray(respuesta.data) ? respuesta.data : [];

                setPartidos(historial.filter((partido) => !estaCancelado(partido)));
            } catch {
                setMensaje("No se ha podido cargar tu historial.");
            } finally {
                setCargando(false);
            }
        };

        cargarHistorial();
    }, []);

    // Vista que se muestra al usuario.
    return (
        <section className="bloque-historial-partidos">
            <div className="cabecera-bloque-partidos">
                <div>
                    <h2>Historial de partidos</h2>
                    <p>Partidos ya finalizados despues de la ventana de 24 horas.</p>
                </div>
                <span>{partidos.length} finalizados</span>
            </div>

            {mensaje && <p className="mensaje mensaje-error">{mensaje}</p>}
            {cargando && <p className="estado">Cargando historial...</p>}

            {!cargando && partidos.length === 0 && (
                <div className="estado-vacio-partidos">
                    <h2>Aun no hay historial</h2>
                    <p>Los partidos aparecerán aquí cuando pasen más de 24 horas desde su hora de inicio.</p>
                </div>
            )}

            {!cargando && partidos.length > 0 && (
                <div className="historial-partidos-grid">
                    {partidos.map((partido) => (
                        <article className="historial-partido-card" key={partido.id_partido}>
                            <div className="historial-partido-top">
                                <div>
                                    <span className="etiqueta-finalizado">Finalizado</span>
                                    <h3>{partido.titulo}</h3>
                                </div>
                                <strong>{obtenerResultado(partido)}</strong>
                            </div>

                            <div className="historial-partido-meta">
                                <span>{partido.fecha || "Sin fecha"}</span>
                                <span>{partido.hora || "Sin hora"}</span>
                                <span>{obtenerCampo(partido)}</span>
                                <span>MVP: {obtenerMvp(partido)}</span>
                            </div>

                            <button type="button" onClick={() => navigate(`/partidos/${partido.id_partido}/sala`)}>
                                Ver partido
                            </button>
                        </article>
                    ))}
                </div>
            )}
        </section>
    );
};

export default HistorialPartidos;
