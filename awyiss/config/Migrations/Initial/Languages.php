<?php declare(strict_types=1);


/**
 * Class Languages
 */
class Languages {
	/**
	 * @var \Initial $migration The migration that is being migrated
	 */
	protected Initial $migration;


	/**
	 * Constructor
	 *
	 * @param \Initial $migration The migration that is being migrated
	 */
	public function __construct(Initial $migration) {
		$this->migration = $migration;
	}


	/**
	 * Migrate Up.
	 * 
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$this->migration->table('languages')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('realm', 'string', [
			'default' => null,
			'limit' => 20,
			'null' => false,
		])->addColumn('shortcode', 'char', [
			'default' => null,
			'limit' => 2,
			'null' => false,
		])->addColumn('timezone', 'string', [
			'default' => null,
			'limit' => 32,
			'null' => false,
		])->addColumn('locale', 'string', [
			'default' => null,
			'limit' => 5,
			'null' => false,
		])->addColumn('date_format', 'string', [
			'default' => null,
			'limit' => 30,
			'null' => true,
		])->addColumn('time_format', 'string', [
			'default' => null,
			'limit' => 30,
			'null' => true,
		])->addColumn('title', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => false,
		])->addColumn('system_order', 'integer', [
			'default' => '0',
			'limit' => null,
			'null' => false,
			'signed' => true,
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
			'limit' => null,
			'null' => true,
			'signed' => true,
		])->addColumn('created_on', 'datetime', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('changed_by', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => true,
			'signed' => true,
		])->addColumn('changed_on', 'datetime', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('deleted_by', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => true,
			'signed' => true,
		])->addColumn('deleted_on', 'datetime', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addIndex(
			[
				'realm',
			], [
				'name' => 'LANGUAGES_REALM',
			]
		)->addIndex(
			[
				'shortcode',
			], [
				'name' => 'LANGUAGES_SHORTCODE',
			]
		)->addIndex(
			[
				'active',
			], [
				'name' => 'LANGUAGES_ACTIVE',
			]
		)->addIndex(
			[
				'deleted',
			], [
				'name' => 'LANGUAGES_DELETED',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 * 
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('languages')->drop()->save();
	}
}
