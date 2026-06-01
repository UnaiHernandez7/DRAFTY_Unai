import { useMemo, useState } from "react";
import api from "../../api/api.js";
import escudoA from "../../img/A (2).png";
import escudoB from "../../img/B.png";

const ordenRondas = ["Octavos", "Cuartos", "Semifinales", "Final"];

const jugadoresActivos = (equipo) => (
    equipo?.usuarios?.filter((jugador) => (
        jugador.pivot?.rol_en_equipo !== "invitado" &&
        (!jugador.pivot?.estado || jugador.pivot.estado === "activo")
    )) || []
);

const BracketPartido = ({ partido, puedeEditar, onActualizado }) => {
    const [gol, setGol] = useState({
        id_equipo: partido.id_equipo_local || "",
        id_usuario: "",
        minuto: ""
    });
    const [guardando, setGuardando] = useState(false);

    const local = partido.equipo_local;
    const visitante = partido.equipo_visitante;
    const tieneEquipos = partido.id_equipo_local && partido.id_equipo_visitante;
    const tieneResultado = partido.goles_local !== null && partido.goles_visitante !== null && partido.estado === "jugado";
    const golesLocal = partido.goles?.filter((item) => Number(item.id_equipo) === Number(partido.id_equipo_local)).length ?? partido.goles_local ?? 0;
    const golesVisitante = partido.goles?.filter((item) => Number(item.id_equipo) === Number(partido.id_equipo_visitante)).length ?? partido.goles_visitante ?? 0;
    const jugadoresDisponibles = Number(gol.id_equipo) === Number(partido.id_equipo_visitante)
        ? jugadoresActivos(visitante)
        : jugadoresActivos(local);

    const cambiarEquipo = (idEquipo) => {
        setGol({ id_equipo: idEquipo, id_usuario: "", minuto: "" });
    };

    const agregarGol = async () => {
        if (!gol.id_equipo || !gol.id_usuario) {
            onActualizado("Selecciona equipo y goleador.", "error");
            return;
        }

        try {
            setGuardando(true);
            await api.post(`/torneos/partidos/${partido.id_torneo_partido}/goles`, {
                id_equipo: gol.id_equipo,
                id_usuario: gol.id_usuario,
                minuto: gol.minuto || null
            });
            setGol({ id_equipo: gol.id_equipo, id_usuario: "", minuto: "" });
            onActualizado("Gol añadido correctamente.", "exito");
        } catch (error) {
            onActualizado(error.response?.data?.mensaje || "No se ha podido añadir el gol.", "error");
        } finally {
            setGuardando(false);
        }
    };

    const eliminarGol = async (idGol) => {
        try {
            await api.delete(`/torneos/goles/${idGol}`);
            onActualizado("Gol eliminado.", "exito");
        } catch (error) {
            onActualizado(error.response?.data?.mensaje || "No se ha podido eliminar el gol.", "error");
        }
    };

    const confirmar = async () => {
        try {
            await api.put(`/torneos/partidos/${partido.id_torneo_partido}/resultado`);
            onActualizado("Resultado confirmado.", "exito");
        } catch (error) {
            onActualizado(error.response?.data?.mensaje || "No se ha podido confirmar el resultado.", "error");
        }
    };

    return (
        <article className={tieneResultado ? "bracket-match visual finalizado" : "bracket-match visual"}>
            <div className="bracket-match-top">
                <span className={tieneResultado ? "bracket-estado finalizado" : "bracket-estado"}>{tieneResultado ? "Finalizado" : "Pendiente"}</span>
                {tieneResultado && <strong>{partido.goles_local} - {partido.goles_visitante}</strong>}
            </div>

            <div className="bracket-versus">
                <div className={Number(partido.id_equipo_ganador) === Number(partido.id_equipo_local) ? "bracket-club ganador" : "bracket-club"}>
                    <img src={escudoA} alt="" />
                    <span>{local?.nombre_equipo || "Pendiente"}</span>
                    <b>{golesLocal}</b>
                </div>
                <div className="bracket-vs-line">VS</div>
                <div className={Number(partido.id_equipo_ganador) === Number(partido.id_equipo_visitante) ? "bracket-club ganador" : "bracket-club"}>
                    <img src={escudoB} alt="" />
                    <span>{visitante?.nombre_equipo || "Pendiente"}</span>
                    <b>{golesVisitante}</b>
                </div>
            </div>

            {partido.goles?.length > 0 && (
                <div className="bracket-goles-list">
                    {partido.goles.map((item) => (
                        <span key={item.id_gol_torneo}>
                            {item.minuto ? `${item.minuto}' ` : ""}
                            {item.usuario?.nombre_usuario || "Jugador"}
                            {!tieneResultado && puedeEditar && (
                                <button type="button" onClick={() => eliminarGol(item.id_gol_torneo)}>x</button>
                            )}
                        </span>
                    ))}
                </div>
            )}

            {puedeEditar && tieneEquipos && partido.estado !== "jugado" && (
                <div className="resultado-bracket">
                    <div className="resultado-bracket-grid">
                        <select value={gol.id_equipo} onChange={(e) => cambiarEquipo(e.target.value)}>
                            <option value={partido.id_equipo_local}>{local?.nombre_equipo || "Local"}</option>
                            <option value={partido.id_equipo_visitante}>{visitante?.nombre_equipo || "Visitante"}</option>
                        </select>
                        <select value={gol.id_usuario} onChange={(e) => setGol({ ...gol, id_usuario: e.target.value })}>
                            <option value="">Goleador</option>
                            {jugadoresDisponibles.map((jugador) => (
                                <option key={jugador.id_usuario} value={jugador.id_usuario}>
                                    {jugador.nombre_usuario || jugador.nombre}
                                </option>
                            ))}
                        </select>
                        <input
                            type="number"
                            min="1"
                            max="130"
                            value={gol.minuto}
                            onChange={(e) => setGol({ ...gol, minuto: e.target.value })}
                            placeholder="Min."
                        />
                    </div>
                    <div className="resultado-bracket-actions">
                        <button type="button" onClick={agregarGol} disabled={guardando}>Añadir gol</button>
                        <button type="button" className="secundario" onClick={confirmar}>Confirmar {golesLocal} - {golesVisitante}</button>
                    </div>
                </div>
            )}
        </article>
    );
};

const BracketTorneo = ({ partidos = [], puedeEditar = false, onActualizado }) => {
    const rondas = useMemo(() => (
        ordenRondas
            .map((ronda) => ({
                ronda,
                partidos: partidos.filter((partido) => partido.ronda === ronda)
            }))
            .filter((grupo) => grupo.partidos.length > 0)
    ), [partidos]);
    const semifinales = partidos.filter((partido) => partido.ronda === "Semifinales");
    const finales = partidos.filter((partido) => partido.ronda === "Final");
    const rondasPrevias = rondas.filter((grupo) => !["Semifinales", "Final"].includes(grupo.ronda));
    const tieneBloqueFinal = semifinales.length > 0 && finales.length > 0;

    if (rondas.length === 0) {
        return (
            <div className="torneo-empty compacto">
                <h3>Bracket pendiente</h3>
                <p>El organizador podra iniciarlo cuando haya equipos suficientes.</p>
            </div>
        );
    }

    return (
        <div className="bracket-torneo visual">
            {rondasPrevias.map((grupo) => (
                <section className="bracket-ronda visual" key={grupo.ronda}>
                    <h3>{grupo.ronda}</h3>
                    <div className="bracket-partidos visual">
                        {grupo.partidos.map((partido) => (
                            <BracketPartido
                                key={partido.id_torneo_partido}
                                partido={partido}
                                puedeEditar={puedeEditar}
                                onActualizado={onActualizado}
                            />
                        ))}
                    </div>
                    <span className="bracket-conector" />
                </section>
            ))}

            {tieneBloqueFinal ? (
                <section className="bracket-final-layout">
                    <div className="bracket-final-side">
                        <h3>Semifinal</h3>
                        {semifinales[0] && (
                            <BracketPartido
                                partido={semifinales[0]}
                                puedeEditar={puedeEditar}
                                onActualizado={onActualizado}
                            />
                        )}
                    </div>
                    <div className="bracket-final-center">
                        <h3>Final</h3>
                        {finales.map((partido) => (
                            <BracketPartido
                                key={partido.id_torneo_partido}
                                partido={partido}
                                puedeEditar={puedeEditar}
                                onActualizado={onActualizado}
                            />
                        ))}
                    </div>
                    <div className="bracket-final-side">
                        <h3>Semifinal</h3>
                        {semifinales[1] && (
                            <BracketPartido
                                partido={semifinales[1]}
                                puedeEditar={puedeEditar}
                                onActualizado={onActualizado}
                            />
                        )}
                    </div>
                </section>
            ) : (
                rondas.filter((grupo) => ["Semifinales", "Final"].includes(grupo.ronda)).map((grupo) => (
                    <section className="bracket-ronda visual" key={grupo.ronda}>
                        <h3>{grupo.ronda}</h3>
                        <div className="bracket-partidos visual">
                            {grupo.partidos.map((partido) => (
                                <BracketPartido
                                    key={partido.id_torneo_partido}
                                    partido={partido}
                                    puedeEditar={puedeEditar}
                                    onActualizado={onActualizado}
                                />
                            ))}
                        </div>
                    </section>
                ))
            )}
        </div>
    );
};

export default BracketTorneo;
