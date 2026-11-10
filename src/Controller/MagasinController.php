<?php

namespace App\Controller;

use App\Repository\MagasinRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MagasinController extends AbstractController
{
    #[Route('/magasins', name: 'app_magasins', methods: ['GET'])]
    public function index(MagasinRepository $magasinRepository): Response
    {
        $magasins = $magasinRepository->findBy(
            ['actif' => true],
            ['ville' => 'ASC', 'nom' => 'ASC']
        );

        return $this->render('magasin/index.html.twig', [
            'magasins' => $magasins,
        ]);
    }

    #[Route('/magasins/statistiques', name: 'app_magasins_statistiques', methods: ['GET'])]
    public function statistiques(MagasinRepository $magasinRepository): Response
    {
        // Tous les magasins actifs.
        $magasins = $magasinRepository->findBy(
            ['actif' => true],
            ['ville' => 'ASC', 'nom' => 'ASC']
        );

        // Nombre total de magasins actifs.
        $totalMagasins = count($magasins);

        // Nombre de magasins ayant des coordonnées GPS.
        $magasinsGeolocalises = 0;

        foreach ($magasins as $magasin) {
            if (
                $magasin->getLatitude() !== null &&
                $magasin->getLongitude() !== null
            ) {
                $magasinsGeolocalises++;
            }
        }

        // Répartition par ville.
        $parVille = $magasinRepository->createQueryBuilder('m')
            ->select('m.ville AS nom, COUNT(m.id) AS total')
            ->where('m.actif = :actif')
            ->setParameter('actif', true)
            ->groupBy('m.ville')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();

        // Répartition par région.
        $parRegion = $magasinRepository->createQueryBuilder('m')
            ->select('m.region AS nom, COUNT(m.id) AS total')
            ->where('m.actif = :actif')
            ->setParameter('actif', true)
            ->groupBy('m.region')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();

        // Nombre de villes et de régions distinctes.
        $totalVilles = count(array_unique(
            array_map(fn($m) => $m->getVille(), $magasins)
        ));

        $totalRegions = count(array_unique(
            array_filter(array_map(
                fn($m) => $m->getRegion(),
                $magasins
            ))
        ));

        return $this->render('magasin/statistiques.html.twig', [
            'magasins' => $magasins,
            'totalMagasins' => $totalMagasins,
            'magasinsGeolocalises' => $magasinsGeolocalises,
            'totalVilles' => $totalVilles,
            'totalRegions' => $totalRegions,
            'parVille' => $parVille,
            'parRegion' => $parRegion,
        ]);
    }
}
