<?php

namespace App\DataFixtures;

use App\Entity\Parcours;
use App\Entity\Etape;
use App\Entity\Equipe;
use App\Entity\Joueur;
use App\Enum\StatutParcours;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Parcours
        $parcours = new Parcours();

        $parcours->setNom('Découverte autour de la gare de Bègles');
        $parcours->setDescription(
            'Parcours test autour de la gare et des rues de Bègles'
        );
        $parcours->setVille('Bègles');
        $parcours->setStatut(StatutParcours::Publie);
        $parcours->setDateCreation(new \DateTimeImmutable());
        $parcours->setEstArchive(false);

        $manager->persist($parcours);


        // Etapes GPS
        $etapes = [
            [
                'libelle' => 'Départ Gare de Bègles',
                'latitude' => '44.8086000',
                'longitude' => '-0.5478000',
            ],
            [
                'libelle' => 'Point rue Ferdinand',
                'latitude' => '44.8087000',
                'longitude' => '-0.5479000',
            ],
            [
                'libelle' => 'Point découverte',
                'latitude' => '44.8088000',
                'longitude' => '-0.5480000',
            ],
            [
                'libelle' => 'Arrivée du parcours',
                'latitude' => '44.8089000',
                'longitude' => '-0.5481000',
            ],
        ];


        $ordre = 1;

        foreach ($etapes as $data) {

            $etape = new Etape();

            $etape->setLibelle($data['libelle']);
            $etape->setConsigne('Rendez-vous à ce point GPS');
            $etape->setOrdre($ordre);

            $etape->setLatitude($data['latitude']);
            $etape->setLongitude($data['longitude']);

            $etape->setRayonValidationMetres(20);
            $etape->setPoints(10);
            $etape->setNombreEchecsAvantIndice(3);
            $etape->setIndice('Indice de démonstration');
            $etape->setParcours($parcours);

            $manager->persist($etape);

            $ordre++;
        }


        // Equipe test
        $equipe = new Equipe();

        $equipe->setNom('Equipe Test Bègles');
        $equipe->setCodeAcces('BEGLES2026');
        $equipe->setDateDemarrage(new \DateTimeImmutable());
        $equipe->setParcours($parcours);

        $manager->persist($equipe);


        // Joueur 1
        $joueur1 = new Joueur();

        $joueur1->setPseudo('Alice');
        $joueur1->setCodePin(
            password_hash('1234', PASSWORD_DEFAULT)
        );
        $joueur1->setEquipe($equipe);

        $manager->persist($joueur1);


        // Joueur 2
        $joueur2 = new Joueur();

        $joueur2->setPseudo('Bob');
        $joueur2->setCodePin(
            password_hash('5678', PASSWORD_DEFAULT)
        );
        $joueur2->setEquipe($equipe);

        $manager->persist($joueur2);


        $manager->flush();
    }
}