import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import EncabezadoSeccion from "../comunes/EncabezadoSeccion.jsx";
import "./Inicio.css";

// Archivo propio del frontend de Drafty.
const formatearRol = (rol) => {
    // Dato usado para pintar esta pantalla.
    const roles = {
        capitan: "Capitán",
        jugador: "Jugador",
        invitado: "Invitado"
    };

    return roles[rol] || "Jugador";
};

// Funcion auxiliar usada por este componente.
const Equipos = () => {
    // Estado que guarda informacion de la pantalla.
    const [equipos, setEquipos] = useState([]);
    // Estado que guarda informacion de la pantalla.
    const [mensaje, setMensaje] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [cargando, setCargando] = useState(true);
    const { isAuth, usuario } = useAuth();
    // Dato usado para pintar esta pantalla.
    const navigate = useNavigate();

    // Funcion que llama al servidor y actualiza la pantalla.
    const cargarEquipos = async () => {
        if (!isAuth) {
            setCargando(false);
            return;
        }

        try {
            // Dato usado para pintar esta pantalla.
            const respuesta = await api.get("/mis-equipos");
            setEquipos(Array.isArray(respuesta.data) ? respuesta.data : []);
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se han podido cargar tus equipos.");
        } finally {
            setCargando(false);
        }
    };

    // Efecto que se ejecuta cuando cambian los datos indicados.
    useEffect(() => {
        cargarEquipos();
    }, [isAuth]);

    // Funcion auxiliar usada por este componente.
    const rolUsuario = (equipo) => {
        // Dato usado para pintar esta pantalla.
        const miembro = equipo.usuarios?.find((item) => Number(item.id_usuario) === Number(usuario?.id_usuario));
        return miembro?.pivot?.rol_en_equipo || equipo.pivot?.rol_en_equipo || "jugador";
    };

    // Funcion que llama al servidor y actualiza la pantalla.
    const abandonarEquipo = async (idEquipo) => {
        try {
            // Dato usado para pintar esta pantalla.
            const respuesta = await api.post(`/equipos/${idEquipo}/salir`);
            setMensaje(respuesta.data?.mensaje || "Has abandonado el equipo.");
            cargarEquipos();
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido abandonar el equipo.");
        }
    };

    if (!isAuth) {
        // Vista que se muestra al usuario.
        return (
            <main className="inicio equipos-page">
                <EncabezadoSeccion
                    titulo="Equipos"
                    descripcion="Inicia sesión para crear equipos, unirte a grupos y competir con tu gente."
                    accion={<button type="button" onClick={() => navigate("/login")}>Iniciar sesión</button>}
                />
            </main>
        );
    }

    // Vista que se muestra al usuario.
    return (
        <main className="inicio equipos-page">
            <EncabezadoSeccion
                titulo="Equipos"
                descripcion="Administra tus equipos y visualiza la información de cada uno."
                accion={(
                    <>
                        <Link to="/equipos/crear">Crear equipo</Link>
                        <Link to="/equipos/unirse">Unirse a equipo</Link>
                    </>
                )}
            />

            {mensaje && <p className="mensaje mensaje-error">{mensaje}</p>}

            <section className="panel-equipos">
                <div className="cabecera-seccion-equipos">
                    <div>
                        <h2>Tus equipos</h2>
                        <p>{equipos.length} equipos activos</p>
                    </div>
                </div>

                {cargando && <p className="estado">Cargando equipos...</p>}

                {!cargando && equipos.length === 0 && (
                    <div className="estado-vacio-equipos">
                        <h3>Todavía no perteneces a ningún equipo</h3>
                        <p>Crea uno nuevo o únete a un equipo existente para empezar.</p>
                    </div>
                )}

                <div className="grid-equipos">
                    {equipos.map((equipo) => (
                        <article className="card-equipo" key={equipo.id_equipo}>
                            <div className="card-equipo-top">
                                <div className="titulo-card-equipo">
                                    <h3>{equipo.nombre_equipo}</h3>
                                    <span className={rolUsuario(equipo) === "capitan" ? "badge-capitan" : "badge-equipo"}>
                                        {formatearRol(rolUsuario(equipo))}
                                    </span>
                                    <span className={equipo.privacidad === "privado" ? "badge-equipo badge-equipo-privado" : "badge-equipo"}>
                                        {equipo.privacidad === "privado" ? "Privado" : "Público"}
                                    </span>
                                </div>
                            </div>

                            <p>{equipo.descripcion || "Sin descripción"}</p>

                            <div className="meta-equipo-card">
                                <span>Jugadores</span>
                                <strong>{equipo.jugadores_count ?? equipo.usuarios?.length ?? 0}</strong>
                                <span>Creado</span>
                                <strong>{equipo.fecha_creacion || "Sin fecha"}</strong>
                            </div>

                            <div className="acciones-card-equipo">
                                <button type="button" onClick={() => navigate(`/equipos/${equipo.id_equipo}`)}>
                                    Ver equipo
                                </button>
                                <button type="button" className="boton-abandonar-equipo" onClick={() => abandonarEquipo(equipo.id_equipo)}>
                                    Abandonar
                                </button>
                            </div>
                        </article>
                    ))}
                </div>
            </section>
        </main>
    );
};

export default Equipos;
