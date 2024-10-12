<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase;


use Awyiss\Test\TestSuite\TestCase;


/**
 * BootstrapTest class
 *
 * Test php unit bootstrap logic
 */
class BootstrapTest extends TestCase {
	/**
	 * Test bootstrap and if constants are defined.
	 *
	 * @return void
	 */
	public function testBootstrap(): void {
		$this->assertTrue(defined('CUSTOM_DIR'), 'CUSTOM_DIR is not defined');

		$this->assertTrue(defined('CUSTOM_NAMESPACE'), 'CUSTOM_NAMESPACE is not defined');

		// Check if Customer\Model\Enum\PageRoleEnum exists
		$this->assertTrue(class_exists('\Customer\Model\Enum\PageRole'), 'Could not find \Customer\Model\Enum\PageRole');
	}
}
