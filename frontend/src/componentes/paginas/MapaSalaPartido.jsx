import { MapContainer, Marker, TileLayer } from "react-leaflet";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

// Archivo propio del frontend de Drafty.
const marcadorDrafty = L.divIcon({
    className: "marcador-drafty",
    html: "<span></span>",
    iconSize: [28, 28],
    iconAnchor: [14, 14]
});

// Funcion auxiliar usada por este componente.
const MapaSalaPartido = ({ campo }) => {
    // Dato usado para pintar esta pantalla.
    const latitud = campo?.latitud === null || campo?.latitud === "" ? null : Number(campo?.latitud);
    // Dato usado para pintar esta pantalla.
    const longitud = campo?.longitud === null || campo?.longitud === "" ? null : Number(campo?.longitud);
    // Dato usado para pintar esta pantalla.
    const tieneCoordenadas = Number.isFinite(latitud)
        && Number.isFinite(longitud)
        && !(latitud === 0 && longitud === 0);

    if (!tieneCoordenadas) {
        // Vista que se muestra al usuario.
        return (
            <div className="mapa-sala-vacio">
                <p>No hay coordenadas guardadas para este campo.</p>
            </div>
        );
    }

    // Vista que se muestra al usuario.
    return (
        <div className="mapa-sala-wrapper">
            <MapContainer
                center={[latitud, longitud]}
                zoom={15}
                scrollWheelZoom={false}
                className="mapa-sala-partido"
            >
                <TileLayer
                    attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                />
                <Marker position={[latitud, longitud]} icon={marcadorDrafty} />
            </MapContainer>
        </div>
    );
};

export default MapaSalaPartido;
