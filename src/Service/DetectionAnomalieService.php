<?php

namespace App\Service;

class DetectionAnomalieService
{
    /**
     * Très au-dessus de toute vitesse plausible en contexte piéton urbain
     * (une voiture en ville dépasse rarement 60-80 km/h) : marge confortable
     * contre les faux positifs liés à l'imprécision GPS, tout en détectant
     * une "téléportation" physiquement impossible entre deux validations.
     */
    private const SEUIL_VITESSE_ANORMALE_KMH = 150.0;

    /**
     * Vitesse implicite (km/h) entre deux positions/horodatages. Si les deux
     * horodatages sont identiques (ou inversés) et la distance non nulle, la
     * vitesse est considérée infinie plutôt que de diviser par zéro.
     */
    public function calculerVitesseKmh(float $distanceMetres, \DateTimeInterface $dateDebut, \DateTimeInterface $dateFin): float
    {
        $secondes = $dateFin->getTimestamp() - $dateDebut->getTimestamp();

        if ($secondes <= 0) {
            return $distanceMetres > 0.0 ? \PHP_FLOAT_MAX : 0.0;
        }

        return ($distanceMetres / $secondes) * 3.6;
    }

    public function estVitesseAnormale(float $vitesseKmh): bool
    {
        return $vitesseKmh > self::SEUIL_VITESSE_ANORMALE_KMH;
    }
}