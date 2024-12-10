<?php declare(strict_types=1);


/**
 * Class UrlsNotFound
 */
class UrlsNotFound {
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
		if ($this->migration->hasTable('urls_not_found')) {
			$this->migration->table('urls_not_found')->drop()->save();
		}

		$this->migration->table('urls_not_found')
		->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('url', 'string', [
			'default' => null,
			'limit' => 2048,
			'null' => false,
		])->addColumn('referrer', 'string', [
			'default' => null,
			'limit' => 2048,
			'null' => true,
		])->addColumn('is_robot', 'boolean', [
			'default' => 0,
			'limit' => null,
			'null' => false,
		])->addColumn('created_on', 'datetime', [
			'default' => null,
			'limit' => null,
			'null' => false,
		])->addIndex(
			[
				'url',
			], [
				'name' => 'URLS_NOT_FOUND_URL',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('urls_not_found')->drop()->save();
	}
}
