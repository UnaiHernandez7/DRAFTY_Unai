import escudoA from "../../img/A (2).png";
import escudoB from "../../img/B.png";

const formatearTipo = (tipo = "") => {
    if (tipo === "5v5") return "Fútbol 5v5";
    if (tipo === "7v7") return "Fútbol 7v7";
    if (tipo === "11v11") return "Fútbol 11v11";

    return tipo || "Sin tipo";
};

const obtenerMarcador = (partido) => {
    const golesLocal = partido.resultado?.goles_local ?? partido.goles_equipo_a;
    const golesVisitante = partido.resultado?.goles_visitante ?? partido.goles_equipo_b;

    if (golesLocal === null || golesLocal === undefined || golesVisitante === null || golesVisitante === undefined) {
        return "VS";
    }

    return `${golesLocal} - ${golesVisitante}`;
};

const obtenerCapacidadPorTipo = (tipoFutbol = "") => {
    const tipo = String(tipoFutbol || "").toLowerCase();

    if (tipo.includes("5v5") || tipo.includes("sala")) {
        return 14;
    }

    if (tipo.includes("7")) {
        return 20;
    }

    return 26;
};

const obtenerCapacidad = (partido = {}) => {
    const capacidadPorTipo = obtenerCapacidadPorTipo(partido.tipo_futbol);
    const capacidadGuardada = partido.plazas_totales_calculadas || partido.plazas_totales || 0;

    return Math.max(capacidadGuardada, capacidadPorTipo);
};

const PartidoLista = ({
    partido,
    estaUnido,
    estaCompleto,
    onAccion,
    textoAccion,
    mostrarPlazasOcupadas = false,
    accionesExtra = null
}) => {
    const campo = partido.campo?.nombre_campo || partido.campo?.nombre || "Campo por confirmar";
    const ciudad = [partido.campo?.ciudad, partido.campo?.provincia].filter(Boolean).join(", ") || "Ubicación por confirmar";
    const equipoLocal = partido.equipo_local?.nombre_equipo || partido.equipoLocal?.nombre_equipo || "Equipo A";
    const equipoVisitante = partido.equipo_visitante?.nombre_equipo || partido.equipoVisitante?.nombre_equipo || "Equipo B";
    const capacidad = obtenerCapacidad(partido);
    const ocupadas = partido.usuarios_count || 0;
    const plazasLibres = Math.max(0, capacidad - ocupadas);
    const textoPlazas = mostrarPlazasOcupadas
        ? `${ocupadas}/${capacidad} jugadores`
        : `${plazasLibres}/${capacidad} plazas libres`;
    const estadoVisible = plazasLibres <= 0 && capacidad > 0 ? "completo" : (partido.estado === "completo" ? "abierto" : partido.estado || "abierto");
    const marcador = obtenerMarcador(partido);
    const distancia = partido.distancia_km !== null && partido.distancia_km !== undefined
        ? `${partido.distancia_km} km`
        : null;
    const esCompetitivo = partido.es_competitivo || partido.nivel === "Competitivo";

    return (
        <article className={esCompetitivo ? "partido-lista-card partido-lista-competitivo" : "partido-lista-card partido-lista-casual"}>
            <div className="partido-lista-info partido-lista-info-izq">
                <span>{formatearTipo(partido.tipo_futbol)} | {partido.nivel || "Sin nivel"}</span>
                <strong className={esCompetitivo ? "badge-tipo-partido competitivo" : "badge-tipo-partido casual"}>
                    {esCompetitivo ? "Competitivo" : "Casual"}
                </strong>
                <h2>{partido.titulo}</h2>
                <p>{partido.fecha || "Sin fecha"} - {partido.hora || "Sin hora"}</p>
                <p>{campo}</p>
            </div>

            <div className="partido-lista-marcador">
                <div className="equipo-resumen">
                    <img src={escudoA} alt={equipoLocal} />
                </div>

                <strong className={marcador === "VS" ? "marcador-vs" : ""}>{marcador}</strong>

                <div className="equipo-resumen">
                    <img src={escudoB} alt={equipoVisitante} />
                </div>
            </div>

            <div className="partido-lista-info partido-lista-info-der">
                <span>{ciudad}</span>
                <p>{textoPlazas}</p>
                <p>Estado: {estadoVisible}</p>
                {distancia && <p className="distancia-partido">{distancia}</p>}
                <div className="acciones-partido-lista">
                    <button type="button" disabled={estaCompleto && !estaUnido} onClick={() => onAccion(partido.id_partido)}>
                        {textoAccion || (estaUnido ? "Ver sala" : estaCompleto ? "Completo" : "Unirse")}
                    </button>
                    {accionesExtra}
                </div>
            </div>
        </article>
    );
};

export default PartidoLista;
