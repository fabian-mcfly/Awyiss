<?php declare(strict_types=1);


/**
 * Class Users
 */
class Users {
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
		if ($this->migration->hasTable('users')) {
			$this->migration->table('users')->drop()->save();
		}

		$this->migration->table('users')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('username', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => false,
		])->addColumn('password', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		])->addColumn('firstname', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => true,
		])->addColumn('lastname', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => true,
		])->addColumn('email', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => true,
		])->addColumn('last_login', 'datetime', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('failed_attempts', 'integer', [
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
				'active',
			], [
				'name' => 'USERS_ACTIVE',
			]
		)->addIndex(
			[
				'deleted',
			], [
				'name' => 'USERS_DELETED',
			]
		)->addIndex(
			[
				'username',
			], [
				'name' => 'USERS_USERNAME',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 * 
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('users')->drop()->save();
	}
}
