<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Constants;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to create a release package with pre-installed composer, SQLite database and user.
 */
class CreateReleaseCommand extends Command
{
    const CLONE_CMD = 'git clone -b %s --depth 1 https://github.com/kevinpapst/kimai2.git';

    /**
     * @var string
     */
    protected $rootDir = '';

    /**
     * @param string $projectDirectory
     */
    public function __construct(string $projectDirectory)
    {
        $this->rootDir = realpath($projectDirectory);
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:create-release')
            ->setDescription('Create a pre-installed release package')
            ->setHelp('This command will create a release package with pre-installed composer, SQLite database and user.')
            ->addOption('directory', null, InputOption::VALUE_OPTIONAL, 'Directory where the release package will be stored', 'var/data/')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (getenv('APP_ENV') === 'prod') {
            $io->error('kimai:reset-dev is not allowed in production');
            return -2;
        }

        $directory = $input->getOption('directory');

        if ($directory[0] === '/') {
            $directory = realpath($directory);
        } else {
            $directory = realpath($this->rootDir . '/' . $directory);
        }

        $tmpDir = $directory . '/' . uniqid('kimai_release_');

        if (!is_dir($directory)) {
            $io->error('Given directory is not existing: ' . $directory);
            return 1;
        }

        if (is_dir($directory) && !is_writable($directory)) {
            $io->error('Cannot write in directory: ' . $directory);
            return 1;
        }

        $gitCmd = sprintf(self::CLONE_CMD, 'master');
        $tar = 'kimai-release-' . str_replace('.', '_', Constants::VERSION) . '.tar.gz';
        $zip = 'kimai-release-' . Constants::VERSION . '.zip';

        // this removes the current env settings, as they might differ from the release ones
        // if we don't unset them, the .env file won't be read when executing bin/console commands
        putenv('DATABASE_URL');
        putenv('APP_ENV');

        $commands = [
            'Clone repository' => $gitCmd . ' ' . $tmpDir,
            'Install composer dependencies' => 'cd ' . $tmpDir . ' && composer install --no-dev --optimize-autoloader',
            'Create .env file' => 'cd ' . $tmpDir . ' && cp .env.dist .env',
            'Create database' => 'cd ' . $tmpDir . ' && bin/console doctrine:database:create -n',
            'Create tables' => 'cd ' . $tmpDir . ' && bin/console doctrine:schema:create -n',
            'Add all migrations' => 'cd ' . $tmpDir . ' && bin/console doctrine:migrations:version --add --all -n',
            'Delete .git' => 'cd ' . $tmpDir . ' && rm -rf .git/*',
            'Delete .github' => 'cd ' . $tmpDir . ' && rm -rf .github/*',
            'Delete cache' => 'cd ' . $tmpDir . ' && rm -rf var/cache/*',
            'Delete test DB' => 'cd ' . $tmpDir . ' && rm -f var/data/kimai_test.sqlite',
            'Delete logs' => 'cd ' . $tmpDir . ' && rm -f var/log/*.log',
            'Delete sessions' => 'cd ' . $tmpDir . ' && rm -rf var/sessions/*',
            'Create tar' => 'cd ' . $tmpDir . ' && tar -czf ' . $directory. '/' . $tar . ' .',
            'Create zip' => 'cd ' . $tmpDir . ' && zip -r ' . $directory. '/' . $zip . ' .',
            'Remove tmp directory' => 'rm -rf ' . $tmpDir,
        ];

        $exitCode = 0;
        foreach($commands as $title => $command) {
            passthru($command, $exitCode);
            if ($exitCode !== 0) {
                $io->error('Failed with command: ' . $command);
                return -1;
            }
            $io->success($title);
        }

        $io->success(
            'New release packages available at: ' . PHP_EOL .
            $directory . '/' . $tar. PHP_EOL .
            $directory . '/' . $zip
        );

        return 0;
    }
}
