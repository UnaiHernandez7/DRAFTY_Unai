import "./EncabezadoSeccion.css";

const EncabezadoSeccion = ({ titulo, descripcion, accion }) => (
    <section className="encabezado-seccion">
        <div>
            <h1>{titulo}</h1>
            {descripcion && <p>{descripcion}</p>}
        </div>
        {accion && <div className="encabezado-seccion-accion">{accion}</div>}
    </section>
);

export default EncabezadoSeccion;
