import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import "./Inicio.css";

const posiciones = ["Portero", "Defensa", "Mediocentro", "Delantero"];
const nombreJugador = (jugador) => jugador?.nombre_usuario || jugador?.nombre || "Jugador";

const Perfil = () => {
    const { usuario } = useAuth();
    const [formulario, setFormulario] = useState(null);
    const [estadisticas, setEstadisticas] = useState(null);
    const [competitivo, setCompetitivo] = useState(null);
    const [valoraciones, setValoraciones] = useState(null);
    const [mensaje, setMensaje] = useState("");
    const [tipoMensaje, setTipoMensaje] = useState("info");
    const [estadoNombreUsuario, setEstadoNombreUsuario] = useState({
        estado: "idle",
        mensaje: ""
    });

    useEffect(() => {
        const cargarDatosPerfil = async () => {
            try {
                const [respuestaEstadisticas, respuestaCompetitivo, respuestaValoraciones] = await Promise.all([
                    api.get("/mis-estadisticas"),
                    api.get("/mi-competitivo"),
                    api.get(`/usuarios/${usuario.id_usuario}/valoraciones`)
                ]);

                setEstadisticas(respuestaEstadisticas.data);
                setCompetitivo(respuestaCompetitivo.data);
                setValoraciones(respuestaValoraciones.data);
            } catch {
                setEstadisticas(null);
                setCompetitivo(null);
                setValoraciones(null);
            }
        };

        if (usuario) {
            setFormulario({
                nombre_usuario: usuario.nombre_usuario || "",
                nombre: usuario.nombre || "",
                apellido: usuario.apellido || "",
                ciudad: usuario.ciudad || "",
                posiciones_favoritas: usuario.posiciones_favoritas || ""
            });
            setEstadoNombreUsuario({ estado: "idle", mensaje: "" });
            cargarDatosPerfil();
        }
    }, [usuario]);

    useEffect(() => {
        if (!formulario || !usuario) {
            return;
        }

        const nombreUsuario = formulario.nombre_usuario.trim();

        if (!nombreUsuario || nombreUsuario === (usuario.nombre_usuario || "")) {
            setEstadoNombreUsuario({ estado: "idle", mensaje: "" });
            return;
        }

        setEstadoNombreUsuario({
            estado: "comprobando",
            mensaje: "Comprobando disponibilidad..."
        });

        let comprobacionActiva = true;
        const temporizador = setTimeout(async () => {
            try {
                const respuesta = await api.get("/usuarios/nombre-disponible", {
                    params: { nombre_usuario: nombreUsuario }
                });

                if (!comprobacionActiva) {
                    return;
                }

                setEstadoNombreUsuario({
                    estado: respuesta.data.disponible ? "disponible" : "ocupado",
                    mensaje: respuesta.data.mensaje
                });
            } catch (error) {
                if (!comprobacionActiva) {
                    return;
                }

                setEstadoNombreUsuario({
                    estado: "ocupado",
                    mensaje: error.response?.data?.message || "No se ha podido comprobar el nombre de usuario."
                });
            }
        }, 400);

        return () => {
            comprobacionActiva = false;
            clearTimeout(temporizador);
        };
    }, [formulario?.nombre_usuario, usuario]);

    const cambiarCampo = (campo, valor) => {
        setFormulario({ ...formulario, [campo]: valor });
        if (campo === "nombre_usuario") {
            setMensaje("");
        }
    };

    const cambiarPosicion = (posicion) => {
        const actuales = formulario.posiciones_favoritas
            ? formulario.posiciones_favoritas.split(", ").filter(Boolean)
            : [];
        const nuevas = actuales.includes(posicion)
            ? actuales.filter((item) => item !== posicion)
            : [...actuales, posicion];

        setFormulario({ ...formulario, posiciones_favoritas: nuevas.join(", ") });
    };

    const guardarPerfil = async (e) => {
        e.preventDefault();
        setMensaje("");
        setTipoMensaje("info");

        try {
            await api.patch("/perfil", formulario);
            setTipoMensaje("info");
            setEstadoNombreUsuario({ estado: "idle", mensaje: "" });
            setMensaje("Perfil actualizado correctamente. Vuelve a iniciar sesion si no ves el cambio en la cabecera.");
        } catch (error) {
            const errores = error.response?.data?.errors;
            const primerError = errores ? Object.values(errores).flat()[0] : null;
            const mensajeError = primerError || error.response?.data?.mensaje || "No se ha podido actualizar el perfil.";
            const erroresNombreUsuario = errores?.nombre_usuario;
            const nombreUsuarioEnUso = Boolean(erroresNombreUsuario) || /nombre.*usuario|usuario|uso|existe|unique/i.test(mensajeError);

            if (nombreUsuarioEnUso) {
                setEstadoNombreUsuario({
                    estado: "ocupado",
                    mensaje: mensajeError
                });
            }
            setTipoMensaje("error");
            setMensaje(mensajeError);
        }
    };

    const claseNombreUsuario = estadoNombreUsuario.estado === "ocupado"
        ? "campo-error"
        : estadoNombreUsuario.estado === "disponible"
            ? "campo-valido"
            : "";

    if (!formulario) {
        return <main className="inicio"><p className="estado">Cargando perfil...</p></main>;
    }

    return (
        <main className="inicio">
            <section className="portada">
                <h1>Mi perfil</h1>
                <p>Edita tus datos y posiciones favoritas.</p>
            </section>

            {mensaje && <p className={`mensaje ${tipoMensaje === "error" ? "mensaje-error" : ""}`}>{mensaje}</p>}

            <section className="panel-admin">
                <form className="formulario-admin" onSubmit={guardarPerfil}>
                    <label>
                        Nombre de usuario
                        <input className={claseNombreUsuario} value={formulario.nombre_usuario} onChange={(e) => cambiarCampo("nombre_usuario", e.target.value)} aria-invalid={estadoNombreUsuario.estado === "ocupado"} />
                        {estadoNombreUsuario.mensaje && <span className={`ayuda-campo ayuda-campo-${estadoNombreUsuario.estado}`}>{estadoNombreUsuario.mensaje}</span>}
                    </label>
                    <label>
                        Nombre
                        <input value={formulario.nombre} onChange={(e) => cambiarCampo("nombre", e.target.value)} />
                    </label>
                    <label>
                        Apellido
                        <input value={formulario.apellido} onChange={(e) => cambiarCampo("apellido", e.target.value)} />
                    </label>
                    <label>
                        Ciudad
                        <input value={formulario.ciudad} onChange={(e) => cambiarCampo("ciudad", e.target.value)} />
                    </label>

                    <div className="campo-completo">
                        <p className="label-posiciones">Posiciones favoritas</p>
                        <div className="botones-formacion">
                            {posiciones.map((posicion) => (
                                <button
                                    type="button"
                                    key={posicion}
                                    className={formulario.posiciones_favoritas.includes(posicion) ? "formacion-activa" : ""}
                                    onClick={() => cambiarPosicion(posicion)}
                                >
                                    {posicion}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="acciones-admin">
                        <button type="submit" disabled={estadoNombreUsuario.estado === "ocupado" || estadoNombreUsuario.estado === "comprobando"}>Guardar perfil</button>
                    </div>
                </form>
            </section>

            <section className="info-partido-sala">
                <div className="info-principal">
                    <h2>Plan competitivo</h2>
                    <p>{competitivo?.activo ? "Tu acceso competitivo esta activo." : "Necesitas tener un pago confirmado para buscar partidas competitivas."}</p>
                </div>

                <div className="datos-partido">
                    <div><span>Estado</span><strong>{competitivo?.activo ? "Activo" : "Inactivo"}</strong></div>
                    <div><span>Precio</span><strong>{competitivo?.precio_mensual ?? "3.99"} EUR / mes</strong></div>
                    <div><span>Pago</span><strong>{competitivo?.estado_pago || "pendiente"}</strong></div>
                    <div><span>Rango</span><strong>{competitivo?.rango || "Bronce 1"}</strong></div>
                    <div><span>Puntos</span><strong>{competitivo?.puntos_competitivos ?? 0}</strong></div>
                    <div><span>Inicio</span><strong>{competitivo?.fecha_inicio_suscripcion || "-"}</strong></div>
                    <div><span>Fin</span><strong>{competitivo?.fecha_fin_suscripcion || "-"}</strong></div>
                </div>

                {!competitivo?.activo && (
                    <div className="acciones-admin acciones-plan-competitivo">
                        <Link className="boton-enlace" to="/competitivo">Solicitar activacion</Link>
                    </div>
                )}
            </section>

            <section className="info-partido-sala">
                <div className="info-principal">
                    <h2>Estadísticas personales</h2>
                    <p>Datos acumulados en todos los modos de juego.</p>
                </div>

                <div className="datos-partido">
                    <div><span>Partidos</span><strong>{estadisticas?.partidos_jugados ?? 0}</strong></div>
                    <div><span>Victorias</span><strong>{estadisticas?.partidos_ganados ?? 0}</strong></div>
                    <div><span>Derrotas</span><strong>{estadisticas?.partidos_perdidos ?? 0}</strong></div>
                    <div><span>MVP</span><strong>{estadisticas?.mvps ?? 0}</strong></div>
                    <div><span>Goles</span><strong>{estadisticas?.goles ?? 0}</strong></div>
                    <div><span>Porterías a cero</span><strong>{estadisticas?.porterias_cero ?? 0}</strong></div>
                </div>
            </section>
            <section className="info-partido-sala">
                <div className="info-principal">
                    <h2>Estadisticas competitivas</h2>
                    <p>Solo cuentan los partidos jugados en modo competitivo.</p>
                </div>

                <div className="datos-partido">
                    <div><span>Partidos</span><strong>{competitivo?.partidos_competitivos_jugados ?? 0}</strong></div>
                    <div><span>Victorias</span><strong>{competitivo?.partidos_competitivos_ganados ?? 0}</strong></div>
                    <div><span>Derrotas</span><strong>{competitivo?.partidos_competitivos_perdidos ?? 0}</strong></div>
                    <div><span>MVP</span><strong>{competitivo?.mvps_competitivo ?? 0}</strong></div>
                    <div><span>Goles</span><strong>{competitivo?.goles_competitivo ?? 0}</strong></div>
                    <div><span>Porterías a cero</span><strong>{competitivo?.porterias_cero_competitivo ?? 0}</strong></div>
                    <div><span>Amarillas</span><strong>{competitivo?.tarjetas_amarillas_competitivo ?? 0}</strong></div>
                    <div><span>Rojas</span><strong>{competitivo?.tarjetas_rojas_competitivo ?? 0}</strong></div>
                </div>
            </section>

            <section className="info-partido-sala">
                <div className="info-principal">
                    <h2>Estadisticas de torneos</h2>
                    <p>Datos acumulados en torneos jugados con tus equipos.</p>
                </div>

                <div className="datos-partido">
                    <div><span>Torneos jugados</span><strong>{estadisticas?.torneos?.torneos_jugados ?? 0}</strong></div>
                    <div><span>Torneos ganados</span><strong>{estadisticas?.torneos?.torneos_ganados ?? 0}</strong></div>
                    <div><span>Goles</span><strong>{estadisticas?.torneos?.goles ?? 0}</strong></div>
                    <div><span>Porterías a cero</span><strong>{estadisticas?.torneos?.porterias_cero ?? 0}</strong></div>
                </div>
            </section>

            <section className="info-partido-sala">
                <div className="info-principal">
                    <h2>Valoraciones recibidas</h2>
                    <p>Media y últimas valoraciones de capitanes.</p>
                </div>

                <div className="datos-partido">
                    <div><span>Media</span><strong>{valoraciones?.media ?? 0}/5</strong></div>
                    <div><span>Total</span><strong>{valoraciones?.total ?? 0}</strong></div>
                </div>

                {(valoraciones?.valoraciones || []).length > 0 && (
                    <div className="lista-simple lista-valoraciones-perfil">
                        {valoraciones.valoraciones.map((valoracion) => (
                            <div className="fila-simple fila-valoracion-perfil" key={valoracion.id_valoracion}>
                                <div className="valoracion-resumen">
                                    <strong>{valoracion.puntuacion}/5</strong>
                                    <span>{valoracion.partido?.titulo || "Partido"}</span>
                                </div>
                                <p className="valoracion-comentario">
                                    <strong>{nombreJugador(valoracion.valorador)}</strong>
                                    {valoracion.comentario ? ` "${valoracion.comentario}"` : ""}
                                </p>
                            </div>
                        ))}
                    </div>
                )}
            </section>
        </main>
    );
};

export default Perfil;
