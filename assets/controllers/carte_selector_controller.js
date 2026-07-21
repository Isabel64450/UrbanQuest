import { Controller } from '@hotwired/stimulus';
import * as L from 'leaflet';
import 'leaflet/dist/leaflet.min.css';

/* stimulusFetch: 'lazy' */

// Centre par défaut : Bordeaux, ville des parcours de démonstration du projet.
const CENTRE_PAR_DEFAUT = [44.837789, -0.579180];

export default class extends Controller {
    static targets = ['carte', 'latitude', 'longitude'];
    static values = { latitude: Number, longitude: Number };

    connect() {
        const aDesCoordonnees = 0 !== this.latitudeValue || 0 !== this.longitudeValue;
        const centre = aDesCoordonnees ? [this.latitudeValue, this.longitudeValue] : CENTRE_PAR_DEFAUT;

        this.map = L.map(this.carteTarget).setView(centre, aDesCoordonnees ? 17 : 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(this.map);

        this.marker = aDesCoordonnees ? this.placerMarqueur(centre) : null;

        this.map.on('click', (event) => {
            if (this.marker) {
                this.marker.setLatLng(event.latlng);
            } else {
                this.marker = this.placerMarqueur(event.latlng);
            }
            this.mettreAJourChamps(event.latlng);
        });
    }

    disconnect() {
        this.map?.remove();
    }

    placerMarqueur(latlng) {
        const marqueur = L.marker(latlng, { draggable: true }).addTo(this.map);
        marqueur.on('dragend', () => this.mettreAJourChamps(marqueur.getLatLng()));

        return marqueur;
    }

    mettreAJourChamps(latlng) {
        this.latitudeTarget.value = latlng.lat;
        this.longitudeTarget.value = latlng.lng;
    }
}