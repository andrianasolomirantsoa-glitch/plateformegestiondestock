<?php

namespace App\Command;

use App\Entity\Client;
use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:clients:synchroniser',
    description: 'Synchronise les comptes clients User avec les profils Client.'
)]
class SynchroniserClientsCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private ClientRepository $clientRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $users = $this->userRepository->findAll();

        $created = 0;
        $linked = 0;
        $skipped = 0;

        foreach ($users as $user) {

            /*
             * On ne traite que les comptes clients.
             */
            if (!$user->hasRole('ROLE_CLIENT')) {
                continue;
            }

            /*
             * Le compte possède déjà un profil Client.
             */
            if ($user->getClient() !== null) {
                $skipped++;
                continue;
            }

            /*
             * Chercher un ancien profil Client avec le même email.
             */
            $client = null;

            if ($user->getEmail() !== null) {
                $client = $this->clientRepository->findOneBy([
                    'email' => $user->getEmail(),
                ]);
            }

            /*
             * Si un ancien Client existe, on le rattache.
             */
            if ($client !== null) {

                /*
                 * Éviter d'écraser un lien déjà utilisé
                 * par un autre User.
                 */
                if (
                    $client->getUser() !== null &&
                    $client->getUser()->getId() !== $user->getId()
                ) {
                    $output->writeln(
                        '<comment>Conflit email : ' .
                        $user->getEmail() .
                        '</comment>'
                    );

                    continue;
                }

                $client->setUser($user);

                /*
                 * Synchroniser les informations principales.
                 */
                $client->setNom(
                    $user->getNom() ?? ''
                );

                $client->setPrenom(
                    $user->getPrenom()
                );

                $client->setEmail(
                    $user->getEmail()
                );

                $client->setActif(
                    $user->isActif()
                );

                $client->setUpdatedAt(
                    new \DateTimeImmutable()
                );

                $this->entityManager->persist($client);

                $linked++;

                $output->writeln(
                    '<info>Client existant rattaché : ' .
                    $user->getEmail() .
                    '</info>'
                );

                continue;
            }

            /*
             * Aucun ancien profil trouvé :
             * on crée automatiquement le profil Client.
             */
            $client = new Client();

            $client->setNom(
                $user->getNom() ?? ''
            );

            $client->setPrenom(
                $user->getPrenom()
            );

            $client->setEmail(
                $user->getEmail()
            );

            $client->setActif(
                $user->isActif()
            );

            $client->setUser($user);

            $this->entityManager->persist($client);

            $created++;

            $output->writeln(
                '<info>Nouveau profil Client créé : ' .
                $user->getEmail() .
                '</info>'
            );
        }

        $this->entityManager->flush();

        $output->writeln('');
        $output->writeln('<info>Synchronisation terminée.</info>');
        $output->writeln(
            'Profils créés : ' . $created
        );
        $output->writeln(
            'Profils rattachés : ' . $linked
        );
        $output->writeln(
            'Profils déjà synchronisés : ' . $skipped
        );

        return Command::SUCCESS;
    }
}

