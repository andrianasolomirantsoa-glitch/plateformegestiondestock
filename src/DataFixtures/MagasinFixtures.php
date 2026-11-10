<?php

namespace App\DataFixtures;

use App\Entity\Magasin;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MagasinFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $magasins = [
            // Antananarivo
            [
                'nom' => 'Jumbo Score Talatamaty',
                'ville' => 'Talatamaty',
                'region' => 'Analamanga',
            ],
            [
                'nom' => 'Jumbo Score Itaosy',
                'ville' => 'Itaosy',
                'region' => 'Analamanga',
            ],
            [
                'nom' => 'Jumbo Score Antsahabe',
                'ville' => 'Antananarivo',
                'region' => 'Analamanga',
            ],
            [
                'nom' => 'Jumbo Score Digue',
                'ville' => 'Antananarivo',
                'region' => 'Analamanga',
                'adresse' => 'Centre Commercial Akoor Digue, PK 4,5 Route Digue',
            ],
            [
                'nom' => 'Jumbo Score Ankorondrano',
                'ville' => 'Antananarivo',
                'region' => 'Analamanga',
            ],
            [
                'nom' => 'Jumbo Score Tanjombato',
                'ville' => 'Tanjombato',
                'region' => 'Analamanga',
            ],

            // Antsiranana (Diego)
            [
                'nom' => 'Jumbo Score Diego 1',
                'ville' => 'Antsiranana',
                'region' => 'Diana',
            ],
            [
                'nom' => 'Jumbo Score Diego 2',
                'ville' => 'Antsiranana',
                'region' => 'Diana',
            ],

            // Toamasina (Tamatave)
            [
                'nom' => 'Jumbo Score Tamatave 1',
                'ville' => 'Toamasina',
                'region' => 'Atsinanana',
            ],
            [
                'nom' => 'Jumbo Score Tamatave 2',
                'ville' => 'Toamasina',
                'region' => 'Atsinanana',
            ],

            // Autres villes
            [
                'nom' => 'Jumbo Score Antsirabe',
                'ville' => 'Antsirabe',
                'region' => 'Vakinankaratra',
            ],
            [
                'nom' => 'Jumbo Score Majunga',
                'ville' => 'Mahajanga',
                'region' => 'Boeny',
            ],
            [
                'nom' => 'Jumbo Score Fianarantsoa',
                'ville' => 'Fianarantsoa',
                'region' => 'Haute Matsiatra',
            ],
            [
                'nom' => 'Jumbo Score Toliara',
                'ville' => 'Toliara',
                'region' => 'Atsimo-Andrefana',
            ],
        ];

        foreach ($magasins as $donnees) {
            // Vérifier si le magasin existe déjà pour éviter les doublons.
            $magasin = $manager->getRepository(Magasin::class)->findOneBy([
                'nom' => $donnees['nom'],
                'ville' => $donnees['ville'],
            ]);

            if ($magasin !== null) {
                continue;
            }

            $magasin = new Magasin();
            $magasin
                ->setNom($donnees['nom'])
                ->setVille($donnees['ville'])
                ->setRegion($donnees['region'])
                ->setAdresse($donnees['adresse'] ?? null)
                ->setLatitude(null)
                ->setLongitude(null)
                ->setActif(true);

            $manager->persist($magasin);
        }

        $manager->flush();
    }
}
