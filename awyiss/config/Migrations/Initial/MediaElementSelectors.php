<?php declare(strict_types=1);

/**
 * Class MediaElementSelectors
 */
class MediaElementSelectors {
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
		if ($this->migration->hasTable('media_element_selectors')) {
			$this->migration
				->table('media_element_selectors')
				->drop()
				->save()
			;
		}

		$this->migration
			->table('media_element_selectors')
			->addColumn('id', 'integer', [
				'autoIncrement' => true,
				'default' => null,
				'limit' => null,
				'null' => false,
				'signed' => true,
			])
			->addPrimaryKey(['id'])
			->addColumn('media_element_id', 'integer', [
				'limit' => 11,
				'null' => false,
			])
			->addColumn('media_selector_id', 'integer', [
				'limit' => 11,
				'null' => false,
			])
			->addColumn('title', 'string', [
				'default' => null,
				'limit' => 50,
				'null' => false,
			])
			->addColumn('identifier', 'string', [
				'default' => null,
				'limit' => 50,
				'null' => false,
			])
			->addColumn('column_span', 'string', [
				'default' => '12/12',
				'limit' => 5,
				'null' => false,
			])
			->addColumn('required', 'boolean', [
				'default' => false,
				'limit' => null,
				'null' => false,
			])
			->addColumn('system_order', 'integer', [
				'default' => '0',
				'limit' => null,
				'null' => false,
				'signed' => true,
			])
			->addIndex(
				[
					'identifier',
				], [
					'name' => 'MEDIA_ELEMENT_SELECTORS_IDENTIFIER',
				]
			)
			->addIndex(
				[
					'media_element_id',
				], [
					'name' => 'MEDIA_ELEMENT_SELECTORS_MEDIA_ELEMENT_ID',
				]
			)
			->addIndex(
				[
					'media_selector_id',
				], [
					'name' => 'MEDIA_ELEMENT_SELECTORS_MEDIA_SELECTOR_ID',
				]
			)
			->addIndex(
				[
					'system_order',
				], [
					'name' => 'MEDIA_ELEMENT_SELECTORS_SYSTEM_ORDER',
				]
			)
			->create()
		;

		// Insert a dummy record with id 10 and then delete it
		$this->migration->execute(
			'INSERT INTO `media_element_selectors` (`id`, `media_element_id`, `media_selector_id`, `title`, `identifier`)
			VALUES (10, "1", "1", "dummy_title", "dummy_identifier")'
		);
		$this->migration->execute('DELETE FROM `media_element_selectors` WHERE `id` = 10');
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration
			->table('media_element_selectors')
			->drop()
			->save()
		;
	}
}
