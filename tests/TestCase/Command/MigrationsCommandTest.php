<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Command;


use Awyiss\Command\MigrationsCommand;
use Awyiss\Test\TestSuite\TestCase;


/**
 * Class MigrationsCommandTest
 */
class MigrationsCommandTest extends TestCase {
	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetOptionParser() {
		$command = new MigrationsCommand();
		$command->initialize();

		$dispatcher = $this->callProtectedMethod($command, 'getApp');
		$result = $dispatcher::getCommands();

		$this->assertSame(
			[
				'Migrate' => 'Awyiss\Command\Phinx\Migrate',
				'Seed' => 'Awyiss\Command\Phinx\Seed',
			],
			$result
		);
	}
}
