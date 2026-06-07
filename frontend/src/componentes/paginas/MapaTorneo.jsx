import { MapContainer, Marker, Popup, TileLayer } from "react-leaflet";
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
const MapaTorneo = ({ torneo }) => {
    // Dato usado para pintar esta pantalla.
    const latitud = torneo?.latitud === null || torneo?.latitud === "" ? null : Number(torneo?.latitud);
    // Dato usado para pintar esta pantalla.
    const longitud = torneo?.longitud === null || torneo?.longitud === "" ? null : Number(torneo?.longitud);
    // Dato usado para pintar esta pantalla.
    const tieneCoordenadas = Number.isFinite(latitud) && Number.isFinite(longitud) && !(latitud === 0 && longitud === 0);

    if (!tieneCoordenadas) {
        // Vista que se muestra al usuario.
        return (
            <div className="mapa-torneo-vacio">
                <p>No hay ubicación exacta disponible para este torneo.</p>
            </div>
        );
    }

    // Dato usado para pintar esta pantalla.
    const enlaceMaps = `https://www.google.com/maps?q=${latitud},${longitud}`;

    // Vista que se muestra al usuario.
    return (
        <div className="mapa-torneo-wrapper">
            <MapContainer center={[latitud, longitud]} zoom={15} scrollWheelZoom={false} className="mapa-torneo">
                <TileLayer
                    attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                />
                <Marker position={[latitud, longitud]} icon={marcadorDrafty}>
                    <Popup>{torneo.nombre_lugar || torneo.nombre_torneo}</Popup>
                </Marker>
            </MapContainer>
            <a className="torneo-maps-link" href={enlaceMaps} target="_blank" rel="noreferrer">
                Abrir en Google Maps
            </a>
        </div>
    );
};

export default MapaTorneo;
