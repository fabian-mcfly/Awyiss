<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * MediaFolders seed.
 */
class MediaFoldersSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'parent_id' => null,
				'language_shortcode' => null,
				'path' => 'media',
				'title' => 'Media',
				'hidden' => 0,
				'system_order' => 1,
				'active' => 1,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null,
			],
		];

		$table = $this->table('media_folders');
		$table->insert($data)->save();
	}
}
