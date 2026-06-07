import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import logotipoDrafty from "../../img/logotipo_drafty.svg";
import "./Login.css";

// Archivo propio del frontend de Drafty.
const Login = () => {
    // Permite iniciar sesion con usuario o email.
    const [identificador, setIdentificador] = useState("");
    // Se separa del login porque recuperar contrasena siempre usa email.
    const [emailRecuperacion, setEmailRecuperacion] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [contrasena, setContrasena] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [codigoRecuperacion, setCodigoRecuperacion] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [nuevaContrasena, setNuevaContrasena] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [confirmarContrasena, setConfirmarContrasena] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [modoRecuperacion, setModoRecuperacion] = useState(false);
    // Estado que guarda informacion de la pantalla.
    const [codigoEnviado, setCodigoEnviado] = useState(false);
    // Estado que guarda informacion de la pantalla.
    const [mensaje, setMensaje] = useState("");
    // Estado que guarda informacion de la pantalla.
    const [tipoMensaje, setTipoMensaje] = useState("error");
    // Estado que guarda informacion de la pantalla.
    const [cargando, setCargando] = useState(false);
    const { login } = useAuth();
    // Dato usado para pintar esta pantalla.
    const navigate = useNavigate();

    // Funcion que llama al servidor y actualiza la pantalla.
    const manejarSubmit = async (e) => {
        e.preventDefault();
        setCargando(true);
        setMensaje("");

        // Dato usado para pintar esta pantalla.
        const exito = await login(identificador.trim(), contrasena);

        if (exito) {
            navigate("/");
        } else {
            setTipoMensaje("error");
            setMensaje("Credenciales incorrectas. Inténtalo de nuevo.");
        }

        setCargando(false);
    };

    // Funcion auxiliar usada por este componente.
    const activarRecuperacion = () => {
        setModoRecuperacion(true);
        setCodigoEnviado(false);
        // Si ya escribio un email, lo aprovechamos para recuperar.
        setEmailRecuperacion(identificador.includes("@") ? identificador : "");
        setContrasena("");
        setCodigoRecuperacion("");
        setNuevaContrasena("");
        setConfirmarContrasena("");
        setMensaje("");
    };

    // Funcion auxiliar usada por este componente.
    const volverALogin = () => {
        setModoRecuperacion(false);
        setCodigoEnviado(false);
        setCodigoRecuperacion("");
        setNuevaContrasena("");
        setConfirmarContrasena("");
        setMensaje("");
    };

    // Funcion que llama al servidor y actualiza la pantalla.
    const enviarCodigoRecuperacion = async () => {
        setCargando(true);
        setMensaje("");
        setTipoMensaje("info");

        try {
            // Dato usado para pintar esta pantalla.
            const respuesta = await api.post("/recuperar-contrasena/codigo", { email: emailRecuperacion });
            setCodigoEnviado(true);
            setMensaje(respuesta.data?.mensaje || "Te hemos enviado un código para cambiar la contraseña.");
        } catch (error) {
            // Dato usado para pintar esta pantalla.
            const errores = error.response?.data?.errors;
            // Dato usado para pintar esta pantalla.
            const primerError = errores ? Object.values(errores).flat()[0] : null;
            setTipoMensaje("error");
            setMensaje(primerError || error.response?.data?.mensaje || "No se ha podido enviar el código.");
        } finally {
            setCargando(false);
        }
    };

    // Funcion que pide el codigo desde el formulario.
    const solicitarCodigoRecuperacion = async (e) => {
        e.preventDefault();
        await enviarCodigoRecuperacion();
    };

    // Funcion que vuelve a pedir el codigo desde el boton secundario.
    const reenviarCodigoRecuperacion = async () => {
        await enviarCodigoRecuperacion();
    };

    // Funcion que llama al servidor y actualiza la pantalla.
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
            // Dato usado para pintar esta pantalla.
            const respuesta = await api.patch("/recuperar-contrasena", {
                email: emailRecuperacion,
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
            // Dato usado para pintar esta pantalla.
            const errores = error.response?.data?.errors;
            // Dato usado para pintar esta pantalla.
            const primerError = errores ? Object.values(errores).flat()[0] : null;
            setTipoMensaje("error");
            setMensaje(primerError || error.response?.data?.mensaje || "No se ha podido cambiar la contraseña.");
        } finally {
            setCargando(false);
        }
    };

    // Vista que se muestra al usuario.
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
                        {modoRecuperacion ? "Email" : "Usuario o email"}
                        {/* El mismo campo cambia segun el modo del formulario. */}
                        <input
                            type={modoRecuperacion ? "email" : "text"}
                            value={modoRecuperacion ? emailRecuperacion : identificador}
                            onChange={(e) => modoRecuperacion ? setEmailRecuperacion(e.target.value) : setIdentificador(e.target.value)}
                            disabled={modoRecuperacion && codigoEnviado}
                            autoComplete={modoRecuperacion ? "email" : "username"}
                            required
                        />
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
                                <button type="button" onClick={reenviarCodigoRecuperacion} disabled={cargando}>
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
                        ¿No tienes cuenta? <Link to="/registro">Regístrate</Link>
                    </p>
                )}
            </section>
        </main>
    );
};

export default Login;
