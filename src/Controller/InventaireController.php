<?php

namespace App\Controller;

use App\Entity\Inventaire;
use App\Entity\LigneInventaire;
use App\Entity\Magasin;
use App\Entity\StockMagasin;
use App\Entity\User;
use App\Repository\InventaireRepository;
use App\Repository\LigneInventaireRepository;
use App\Repository\MagasinRepository;
use App\Repository\StockMagasinRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventaires')]
#[IsGranted('ROLE_ADMIN')]
class InventaireController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private InventaireRepository $inventaireRepository,
        private LigneInventaireRepository $ligneInventaireRepository,
        private MagasinRepository $magasinRepository,
        private StockMagasinRepository $stockMagasinRepository,
    ) {
    }

    /**
     * Liste de tous les inventaires.
     */
    #[Route('/', name: 'admin_inventaire_index', methods: ['GET'])]
    public function index(): Response
    {
        $inventaires = $this->inventaireRepository->findBy(
            [],
            ['dateInventaire' => 'DESC']
        );

        return $this->render('inventaire/index.html.twig', [
            'inventaires' => $inventaires,
        ]);
    }

    /**
     * Création d'un nouvel inventaire pour un magasin.
     *
     * Les lignes sont initialisées à partir des stocks existants
     * dans StockMagasin.
     */
    #[Route('/nouveau', name: 'admin_inventaire_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $magasins = $this->magasinRepository->findBy(
            ['actif' => true],
            ['nom' => 'ASC']
        );

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'inventaire_new',
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Jeton de sécurité invalide.'
                );
            }

            $magasinId = $request->request->get('magasin_id');
            $magasin = $this->magasinRepository->find($magasinId);

            if (!$magasin instanceof Magasin || !$magasin->isActif()) {
                $this->addFlash('danger', 'Veuillez sélectionner un magasin actif.');

                return $this->redirectToRoute('admin_inventaire_new');
            }

            // Un seul inventaire en cours par magasin.
            $inventaireOuvert = $this->inventaireRepository->findOneBy([
                'magasin' => $magasin,
                'statut' => Inventaire::STATUT_EN_COURS,
            ]);

            if ($inventaireOuvert) {
                $this->addFlash(
                    'warning',
                    'Un inventaire est déjà en cours pour ce magasin.'
                );

                return $this->redirectToRoute('admin_inventaire_show', [
                    'id' => $inventaireOuvert->getId(),
                ]);
            }

            // Récupération des stocks réellement enregistrés pour ce magasin.
            $stocks = $this->stockMagasinRepository->findByMagasin($magasin);

            if (count($stocks) === 0) {
                $this->addFlash(
                    'warning',
                    'Ce magasin ne possède aucun stock enregistré. Impossible de créer un inventaire.'
                );

                return $this->redirectToRoute('admin_inventaire_index');
            }

            $inventaire = new Inventaire();
            $inventaire->setMagasin($magasin);

            $utilisateur = $this->getUser();

            if ($utilisateur instanceof User) {
                $inventaire->setUtilisateur($utilisateur);
            }

            // Date de l'inventaire.
            $dateSaisie = $request->request->get('date_inventaire');

            if (!empty($dateSaisie)) {
                try {
                    $inventaire->setDateInventaire(
                        new \DateTimeImmutable($dateSaisie)
                    );
                } catch (\Exception) {
                    $this->addFlash('danger', 'La date de l’inventaire est invalide.');

                    return $this->redirectToRoute('admin_inventaire_new');
                }
            }

            $commentaire = trim((string) $request->request->get('commentaire', ''));

            $inventaire->setCommentaire(
                $commentaire !== '' ? $commentaire : null
            );

            // Création des lignes avec le stock théorique actuel.
            foreach ($stocks as $stock) {
                $ligne = new LigneInventaire();

                $ligne->setProduit($stock->getProduit());
                $ligne->setStockTheorique($stock->getQuantite());

                $inventaire->addLigne($ligne);
            }

            $this->em->persist($inventaire);
            $this->em->flush();

            $this->addFlash(
                'success',
                'Inventaire créé avec succès. Vous pouvez maintenant saisir les quantités physiques.'
            );

            return $this->redirectToRoute('admin_inventaire_comptage', [
                'id' => $inventaire->getId(),
            ]);
        }

        return $this->render('inventaire/new.html.twig', [
            'magasins' => $magasins,
        ]);
    }

    /**
     * Génération du rapport détaillé d'un inventaire.
     */
    #[Route(
        '/{id}/rapport',
        name: 'admin_inventaire_rapport',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function rapport(Inventaire $inventaire): Response
    {
        $lignes = $this->ligneInventaireRepository->findByInventaire($inventaire);

        $totalTheorique = 0;
        $totalPhysique = 0;
        $totalEcart = 0;
        $nombreComptes = 0;
        $nombreNonComptes = 0;
        $nombreEcartsPositifs = 0;
        $nombreEcartsNegatifs = 0;
        $nombreEcartsNuls = 0;

        foreach ($lignes as $ligne) {
            $totalTheorique += $ligne->getStockTheorique();

            if ($ligne->getQuantitePhysique() === null) {
                $nombreNonComptes++;
                continue;
            }

            $nombreComptes++;
            $totalPhysique += $ligne->getQuantitePhysique();

            $ecart = $ligne->getEcart() ?? 0;
            $totalEcart += $ecart;

            if ($ecart > 0) {
                $nombreEcartsPositifs++;
            } elseif ($ecart < 0) {
                $nombreEcartsNegatifs++;
            } else {
                $nombreEcartsNuls++;
            }
        }

        return $this->render('inventaire/rapport.html.twig', [
            'inventaire' => $inventaire,
            'lignes' => $lignes,
            'totalTheorique' => $totalTheorique,
            'totalPhysique' => $totalPhysique,
            'totalEcart' => $totalEcart,
            'nombreComptes' => $nombreComptes,
            'nombreNonComptes' => $nombreNonComptes,
            'nombreEcartsPositifs' => $nombreEcartsPositifs,
            'nombreEcartsNegatifs' => $nombreEcartsNegatifs,
            'nombreEcartsNuls' => $nombreEcartsNuls,
        ]);
    }

    /**
     * Affichage des détails d'un inventaire.
     */
    #[Route('/{id}', name: 'admin_inventaire_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Inventaire $inventaire): Response
    {
        $lignes = $this->ligneInventaireRepository->findByInventaire($inventaire);

        $totalEcart = 0;
        $nombreComptes = 0;
        $nombreNonComptes = 0;

        foreach ($lignes as $ligne) {
            if ($ligne->getQuantitePhysique() === null) {
                $nombreNonComptes++;
            } else {
                $nombreComptes++;
                $totalEcart += $ligne->getEcart() ?? 0;
            }
        }

        return $this->render('inventaire/show.html.twig', [
            'inventaire' => $inventaire,
            'lignes' => $lignes,
            'nombreComptes' => $nombreComptes,
            'nombreNonComptes' => $nombreNonComptes,
            'totalEcart' => $totalEcart,
        ]);
    }

    /**
     * Saisie des quantités physiques.
     *
     * Cette action ne modifie PAS les stocks.
     */
    #[Route('/{id}/comptage', name: 'admin_inventaire_comptage', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function comptage(Inventaire $inventaire, Request $request): Response
    {
        if ($inventaire->getStatut() !== Inventaire::STATUT_EN_COURS) {
            $this->addFlash(
                'warning',
                'Cet inventaire n’est plus modifiable.'
            );

            return $this->redirectToRoute('admin_inventaire_show', [
                'id' => $inventaire->getId(),
            ]);
        }

        $lignes = $this->ligneInventaireRepository->findByInventaire($inventaire);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'inventaire_comptage_' . $inventaire->getId(),
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Jeton de sécurité invalide.'
                );
            }

            $quantites = $request->request->all('quantites');
            $observations = $request->request->all('observations');

            // On construit une liste des lignes appartenant à cet inventaire.
            $lignesAutorisees = [];

            foreach ($lignes as $ligne) {
                $lignesAutorisees[$ligne->getId()] = $ligne;
            }

            $erreurs = [];

            foreach ($quantites as $ligneId => $quantiteSaisie) {
                // Empêche la modification d'une ligne étrangère à cet inventaire.
                if (!isset($lignesAutorisees[$ligneId])) {
                    $erreurs[] = 'Une ligne transmise ne correspond pas à cet inventaire.';
                    continue;
                }

                $ligne = $lignesAutorisees[$ligneId];

                $valeur = trim((string) $quantiteSaisie);

                // Une valeur vide signifie que la ligne n'est pas encore comptée.
                if ($valeur === '') {
                    $ligne->setQuantitePhysique(null);
                } elseif (!preg_match('/^\d+$/D', $valeur)) {
                    $erreurs[] = 'Une quantité saisie est invalide.';
                    continue;
                } else {
                    $quantite = (int) $valeur;

                    if ($quantite < 0) {
                        $erreurs[] = 'Les quantités ne peuvent pas être négatives.';
                        continue;
                    }

                    $ligne->setQuantitePhysique($quantite);
                }

                $observation = trim(
                    (string) ($observations[$ligneId] ?? '')
                );

                $ligne->setObservation(
                    $observation !== '' ? $observation : null
                );
            }

            if (!empty($erreurs)) {
                foreach (array_unique($erreurs) as $erreur) {
                    $this->addFlash('danger', $erreur);
                }

                return $this->render('inventaire/comptage.html.twig', [
                    'inventaire' => $inventaire,
                    'lignes' => $lignes,
                ]);
            }

            $this->em->flush();

            $this->addFlash(
                'success',
                'Les quantités physiques ont été enregistrées. Les stocks du magasin n’ont pas encore été modifiés.'
            );

            return $this->redirectToRoute('admin_inventaire_show', [
                'id' => $inventaire->getId(),
            ]);
        }

        return $this->render('inventaire/comptage.html.twig', [
            'inventaire' => $inventaire,
            'lignes' => $lignes,
        ]);
    }

    /**
     * Validation définitive de l'inventaire.
     *
     * Met à jour StockMagasin uniquement après vérification
     * des quantités et des éventuels mouvements intervenus
     * depuis la création de l'inventaire.
     */
    #[Route('/{id}/valider', name: 'admin_inventaire_valider', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function valider(Inventaire $inventaire, Request $request): Response
    {
        if (!$this->isCsrfTokenValid(
            'inventaire_valider_' . $inventaire->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton de sécurité invalide.'
            );
        }

        if ($inventaire->getStatut() !== Inventaire::STATUT_EN_COURS) {
            $this->addFlash(
                'warning',
                'Cet inventaire a déjà été traité.'
            );

            return $this->redirectToRoute('admin_inventaire_show', [
                'id' => $inventaire->getId(),
            ]);
        }

        $connexion = $this->em->getConnection();

        try {
            $connexion->beginTransaction();

            // Verrouillage de l'inventaire pour empêcher une double validation.
            $this->em->lock($inventaire, LockMode::PESSIMISTIC_WRITE);
            $this->em->refresh($inventaire);

            if ($inventaire->getStatut() !== Inventaire::STATUT_EN_COURS) {
                throw new \RuntimeException(
                    'Cet inventaire a déjà été validé ou annulé.'
                );
            }

            $lignes = $this->ligneInventaireRepository->findByInventaire($inventaire);

            if (count($lignes) === 0) {
                throw new \RuntimeException(
                    'Cet inventaire ne contient aucune ligne.'
                );
            }

            // Toutes les lignes doivent être comptées.
            foreach ($lignes as $ligne) {
                if ($ligne->getQuantitePhysique() === null) {
                    throw new \RuntimeException(
                        'Impossible de valider : toutes les lignes doivent être comptées.'
                    );
                }
            }

            // Vérification des stocks et mise à jour dans la transaction.
            foreach ($lignes as $ligne) {
                $stock = $this->stockMagasinRepository->findOneByProduitEtMagasin(
                    $ligne->getProduit(),
                    $inventaire->getMagasin()
                );

                if (!$stock instanceof StockMagasin) {
                    throw new \RuntimeException(
                        'Un stock est introuvable pour le produit « '
                        . $ligne->getProduit()->getNom()
                        . ' ». La validation a été interrompue.'
                    );
                }

                // Verrouillage et relecture de la quantité actuelle.
                $this->em->refresh($stock, LockMode::PESSIMISTIC_WRITE);

                // Si le stock a changé depuis le début de l'inventaire,
                // on refuse la validation pour éviter une correction incorrecte.
                if ($stock->getQuantite() !== $ligne->getStockTheorique()) {
                    throw new \RuntimeException(
                        'Le stock du produit « '
                        . $ligne->getProduit()->getNom()
                        . ' » a changé depuis le début de l’inventaire. '
                        . 'Vérifiez les mouvements de stock et recommencez le comptage.'
                    );
                }

                // Le stock physique devient le nouveau stock réel.
                $stock->setQuantite($ligne->getQuantitePhysique());
            }

            // Passage au statut validé après toutes les vérifications.
            $inventaire->setStatut(Inventaire::STATUT_VALIDE);

            $this->em->flush();
            $connexion->commit();

            $this->addFlash(
                'success',
                'Inventaire validé avec succès. Les stocks du magasin ont été mis à jour.'
            );
        } catch (\Throwable $exception) {
            if ($connexion->isTransactionActive()) {
                $connexion->rollBack();
            }

            $this->em->clear();

            $this->addFlash(
                'danger',
                $exception->getMessage()
            );
        }

        return $this->redirectToRoute('admin_inventaire_show', [
            'id' => $inventaire->getId(),
        ]);
    }

    /**
     * Annulation d'un inventaire en cours.
     *
     * Aucun stock n'est modifié.
     */
    #[Route('/{id}/annuler', name: 'admin_inventaire_annuler', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function annuler(Inventaire $inventaire, Request $request): Response
    {
        if (!$this->isCsrfTokenValid(
            'inventaire_annuler_' . $inventaire->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton de sécurité invalide.'
            );
        }

        if ($inventaire->getStatut() !== Inventaire::STATUT_EN_COURS) {
            $this->addFlash(
                'warning',
                'Seul un inventaire en cours peut être annulé.'
            );

            return $this->redirectToRoute('admin_inventaire_show', [
                'id' => $inventaire->getId(),
            ]);
        }

        $inventaire->setStatut(Inventaire::STATUT_ANNULE);

        $this->em->flush();

        $this->addFlash(
            'success',
            'Inventaire annulé. Aucun stock n’a été modifié.'
        );

        return $this->redirectToRoute('admin_inventaire_show', [
            'id' => $inventaire->getId(),
        ]);
    }
}
