<?php declare(strict_types=1);


/**
 * Class ContentTemplateContentAreas
 */
class ContentTemplateContentAreas {
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
		if ($this->migration->hasTable('content_template_content_areas')) {
			$this->migration->table('content_template_content_areas')->drop()->save();
		}

		$this->migration->table('content_template_content_areas')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('content_template_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addColumn('content_area_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addColumn('page_template_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addIndex(
			[
				'content_template_id',
			], [
				'name' => 'CONTENT_TEMPLATE_CONTENT_AREAS_CONTENT_TEMPLATE_ID',
			]
		)->addIndex(
			[
				'content_area_id',
			], [
				'name' => 'CONTENT_TEMPLATE_CONTENT_AREAS_CONTENT_AREA_ID',
			]
		)->addIndex(
			[
				'page_template_id',
			], [
				'name' => 'CONTENT_TEMPLATE_CONTENT_AREAS_PAGE_TEMPLATE_ID',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 * 
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('content_template_content_areas')->drop()->save();
	}
}
