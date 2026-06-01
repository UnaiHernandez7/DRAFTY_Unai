import { useState } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import "./Login.css";

const VerificarCodigo = () => {
    const location = useLocation();
    const navigate = useNavigate();
    const { verificarCodigo, reenviarCodigo } = useAuth();
    const [email, setEmail] = useState(location.state?.email || sessionStorage.getItem("email_verificacion") || "");
    const [codigo, setCodigo] = useState("");
    const [mensaje, setMensaje] = useState(location.state?.mensaje || "");
    const [error, setError] = useState("");
    const [cargando, setCargando] = useState(false);
    const [reenviando, setReenviando] = useState(false);

    const cambiarCodigo = (valor) => {
        setCodigo(valor.replace(/\D/g, "").slice(0, 6));
        setError("");
    };

    const manejarSubmit = async (e) => {
        e.preventDefault();
        setError("");
        setMensaje("");

        if (codigo.length !== 6) {
            setError("Introduce el código de 6 dígitos.");
            return;
        }

        setCargando(true);
        const resultado = await verificarCodigo(email, codigo);

        if (resultado.ok) {
            sessionStorage.removeItem("email_verificacion");
            navigate("/");
        } else {
            setError(resultado.mensaje);
        }

        setCargando(false);
    };

    const manejarReenvio = async () => {
        setError("");
        setMensaje("");

        if (!email) {
            setError("Introduce tu email para reenviar el código.");
            return;
        }

        setReenviando(true);
        const resultado = await reenviarCodigo(email);

        if (resultado.ok) {
            setMensaje(resultado.mensaje || "Código reenviado al correo.");
            setCodigo("");
        } else {
            setError(resultado.mensaje);
        }

        setReenviando(false);
    };

    return (
        <main className="auth-page">
            <section className="auth-brand">
                <span className="auth-kicker">DRAFTY</span>
                <h1>Verifica tu correo.</h1>
                <p>Te hemos enviado un código de 6 dígitos. Al confirmarlo se creara tu cuenta y entraras automáticamente.</p>

                <div className="auth-stats">
                    <div><strong>6</strong><span>digitos</span></div>
                    <div><strong>10 min</strong><span>caducidad</span></div>
                    <div><strong>OTP</strong><span>sin enlaces</span></div>
                </div>
            </section>

            <section className="auth-card">
                <div className="auth-card-header">
                    <span>Ultimo paso</span>
                    <h2>Código de verificación</h2>
                </div>

                <form onSubmit={manejarSubmit} className="auth-form">
                    <label>
                        Email
                        <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
                    </label>

                    <label>
                        Codigo
                        <input
                            inputMode="numeric"
                            pattern="[0-9]{6}"
                            value={codigo}
                            onChange={(e) => cambiarCodigo(e.target.value)}
                            placeholder="000000"
                            required
                        />
                    </label>

                    {mensaje && <p className="auth-success">{mensaje}</p>}
                    {error && <p className="auth-error">{error}</p>}

                    <button type="submit" disabled={cargando}>
                        {cargando ? "Verificando..." : "Verificar correo"}
                    </button>
                </form>

                <div className="auth-secondary-actions">
                    <button type="button" onClick={manejarReenvio} disabled={reenviando}>
                        {reenviando ? "Reenviando..." : "Reenviar código"}
                    </button>
                </div>

                <p className="auth-link">
                    ¿Ya tienes cuenta? <Link to="/login">Inicia sesión</Link>
                </p>
            </section>
        </main>
    );
};

export default VerificarCodigo;
