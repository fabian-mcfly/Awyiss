<?php declare(strict_types=1);


/**
 * Class Configuration
 */
class Configuration {
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
		$this->migration->table('configuration')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('realm', 'string', [
			'default' => null,
			'limit' => 20,
			'null' => false,
		])->addColumn('scope', 'string', [
			'default' => 'global',
			'limit' => 50,
			'null' => false,
		])->addColumn('identifier', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => false,
		])->addColumn('value', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('language_shortcode', 'char', [
			'default' => null,
			'limit' => 2,
			'null' => true,
		])->addColumn('description', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
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
				'name' => 'realm',
			]
		)->addIndex(
			[
				'scope',
			], [
				'name' => 'scope',
			]
		)->addIndex(
			[
				'identifier',
			], [
				'name' => 'identifier',
			]
		)->addIndex(
			[
				'language_shortcode',
			], [
				'name' => 'language_shortcode',
			]
		)->create();

		$this->migration->table('configuration')
		->changeComment('Do not alter values in scope, identifier, type and/or required')
		->save();
	}


	/**
	 * Migrate Down.
	 * 
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('configuration')->drop()->save();
	}
}
