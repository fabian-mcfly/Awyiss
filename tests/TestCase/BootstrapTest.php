<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase;


use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\ConnectionManager;


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


	/**
	 * Test if all tables exists
	 * If a model exists, the table must exist as well
	 * Otherwise the migration is not correct
	 */
	public function testTablesExist(): void {
		/**
		 * Find all models
		 *
		 * @var array<class-string<\Awyiss\Model\Table>> $la_models
		 */
		$la_models = glob(ROOT . '/awyiss/Model/Table/*.php');

		$la_tables = ConnectionManager::get('test')->getSchemaCollection()->listTables();

		// Get all tables
		foreach ($la_models as $ls_model) {
			$ls_model = '\Awyiss\Model\Table\\' . basename($ls_model, '.php');

			if (empty($ls_model::TABLE)) {
				continue;
			}

			// Check if the table exists in the database
			$this->assertContains($ls_model::TABLE, $la_tables, 'Table ' . $ls_model::TABLE . ' does not exist');
		}
	}
}
