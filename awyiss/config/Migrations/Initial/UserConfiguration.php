<?php declare(strict_types=1);


/**
 * Class UserConfiguration
 */
class UserConfiguration {
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
		$this->migration->table('user_configuration')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('user_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
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
		])->create();
	}


	/**
	 * Migrate Down.
	 * 
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('user_configuration')->drop()->save();
	}
}
