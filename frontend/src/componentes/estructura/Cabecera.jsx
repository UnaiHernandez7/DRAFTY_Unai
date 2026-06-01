import { useEffect, useState } from "react";
import { Link, useLocation } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import "./Cabecera.css";

const Cabecera = () => {
    const { usuario, logout, isAuth, isAdmin } = useAuth();
    const location = useLocation();
    const [menuAbierto, setMenuAbierto] = useState(false);
    const [notificacionesAmigos, setNotificacionesAmigos] = useState({
        hay_nuevas: false,
        total: 0
    });

    const cargarNotificacionesAmigos = async () => {
        if (!isAuth) {
            setNotificacionesAmigos({ hay_nuevas: false, total: 0 });
            return;
        }

        try {
            const respuesta = await api.get("/amistades/notificaciones");
            setNotificacionesAmigos({
                hay_nuevas: Boolean(respuesta.data?.hay_nuevas),
                total: respuesta.data?.total ?? 0
            });
        } catch {
            setNotificacionesAmigos({ hay_nuevas: false, total: 0 });
        }
    };

    const marcarAmigosVistos = async () => {
        setNotificacionesAmigos({ hay_nuevas: false, total: 0 });

        try {
            await api.post("/amistades/notificaciones/vistas");
        } catch {
            // Si falla, se volvera a comprobar en la siguiente carga.
        }
    };

    useEffect(() => {
        cargarNotificacionesAmigos();

        if (!isAuth) {
            return undefined;
        }

        const intervalo = setInterval(cargarNotificacionesAmigos, 30000);

        return () => clearInterval(intervalo);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isAuth, usuario?.id_usuario]);

    useEffect(() => {
        if (isAuth && location.pathname === "/amigos") {
            marcarAmigosVistos();
        }

        setMenuAbierto(false);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isAuth, location.pathname]);

    return (
        <header className="cabecera">
            <Link to="/" className="logo">DRAFTY</Link>

            {isAuth && (
                <button
                    type="button"
                    className={menuAbierto ? "menu-movil-toggle abierto" : "menu-movil-toggle"}
                    onClick={() => setMenuAbierto((abierto) => !abierto)}
                    aria-expanded={menuAbierto}
                    aria-label="Abrir menu de navegacion"
                >
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            )}

            {isAuth && (
                <nav className={menuAbierto ? "nav nav-abierta" : "nav"}>
                    <Link to="/" onClick={() => setMenuAbierto(false)}>Inicio</Link>
                    <Link to="/mis-partidos" onClick={() => setMenuAbierto(false)}>Mis partidos</Link>
                    <Link to="/crear-partido" onClick={() => setMenuAbierto(false)}>Crear partido</Link>
                    <Link to="/equipos" onClick={() => setMenuAbierto(false)}>Equipos</Link>
                    <Link to="/torneos" onClick={() => setMenuAbierto(false)}>Torneos</Link>
                    <Link to="/competitivo" onClick={() => setMenuAbierto(false)}>Competitivo</Link>
                    <Link
                        to="/amigos"
                        className="nav-link-notificacion"
                        onClick={() => {
                            setMenuAbierto(false);
                            marcarAmigosVistos();
                        }}
                    >
                        Amigos
                        {notificacionesAmigos.hay_nuevas && (
                            <span className="badge-amigos" aria-label={`${notificacionesAmigos.total} solicitudes nuevas`}>
                                {notificacionesAmigos.total > 9 ? "9+" : notificacionesAmigos.total}
                            </span>
                        )}
                    </Link>
                    {isAdmin && <Link to="/admin" onClick={() => setMenuAbierto(false)}>Admin</Link>}
                    <button
                        type="button"
                        className="nav-logout-movil"
                        onClick={() => {
                            setMenuAbierto(false);
                            logout();
                        }}
                    >
                        Cerrar sesión
                    </button>
                </nav>
            )}

            <div className="login-area">
                {isAuth ? (
                    <>
                        <Link to="/perfil" className="link-perfil">
                            {usuario?.nombre_usuario || usuario?.nombre}
                        </Link>
                        <button className="boton-logout" onClick={logout}>Cerrar sesión</button>
                    </>
                ) : (
                    <Link to="/login" className="btn-login">Iniciar sesión</Link>
                )}
            </div>
        </header>
    );
};

export default Cabecera;
