<?php declare(strict_types=1);


/**
 * Class Locks
 */
class Locks {
	/**
	 * @var \Initial $migration The migration that is being migrated
	 */
	protected \Initial $migration;


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
		if ($this->migration->hasTable('locks')) {
			$this->migration->table('locks')->drop()->save();
		}

		$this->migration->table('locks')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('scope', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => false,
		])->addColumn('foreign_key', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addColumn('unique_id', 'char', [
			'default' => null,
			'limit' => 36,
			'null' => false,
		])->addColumn('created_by', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addColumn('created_on', 'datetime', [
			'default' => null,
			'limit' => null,
			'null' => false,
		])->addIndex(
			[
				'scope',
				'foreign_key',
			], [
				'name' => 'LOCKS_SCOPE_KEY',
				'unique' => true,
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('locks')->drop()->save();
	}
}
