<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Command\Migrations;


use Awyiss\Command\Migrations\MigrateCommand;
use Awyiss\Test\TestSuite\TestCase;


/**
 * Class MigrateCommandTest
 */
class MigrateCommandTest extends TestCase {
	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetOptionParser() {
		$command = new MigrateCommand();

		$options = $command->getOptionParser()->options();

		$this->assertIsArray($options);
		$this->assertArrayHasKey('folder', $options);
	}
}
