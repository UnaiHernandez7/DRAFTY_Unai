import { createContext, useContext, useState, useEffect, useCallback } from "react";
import api from "../api/api.js";

// Crear contexto
const AuthContext = createContext();

// Provider
export function ProveedorAuth({ children }) {
  const [usuario, setUsuario] = useState(null);
  const [token, setToken] = useState(localStorage.getItem("token"));
  const [cargandoAuth, setCargandoAuth] = useState(Boolean(localStorage.getItem("token")));

  // Obtener usuario logueado
  const obtenerPerfil = useCallback(async () => {
    try {
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

  // Login
  async function login(identificador, contrasena) {
    try {
      const res = await api.post("/login", { identificador, contrasena });

      setToken(res.data.token);
      localStorage.setItem("token", res.data.token);
      setUsuario(res.data.usuario);
      setCargandoAuth(false);

      return true;
    } catch {
      return false;
    }
  }

  // Registro 
  async function register(data) {
    try {
      const res = await api.post("/register", data);

      return { ok: true, email: res.data.email, mensaje: res.data.mensaje };
    } catch (error) {
      const errores = error.response?.data?.errors;
      const primerError = errores ? Object.values(errores).flat()[0] : null;
      const emailPendiente = error.response?.data?.email;
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

  async function verificarCodigo(email, codigo) {
    try {
      const res = await api.post("/verificar-codigo", { email, codigo });

      setToken(res.data.token);
      localStorage.setItem("token", res.data.token);
      sessionStorage.removeItem("email_verificacion");
      setUsuario(res.data.usuario);
      setCargandoAuth(false);

      return { ok: true };
    } catch (error) {
      const errores = error.response?.data?.errors;
      const primerError = errores ? Object.values(errores).flat()[0] : null;

      return {
        ok: false,
        mensaje: primerError || error.response?.data?.mensaje || "No se ha podido verificar el código."
      };
    }
  }

  async function reenviarCodigo(email) {
    try {
      const res = await api.post("/reenviar-codigo", { email });

      return { ok: true, mensaje: res.data.mensaje };
    } catch (error) {
      const errores = error.response?.data?.errors;
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
