<?php declare(strict_types=1);


/**
 * Class UsergroupPermissions
 */
class UsergroupPermissions {
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
		$this->migration->table('usergroup_permissions')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('usergroup_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addColumn('scope', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => false,
		])->addColumn('identifier', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => false,
		])->addColumn('access', 'boolean', [
			'default' => false,
			'limit' => null,
			'null' => false,
		])->addColumn('settings', 'text', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addIndex(
			[
				'usergroup_id',
			], [
				'name' => 'usergroup_id',
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
		)->create();
	}


	/**
	 * Migrate Down.
	 * 
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('usergroup_permissions')->drop()->save();
	}
}
