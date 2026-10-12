<?php

namespace App\Controller;

use App\Entity\Fournisseur;
use App\Repository\FournisseurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/fournisseurs')]
#[IsGranted('ROLE_ADMIN')]
class FournisseurController extends AbstractController
{
    /*
     * =========================================================
     * LISTE DES FOURNISSEURS
     * =========================================================
     */
    #[Route('', name: 'app_fournisseur_index', methods: ['GET'])]
    public function index(
        Request $request,
        FournisseurRepository $fournisseurRepository
    ): Response {
        $recherche = trim(
            (string) $request->query->get('q', '')
        );

        $fournisseurs = $fournisseurRepository->findBy(
            [],
            ['nom' => 'ASC']
        );

        /*
         * Recherche côté PHP
         */
        if ($recherche !== '') {

            $rechercheMin = mb_strtolower($recherche);

            $fournisseurs = array_filter(
                $fournisseurs,
                function (Fournisseur $fournisseur) use ($rechercheMin): bool {

                    $texte = mb_strtolower(
                        ($fournisseur->getNom() ?? '') . ' ' .
                        ($fournisseur->getEmail() ?? '') . ' ' .
                        ($fournisseur->getTelephone() ?? '') . ' ' .
                        ($fournisseur->getAdresse() ?? '') . ' ' .
                        ($fournisseur->getVille() ?? '') . ' ' .
                        ($fournisseur->getPays() ?? '')
                    );

                    return mb_strpos(
                        $texte,
                        $rechercheMin
                    ) !== false;
                }
            );
        }

        /*
         * Statistiques
         */
        $nombreFournisseurs = count($fournisseurs);
        $fournisseursActifs = 0;
        $fournisseursInactifs = 0;

        foreach ($fournisseurs as $fournisseur) {

            if ($fournisseur->isActif()) {
                $fournisseursActifs++;
            } else {
                $fournisseursInactifs++;
            }
        }

        return $this->render(
            'admin/fournisseur/index.html.twig',
            [
                'fournisseurs' => $fournisseurs,
                'recherche' => $recherche,
                'nombreFournisseurs' => $nombreFournisseurs,
                'fournisseursActifs' => $fournisseursActifs,
                'fournisseursInactifs' => $fournisseursInactifs,
            ]
        );
    }


    /*
     * =========================================================
     * NOUVEAU FOURNISSEUR
     * =========================================================
     */
    #[Route(
        '/nouveau',
        name: 'app_fournisseur_new',
        methods: ['GET', 'POST']
    )]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {

        $fournisseur = new Fournisseur();

        if ($request->isMethod('POST')) {

            /*
             * CSRF
             */
            if (!$this->isCsrfTokenValid(
                'creer_fournisseur',
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Token CSRF invalide.'
                );
            }


            /*
             * Nom
             */
            $nom = trim(
                (string) $request->request->get('nom', '')
            );

            if ($nom === '') {

                $this->addFlash(
                    'error',
                    'Le nom du fournisseur est obligatoire.'
                );

                return $this->redirectToRoute(
                    'app_fournisseur_new'
                );
            }


            if (mb_strlen($nom) > 150) {

                $this->addFlash(
                    'error',
                    'Le nom du fournisseur ne doit pas dépasser 150 caractères.'
                );

                return $this->redirectToRoute(
                    'app_fournisseur_new'
                );
            }


            /*
             * Email
             */
            $email = trim(
                (string) $request->request->get('email', '')
            );

            if ($email !== '' &&
                !filter_var($email, FILTER_VALIDATE_EMAIL)
            ) {

                $this->addFlash(
                    'error',
                    'L’adresse e-mail du fournisseur est invalide.'
                );

                return $this->redirectToRoute(
                    'app_fournisseur_new'
                );
            }


            /*
             * Téléphone
             */
            $telephone = trim(
                (string) $request->request->get(
                    'telephone',
                    ''
                )
            );

            /*
             * Adresse
             */
            $adresse = trim(
                (string) $request->request->get(
                    'adresse',
                    ''
                )
            );

            /*
             * Ville
             */
            $ville = trim(
                (string) $request->request->get(
                    'ville',
                    ''
                )
            );

            /*
             * Pays
             */
            $pays = trim(
                (string) $request->request->get(
                    'pays',
                    ''
                )
            );


            /*
             * Valeurs par défaut
             */
            $fournisseur
                ->setNom($nom)
                ->setEmail($email !== '' ? $email : null)
                ->setTelephone(
                    $telephone !== ''
                        ? $telephone
                        : null
                )
                ->setAdresse(
                    $adresse !== ''
                        ? $adresse
                        : null
                )
                ->setVille(
                    $ville !== ''
                        ? $ville
                        : null
                )
                ->setPays(
                    $pays !== ''
                        ? $pays
                        : 'Madagascar'
                )
                ->setActif(true)
                ->setCreatedAt(new \DateTimeImmutable());


            $entityManager->persist($fournisseur);
            $entityManager->flush();


            $this->addFlash(
                'success',
                'Le fournisseur a été créé avec succès.'
            );

            return $this->redirectToRoute(
                'app_fournisseur_index'
            );
        }


        return $this->render(
            'admin/fournisseur/new.html.twig',
            [
                'fournisseur' => $fournisseur,
            ]
        );
    }


    /*
     * =========================================================
     * MODIFIER UN FOURNISSEUR
     * =========================================================
     */
    #[Route(
        '/{id}/modifier',
        name: 'app_fournisseur_edit',
        methods: ['GET', 'POST']
    )]
    public function edit(
        Fournisseur $fournisseur,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {

        if ($request->isMethod('POST')) {

            /*
             * CSRF
             */
            if (!$this->isCsrfTokenValid(
                'modifier_fournisseur_' . $fournisseur->getId(),
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Token CSRF invalide.'
                );
            }


            /*
             * Nom
             */
            $nom = trim(
                (string) $request->request->get('nom', '')
            );

            if ($nom === '') {

                $this->addFlash(
                    'error',
                    'Le nom du fournisseur est obligatoire.'
                );

                return $this->redirectToRoute(
                    'app_fournisseur_edit',
                    [
                        'id' => $fournisseur->getId()
                    ]
                );
            }


            if (mb_strlen($nom) > 150) {

                $this->addFlash(
                    'error',
                    'Le nom du fournisseur ne doit pas dépasser 150 caractères.'
                );

                return $this->redirectToRoute(
                    'app_fournisseur_edit',
                    [
                        'id' => $fournisseur->getId()
                    ]
                );
            }


            /*
             * Email
             */
            $email = trim(
                (string) $request->request->get('email', '')
            );

            if (
                $email !== '' &&
                !filter_var($email, FILTER_VALIDATE_EMAIL)
            ) {

                $this->addFlash(
                    'error',
                    'L’adresse e-mail du fournisseur est invalide.'
                );

                return $this->redirectToRoute(
                    'app_fournisseur_edit',
                    [
                        'id' => $fournisseur->getId()
                    ]
                );
            }


            /*
             * Autres informations
             */
            $telephone = trim(
                (string) $request->request->get(
                    'telephone',
                    ''
                )
            );

            $adresse = trim(
                (string) $request->request->get(
                    'adresse',
                    ''
                )
            );

            $ville = trim(
                (string) $request->request->get(
                    'ville',
                    ''
                )
            );

            $pays = trim(
                (string) $request->request->get(
                    'pays',
                    ''
                )
            );


            if ($pays === '') {
                $pays = 'Madagascar';
            }


            /*
             * Mise à jour
             */
            $fournisseur
                ->setNom($nom)
                ->setEmail(
                    $email !== ''
                        ? $email
                        : null
                )
                ->setTelephone(
                    $telephone !== ''
                        ? $telephone
                        : null
                )
                ->setAdresse(
                    $adresse !== ''
                        ? $adresse
                        : null
                )
                ->setVille(
                    $ville !== ''
                        ? $ville
                        : null
                )
                ->setPays($pays)
                ->setUpdatedAt(
                    new \DateTimeImmutable()
                );


            $entityManager->flush();


            $this->addFlash(
                'success',
                'Le fournisseur a été modifié avec succès.'
            );

            return $this->redirectToRoute(
                'app_fournisseur_index'
            );
        }


        return $this->render(
            'admin/fournisseur/edit.html.twig',
            [
                'fournisseur' => $fournisseur,
            ]
        );
    }


    /*
     * =========================================================
     * ACTIVER / DESACTIVER
     * =========================================================
     */
    #[Route(
        '/{id}/toggle',
        name: 'app_fournisseur_toggle',
        methods: ['POST']
    )]
    public function toggle(
        Fournisseur $fournisseur,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {

        if (!$this->isCsrfTokenValid(
            'toggle_fournisseur_' . $fournisseur->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }


        $fournisseur->setActif(
            !$fournisseur->isActif()
        );

        $fournisseur->setUpdatedAt(
            new \DateTimeImmutable()
        );


        $entityManager->flush();


        if ($fournisseur->isActif()) {

            $message =
                'Le fournisseur a été activé avec succès.';

        } else {

            $message =
                'Le fournisseur a été désactivé avec succès.';
        }


        $this->addFlash(
            'success',
            $message
        );


        return $this->redirectToRoute(
            'app_fournisseur_index'
        );
    }
}
