import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import "./Login.css";

const Login = () => {
    const [email, setEmail] = useState("");
    const [contrasena, setContrasena] = useState("");
    const [mensaje, setMensaje] = useState("");
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
            setMensaje("Credenciales incorrectas. Inténtalo de nuevo.");
        }

        setCargando(false);
    };

    return (
        <main className="auth-page">
            <section className="auth-brand">
                <span className="auth-kicker">DRAFTY</span>
                <h1>Juega. Compite. Conecta.</h1>
                <p>Encuentra partidos cerca, crea equipos y vive el fútbol con una experiencia moderna.</p>

                <div className="auth-stats">
                    <div><strong>5v5</strong><span>pachangas rápidas</span></div>
                    <div><strong>7v7</strong><span>equipos completos</span></div>
                    <div><strong>11v11</strong><span>partidos grandes</span></div>
                    <div><strong>Rank</strong><span>modo competitivo</span></div>
                </div>
            </section>

            <section className="auth-card">
                <div className="auth-card-header">
                    <span>Bienvenido de nuevo</span>
                    <h2>Iniciar sesión</h2>
                </div>

                <form onSubmit={manejarSubmit} className="auth-form">
                    <label>
                        Email
                        <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
                    </label>

                    <label>
                        Contraseña
                        <input type="password" value={contrasena} onChange={(e) => setContrasena(e.target.value)} required />
                    </label>

                    {mensaje && <p className="auth-error">{mensaje}</p>}

                    <button type="submit" disabled={cargando}>
                        {cargando ? "Iniciando..." : "Iniciar sesión"}
                    </button>
                </form>

                <p className="auth-link">
                    No tienes cuenta? <Link to="/registro">Regístrate</Link>
                </p>
            </section>
        </main>
    );
};

export default Login;
