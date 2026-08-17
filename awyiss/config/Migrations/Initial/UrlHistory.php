<?php declare(strict_types=1);

/**
 * Class UrlHistory
 */
class UrlHistory {
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
		if ($this->migration->hasTable('url_history')) {
			$this->migration
				->table('url_history')
				->drop()
				->save()
			;
		}

		$this->migration
			->table('url_history')
			->addColumn('id', 'integer', [
				'autoIncrement' => true,
				'default' => null,
				'limit' => null,
				'null' => false,
				'signed' => true,
			])
			->addPrimaryKey(['id'])
			->addColumn('url', 'string', [
				'default' => null,
				'limit' => 1024,
				'null' => false,
			])
			->addColumn('scope', 'string', [
				'default' => null,
				'limit' => 50,
				'null' => true,
			])
			->addColumn('foreign_key', 'integer', [
				'default' => null,
				'limit' => 11,
				'null' => true,
				'signed' => true,
			])
			->addColumn('target', 'string', [
				'default' => null,
				'limit' => 1024,
				'null' => true,
			])
			->addColumn('status', 'integer', [
				'default' => null,
				'limit' => 3,
				'null' => true,
				'signed' => true,
			])
			->addColumn('created_by', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => true,
				'signed' => true,
			])
			->addColumn('created_on', 'datetime', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->addColumn('changed_by', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => true,
				'signed' => true,
			])
			->addColumn('changed_on', 'datetime', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->addColumn('deleted_by', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => true,
				'signed' => true,
			])
			->addColumn('deleted_on', 'datetime', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->addIndex(
				[
					'url',
				], [
					'name' => 'URL_HISTORY_URL',
				]
			)
			->addIndex(
				[
					'scope',
				], [
					'name' => 'URL_HISTORY_SCOPE',
				]
			)
			->addIndex(
				[
					'foreign_key',
				], [
					'name' => 'URL_HISTORY_FOREIGN_KEY',
				]
			)
			->addIndex(
				[
					'scope',
					'foreign_key',
				], [
					'name' => 'URL_HISTORY_SCOPE_FOREIGN_KEY',
				]
			)
			->create()
		;
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration
			->table('url_history')
			->drop()
			->save()
		;
	}
}
