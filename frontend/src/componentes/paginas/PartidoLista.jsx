import escudoA from "../../img/A (2).png";
import escudoB from "../../img/B.png";

// Archivo propio del frontend de Drafty.
const formatearTipo = (tipo = "") => {
    if (tipo === "5v5") return "Fútbol 5v5";
    if (tipo === "7v7") return "Fútbol 7v7";
    if (tipo === "11v11") return "Fútbol 11v11";

    return tipo || "Sin tipo";
};

// Funcion auxiliar usada por este componente.
const obtenerMarcador = (partido) => {
    // Dato usado para pintar esta pantalla.
    const golesLocal = partido.resultado?.goles_local ?? partido.goles_equipo_a;
    // Dato usado para pintar esta pantalla.
    const golesVisitante = partido.resultado?.goles_visitante ?? partido.goles_equipo_b;

    if (golesLocal === null || golesLocal === undefined || golesVisitante === null || golesVisitante === undefined) {
        return "VS";
    }

    return `${golesLocal} - ${golesVisitante}`;
};

// Funcion auxiliar usada por este componente.
const obtenerCapacidadPorTipo = (tipoFutbol = "") => {
    // Dato usado para pintar esta pantalla.
    const tipo = String(tipoFutbol || "").toLowerCase();

    if (tipo.includes("5v5") || tipo.includes("sala")) {
        return 14;
    }

    if (tipo.includes("7")) {
        return 20;
    }

    return 26;
};

// Funcion auxiliar usada por este componente.
const obtenerCapacidad = (partido = {}) => {
    // Dato usado para pintar esta pantalla.
    const capacidadPorTipo = obtenerCapacidadPorTipo(partido.tipo_futbol);
    // Dato usado para pintar esta pantalla.
    const capacidadGuardada = partido.plazas_totales_calculadas || partido.plazas_totales || 0;

    return Math.max(capacidadGuardada, capacidadPorTipo);
};

// Componente que pinta esta parte de la aplicacion.
const PartidoLista = ({
    partido,
    estaUnido,
    estaCompleto,
    onAccion,
    textoAccion,
    mostrarPlazasOcupadas = false,
    accionesExtra = null
}) => {
    // Dato usado para pintar esta pantalla.
    const campo = partido.campo?.nombre_campo || partido.campo?.nombre || "Campo por confirmar";
    // Dato usado para pintar esta pantalla.
    const ciudad = [partido.campo?.ciudad, partido.campo?.provincia].filter(Boolean).join(", ") || "Ubicación por confirmar";
    // Dato usado para pintar esta pantalla.
    const equipoLocal = partido.equipo_local?.nombre_equipo || partido.equipoLocal?.nombre_equipo || "Equipo A";
    // Dato usado para pintar esta pantalla.
    const equipoVisitante = partido.equipo_visitante?.nombre_equipo || partido.equipoVisitante?.nombre_equipo || "Equipo B";
    // Dato usado para pintar esta pantalla.
    const capacidad = obtenerCapacidad(partido);
    // Dato usado para pintar esta pantalla.
    const ocupadas = partido.usuarios_count || 0;
    // Dato usado para pintar esta pantalla.
    const plazasLibres = Math.max(0, capacidad - ocupadas);
    // Dato usado para pintar esta pantalla.
    const textoPlazas = mostrarPlazasOcupadas
        ? `${ocupadas}/${capacidad} jugadores`
        : `${plazasLibres}/${capacidad} plazas libres`;
    // Dato usado para pintar esta pantalla.
    const estadoVisible = plazasLibres <= 0 && capacidad > 0 ? "completo" : (partido.estado === "completo" ? "abierto" : partido.estado || "abierto");
    // Dato usado para pintar esta pantalla.
    const marcador = obtenerMarcador(partido);
    // Dato usado para pintar esta pantalla.
    const distancia = partido.distancia_km !== null && partido.distancia_km !== undefined
        ? `${partido.distancia_km} km`
        : null;
    // Dato usado para pintar esta pantalla.
    const esCompetitivo = partido.es_competitivo || partido.nivel === "Competitivo";

    // Vista que se muestra al usuario.
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
