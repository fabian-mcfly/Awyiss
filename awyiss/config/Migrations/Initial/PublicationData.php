<?php declare(strict_types=1);


/**
 * Class PublicationData
 */
class PublicationData {
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
		if ($this->migration->hasTable('publication_data')) {
			$this->migration->table('publication_data')->drop()->save();
		}

		$this->migration->table('publication_data')->addColumn('id', 'integer', [
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
		])->addColumn('type', 'string', [
			'default' => null,
			'limit' => 20,
			'null' => false,
		])->addColumn('date_time', 'datetime', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addIndex(
			[
				'foreign_key',
				'scope',
			], [
				'name' => 'PUBLICATION_DATA_FOREIGN_KEY_SCOPE',
			]
		)->addIndex(
			[
				'type',
			], [
				'name' => 'PUBLICATION_DATA_TYPE',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 * 
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('publication_data')->drop()->save();
	}
}
