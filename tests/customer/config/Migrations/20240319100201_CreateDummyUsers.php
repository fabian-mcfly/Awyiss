<?php declare(strict_types=1);


use Migrations\AbstractMigration;


/**
 * Class CreateDummyUsers
 */
class CreateDummyUsers extends AbstractMigration {
	/**
	 * @var bool $autoId A flag to indicate whether to automatically generate an ID for the migration.
	 */
	public bool $autoId = false;


	/**
	 * Migrate Up.
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$this->table('dummy_users')->addColumn('id', 'integer', [
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
		])->addColumn('lastLogin', 'datetime', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('failedAttempts', 'integer', [
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
		])->addColumn('createdBy', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => true,
			'signed' => true,
		])->addColumn('createdOn', 'datetime', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('changedBy', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => true,
			'signed' => true,
		])->addColumn('changedOn', 'datetime', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addColumn('deletedBy', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => true,
			'signed' => true,
		])->addColumn('deletedOn', 'datetime', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addIndex(
			[
				'active',
			],
			[
				'name' => 'DUMMY_USERS_ACTIVE',
			]
		)->addIndex(
			[
				'deleted',
			],
			[
				'name' => 'DUMMY_USERS_DELETED',
			]
		)->addIndex(
			[
				'username',
			],
			[
				'name' => 'DUMMY_USERS_USERNAME',
			]
		)->create();
	}
}
