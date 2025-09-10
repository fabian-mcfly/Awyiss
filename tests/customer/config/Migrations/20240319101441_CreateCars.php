<?php declare(strict_types=1);


use Migrations\AbstractMigration;


/**
 * Class CreateCars
 */
class CreateCars extends AbstractMigration {
	/**
	 * Migrate Up.
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$this->table('cars')->addColumn('parent_id', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => true,
		])->addColumn('language_shortcode', 'char', [
			'default' => null,
			'limit' => 2,
			'null' => true,
		])->addColumn('title', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('system_order', 'integer', [
			'default' => '0',
			'limit' => 11,
			'null' => false,
		])->addColumn('active', 'boolean', [
			'default' => true,
			'limit' => null,
			'null' => false,
		])->addColumn('deleted', 'boolean', [
			'default' => false,
			'limit' => null,
			'null' => false,
		])->addColumn('created_by', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => true,
		])->addColumn('created_on', 'datetime', [
			'default' => null,
			'null' => true,
		])->addColumn('changed_by', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => true,
		])->addColumn('changed_on', 'datetime', [
			'default' => null,
			'null' => true,
		])->addColumn('deleted_by', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => true,
		])->addColumn('deleted_on', 'datetime', [
			'default' => null,
			'null' => true,
		])->create();
	}
}
