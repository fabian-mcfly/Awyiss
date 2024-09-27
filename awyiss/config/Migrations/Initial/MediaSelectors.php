<?php declare(strict_types=1);


/**
 * Class MediaSelectors
 */
class MediaSelectors {
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
		if ($this->migration->hasTable('media_selectors')) {
			$this->migration->table('media_selectors')->drop()->save();
		}

		$this->migration->table('media_selectors')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('title', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => false,
		])->addColumn('identifier', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => false,
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
				'identifier',
			], [
				'name' => 'MEDIA_SELECTORS_IDENTIFIER',
			]
		)->addIndex(
			[
				'active',
			], [
				'name' => 'MEDIA_SELECTORS_ACTIVE',
			]
		)->addIndex(
			[
				'deleted',
			], [
				'name' => 'MEDIA_SELECTORS_DELETED',
			]
		)->create();

		// Insert a dummy record with id 10 and then delete it
		$this->migration->execute(
			'INSERT INTO `media_selectors` (`id`, `title`, `identifier`, `active`, `deleted`) 
            VALUES (10, "dummy_title", "dummy_identifier", 1, 0)'
		);
		$this->migration->execute('DELETE FROM `media_selectors` WHERE `id` = 10');
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('media_selectors')->drop()->save();
	}
}
