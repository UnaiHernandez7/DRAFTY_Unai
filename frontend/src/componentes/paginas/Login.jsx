import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import logotipoDrafty from "../../img/logotipo_drafty.svg";
import "./Login.css";

const Login = () => {
    const [email, setEmail] = useState("");
    const [contrasena, setContrasena] = useState("");
    const [codigoRecuperacion, setCodigoRecuperacion] = useState("");
    const [nuevaContrasena, setNuevaContrasena] = useState("");
    const [confirmarContrasena, setConfirmarContrasena] = useState("");
    const [modoRecuperacion, setModoRecuperacion] = useState(false);
    const [codigoEnviado, setCodigoEnviado] = useState(false);
    const [mensaje, setMensaje] = useState("");
    const [tipoMensaje, setTipoMensaje] = useState("error");
    const [cargando, setCargando] = useState(false);
    const { login } = useAuth();
    const navigate = useNavigate();

    const manejarSubmit = async (e) => {
        e.preventDefault();
        setCargando(true);
        setMensaje("");

        const exito = await login(email, contrasena);

        if (exito) {
            navigate("/");
        } else {
            setTipoMensaje("error");
            setMensaje("Credenciales incorrectas. Inténtalo de nuevo.");
        }

        setCargando(false);
    };

    const activarRecuperacion = () => {
        setModoRecuperacion(true);
        setCodigoEnviado(false);
        setContrasena("");
        setCodigoRecuperacion("");
        setNuevaContrasena("");
        setConfirmarContrasena("");
        setMensaje("");
    };

    const volverALogin = () => {
        setModoRecuperacion(false);
        setCodigoEnviado(false);
        setCodigoRecuperacion("");
        setNuevaContrasena("");
        setConfirmarContrasena("");
        setMensaje("");
    };

    const solicitarCodigoRecuperacion = async (e) => {
        e.preventDefault();
        setCargando(true);
        setMensaje("");
        setTipoMensaje("info");

        try {
            const respuesta = await api.post("/recuperar-contrasena/codigo", { email });
            setCodigoEnviado(true);
            setMensaje(respuesta.data?.mensaje || "Te hemos enviado un código para cambiar la contraseña.");
        } catch (error) {
            const errores = error.response?.data?.errors;
            const primerError = errores ? Object.values(errores).flat()[0] : null;
            setTipoMensaje("error");
            setMensaje(primerError || error.response?.data?.mensaje || "No se ha podido enviar el código.");
        } finally {
            setCargando(false);
        }
    };

    const cambiarContrasenaConCodigo = async (e) => {
        e.preventDefault();
        setMensaje("");
        setTipoMensaje("info");

        if (nuevaContrasena !== confirmarContrasena) {
            setTipoMensaje("error");
            setMensaje("La confirmación no coincide con la nueva contraseña.");
            return;
        }

        setCargando(true);

        try {
            const respuesta = await api.patch("/recuperar-contrasena", {
                email,
                codigo: codigoRecuperacion,
                contrasena: nuevaContrasena,
                contrasena_confirmation: confirmarContrasena
            });
            setModoRecuperacion(false);
            setCodigoEnviado(false);
            setContrasena("");
            setCodigoRecuperacion("");
            setNuevaContrasena("");
            setConfirmarContrasena("");
            setTipoMensaje("info");
            setMensaje(respuesta.data?.mensaje || "Contraseña actualizada correctamente. Ya puedes iniciar sesión.");
        } catch (error) {
            const errores = error.response?.data?.errors;
            const primerError = errores ? Object.values(errores).flat()[0] : null;
            setTipoMensaje("error");
            setMensaje(primerError || error.response?.data?.mensaje || "No se ha podido cambiar la contraseña.");
        } finally {
            setCargando(false);
        }
    };

    return (
        <main className="auth-page">
            <section className="auth-brand">
                <img className="auth-logo" src={logotipoDrafty} alt="DRAFTY" />
                <h1>Juega. Compite. Conecta.</h1>
                <p>Encuentra partidos cerca, crea equipos y vive el fútbol con una experiencia moderna.</p>

                <div className="auth-stats">
                    <div><strong>5v5</strong><span>partidos rápidos</span></div>
                    <div><strong>7v7</strong><span>equipos completos</span></div>
                    <div><strong>11v11</strong><span>partidos grandes</span></div>
                    <div><strong>Rank</strong><span>modo competitivo</span></div>
                </div>
            </section>

            <section className="auth-card">
                <div className="auth-card-header">
                    <span>{modoRecuperacion ? "Recupera tu acceso" : "Bienvenido de nuevo"}</span>
                    <h2>{modoRecuperacion ? "Cambiar contraseña" : "Iniciar sesión"}</h2>
                </div>

                <form onSubmit={modoRecuperacion ? (codigoEnviado ? cambiarContrasenaConCodigo : solicitarCodigoRecuperacion) : manejarSubmit} className="auth-form">
                    <label>
                        Email
                        <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} disabled={modoRecuperacion && codigoEnviado} required />
                    </label>

                    {!modoRecuperacion && (
                        <label>
                            Contraseña
                            <input type="password" value={contrasena} onChange={(e) => setContrasena(e.target.value)} required />
                        </label>
                    )}

                    {modoRecuperacion && codigoEnviado && (
                        <>
                            <label>
                                Código del correo
                                <input value={codigoRecuperacion} onChange={(e) => setCodigoRecuperacion(e.target.value)} inputMode="numeric" maxLength={6} autoComplete="one-time-code" required />
                            </label>

                            <label>
                                Nueva contraseña
                                <input type="password" value={nuevaContrasena} onChange={(e) => setNuevaContrasena(e.target.value)} autoComplete="new-password" minLength={6} required />
                            </label>

                            <label>
                                Confirmar nueva contraseña
                                <input type="password" value={confirmarContrasena} onChange={(e) => setConfirmarContrasena(e.target.value)} autoComplete="new-password" minLength={6} required />
                            </label>
                        </>
                    )}

                    {mensaje && <p className={tipoMensaje === "error" ? "auth-error" : "auth-success"}>{mensaje}</p>}

                    <button type="submit" disabled={cargando}>
                        {modoRecuperacion
                            ? cargando
                                ? (codigoEnviado ? "Guardando..." : "Enviando...")
                                : (codigoEnviado ? "Cambiar contraseña" : "Enviar código")
                            : cargando ? "Iniciando..." : "Iniciar sesión"}
                    </button>
                </form>

                <div className="auth-secondary-actions">
                    {modoRecuperacion ? (
                        <>
                            {codigoEnviado && (
                                <button type="button" onClick={solicitarCodigoRecuperacion} disabled={cargando}>
                                    Reenviar código
                                </button>
                            )}
                            <button type="button" onClick={volverALogin}>
                                Volver a iniciar sesión
                            </button>
                        </>
                    ) : (
                        <button type="button" onClick={activarRecuperacion}>
                            No recuerdo mi contraseña
                        </button>
                    )}
                </div>

                {!modoRecuperacion && (
                    <p className="auth-link">
                        No tienes cuenta? <Link to="/registro">Regístrate</Link>
                    </p>
                )}
            </section>
        </main>
    );
};

export default Login;
