import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import EncabezadoSeccion from "../comunes/EncabezadoSeccion.jsx";
import "./Inicio.css";

// Archivo propio del frontend de Drafty.
const Amigos = () => {
    // Dato usado para pintar esta pantalla.
    const navigate = useNavigate();
    const { usuario } = useAuth();
    // Estado que guarda informacion de la pantalla.
    const [amigos, setAmigos] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [recibidas, setRecibidas] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [enviadas, setEnviadas] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [invitacionesSala, setInvitacionesSala] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [invitacionesEquipo, setInvitacionesEquipo] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [partidos, setPartidos] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [equipos, setEquipos] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [busqueda, setBusqueda] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [sugerencias, setSugerencias] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [buscandoUsuarios, setBuscandoUsuarios] = useState(false);
    // Estado que guarda informacion de la pantalla.
    const [selecciones, setSelecciones] = useState({});
    // Estado que guarda informacion de la pantalla.
    const [mensaje, setMensaje] = useState("");

    // Funcion que llama al servidor y actualiza la pantalla.
    const obtenerLista = async (peticion) => {
        try {
            // Dato usado para pintar esta pantalla.
            const respuesta = await peticion();
            return Array.isArray(respuesta.data) ? respuesta.data : [];
        } catch (error) {
            console.error("No se pudo cargar una seccion de amigos:", error);
            return [];
        }
    };

    // Funcion que llama al servidor y actualiza la pantalla.
    const cargarDatos = async () => {
        try {
            await api.post("/amistades/notificaciones/vistas");
        } catch {
            // La pagina puede seguir cargando aunque falle marcar como visto.
        }

        const [
            listaAmigos,
            listaRecibidas,
            listaEnviadas,
            listaInvitacionesSala,
            listaInvitacionesEquipo,
            listaPartidos,
            listaEquipos
        ] = await Promise.all([
            obtenerLista(() => api.get("/amigos")),
            obtenerLista(() => api.get("/amistades/recibidas")),
            obtenerLista(() => api.get("/amistades/enviadas")),
            obtenerLista(() => api.get("/partidos-invitaciones")),
            obtenerLista(() => api.get("/equipos-invitaciones")),
            obtenerLista(() => api.get("/mis-partidos-detalle")),
            obtenerLista(() => api.get("/mis-equipos"))
        ]);

        setAmigos(listaAmigos);
        setRecibidas(listaRecibidas);
        setEnviadas(listaEnviadas);
        setInvitacionesSala(listaInvitacionesSala);
        setInvitacionesEquipo(listaInvitacionesEquipo);
        setPartidos(listaPartidos);
        setEquipos(listaEquipos);
    };

    // Efecto que se ejecuta cuando cambian los datos indicados.
    useEffect(() => {
        cargarDatos();
    }, []);

    // Efecto que se ejecuta cuando cambian los datos indicados.
    useEffect(() => {
        // Dato usado para pintar esta pantalla.
        const texto = busqueda.trim();

        if (!texto) {
            setSugerencias([]);
            setBuscandoUsuarios(false);
            return;
        }

        let busquedaActiva = true;
        setBuscandoUsuarios(true);

        // Dato usado para pintar esta pantalla.
        const temporizador = setTimeout(async () => {
            try {
                // Dato usado para pintar esta pantalla.
                const respuesta = await api.get("/usuarios/buscar", {
                    params: { query: texto }
                });

                if (busquedaActiva) {
                    setSugerencias(Array.isArray(respuesta.data) ? respuesta.data : []);
                }
            } catch (error) {
                try {
                    // Dato usado para pintar esta pantalla.
                    const respuestaUsuarios = await api.get("/usuarios");
                    // Dato usado para pintar esta pantalla.
                    const listaUsuarios = Array.isArray(respuestaUsuarios.data) ? respuestaUsuarios.data : [];
                    // Dato usado para pintar esta pantalla.
                    const idsAmigos = amigos.map((amigo) => Number(amigo.id_usuario));
                    // Dato usado para pintar esta pantalla.
                    const idsEnviadas = enviadas.map((solicitud) => Number(solicitud.id_usuario_receptor ?? solicitud.id_amigo));
                    // Dato usado para pintar esta pantalla.
                    const idsRecibidas = recibidas.map((solicitud) => Number(solicitud.id_usuario_emisor ?? solicitud.id_usuario));
                    // Dato usado para pintar esta pantalla.
                    const textoNormalizado = texto.toLowerCase();
                    // Dato usado para pintar esta pantalla.
                    const resultados = listaUsuarios
                        .filter((item) => (
                            Number(item.id_usuario) !== Number(usuario?.id_usuario) &&
                            !idsAmigos.includes(Number(item.id_usuario)) &&
                            !idsEnviadas.includes(Number(item.id_usuario)) &&
                            !idsRecibidas.includes(Number(item.id_usuario))
                        ))
                        .filter((item) => [
                            item.nombre_usuario,
                            item.nombre,
                            item.apellido
                        ].filter(Boolean).join(" ").toLowerCase().includes(textoNormalizado))
                        .slice(0, 8);

                    if (busquedaActiva) {
                        setSugerencias(resultados);
                    }
                } catch {
                    setMensaje(error.response?.data?.mensaje || "No se ha podido buscar usuarios.");
                }
            } finally {
                if (busquedaActiva) {
                    setBuscandoUsuarios(false);
                }
            }
        }, 350);

        return () => {
            busquedaActiva = false;
            clearTimeout(temporizador);
        };
    }, [amigos, busqueda, enviadas, recibidas, usuario]);

    // Funcion que llama al servidor y actualiza la pantalla.
    const ejecutar = async (accion, textoOk) => {
        try {
            // Dato usado para pintar esta pantalla.
            const respuesta = await accion();
            setMensaje(respuesta.data?.mensaje || textoOk);
            setBusqueda("");
            setSugerencias([]);
            cargarDatos();
        } catch (error) {
            // Dato usado para pintar esta pantalla.
            const errores = error.response?.data?.errors;
            // Dato usado para pintar esta pantalla.
            const primerError = errores ? Object.values(errores).flat()[0] : null;
            setMensaje(
                error.response?.data?.mensaje ||
                primerError ||
                error.response?.data?.message ||
                "No se ha podido completar la accion."
            );
        }
    };

    // Funcion auxiliar usada por este componente.
    const cambiarSeleccion = (idUsuario, campo, valor) => {
        setSelecciones({
            ...selecciones,
            [idUsuario]: {
                ...(selecciones[idUsuario] || {}),
                [campo]: valor
            }
        });
    };

    // Funcion auxiliar usada por este componente.
    const invitarASala = (amigo) => {
        // Dato usado para pintar esta pantalla.
        const idPartido = selecciones[amigo.id_usuario]?.partido || partidos[0]?.id_partido;

        if (!idPartido) {
            setMensaje("Primero tienes que estar en una sala para invitar a un amigo.");
            return;
        }

        ejecutar(
            () => api.post(`/partidos/${idPartido}/invitar-amigo/${amigo.id_usuario}`),
            "Amigo invitado a la sala"
        );
    };

    // Funcion auxiliar usada por este componente.
    const invitarAEquipo = (amigo) => {
        // Dato usado para pintar esta pantalla.
        const idEquipo = selecciones[amigo.id_usuario]?.equipo || equipos[0]?.id_equipo;

        if (!idEquipo) {
            setMensaje("Primero crea o unete a un equipo para invitar a un amigo.");
            return;
        }

        ejecutar(
            () => api.post(`/equipos/${idEquipo}/invitar-amigo/${amigo.id_usuario}`),
            "Amigo invitado al equipo"
        );
    };

    // Dato usado para pintar esta pantalla.
    const mostrarSugerencias = busqueda.trim().length > 0;

    // Vista que se muestra al usuario.
    return (
        <main className="inicio">
            <EncabezadoSeccion
                titulo="Amigos"
                descripcion="Conecta con otros jugadores, acepta solicitudes y forma tu red DRAFTY."
            />

            {mensaje && <p className="mensaje">{mensaje}</p>}

            <section className="alineaciones">
                <article className="panel-admin">
                    <h2>Buscar usuarios</h2>
                    <div className="buscador-amigos-moderno">
                        <label className="campo-completo">
                            Buscar amigo
                            <div className="input-buscador-amigos">
                                <input
                                    type="search"
                                    value={busqueda}
                                    onChange={(e) => setBusqueda(e.target.value)}
                                    placeholder="Busca por usuario o nombre"
                                />
                                {buscandoUsuarios && <span className="spinner-busqueda" />}
                            </div>
                        </label>

                        {mostrarSugerencias && (
                            <div className="sugerencias-amigos">
                                {!buscandoUsuarios && sugerencias.length === 0 && (
                                    <p className="estado">No se encontraron usuarios.</p>
                                )}

                                {sugerencias.map((item) => (
                                    <div className="sugerencia-amigo" key={item.id_usuario}>
                                        <div className="avatar-amigo">
                                            {item.foto_perfil ? (
                                                <img src={item.foto_perfil} alt={item.nombre_usuario} />
                                            ) : (
                                                <span>{(item.nombre_usuario || item.nombre || "D").slice(0, 1).toUpperCase()}</span>
                                            )}
                                        </div>
                                        <div className="datos-sugerencia-amigo">
                                            <strong>{item.nombre} {item.apellido}</strong>
                                            <p>@{item.nombre_usuario}</p>
                                        </div>
                                        <button type="button" onClick={() => ejecutar(
                                            () => api.post(`/amistades/enviar/${item.id_usuario}`),
                                            "Solicitud enviada"
                                        )}>
                                            Agregar
                                        </button>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </article>

                <article className="panel-admin">
                    <h2>Solicitudes recibidas</h2>
                    <div className="lista-simple">
                        {recibidas.length === 0 && <p className="estado">No tienes solicitudes pendientes.</p>}
                        {recibidas.map((solicitud) => (
                            <div className="fila-simple" key={solicitud.id_amistad}>
                                <div>
                                    <strong>{solicitud.emisor?.nombre_usuario}</strong>
                                    <p>{solicitud.emisor?.nombre} {solicitud.emisor?.apellido}</p>
                                </div>
                                <div className="acciones-admin acciones-tarjeta">
                                    <button type="button" onClick={() => ejecutar(
                                        () => api.put(`/amistades/${solicitud.id_amistad}/aceptar`),
                                        "Solicitud aceptada"
                                    )}>
                                        Aceptar
                                    </button>
                                    <button type="button" onClick={() => ejecutar(
                                        () => api.put(`/amistades/${solicitud.id_amistad}/rechazar`),
                                        "Solicitud rechazada"
                                    )}>
                                        Rechazar
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                </article>

                <article className="panel-admin">
                    <h2>Invitaciones</h2>
                    <div className="lista-simple">
                        {invitacionesSala.length === 0 && invitacionesEquipo.length === 0 && (
                            <p className="estado">No tienes invitaciones pendientes.</p>
                        )}

                        {invitacionesSala.map((partido) => (
                            <div className="fila-simple" key={`sala-${partido.id_partido}`}>
                                <div>
                                    <strong>{partido.titulo}</strong>
                                    <p>Sala - {partido.fecha || "sin fecha"} {partido.hora || ""}</p>
                                </div>
                                <div className="acciones-admin acciones-tarjeta">
                                    <button type="button" onClick={() => ejecutar(
                                        () => api.put(`/partidos/${partido.id_partido}/invitacion/aceptar`),
                                        "Invitacion aceptada"
                                    )}>
                                        Aceptar
                                    </button>
                                    <button type="button" onClick={() => ejecutar(
                                        () => api.delete(`/partidos/${partido.id_partido}/invitacion`),
                                        "Invitacion rechazada"
                                    )}>
                                        Rechazar
                                    </button>
                                </div>
                            </div>
                        ))}

                        {invitacionesEquipo.map((equipo) => (
                            <div className="fila-simple" key={`equipo-${equipo.id_equipo}`}>
                                <div>
                                    <strong>{equipo.nombre_equipo}</strong>
                                    <p>Equipo</p>
                                </div>
                                <div className="acciones-admin acciones-tarjeta">
                                    <button type="button" onClick={() => ejecutar(
                                        () => api.put(`/equipos/${equipo.id_equipo}/invitacion/aceptar`),
                                        "Invitacion aceptada"
                                    )}>
                                        Aceptar
                                    </button>
                                    <button type="button" onClick={() => ejecutar(
                                        () => api.delete(`/equipos/${equipo.id_equipo}/invitacion`),
                                        "Invitacion rechazada"
                                    )}>
                                        Rechazar
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                </article>

                <article className="panel-admin">
                    <h2>Mis amigos</h2>
                    <div className="lista-simple">
                        {amigos.length === 0 && <p className="estado">Todavía no tienes amigos.</p>}
                        {amigos.map((amigo) => (
                            <div className="tarjeta-amigo" key={amigo.id_usuario}>
                                <div className="avatar-amigo avatar-amigo-lista">
                                    {amigo.foto_perfil ? (
                                        <img src={amigo.foto_perfil} alt={amigo.nombre_usuario} />
                                    ) : (
                                        <span>{(amigo.nombre_usuario || amigo.nombre || "D").slice(0, 1).toUpperCase()}</span>
                                    )}
                                </div>

                                <div className="datos-amigo">
                                    <div className="cabecera-amigo">
                                        <strong>{amigo.nombre_usuario}</strong>
                                        <div className="chips-amigo">
                                            <span>{amigo.competitivo?.rango || "Bronce 1"}</span>
                                            <span>{amigo.competitivo?.puntos_competitivos ?? 0} pts</span>
                                        </div>
                                    </div>
                                </div>

                                <div className="acciones-amigo-iconos">
                                    <button type="button" onClick={() => navigate(`/usuarios/${amigo.id_usuario}/perfil`)} title="Ver perfil" aria-label="Ver perfil">
                                        i
                                    </button>

                                    <button type="button" className="boton-peligro-icono" onClick={() => ejecutar(
                                        () => api.delete(`/amistades/${amigo.id_amistad}`),
                                        "Amigo eliminado"
                                    )} title="Eliminar amigo" aria-label="Eliminar amigo">
                                        x
                                    </button>
                                </div>

                                <div className="selectores-amigo">
                                    <div className="selector-invitacion-amigo">
                                        <label>
                                            Sala
                                            <select
                                                value={selecciones[amigo.id_usuario]?.partido || partidos[0]?.id_partido || ""}
                                                onChange={(e) => cambiarSeleccion(amigo.id_usuario, "partido", e.target.value)}
                                            >
                                                {partidos.length === 0 && <option value="">Sin salas</option>}
                                                {partidos.map((partido) => (
                                                    <option value={partido.id_partido} key={partido.id_partido}>
                                                        {partido.titulo} - {partido.fecha || "sin fecha"}
                                                    </option>
                                                ))}
                                            </select>
                                        </label>
                                        <button type="button" onClick={() => invitarASala(amigo)} title="Invitar a sala" aria-label="Invitar a sala">
                                            +
                                        </button>
                                    </div>

                                    <div className="selector-invitacion-amigo">
                                        <label>
                                            Equipo
                                            <select
                                                value={selecciones[amigo.id_usuario]?.equipo || equipos[0]?.id_equipo || ""}
                                                onChange={(e) => cambiarSeleccion(amigo.id_usuario, "equipo", e.target.value)}
                                            >
                                                {equipos.length === 0 && <option value="">Sin equipos</option>}
                                                {equipos.map((equipo) => (
                                                    <option value={equipo.id_equipo} key={equipo.id_equipo}>
                                                        {equipo.nombre_equipo}
                                                    </option>
                                                ))}
                                            </select>
                                        </label>
                                        <button type="button" onClick={() => invitarAEquipo(amigo)} title="Invitar a equipo" aria-label="Invitar a equipo">
                                            +
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </article>

                <article className="panel-admin">
                    <h2>Solicitudes enviadas</h2>
                    <div className="lista-simple">
                        {enviadas.length === 0 && <p className="estado">No has enviado solicitudes.</p>}
                        {enviadas.map((solicitud) => (
                            <div className="fila-simple" key={solicitud.id_amistad}>
                                <div>
                                    <strong>{solicitud.receptor?.nombre_usuario}</strong>
                                    <p>Pendiente de respuesta</p>
                                </div>
                                <button type="button" onClick={() => ejecutar(
                                    () => api.delete(`/amistades/${solicitud.id_amistad}`),
                                    "Solicitud cancelada"
                                )}>
                                    Cancelar
                                </button>
                            </div>
                        ))}
                    </div>
                </article>
            </section>
        </main>
    );
};

export default Amigos;
