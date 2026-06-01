import { MapContainer, Marker, TileLayer, useMapEvents } from "react-leaflet";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

const marcadorDrafty = L.divIcon({
    className: "marcador-drafty",
    html: "<span></span>",
    iconSize: [28, 28],
    iconAnchor: [14, 14]
});

const ClickMapa = ({ onSeleccionar }) => {
    useMapEvents({
        click(evento) {
            onSeleccionar({
                latitud: Number(evento.latlng.lat.toFixed(7)),
                longitud: Number(evento.latlng.lng.toFixed(7))
            });
        }
    });

    return null;
};

const SelectorMapa = ({ coordenadas, onSeleccionar }) => {
    const centro = coordenadas?.latitud && coordenadas?.longitud
        ? [coordenadas.latitud, coordenadas.longitud]
        : [38.3452, -0.481];

    return (
        <div className="selector-mapa">
            <MapContainer center={centro} zoom={13} scrollWheelZoom className="mapa-crear-partido">
                <TileLayer
                    attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                />
                <ClickMapa onSeleccionar={onSeleccionar} />
                {coordenadas?.latitud && coordenadas?.longitud && (
                    <Marker position={[coordenadas.latitud, coordenadas.longitud]} icon={marcadorDrafty} />
                )}
            </MapContainer>
        </div>
    );
};

export default SelectorMapa;
