<?php

namespace App\Command;

use App\Entity\Feedback;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:add-feedback-placeholders',
    description: 'Add placeholder feedback entries',
)]
class AddFeedbackPlaceholdersCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Find or create admin user
        $userRepository = $this->entityManager->getRepository(User::class);
        $adminUser = $userRepository->findOneBy(['email' => 'admin@example.com']);
        
        if (!$adminUser) {
            $io->note('Admin user not found, creating one...');
            $adminUser = new User();
            $adminUser->setEmail('admin@example.com');
            $adminUser->setFirstName('Admin');
            $adminUser->setLastName('User');
            $adminUser->setRoles(['ROLE_ADMIN']);
            
            $hashedPassword = $this->passwordHasher->hashPassword(
                $adminUser,
                'Admin123!'
            );
            $adminUser->setPassword($hashedPassword);
            
            $this->entityManager->persist($adminUser);
            $this->entityManager->flush();
            $io->success('Admin user created');
        }
        
        // Sample event names
        $eventNames = [
            'Annual Regatta 2023',
            'Summer Sailing Camp',
            'Coastal Race Challenge',
            'Beginners Yacht Training',
            'Nautical Festival'
        ];
        
        // Sample usernames
        $usernames = [
            'JohnSailor', 
            'MarineLover', 
            'OceanExplorer', 
            'SailingQueen', 
            'CaptainJack',
            'WaveRider',
            'SeaAdventurer'
        ];
        
        // Sample comments
        $comments = [
            'Amazing experience! The instructors were very knowledgeable and friendly.',
            'Had a wonderful time at this event. Will definitely come back next year!',
            'The organization was top-notch. Everything went smoothly.',
            'I learned so much during this event. The staff was very professional.',
            'Great atmosphere and beautiful location. Highly recommended!',
            'My family enjoyed every moment. The children especially loved the water activities.',
            'A perfect day on the water with excellent guidance from the team.',
            'The equipment was in excellent condition and the safety measures were impressive.',
            'Fantastic event for both beginners and experienced sailors alike.',
            'The views were breathtaking and the experience unforgettable!'
        ];
        
        // Create 10 random feedback entries
        for ($i = 0; $i < 10; $i++) {
            $feedback = new Feedback();
            $feedback->setUser($adminUser);
            $feedback->setRating(random_int(3, 5));
            $feedback->setUsername($usernames[array_rand($usernames)]);
            $feedback->setComment($comments[array_rand($comments)]);
            $feedback->setEventName($eventNames[array_rand($eventNames)]);
            $feedback->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', random_int(1, 60))));
            $feedback->setIsAdmin(false);
            
            $this->entityManager->persist($feedback);
        }
        
        $this->entityManager->flush();
        
        $io->success('10 placeholder feedback entries have been added successfully!');

        return Command::SUCCESS;
    }
} 