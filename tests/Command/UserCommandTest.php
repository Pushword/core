<?php

namespace Pushword\Core\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UserCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find('pushword:user:create');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'email' => 'user@example.tld',
            'password' => 'mySecr3tpAssword',
            'role' => 'ROLE_USER',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertTrue(str_contains($output, 'success'));
    }
}
