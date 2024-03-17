<?php declare(strict_types=1);


/**
 * Class UsersExternal
 */
class UsersExternal  {
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
		$this->migration->table('users_external')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('provider', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => false,
		])->addColumn('provider_id', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => false,
		])->addColumn('username', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => false,
		])->addColumn('last_login', 'datetime', [
			'default' => 'current_timestamp()',
			'limit' => null,
			'null' => false,
		])->addIndex(
			[
				'provider_id',
				'username',
			], [
				'name' => 'provider_id',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 * 
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('users_external')->drop()->save();
	}
}
