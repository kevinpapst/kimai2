<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command used to create application user.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class CreateUserCommand extends Command
{
    /**
     * @var UserPasswordEncoder
     */
    protected $encoder;
    /**
     * @var Registry
     */
    protected $doctrine;
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @param UserPasswordEncoderInterface $encoder
     * @param RegistryInterface $registry
     * @param ValidatorInterface $validator
     */
    public function __construct(
        UserPasswordEncoderInterface $encoder,
        RegistryInterface $registry,
        ValidatorInterface $validator
    ) {
        $this->encoder = $encoder;
        $this->doctrine = $registry;
        $this->validator = $validator;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('kimai:create-user')
            ->setDescription('Create a new user')
            ->setHelp('This command allows you to create a new user.')
            ->addArgument('username', InputArgument::REQUIRED, 'New username (must be unique)')
            ->addArgument('email', InputArgument::REQUIRED, 'Users email address (must be unique)')
            ->addArgument('role', InputArgument::OPTIONAL, 'Users role (comma separated list)', User::DEFAULT_ROLE)
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        /* @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $passwordQuestion = new Question('Please enter the password');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setHiddenFallback(false);
        $passwordQuestion->setValidator(function (?string $value) {
            if (trim($value) == '') {
                throw new \Exception('The password cannot be empty');
            }
            return $value;
        });
        $passwordQuestion->setMaxAttempts(3);

        $password = $helper->ask($input, $output, $passwordQuestion);

        $username = $input->getArgument('username');
        $email = $input->getArgument('email');
        $role = $input->getArgument('role');

        $role = $role ?: User::DEFAULT_ROLE;

        $user = new User();
        $user->setUsername($username)
            ->setPlainPassword($password)
            ->setEmail($email)
            ->setRoles(explode(',', $role))
        ;

        $pwd = $this->encoder->encodePassword($user, $user->getPlainPassword());
        $user->setPassword($pwd);

        $errors = $this->validator->validate($user);
        if ($errors->count() > 0) {
            /** @var \Symfony\Component\Validator\ConstraintViolation $error */
            foreach ($errors as $error) {
                $value = $error->getInvalidValue();
                $io->error(
                    $error->getPropertyPath()
                    . ' (' . (is_array($value) ? implode(',', $value) : $value) . ')'
                    . "\n    "
                    . $error->getMessage()
                );
            }
            return;
        }

        try {
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            $io->success('Success! Created user: ' . $user->getUsername());
        } catch (\Exception $ex) {
            $io->error('Failed to create user: ' . $user->getUsername());
            $io->error('Reason: ' . $ex->getMessage());
        }
    }
}
