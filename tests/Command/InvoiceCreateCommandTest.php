<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\InvoiceCreateCommand;
use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Invoice\ServiceInvoice;
use App\Repository\CustomerRepository;
use App\Repository\InvoiceTemplateRepository;
use App\Repository\TimesheetRepository;
use App\Repository\UserRepository;
use App\Tests\DataFixtures\InvoiceFixtures;
use App\Tests\KernelTestTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\InvoiceCreateCommand
 * @group integration
 */
class InvoiceCreateCommandTest extends KernelTestCase
{
    use KernelTestTrait;

    /**
     * @var Application
     */
    protected $application;

    private function clearInvoiceFiles()
    {
        $path = __DIR__ . '/../_data/invoices/';

        if (is_dir($path)) {
            $files = glob($path . '*');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearInvoiceFiles();
    }

    protected function setUp(): void
    {
        $this->clearInvoiceFiles();
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $container = self::$container;

        $this->application->add(new InvoiceCreateCommand(
            $container->get(ServiceInvoice::class),
            $container->get(TimesheetRepository::class),
            $container->get(CustomerRepository::class),
            $container->get(InvoiceTemplateRepository::class),
            $container->get(UserRepository::class),
            $container->get('event_dispatcher')
        ));
    }

    /**
        'user'
        'start'
        'end'
        'timezone'
        'customer'
        'template'
        'search'
        'exported'
        'by-customer'
        'by-project'
        'set-exported'
        'template-meta'
     * @param $user
     * @param array $params
     * @return CommandTester
     */
    protected function createInvoice(array $options = [])
    {
        $command = $this->application->find('kimai:invoice:create');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array_merge($options, [
            'command' => $command->getName(),
        ]));

        return $commandTester;
    }

    protected function assertCommandErrors(array $options = [], string $errorMessage = '')
    {
        $commandTester = $this->createInvoice($options);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[ERROR] ' . $errorMessage, $output);
    }

    public function testCreateWithUnknownExportFilter()
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--exported' => 'foo'], 'Unknown "exported" filter given');
    }

    public function testCreateWithMissingUser()
    {
        $this->assertCommandErrors([], 'You must set a "user" to create invoices');
    }

    public function testCreateWithMissingEnd()
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--start' => '2020-01-01'], 'You need to supply a end date if a start date was given');
    }

    public function testCreateByCustomerAndByProject()
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--by-customer' => null, '--by-project' => null], 'You cannot mix "by-customer" and "by-project"');
    }

    public function testCreateWithMissingGenerationMode()
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN], 'Could not determine generation mode');
    }

    public function testCreateWithMissingTemplate()
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--customer' => 1], 'You must either pass the "template" or "template-meta" option');
    }

    public function testCreateWithInvalidStart()
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--customer' => 1, '--template' => 'x', '--start' => 'öäüß', '--end' => '2020-01-01'], 'Invalid start date given');
    }

    public function testCreateWithInvalidEnd()
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--customer' => 1, '--template' => 'x', '--start' => '2020-01-01', '--end' => 'öäüß'], 'Invalid end date given');
    }

    public function testCreateInvoice()
    {
        $fixture = new InvoiceFixtures();
        $this->importFixture($this, $fixture);

        $commandTester = $this->createInvoice(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--customer' => 1, '--template' => 'Invoice', '--start' => '2020-01-01', '--end' => '2020-03-01']);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('+----+----------+-------+------------- Created 1 invoice(s) --------------------------------------+', $output);
        $this->assertStringContainsString('| ID | Customer | Total | Filename                                                                |', $output);
        $this->assertStringContainsString('+----+----------+-------+-------------------------------------------------------------------------+', $output);
        $this->assertStringContainsString('| 1  | Test     | 0 EUR | /', $output);
        $this->assertStringContainsString('/tests/_data/invoices/2020-001-test.html |', $output);
    }
}