<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Paiement;
use App\Entity\User;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/paiement')]
#[IsGranted('ROLE_CLIENT')]
class PaiementController extends AbstractController
{
    #[Route(
        '/{id}',
        name: 'app_client_paiement_new',
        methods: ['GET', 'POST']
    )]
    public function new(
        Commande $commande,
        PaiementRepository $paiementRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        /*
         * Vérifier que la commande appartient bien au client connecté.
         */
        if (
            $commande->getUser() === null ||
            $commande->getUser()->getId() !== $user->getId()
        ) {
            throw $this->createAccessDeniedException(
                'Vous ne pouvez pas payer cette commande.'
            );
        }

        /*
         * Une commande doit être confirmée avant le paiement.
         */
        if ($commande->getStatut() !== 'CONFIRMEE') {
            $this->addFlash(
                'warning',
                'Cette commande doit être confirmée avant le paiement.'
            );

            return $this->redirectToRoute(
                'app_client_commande_confirmation',
                ['id' => $commande->getId()]
            );
        }

        /*
         * Vérifier si un paiement existe déjà.
         */
        $paiement = $paiementRepository->findOneBy([
            'commande' => $commande,
        ]);

        if ($paiement !== null) {
            $this->addFlash(
                'info',
                'Un paiement a déjà été déclaré pour cette commande.'
            );

            return $this->redirectToRoute(
                'app_client_commande_confirmation',
                ['id' => $commande->getId()]
            );
        }

        if ($request->isMethod('POST')) {

            if (!$this->isCsrfTokenValid(
                'paiement_' . $commande->getId(),
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Token CSRF invalide.'
                );
            }

            $mode = $request->request->get('mode');
            $reference = trim(
                (string) $request->request->get('reference')
            );

            $modesAutorises = [
                'ESPECES',
                'MOBILE_MONEY',
                'VIREMENT',
            ];

            if (!in_array($mode, $modesAutorises, true)) {
                $this->addFlash(
                    'danger',
                    'Mode de paiement invalide.'
                );

                return $this->render(
                    'client/paiement/new.html.twig',
                    [
                        'commande' => $commande,
                    ]
                );
            }

            /*
             * Création du paiement.
             */
            $paiement = new Paiement();

            $paiement->setCommande($commande);
            $paiement->setMode($mode);
            $paiement->setStatut('EN_ATTENTE');
            $paiement->setMontant($commande->getTotal());

            if ($reference !== '') {
                $paiement->setReference($reference);
            }

            $paiement->setUpdatedAt(
                new \DateTimeImmutable()
            );

            $entityManager->persist($paiement);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre déclaration de paiement a été enregistrée.'
            );

            return $this->redirectToRoute(
                'app_client_commande_confirmation',
                ['id' => $commande->getId()]
            );
        }

        return $this->render(
            'client/paiement/new.html.twig',
            [
                'commande' => $commande,
            ]
        );
    }
}

