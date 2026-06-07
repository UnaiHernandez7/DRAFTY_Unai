import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import api from "../../api/api.js";
import EncabezadoSeccion from "../comunes/EncabezadoSeccion.jsx";
import "./Inicio.css";

// Archivo propio del frontend de Drafty.
const nombreJugador = (jugador) => jugador?.nombre_usuario || jugador?.nombre || "Jugador";

// Funcion auxiliar usada por este componente.
const PerfilUsuario = () => {
    const { id } = useParams();
    // Estado que guarda informacion de la pantalla.
    const [perfil, setPerfil] = useState(null);
    // Estado que guarda informacion de la pantalla.
    const [valoraciones, setValoraciones] = useState(null);
    // Estado que guarda informacion de la pantalla.
    const [mensaje, setMensaje] = useState("");

    // Efecto que se ejecuta cuando cambian los datos indicados.
    useEffect(() => {
        // Funcion que llama al servidor y actualiza la pantalla.
        const cargarPerfil = async () => {
            try {
                const [respuesta, respuestaValoraciones] = await Promise.all([
                    api.get(`/usuarios/${id}`),
                    api.get(`/usuarios/${id}/valoraciones`)
                ]);
                setPerfil(respuesta.data);
                setValoraciones(respuestaValoraciones.data);
            } catch (error) {
                setMensaje(error.response?.data?.mensaje || "No se ha podido cargar el perfil.");
            }
        };

        cargarPerfil();
    }, [id]);

    if (mensaje) {
        // Vista que se muestra al usuario.
        return (
            <main className="inicio">
                <p className="mensaje mensaje-error">{mensaje}</p>
                <Link className="boton-enlace" to="/amigos">Volver a amigos</Link>
            </main>
        );
    }

    if (!perfil) {
        return <main className="inicio"><p className="estado">Cargando perfil...</p></main>;
    }

    // Dato usado para pintar esta pantalla.
    const competitivo = perfil.competitivo;
    // Dato usado para pintar esta pantalla.
    const estadisticas = perfil.estadisticas;

    // Vista que se muestra al usuario.
    return (
        <main className="inicio">
            <EncabezadoSeccion
                titulo={perfil.nombre_usuario}
                descripcion="Perfil público del jugador."
            />

            <section className="panel-admin">
                <h2>Datos del perfil</h2>
                <div className="datos-partido">
                    <div><span>Usuario</span><strong>{perfil.nombre_usuario}</strong></div>
                    <div><span>Nombre</span><strong>{perfil.nombre || "-"}</strong></div>
                    <div><span>Apellido</span><strong>{perfil.apellido || "-"}</strong></div>
                    <div><span>Ciudad</span><strong>{perfil.ciudad || "-"}</strong></div>
                    <div><span>Posiciones</span><strong>{perfil.posiciones_favoritas || "-"}</strong></div>
                </div>
            </section>

            <section className="info-partido-sala">
                <div className="info-principal">
                    <h2>Plan competitivo</h2>
                    <p>{competitivo?.activo ? "Acceso competitivo activo." : "Sin acceso competitivo activo."}</p>
                </div>
                <div className="datos-partido">
                    <div><span>Estado</span><strong>{competitivo?.activo ? "Activo" : "Inactivo"}</strong></div>
                    <div><span>Rango</span><strong>{competitivo?.rango || "Bronce 1"}</strong></div>
                    <div><span>Puntos</span><strong>{competitivo?.puntos_competitivos ?? 0}</strong></div>
                    <div><span>Partidos competitivos</span><strong>{competitivo?.partidos_competitivos_jugados ?? 0}</strong></div>
                </div>
            </section>

            <section className="info-partido-sala">
                <div className="info-principal">
                    <h2>Estadisticas personales</h2>
                    <p>Datos acumulados en todos los modos de juego.</p>
                </div>
                <div className="datos-partido">
                    <div><span>Partidos</span><strong>{estadisticas?.partidos_jugados ?? 0}</strong></div>
                    <div><span>Victorias</span><strong>{estadisticas?.partidos_ganados ?? 0}</strong></div>
                    <div><span>MVP</span><strong>{estadisticas?.mvps ?? 0}</strong></div>
                    <div><span>Goles</span><strong>{estadisticas?.goles ?? 0}</strong></div>
                    <div><span>Porterías a cero</span><strong>{estadisticas?.porterias_cero ?? 0}</strong></div>
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

export default PerfilUsuario;
