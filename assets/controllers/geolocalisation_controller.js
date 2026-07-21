import { Controller } from '@hotwired/stimulus';
import * as Turbo from '@hotwired/turbo';

const INTERVALLE_MIN_MS = 5000;
const DEPLACEMENT_MIN_METRES = 5;

// Formule de Haversine, dupliquée côté client uniquement pour décider si une
// position est assez différente de la précédente pour valoir un envoi.
// Le serveur ne fait jamais confiance à cette distance : il recalcule tout.
function distanceMetres(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const toRad = (deg) => (deg * Math.PI) / 180;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a = Math.sin(dLat / 2) ** 2
        + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;

    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

export default class extends Controller {
    static values = { url: String };
    static targets = ['latitude', 'longitude'];

    connect() {
        this.derniereEnvoi = null;
        this.derniereDatePosition = 0;

        if (!navigator.geolocation) {
            this.afficherErreur('La géolocalisation n\'est pas disponible sur cet appareil.');
            return;
        }

        this.watchId = navigator.geolocation.watchPosition(
            (position) => this.onPosition(position),
            (erreur) => this.onErreur(erreur),
            { enableHighAccuracy: true }
        );
    }

    disconnect() {
        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
        }
    }

    onPosition(position) {
        const { latitude, longitude, accuracy } = position.coords;

        // Toujours tenus à jour, indépendamment du throttle d'envoi : le
        // formulaire de réponse doit soumettre la position la plus fraîche.
        if (this.hasLatitudeTarget) {
            this.latitudeTarget.value = latitude;
        }
        if (this.hasLongitudeTarget) {
            this.longitudeTarget.value = longitude;
        }

        const maintenant = Date.now();

        if (maintenant - this.derniereDatePosition < INTERVALLE_MIN_MS) {
            return;
        }

        if (this.derniereEnvoi) {
            const deplacement = distanceMetres(
                this.derniereEnvoi.latitude,
                this.derniereEnvoi.longitude,
                latitude,
                longitude
            );

            if (deplacement < DEPLACEMENT_MIN_METRES) {
                return;
            }
        }

        this.derniereDatePosition = maintenant;
        this.derniereEnvoi = { latitude, longitude };

        fetch(this.urlValue, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'text/vnd.turbo-stream.html',
            },
            body: JSON.stringify({ latitude, longitude, accuracy }),
        })
            .then((reponse) => reponse.text())
            .then((html) => Turbo.renderStreamMessage(html));
    }

    onErreur(erreur) {
        if (erreur.code === erreur.PERMISSION_DENIED) {
            this.afficherErreur('Merci d\'autoriser la géolocalisation pour continuer la partie.');
        } else {
            this.afficherErreur('Impossible de récupérer votre position pour le moment.');
        }
    }

    afficherErreur(message) {
        const zone = document.getElementById('zone-jeu');
        if (zone) {
            zone.innerHTML = `<p class="rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm font-medium text-amber-300">${message}</p>`;
        }
    }
}