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
		$this->table('attributes_cars')->addColumn('car_id', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => false,
		])->addColumn('free_text', 'text', [
			'default' => null,
			'null' => true,
		])->create();
	}
}
