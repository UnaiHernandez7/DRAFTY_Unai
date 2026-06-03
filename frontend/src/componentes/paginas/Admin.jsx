import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import api from "../../api/api.js";
import { useAuth } from "../../contextos/ProveedorAuth.jsx";
import EncabezadoSeccion from "../comunes/EncabezadoSeccion.jsx";
import "./Inicio.css";

const usuarioVacio = {
    nombre_usuario: "",
    nombre: "",
    apellido: "",
    email: "",
    contrasena: "",
    ciudad: "",
    posiciones_favoritas: "",
    rol: "usuario"
};

const obtenerCapacidad = (partido) => (
    partido.plazas_totales_calculadas || partido.plazas_totales || 0
);

const Admin = () => {
    const { isAdmin, token, usuario } = useAuth();
    const navigate = useNavigate();
    const [vista, setVista] = useState("partidos");
    const [partidos, setPartidos] = useState([]);
    const [usuarios, setUsuarios] = useState([]);
    const [pagos, setPagos] = useState([]);
    const [usuarioForm, setUsuarioForm] = useState(usuarioVacio);
    const [usuarioEditando, setUsuarioEditando] = useState(null);
    const [mensaje, setMensaje] = useState("");
    const [cargando, setCargando] = useState(true);

    const cargarDatos = async () => {
        try {
            setCargando(true);
            const [respuestaPartidos, respuestaPagos, respuestaUsuarios] = await Promise.all([
                api.get("/admin/partidos"),
                api.get("/pagos"),
                api.get("/usuarios")
            ]);

            setPartidos(Array.isArray(respuestaPartidos.data) ? respuestaPartidos.data : []);
            setPagos(Array.isArray(respuestaPagos.data) ? respuestaPagos.data : []);
            setUsuarios(Array.isArray(respuestaUsuarios.data) ? respuestaUsuarios.data : []);
        } catch {
            setMensaje("No se han podido cargar los datos de administracion.");
        } finally {
            setCargando(false);
        }
    };

    useEffect(() => {
        if (!token) {
            navigate("/login");
            return;
        }

        if (!usuario) {
            return;
        }

        if (!isAdmin) {
            navigate("/");
            return;
        }

        cargarDatos();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [token, usuario, isAdmin]);

    const cambiarUsuarioForm = (campo, valor) => {
        setUsuarioForm((actual) => ({ ...actual, [campo]: valor }));
    };

    const limpiarUsuarioForm = () => {
        setUsuarioEditando(null);
        setUsuarioForm(usuarioVacio);
    };

    const empezarEdicionUsuario = (item) => {
        setVista("usuarios");
        setUsuarioEditando(item);
        setUsuarioForm({
            nombre_usuario: item.nombre_usuario || "",
            nombre: item.nombre || "",
            apellido: item.apellido || "",
            email: item.email || "",
            contrasena: "",
            ciudad: item.ciudad || "",
            posiciones_favoritas: item.posiciones_favoritas || "",
            rol: item.rol || "usuario"
        });
    };

    const guardarUsuario = async (e) => {
        e.preventDefault();
        setMensaje("");

        try {
            const payload = { ...usuarioForm };

            if (usuarioEditando && !payload.contrasena) {
                delete payload.contrasena;
            }

            if (usuarioEditando) {
                await api.put(`/usuarios/${usuarioEditando.id_usuario}`, payload);
                setMensaje("Usuario actualizado correctamente.");
            } else {
                await api.post("/usuarios", payload);
                setMensaje("Usuario creado correctamente.");
            }

            limpiarUsuarioForm();
            cargarDatos();
        } catch (error) {
            const errores = error.response?.data?.errors;
            const primerError = errores ? Object.values(errores).flat()[0] : null;
            setMensaje(primerError || error.response?.data?.mensaje || "No se ha podido guardar el usuario.");
        }
    };

    const eliminarUsuario = async (idUsuario) => {
        try {
            await api.delete(`/usuarios/${idUsuario}`);
            setMensaje("Usuario eliminado correctamente.");
            cargarDatos();
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido eliminar el usuario.");
        }
    };

    const cancelarPartido = async (idPartido) => {
        try {
            await api.patch(`/partidos/${idPartido}/cancelar`);
            setMensaje("Partido cancelado correctamente.");
            cargarDatos();
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido cancelar el partido.");
        }
    };

    const eliminarPartido = async (idPartido) => {
        try {
            await api.delete(`/partidos/${idPartido}`);
            setMensaje("Partido eliminado correctamente.");
            cargarDatos();
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido eliminar el partido.");
        }
    };

    const gestionarPago = async (idPago, accion) => {
        try {
            const respuesta = await api.put(`/pagos/${idPago}/${accion}`);
            setMensaje(respuesta.data?.mensaje || "Pago actualizado correctamente.");
            cargarDatos();
        } catch (error) {
            setMensaje(error.response?.data?.mensaje || "No se ha podido actualizar el pago.");
        }
    };

    return (
        <main className="inicio admin-page">
            <EncabezadoSeccion
                titulo="Administración"
                descripcion="Gestiona partidos, usuarios y pagos de DRAFTY."
            />

            <section className="panel-admin admin-tabs">
                <button type="button" className={vista === "partidos" ? "formacion-activa" : ""} onClick={() => setVista("partidos")}>
                    Partidos
                </button>
                <button type="button" className={vista === "usuarios" ? "formacion-activa" : ""} onClick={() => setVista("usuarios")}>
                    Usuarios
                </button>
                <button type="button" className={vista === "pagos" ? "formacion-activa" : ""} onClick={() => setVista("pagos")}>
                    Pagos
                </button>
            </section>

            {mensaje && <p className="mensaje">{mensaje}</p>}
            {cargando && <p className="estado">Cargando administracion...</p>}

            {!cargando && vista === "partidos" && (
                <section className="lista-partidos">
                    {partidos.map((partido) => {
                        const capacidad = obtenerCapacidad(partido);
                        const ocupadas = partido.usuarios_count || 0;

                        return (
                            <article className="tarjeta-partido" key={partido.id_partido}>
                                <h2>{partido.titulo}</h2>
                                <p><strong>Fecha:</strong> {partido.fecha}</p>
                                <p><strong>Hora:</strong> {partido.hora}</p>
                                <p><strong>Tipo:</strong> {partido.tipo_futbol}</p>
                                <p><strong>Nivel:</strong> {partido.nivel}</p>
                                <p><strong>Estado:</strong> {partido.estado}</p>
                                <p><strong>Plazas:</strong> {ocupadas}/{capacidad}</p>
                                <p><strong>Resultado:</strong> {partido.goles_equipo_a ?? "-"} - {partido.goles_equipo_b ?? "-"}</p>

                                <div className="acciones-admin acciones-tarjeta">
                                    <button type="button" onClick={() => navigate(`/partidos/${partido.id_partido}/sala`)}>Editar</button>
                                    <button type="button" className="boton-peligro" onClick={() => cancelarPartido(partido.id_partido)}>Cancelar partido</button>
                                    <button type="button" className="boton-peligro" onClick={() => eliminarPartido(partido.id_partido)}>Eliminar</button>
                                </div>
                            </article>
                        );
                    })}
                </section>
            )}

            {!cargando && vista === "usuarios" && (
                <>
                    <section className="panel-admin">
                        <h2>{usuarioEditando ? "Editar usuario" : "Crear usuario"}</h2>
                        <form className="formulario-admin" onSubmit={guardarUsuario}>
                            <label>
                                Usuario
                                <input value={usuarioForm.nombre_usuario} onChange={(e) => cambiarUsuarioForm("nombre_usuario", e.target.value)} required />
                            </label>
                            <label>
                                Nombre
                                <input value={usuarioForm.nombre} onChange={(e) => cambiarUsuarioForm("nombre", e.target.value)} required />
                            </label>
                            <label>
                                Apellido
                                <input value={usuarioForm.apellido} onChange={(e) => cambiarUsuarioForm("apellido", e.target.value)} required />
                            </label>
                            <label>
                                Email
                                <input type="email" value={usuarioForm.email} onChange={(e) => cambiarUsuarioForm("email", e.target.value)} required />
                            </label>
                            <label>
                                Contraseña
                                <input
                                    type="password"
                                    value={usuarioForm.contrasena}
                                    onChange={(e) => cambiarUsuarioForm("contrasena", e.target.value)}
                                    required={!usuarioEditando}
                                    placeholder={usuarioEditando ? "Dejar vacío para no cambiar" : ""}
                                />
                            </label>
                            <label>
                                Ciudad
                                <input value={usuarioForm.ciudad} onChange={(e) => cambiarUsuarioForm("ciudad", e.target.value)} />
                            </label>
                            <label>
                                Posiciones
                                <input value={usuarioForm.posiciones_favoritas} onChange={(e) => cambiarUsuarioForm("posiciones_favoritas", e.target.value)} placeholder="Delantero, Mediocentro" />
                            </label>
                            <label>
                                Rol
                                <select value={usuarioForm.rol} onChange={(e) => cambiarUsuarioForm("rol", e.target.value)}>
                                    <option value="usuario">usuario</option>
                                    <option value="admin">admin</option>
                                </select>
                            </label>

                            <div className="acciones-admin">
                                <button type="submit">{usuarioEditando ? "Guardar usuario" : "Crear usuario"}</button>
                                {usuarioEditando && <button type="button" onClick={limpiarUsuarioForm}>Cancelar edición</button>}
                            </div>
                        </form>
                    </section>

                    <section className="panel-admin">
                        <h2>Usuarios registrados</h2>
                        <div className="lista-simple">
                            {usuarios.map((item) => (
                                <div className="fila-simple" key={item.id_usuario}>
                                    <div>
                                        <strong>{item.nombre_usuario}</strong>
                                        <p>{item.nombre} {item.apellido} - {item.email}</p>
                                        <p>{item.ciudad || "Sin ciudad"} - {item.rol || "usuario"}</p>
                                    </div>
                                    <div className="acciones-admin acciones-tarjeta">
                                        <button type="button" onClick={() => empezarEdicionUsuario(item)}>Editar</button>
                                        {Number(item.id_usuario) !== Number(usuario?.id_usuario) && (
                                            <button type="button" className="boton-peligro" onClick={() => eliminarUsuario(item.id_usuario)}>Eliminar</button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>
                </>
            )}

            {!cargando && vista === "pagos" && (
                <section className="panel-admin">
                    <h2>Pagos competitivos</h2>
                    <div className="lista-simple">
                        {pagos.length === 0 && <p className="estado">No hay pagos registrados.</p>}
                        {pagos.map((pago) => (
                            <div className="fila-simple" key={pago.id_pago}>
                                <div>
                                    <strong>{pago.usuario?.nombre_usuario || "Usuario"}</strong>
                                    <p>{pago.tipo_pago} - {pago.importe} EUR - {pago.estado_pago}</p>
                                </div>
                                {pago.estado_pago === "pendiente" ? (
                                    <div className="acciones-admin acciones-tarjeta">
                                        <button type="button" onClick={() => gestionarPago(pago.id_pago, "confirmar")}>Confirmar</button>
                                        <button type="button" className="boton-peligro" onClick={() => gestionarPago(pago.id_pago, "cancelar")}>Cancelar</button>
                                    </div>
                                ) : (
                                    <span>{pago.fecha_pago || pago.created_at}</span>
                                )}
                            </div>
                        ))}
                    </div>
                </section>
            )}
        </main>
    );
};

export default Admin;
