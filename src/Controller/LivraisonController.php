<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Livraison;
use App\Entity\User;
use App\Repository\LivraisonRepository;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/livraison')]
#[IsGranted('ROLE_CLIENT')]
class LivraisonController extends AbstractController
{
    #[Route(
        '/{id}',
        name: 'app_client_livraison_new',
        methods: ['GET', 'POST']
    )]
    public function new(
        Commande $commande,
        LivraisonRepository $livraisonRepository,
        PaiementRepository $paiementRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        /*
         * Vérifier que la commande appartient au client connecté.
         */
        if (
            $commande->getUser() === null ||
            $commande->getUser()->getId() !== $user->getId()
        ) {
            throw $this->createAccessDeniedException(
                'Vous ne pouvez pas gérer la livraison de cette commande.'
            );
        }

        /*
         * La commande doit être confirmée.
         */
        if ($commande->getStatut() !== 'CONFIRMEE') {
            $this->addFlash(
                'warning',
                'La commande doit être confirmée avant de demander la livraison.'
            );

            return $this->redirectToRoute(
                'app_client_commande_confirmation',
                ['id' => $commande->getId()]
            );
        }

        /*
         * Le paiement doit être validé.
         */
        $paiement = $paiementRepository->findOneBy([
            'commande' => $commande,
        ]);

        if ($paiement === null || $paiement->getStatut() !== 'PAYE') {
            $this->addFlash(
                'warning',
                'Le paiement doit être validé avant de demander la livraison.'
            );

            return $this->redirectToRoute(
                'app_client_commande_confirmation',
                ['id' => $commande->getId()]
            );
        }

        /*
         * Vérifier si une livraison existe déjà.
         */
        $livraisonExistante = $livraisonRepository->findOneBy([
            'commande' => $commande,
        ]);

        if ($livraisonExistante !== null) {
            $this->addFlash(
                'info',
                'Une livraison existe déjà pour cette commande.'
            );

            return $this->redirectToRoute(
                'app_client_commande_confirmation',
                ['id' => $commande->getId()]
            );
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'livraison_' . $commande->getId(),
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Token CSRF invalide.'
                );
            }

            $modeLivraison = $request->request->get('mode_livraison');
            $adresseLivraison = trim(
                (string) $request->request->get('adresse_livraison')
            );
            $ville = trim(
                (string) $request->request->get('ville')
            );
            $pays = trim(
                (string) $request->request->get('pays')
            );

            $modesAutorises = [
                'DOMICILE',
                'POINT_RELAIS',
                'RETRAIT',
            ];

            if (!in_array($modeLivraison, $modesAutorises, true)) {
                $this->addFlash(
                    'danger',
                    'Mode de livraison invalide.'
                );

                return $this->render(
                    'client/livraison/new.html.twig',
                    [
                        'commande' => $commande,
                    ]
                );
            }

            if (
                $adresseLivraison === '' ||
                $ville === '' ||
                $pays === ''
            ) {
                $this->addFlash(
                    'danger',
                    'Veuillez remplir tous les champs obligatoires.'
                );

                return $this->render(
                    'client/livraison/new.html.twig',
                    [
                        'commande' => $commande,
                    ]
                );
            }

            $livraison = new Livraison();

            $livraison->setCommande($commande);
            $livraison->setModeLivraison($modeLivraison);
            $livraison->setStatut('EN_ATTENTE');
            $livraison->setAdresseLivraison($adresseLivraison);
            $livraison->setVille($ville);
            $livraison->setPays($pays);
            $livraison->setUpdatedAt(
                new \DateTimeImmutable()
            );

            $entityManager->persist($livraison);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre demande de livraison a été enregistrée.'
            );

            return $this->redirectToRoute(
                'app_client_commande_confirmation',
                ['id' => $commande->getId()]
            );
        }

        return $this->render(
            'client/livraison/new.html.twig',
            [
                'commande' => $commande,
            ]
        );
    }
}
