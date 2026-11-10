<?php

namespace App\Command;

use App\Repository\ProduitRepository;
use App\Service\Ean13Generator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:produits:generer-code-barres',
    description: 'Génère les codes-barres EAN-13 des produits qui n’en possèdent pas.'
)]
class GenerateProductBarcodesCommand extends Command
{
    public function __construct(
        private ProduitRepository $produitRepository,
        private EntityManagerInterface $entityManager,
        private Ean13Generator $ean13Generator
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $produits = $this->produitRepository->findBy([
            'codeBarres' => null,
        ]);

        if (count($produits) === 0) {
            $io->success('Tous les produits possèdent déjà un code-barres.');

            return Command::SUCCESS;
        }

        $io->progressStart(count($produits));

        foreach ($produits as $produit) {
            $code = $this->ean13Generator->generate(
                $this->produitRepository
            );

            $produit->setCodeBarres($code);

            $this->entityManager->flush();

            $io->progressAdvance();
        }

        $io->progressFinish();

        $io->success(
            count($produits) . ' produit(s) ont reçu un code EAN-13.'
        );

        return Command::SUCCESS;
    }
}