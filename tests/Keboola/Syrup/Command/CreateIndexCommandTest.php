<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 19/03/15
 * Time: 11:47
 */

namespace Keboola\Syrup\Tests\Command;

use Keboola\Syrup\Command\CreateIndexCommand;
use Keboola\Syrup\Test\CommandTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CreateIndexCommandTest extends CommandTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->application->add(new CreateIndexCommand());
    }

    public function testCreateIndex()
    {
        $command = $this->application->find('syrup:create-index');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '-d' => null
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
