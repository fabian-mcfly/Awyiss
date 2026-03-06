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
				'parentId' => null,
				'languageShortcode' => null,
				'path' => 'media',
				'title' => 'Media',
				'hidden' => 0,
				'systemOrder' => 1,
				'active' => 1,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
		];

		$table = $this->table('media_folders');
		$table->insert($data)->save();
	}
}
