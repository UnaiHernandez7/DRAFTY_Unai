import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import logotipoDrafty from "../../img/logotipo_drafty.svg";
import "./Login.css";
import "./Registro.css";

const posiciones = ["Portero", "Defensa", "Mediocentro", "Delantero"];

const validarContrasenaSegura = (valor) => {
    if (valor.length < 8) return "La contraseña debe tener al menos 8 caracteres.";
    if (!/[a-z]/.test(valor) || !/[A-Z]/.test(valor)) return "La contraseña debe incluir mayúsculas y minúsculas.";
    if (!/[0-9]/.test(valor)) return "La contraseña debe incluir al menos un número.";
    if (!/[^A-Za-z0-9]/.test(valor)) return "La contraseña debe incluir al menos un símbolo.";

    return "";
};

const Registro = () => {
    const [nombreUsuario, setNombreUsuario] = useState("");
    const [nombre, setNombre] = useState("");
    const [apellido, setApellido] = useState("");
    const [email, setEmail] = useState("");
    const [contrasena, setContrasena] = useState("");
    const [ciudad, setCiudad] = useState("");
    const [posicionesFavoritas, setPosicionesFavoritas] = useState([]);
    const [mensaje, setMensaje] = useState("");
    const [cargando, setCargando] = useState(false);
    const [estadoNombreUsuario, setEstadoNombreUsuario] = useState({
        estado: "idle",
        mensaje: ""
    });

    const { register } = useAuth();
    const navigate = useNavigate();

    useEffect(() => {
        const nombre = nombreUsuario.trim();

        if (!nombre) {
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
                    params: { nombre_usuario: nombre }
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
    }, [nombreUsuario]);

    const cambiarPosicion = (posicion) => {
        if (posicionesFavoritas.includes(posicion)) {
            setPosicionesFavoritas(posicionesFavoritas.filter((item) => item !== posicion));
        } else {
            setPosicionesFavoritas([...posicionesFavoritas, posicion]);
        }
    };

    const manejarSubmit = async (e) => {
        e.preventDefault();
        setMensaje("");

        if (estadoNombreUsuario.estado === "ocupado" || estadoNombreUsuario.estado === "comprobando") {
            setMensaje("Elige un nombre de usuario disponible antes de crear la cuenta.");
            return;
        }

        const errorContrasena = validarContrasenaSegura(contrasena);
        if (errorContrasena) {
            setMensaje(errorContrasena);
            return;
        }

        setCargando(true);

        const datos = {
            nombre_usuario: nombreUsuario,
            nombre,
            apellido,
            email,
            contrasena,
            ciudad,
            posiciones_favoritas: posicionesFavoritas.join(", ")
        };

        const resultado = await register(datos);

        if (resultado.ok || resultado.pendiente) {
            sessionStorage.setItem("email_verificacion", resultado.email || email);
            navigate("/verificar-codigo", {
                state: {
                    email: resultado.email || email,
                    mensaje: resultado.mensaje
                }
            });
        } else {
            setMensaje(resultado.mensaje);
        }

        setCargando(false);
    };

    const claseNombreUsuario = estadoNombreUsuario.estado === "ocupado"
        ? "campo-error"
        : estadoNombreUsuario.estado === "disponible"
            ? "campo-valido"
            : "";

    return (
        <main className="auth-page auth-page-registro">
            <section className="auth-brand">
                <img className="auth-logo" src={logotipoDrafty} alt="DRAFTY" />
                <h1>Tu fútbol empieza aquí.</h1>
                <p>Crea tu perfil, guarda tu ciudad y encuentra salas, equipos y retos competitivos cerca de ti.</p>

                <div className="auth-stats">
                    <div><strong>Salas</strong><span>partidos cercanos</span></div>
                    <div><strong>Teams</strong><span>equipos y amigos</span></div>
                    <div><strong>Rank</strong><span>progreso competitivo</span></div>
                </div>
            </section>

            <section className="auth-card auth-card-registro">
                <div className="auth-card-header">
                    <span>Nuevo jugador</span>
                    <h2>Crear cuenta</h2>
                </div>

                <form onSubmit={manejarSubmit} className="auth-form auth-form-registro">
                    <label>
                        Nombre de usuario
                        <input
                            className={claseNombreUsuario}
                            value={nombreUsuario}
                            onChange={(e) => {
                                setNombreUsuario(e.target.value);
                                setMensaje("");
                            }}
                            aria-invalid={estadoNombreUsuario.estado === "ocupado"}
                            required
                        />
                        {estadoNombreUsuario.mensaje && <span className={`ayuda-campo ayuda-campo-${estadoNombreUsuario.estado}`}>{estadoNombreUsuario.mensaje}</span>}
                    </label>

                    <label>
                        Nombre
                        <input value={nombre} onChange={(e) => setNombre(e.target.value)} required />
                    </label>

                    <label>
                        Apellido
                        <input value={apellido} onChange={(e) => setApellido(e.target.value)} required />
                    </label>

                    <label>
                        Email
                        <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
                    </label>

                    <label>
                        Contraseña
                        <input type="password" value={contrasena} onChange={(e) => setContrasena(e.target.value)} required />
                    </label>

                    <label>
                        Ciudad
                        <input value={ciudad} onChange={(e) => setCiudad(e.target.value)} />
                    </label>

                    <div className="auth-field-full">
                        <span className="auth-label">Posiciones favoritas</span>
                        <div className="auth-posiciones">
                            {posiciones.map((posicion) => (
                                <label key={posicion} className="auth-posicion">
                                    <input
                                        type="checkbox"
                                        checked={posicionesFavoritas.includes(posicion)}
                                        onChange={() => cambiarPosicion(posicion)}
                                    />
                                    {posicion}
                                </label>
                            ))}
                        </div>
                    </div>

                    {mensaje && <p className="auth-error auth-field-full">{mensaje}</p>}

                    <button
                        type="submit"
                        disabled={cargando || estadoNombreUsuario.estado === "ocupado" || estadoNombreUsuario.estado === "comprobando"}
                        className="auth-field-full"
                    >
                        {cargando ? "Creando cuenta..." : "Crear cuenta"}
                    </button>
                </form>

                <p className="auth-link">
                    ¿Ya tienes cuenta? <Link to="/login">Inicia sesión</Link>
                </p>
            </section>
        </main>
    );
};

export default Registro;
