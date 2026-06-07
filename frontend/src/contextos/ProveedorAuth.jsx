import { createContext, useContext, useState, useEffect, useCallback } from "react";
import api from "../api/api.js";

// Crear contexto
// Archivo propio del frontend de Drafty.
const AuthContext = createContext();

// Busca un token aunque el backend lo devuelva con otro nombre o anidado.
const extraerToken = (datos) => {
  if (!datos || typeof datos !== "object") {
    return null;
  }

  const clavesToken = ["token", "access_token", "auth_token", "plainTextToken", "bearer_token", "api_token"];

  for (const clave of clavesToken) {
    if (typeof datos[clave] === "string" && datos[clave].length > 10) {
      return datos[clave];
    }
  }

  for (const valor of Object.values(datos)) {
    if (valor && typeof valor === "object") {
      const token = extraerToken(valor);

      if (token) {
        return token;
      }
    }
  }

  return null;
};

// Provider
export function ProveedorAuth({ children }) {
  // Estado que guarda informacion de la pantalla.
  const [usuario, setUsuario] = useState(null);
  // Estado que guarda informacion de la pantalla.
  const [token, setToken] = useState(localStorage.getItem("token"));
  // Estado que guarda informacion de la pantalla.
  const [cargandoAuth, setCargandoAuth] = useState(Boolean(localStorage.getItem("token")));

  // Obtener usuario logueado
  const obtenerPerfil = useCallback(async () => {
    try {
      // Dato usado para pintar esta pantalla.
      const res = await api.get("/perfil");
      setUsuario(res.data);
    } catch {
      logout();
    } finally {
      setCargandoAuth(false);
    }
  }, []);

  // Cuando hay token, cargar usuario
  useEffect(() => {
    if (token) {
      setCargandoAuth(true);
      obtenerPerfil();
    } else {
      setCargandoAuth(false);
    }
    // eslint-disable-next-line react-hooks/set-state-in-effect
  }, [token]);

  // Login con usuario o email.
  async function login(identificador, contrasena) {
    const identificadorLimpio = identificador.trim();

    if (!identificadorLimpio || !contrasena) {
      return {
        ok: false,
        mensaje: "Introduce tu usuario o email y tu contraseña."
      };
    }

    try {
      // Dato usado para pintar esta pantalla.
      const datosLogin = {
        identificador: identificadorLimpio,
        contrasena
      };

      if (identificadorLimpio.includes("@")) {
        datosLogin.email = identificadorLimpio;
      }

      const res = await api.post("/login", datosLogin);
      // Acepta varias formas de respuesta por si el backend desplegado cambia el nombre del token.
      const datosRespuesta = res.data?.data || res.data || {};
      const tokenRespuesta = extraerToken(datosRespuesta);
      const usuarioRespuesta = datosRespuesta.usuario || datosRespuesta.user || null;

      if (!tokenRespuesta) {
        const campos = Object.keys(datosRespuesta).join(", ") || "sin campos";

        return {
          ok: false,
          mensaje: `El servidor ha respondido sin token. Campos recibidos: ${campos}.`
        };
      }

      localStorage.setItem("token", tokenRespuesta);
      setToken(tokenRespuesta);

      if (usuarioRespuesta) {
        setUsuario(usuarioRespuesta);
      } else {
        const perfil = await api.get("/perfil");
        setUsuario(perfil.data);
      }

      setCargandoAuth(false);

      return { ok: true };
    } catch (error) {
      // Dato usado para pintar esta pantalla.
      const errores = error.response?.data?.errors;
      // Dato usado para pintar esta pantalla.
      const primerError = errores ? Object.values(errores).flat()[0] : null;
      // Dato usado para pintar esta pantalla.
      const mensajeConexion = error.response
        ? null
        : "No se puede conectar con el servidor.";

      return {
        ok: false,
        mensaje: primerError || error.response?.data?.mensaje || mensajeConexion || "No se ha podido iniciar sesión."
      };
    }
  }

  // Registro 
  async function register(data) {
    try {
      // Dato usado para pintar esta pantalla.
      const res = await api.post("/register", data);

      return { ok: true, email: res.data.email, mensaje: res.data.mensaje };
    } catch (error) {
      // Dato usado para pintar esta pantalla.
      const errores = error.response?.data?.errors;
      // Dato usado para pintar esta pantalla.
      const primerError = errores ? Object.values(errores).flat()[0] : null;
      // Dato usado para pintar esta pantalla.
      const emailPendiente = error.response?.data?.email;
      // Dato usado para pintar esta pantalla.
      const mensajeConexion = error.response
        ? null
        : "No se pudo conectar con el servidor. Revisa que Docker/backend este arrancado y que /api responda.";

      return {
        ok: false,
        pendiente: !!emailPendiente,
        email: emailPendiente,
        mensaje: primerError || error.response?.data?.mensaje || mensajeConexion || "No se pudo crear la cuenta."
      };
    }
  }

  // Funcion que llama al servidor y actualiza la pantalla.
  async function verificarCodigo(email, codigo) {
    try {
      // Dato usado para pintar esta pantalla.
      const res = await api.post("/verificar-codigo", { email, codigo });

      setToken(res.data.token);
      localStorage.setItem("token", res.data.token);
      sessionStorage.removeItem("email_verificacion");
      setUsuario(res.data.usuario);
      setCargandoAuth(false);

      return { ok: true };
    } catch (error) {
      // Dato usado para pintar esta pantalla.
      const errores = error.response?.data?.errors;
      // Dato usado para pintar esta pantalla.
      const primerError = errores ? Object.values(errores).flat()[0] : null;

      return {
        ok: false,
        mensaje: primerError || error.response?.data?.mensaje || "No se ha podido verificar el código."
      };
    }
  }

  // Funcion que llama al servidor y actualiza la pantalla.
  async function reenviarCodigo(email) {
    try {
      // Dato usado para pintar esta pantalla.
      const res = await api.post("/reenviar-codigo", { email });

      return { ok: true, mensaje: res.data.mensaje };
    } catch (error) {
      // Dato usado para pintar esta pantalla.
      const errores = error.response?.data?.errors;
      // Dato usado para pintar esta pantalla.
      const primerError = errores ? Object.values(errores).flat()[0] : null;

      return {
        ok: false,
        mensaje: primerError || error.response?.data?.mensaje || "No se ha podido reenviar el código."
      };
    }
  }

  // Logout
  function logout() {
    setUsuario(null);
    setToken(null);
    setCargandoAuth(false);
    localStorage.removeItem("token");
  }

  // Vista que se muestra al usuario.
  return (
    <AuthContext.Provider
      value={{
        usuario,
        token,
        cargandoAuth,
        login,
        register,
        verificarCodigo,
        reenviarCodigo,
        logout,
        isAuth: !!usuario,
        isAdmin: usuario?.rol === "admin",
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

// Hook 
export function useAuth() {
  return useContext(AuthContext);
}
