<?php declare(strict_types=1);


use Migrations\AbstractMigration;


/**
 * Class CreateAttributesCars
 */
class CreateAttributesCars extends AbstractMigration {
	/**
	 * Migrate Up.
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$this->table('attributes_cars')->addColumn('carId', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => false,
		])->addColumn('freeText', 'text', [
			'default' => null,
			'null' => true,
		])->addColumn('inputList', 'text', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('inputKeyValueList', 'text', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('dummyPw', 'text', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->create();
	}
}
