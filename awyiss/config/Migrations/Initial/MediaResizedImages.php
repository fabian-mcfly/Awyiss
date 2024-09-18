<?php declare(strict_types=1);


/**
 * Class MediaResizedImages
 */
class MediaResizedImages {
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
		$this->migration->table('media_resized_images')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('media_id', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addColumn('name', 'string', [
			'default' => null,
			'limit' => 100,
			'null' => false,
		])->addColumn('path', 'string', [
			'default' => null,
			'limit' => 1124,
			'null' => false,
		])->addColumn('width', 'integer', [
			'default' => null,
			'null' => true,
			'limit' => 5,
			'signed' => true,
		])->addColumn('height', 'integer', [
			'default' => null,
			'limit' => 5,
			'null' => true,
			'signed' => true,
		])->addColumn('real_width', 'integer', [
			'default' => null,
			'null' => true,
			'limit' => 5,
			'signed' => true,
		])->addColumn('real_height', 'integer', [
			'default' => null,
			'limit' => 5,
			'null' => true,
			'signed' => true,
		])->addColumn('strategy', 'boolean', [
			'default' => 1,
			'limit' => null,
			'null' => false,
		])->addColumn('status', 'boolean', [
			'default' => false,
			'limit' => null,
			'null' => false,
		])->addIndex(
			[
				'media_id',
			], [
				'name' => 'MEDIA_RESIZED_IMAGES_MEDIA_ID',
			]
		)->addIndex(
			[
				'name',
			], [
				'name' => 'MEDIA_RESIZED_IMAGES_NAME',
			]
		)->addIndex(
			[
				'path',
			], [
				'name' => 'MEDIA_RESIZED_IMAGES_PATH',
			]
		)->addIndex(
			[
				'status',
			], [
				'name' => 'MEDIA_RESIZED_IMAGES_STATUS',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('media_resized_images')->drop()->save();
	}
}
