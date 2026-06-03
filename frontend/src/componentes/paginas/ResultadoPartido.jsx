import { useMemo, useState } from "react";
import api from "../../api/api.js";

const nombreJugador = (jugador) => jugador?.nombre_usuario || jugador?.nombre || "Jugador";

const golesPorEquipo = (goles = [], equipo) => (
    goles.filter((gol) => (gol.equipo_sala || gol.usuario?.pivot?.equipo_asignado) === equipo).length
);

const formatearFecha = (valor) => {
    if (!valor) {
        return "Sin límite";
    }

    return new Date(valor).toLocaleString("es-ES", {
        day: "2-digit",
        month: "2-digit",
        hour: "2-digit",
        minute: "2-digit"
    });
};

const ResultadoPartido = ({ partido, participantes, miJugador, onCambio }) => {
    const [nuevoGol, setNuevoGol] = useState({ id_usuario: "", minuto: "" });
    const [mensaje, setMensaje] = useState("");
    const [guardando, setGuardando] = useState(false);
    const goles = partido?.goles || [];
    const resultado = partido?.resultado;
    const soyCapitan = Boolean(miJugador?.pivot?.es_capitan);
    const puedeGestionar = partido?.estado !== "cancelado" && soyCapitan;
    const puedeUsarVentana = Boolean(partido?.ventana_resultado_abierta);
    const estadoResultado = resultado?.estado_resultado;
    const resultadoCerrado = estadoResultado === "cerrado";
    const resultadoSinResultado = estadoResultado === "sin_resultado";
    const resultadoEditable = !resultadoCerrado && !resultadoSinResultado;
    const miEquipo = miJugador?.pivot?.equipo_asignado;

    const marcador = useMemo(() => ({
        local: resultadoSinResultado ? "-" : (resultadoCerrado ? resultado?.goles_local : golesPorEquipo(goles, "Equipo A")),
        visitante: resultadoSinResultado ? "-" : (resultadoCerrado ? resultado?.goles_visitante : golesPorEquipo(goles, "Equipo B"))
    }), [goles, resultado, resultadoCerrado, resultadoSinResultado]);

    const propuestaLocal = resultado?.goles_local_local != null && resultado?.goles_visitante_local != null
        ? `${resultado.goles_local_local} - ${resultado.goles_visitante_local}`
        : "Pendiente";
    const propuestaVisitante = resultado?.goles_local_visitante != null && resultado?.goles_visitante_visitante != null
        ? `${resultado.goles_local_visitante} - ${resultado.goles_visitante_visitante}`
        : "Pendiente";

    const agregarGol = async (e) => {
        e.preventDefault();
        setMensaje("");

        if (!nuevoGol.id_usuario) {
            setMensaje("Selecciona el jugador que ha marcado.");
            return;
        }

        if (nuevoGol.minuto && (Number(nuevoGol.minuto) < 1 || Number(nuevoGol.minuto) > 90)) {
            setMensaje("El minuto del gol debe estar entre 1 y 90.");
            return;
        }

        try {
            setGuardando(true);
            await api.post(`/partidos/${partido.id_partido}/goles`, {
                id_usuario: nuevoGol.id_usuario,
                minuto: nuevoGol.minuto || null
            });
            setNuevoGol({ id_usuario: "", minuto: "" });
            await onCambio();
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido añadir el gol.");
        } finally {
            setGuardando(false);
        }
    };

    const quitarGol = async (idGol) => {
        try {
            setGuardando(true);
            await api.delete(`/goles/${idGol}`);
            await onCambio();
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido quitar el gol.");
        } finally {
            setGuardando(false);
        }
    };

    const registrarResultado = async () => {
        try {
            setGuardando(true);
            const respuesta = await api.post(`/partidos/${partido.id_partido}/resultado`);
            setMensaje(respuesta.data?.mensaje || "Resultado enviado correctamente.");
            await onCambio();
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido registrar el resultado.");
        } finally {
            setGuardando(false);
        }
    };

    if (!partido) {
        return null;
    }

    return (
        <article className="post-card post-card-destacada">
            <div className="post-card-cabecera">
                <div>
                    <span className="post-etiqueta">Post-partido</span>
                    <h2>Resultado</h2>
                </div>
                <div className="post-marcador">
                    <strong>{marcador.local}</strong>
                    <span>-</span>
                    <strong>{marcador.visitante}</strong>
                </div>
            </div>

            <div className="post-alertas">
                {partido.estado === "cancelado" && <p className="post-error">El partido está cancelado.</p>}
                {partido.faltan_jugadores_minimos > 0 && partido.estado !== "cancelado" && (
                    <p>Faltan {partido.faltan_jugadores_minimos} jugadores para completar las alineaciones mínimas.</p>
                )}
                <p>
                    Jugadores confirmados: <strong>{partido.jugadores_confirmados ?? participantes.length}</strong>
                    {" "}de <strong>{partido.jugadores_minimos || "?"}</strong>
                </p>
                <p>
                    Ventana de resultado: <strong>{puedeUsarVentana ? "abierta" : "cerrada"}</strong>
                    {" "}hasta {formatearFecha(partido.fecha_limite_resultado)}
                </p>
                {resultado && (
                    <p>
                        Equipo A: <strong>{propuestaLocal}</strong>
                        {" "}· Equipo B: <strong>{propuestaVisitante}</strong>
                    </p>
                )}
                {estadoResultado === "desacuerdo" && (
                    <p className="post-error">Los resultados no coinciden. Podéis corregir los goles y volver a enviar el marcador antes de que cierre la ventana.</p>
                )}
                {resultadoSinResultado && (
                    <p className="post-error">La ventana terminó sin acuerdo. El partido queda sin resultado.</p>
                )}
                {resultadoCerrado && (
                    <p>Resultado confirmado por los dos equipos.</p>
                )}
            </div>

            {mensaje && <p className="post-mensaje">{mensaje}</p>}

            <div className="post-lista-goles">
                {goles.length === 0 ? (
                    <p className="estado">Todavía no hay goles registrados.</p>
                ) : (
                    goles.map((gol) => (
                        <div className="post-gol" key={gol.id_gol}>
                            <div>
                                <strong>{nombreJugador(gol.usuario)}</strong>
                                <span>{gol.equipo_sala || "Sin equipo"}{gol.minuto ? ` · ${gol.minuto}'` : ""}</span>
                            </div>
                            {puedeGestionar && puedeUsarVentana && resultadoEditable && (
                                <button type="button" onClick={() => quitarGol(gol.id_gol)} disabled={guardando}>
                                    Quitar
                                </button>
                            )}
                        </div>
                    ))
                )}
            </div>

            {puedeGestionar && puedeUsarVentana && resultadoEditable && (
                <>
                    <form className="post-form" onSubmit={agregarGol}>
                        <label>
                            Goleador
                            <select
                                value={nuevoGol.id_usuario}
                                onChange={(e) => setNuevoGol({ ...nuevoGol, id_usuario: e.target.value })}
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
                            Minuto
                            <input
                                type="number"
                                min="1"
                                max="90"
                                value={nuevoGol.minuto}
                                onChange={(e) => setNuevoGol({ ...nuevoGol, minuto: e.target.value })}
                                placeholder="Opcional"
                            />
                        </label>
                        <button type="submit" disabled={guardando}>Añadir gol</button>
                    </form>

                    <div className="post-acciones">
                        <button type="button" onClick={registrarResultado} disabled={guardando}>
                            Enviar resultado {miEquipo ? `de ${miEquipo}` : ""}
                        </button>
                    </div>
                </>
            )}

            {!puedeGestionar && partido.estado !== "cancelado" && (
                <p className="post-ayuda">
                    Solo los capitanes pueden gestionar el resultado.
                </p>
            )}
        </article>
    );
};

export default ResultadoPartido;

