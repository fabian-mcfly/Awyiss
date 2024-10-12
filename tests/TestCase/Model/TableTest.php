<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model;


use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\ConnectionManager;


/**
 * General tests for the Table class
 */
class TableTest extends TestCase {
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
