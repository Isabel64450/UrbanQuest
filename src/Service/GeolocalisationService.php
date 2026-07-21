<?php

namespace App\Service;

class GeolocalisationService
{
    private const RAYON_TERRE_METRES = 6_371_000;

    /**
     * Distance en mètres entre deux points GPS (formule de Haversine).
     */
    public function calculerDistanceMetres(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLatRad = deg2rad($lat2 - $lat1);
        $deltaLngRad = deg2rad($lng2 - $lng1);

        $a = sin($deltaLatRad / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLngRad / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::RAYON_TERRE_METRES * $c;
    }
}