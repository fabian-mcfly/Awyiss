<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Command\Migrations;


use Awyiss\Command\Migrations\SeedCommand;
use Awyiss\Test\TestSuite\TestCase;


/**
 * Class SeedCommandTest
 */
class SeedCommandTest extends TestCase {
	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetOptionParser() {
		$command = new SeedCommand();

		$options = $command->getOptionParser()->options();

		$this->assertIsArray($options);
		$this->assertArrayHasKey('folder', $options);
	}
}
