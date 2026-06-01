const medalla = (indice) => {
    if (indice === 0) return "1";
    if (indice === 1) return "2";
    if (indice === 2) return "3";
    return indice + 1;
};

const RankingTorneo = ({ titulo, datos = [], campo }) => {
    return (
        <section className="torneo-ranking-card">
            <h3>{titulo}</h3>
            {datos.length === 0 ? (
                <p className="torneo-ranking-empty">Todavía no hay estadisticas en este ranking.</p>
            ) : (
                <div className="torneo-ranking-list">
                    {datos.map((fila, indice) => (
                        <div className={indice === 0 ? "torneo-ranking-row lider" : "torneo-ranking-row"} key={`${fila.id_usuario}-${fila.id_equipo}-${indice}`}>
                            <span className="ranking-pos">{medalla(indice)}</span>
                            <div>
                                <strong>{fila.usuario?.nombre_usuario || fila.usuario?.nombre || "Jugador"}</strong>
                                <small>{fila.equipo?.nombre_equipo || "Equipo"}</small>
                            </div>
                            <b>{fila[campo] || 0}</b>
                        </div>
                    ))}
                </div>
            )}
        </section>
    );
};

export default RankingTorneo;
