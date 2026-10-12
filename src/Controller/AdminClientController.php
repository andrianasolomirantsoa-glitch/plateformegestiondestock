<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/clients')]
#[IsGranted('ROLE_ADMIN')]
class AdminClientController extends AbstractController
{
    #[Route('', name: 'app_admin_client_index', methods: ['GET'])]
    public function index(
        Request $request,
        Connection $connection
    ): Response {
        $search = trim(
            (string) $request->query->get('q', '')
        );

        if ($search !== '') {
            $clients = $connection->fetchAllAssociative(
                'SELECT *
                 FROM clients
                 WHERE nom LIKE :search
                    OR prenom LIKE :search
                    OR email LIKE :search
                    OR telephone LIKE :search
                    OR ville LIKE :search
                 ORDER BY created_at DESC',
                [
                    'search' => '%' . $search . '%',
                ]
            );
        } else {
            $clients = $connection->fetchAllAssociative(
                'SELECT *
                 FROM clients
                 ORDER BY created_at DESC'
            );
        }

        return $this->render(
            'admin/client/index.html.twig',
            [
                'clients' => $clients,
                'search' => $search,
            ]
        );
    }

    #[Route(
        '/{id}',
        name: 'app_admin_client_show',
        methods: ['GET']
    )]
    public function show(
        int $id,
        Connection $connection
    ): Response {
        $client = $connection->fetchAssociative(
            'SELECT *
             FROM clients
             WHERE id = :id',
            [
                'id' => $id,
            ]
        );

        if ($client === false) {
            throw $this->createNotFoundException(
                'Client introuvable.'
            );
        }

        $commandes = [];
        $totalCommandes = 0.0;
        $nombreCommandes = 0;

        /*
         * Si le client possède un compte User lié,
         * récupérer ses commandes ainsi que le paiement
         * et la livraison associés.
         */
        if ($client['user_id'] !== null) {
            $commandes = $connection->fetchAllAssociative(
                'SELECT
                    c.id,
                    c.numero,
                    c.statut,
                    c.total,
                    c.created_at,
                    p.mode AS paiement_mode,
                    p.statut AS paiement_statut,
                    l.mode_livraison,
                    l.statut AS livraison_statut
                 FROM commandes c
                 LEFT JOIN paiements p
                    ON p.commande_id = c.id
                 LEFT JOIN livraisons l
                    ON l.commande_id = c.id
                 WHERE c.user_id = :user_id
                 ORDER BY c.created_at DESC',
                [
                    'user_id' => $client['user_id'],
                ]
            );

            $nombreCommandes = count($commandes);

            foreach ($commandes as $commande) {
                $totalCommandes += (float) $commande['total'];
            }
        }

        /*
         * Rechercher le compte User lié au client.
         */
        $user = null;

        if ($client['user_id'] !== null) {
            $user = $connection->fetchAssociative(
                'SELECT
                    id,
                    email,
                    nom,
                    prenom,
                    roles,
                    actif,
                    created_at,
                    updated_at
                 FROM users
                 WHERE id = :id',
                [
                    'id' => $client['user_id'],
                ]
            );
        }

        return $this->render(
            'admin/client/show.html.twig',
            [
                'client' => $client,
                'user' => $user,
                'commandes' => $commandes,
                'nombreCommandes' => $nombreCommandes,
                'totalCommandes' => $totalCommandes,
            ]
        );
    }

    #[Route(
        '/{id}/statut',
        name: 'app_admin_client_toggle',
        methods: ['POST']
    )]
    public function toggleStatus(
        int $id,
        Request $request,
        Connection $connection
    ): Response {
        if (!$this->isCsrfTokenValid(
            'statut_client_' . $id,
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }
    
        $client = $connection->fetchAssociative(
            'SELECT id, actif, user_id
             FROM clients
             WHERE id = :id',
            [
                'id' => $id,
            ]
        );
    
        if ($client === false) {
            throw $this->createNotFoundException(
                'Client introuvable.'
            );
        }
    
        $nouvelEtat = ((int) $client['actif'] === 1)
            ? 0
            : 1;
    
        $connection->beginTransaction();
    
        try {
            /*
             * Mise à jour du profil client.
             */
            $connection->executeStatement(
                'UPDATE clients
                 SET actif = :actif
                 WHERE id = :id',
                [
                    'actif' => $nouvelEtat,
                    'id' => $id,
                ]
            );
    
            /*
             * Si un compte User est lié,
             * synchroniser également son statut.
             */
            if ($client['user_id'] !== null) {
                $connection->executeStatement(
                    'UPDATE users
                     SET actif = :actif,
                         updated_at = NOW()
                     WHERE id = :user_id',
                    [
                        'actif' => $nouvelEtat,
                        'user_id' => $client['user_id'],
                    ]
                );
            }
    
            $connection->commit();
    
        } catch (\Throwable $e) {
            $connection->rollBack();
    
            throw $e;
        }
    
        if ($nouvelEtat === 1) {
            $this->addFlash(
                'success',
                'Le client et son compte utilisateur ont été activés.'
            );
        } else {
            $this->addFlash(
                'warning',
                'Le client et son compte utilisateur ont été désactivés.'
            );
        }
    
        return $this->redirectToRoute(
            'app_admin_client_index'
        );
    }
}

