<?php declare(strict_types=1);


use Migrations\BaseSeed;


/**
 * MediaFoldersCustom seed.
 */
class MediaFoldersCustomSeed extends BaseSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'parentId' => null,
				'languageShortcode' => null,
				'path' => '../awyiss/Command/Media/TestFiles',
				'title' => 'TestFiles',
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
			[
				'id' => 2,
				'parentId' => null,
				'languageShortcode' => 'de',
				'path' => '../tmp/media/testfolder1',
				'title' => 'Testfolder1',
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
			[
				'id' => 3,
				'parentId' => null,
				'languageShortcode' => 'de',
				'path' => '../tmp/media/testfolder2',
				'title' => 'Testfolder2',
				'hidden' => 0,
				'systemOrder' => 2,
				'active' => 1,
				'deleted' => 1,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 4,
				'parentId' => null,
				'languageShortcode' => 'de',
				'path' => '../tmp/media/testfolder3',
				'title' => 'Testfolder3',
				'hidden' => 1,
				'systemOrder' => 3,
				'active' => 0,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 5,
				'parentId' => 2,
				'languageShortcode' => 'de',
				'path' => '../tmp/media/testfolder1/subfolder1',
				'title' => 'Subfolder1',
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
			[
				'id' => 6,
				'parentId' => 5,
				'languageShortcode' => 'de',
				'path' => '../tmp/media/testfolder1/subfolder1/subfolder2',
				'title' => 'Subfolder2',
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
		$table->truncate();
		$table->insert($data)->save();
	}
}
