<?php declare(strict_types=1);


use Migrations\BaseMigration;


/**
 * Class CreateEmployers
 */
class CreateEmployers extends BaseMigration {
	/**
	 * Migrate Up.
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$this->table('employers')->addColumn('parentId', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => true,
		])->addColumn('languageShortcode', 'char', [
			'default' => null,
			'limit' => 2,
			'null' => true,
		])->addColumn('title', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('systemOrder', 'integer', [
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
		])->addColumn('createdBy', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => true,
		])->addColumn('createdOn', 'datetime', [
			'default' => null,
			'null' => true,
		])->addColumn('changedBy', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => true,
		])->addColumn('changedOn', 'datetime', [
			'default' => null,
			'null' => true,
		])->addColumn('deletedBy', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => true,
		])->addColumn('deletedOn', 'datetime', [
			'default' => null,
			'null' => true,
		])->create();
	}
}
