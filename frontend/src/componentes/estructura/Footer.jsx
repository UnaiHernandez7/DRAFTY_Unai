import { Link } from "react-router-dom";
import logotipoDrafty from "../../img/logotipo_drafty.svg";
import "./Footer.css";

const enlacesFooter = [
    { texto: "Inicio", ruta: "/" },
    { texto: "Equipos", ruta: "/equipos" },
    { texto: "Torneos", ruta: "/torneos" },
    { texto: "Competitivo", ruta: "/competitivo" }
];

const Footer = () => {
    return (
        <footer className="footer-drafty">
            <div className="footer-drafty__principal">
                <Link to="/" className="footer-drafty__marca" aria-label="DRAFTY">
                    <img src={logotipoDrafty} alt="DRAFTY" />
                </Link>

                <p className="footer-drafty__frase">
                    Conectando jugadores, organizando partidos.
                </p>

                <nav className="footer-drafty__enlaces" aria-label="Enlaces informativos">
                    {enlacesFooter.map((enlace) => (
                        <Link key={enlace.ruta} to={enlace.ruta}>
                            {enlace.texto}
                        </Link>
                    ))}
                </nav>
            </div>

            <div className="footer-drafty__inferior">
                <p>© 2026 DRAFTY. Todos los derechos reservados.</p>
                <p>DRAFTY es una plataforma diseñada para facilitar la organización de partidos de fútbol amateur.</p>
            </div>
        </footer>
    );
};

export default Footer;
