<?php declare(strict_types=1);


/**
 * Class PagesNotFound
 */
class PagesNotFound {
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
		$this->migration->table('pages_not_found')
		->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('slug', 'string', [
			'default' => null,
			'limit' => 2048,
			'null' => false,
		])->addColumn('referrer', 'string', [
			'default' => null,
			'limit' => 2048,
			'null' => true,
		])->addColumn('is_robot', 'boolean', [
			'default' => false,
			'limit' => null,
			'null' => true,
		])->addColumn('created_on', 'datetime', [
			'default' => 'current_timestamp()',
			'limit' => null,
			'null' => true,
		])->addIndex(
			[
				'slug',
			], [
				'name' => 'slug',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('pages_not_found')->drop()->save();
	}
}
