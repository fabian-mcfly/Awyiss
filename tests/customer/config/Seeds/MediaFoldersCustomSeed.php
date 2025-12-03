<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * MediaFoldersCustom seed.
 */
class MediaFoldersCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'parent_id' => null,
				'language_shortcode' => null,
				'path' => '../awyiss/Command/Media/TestFiles',
				'title' => 'TestFiles',
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
			[
				'id' => 2,
				'parent_id' => null,
				'language_shortcode' => 'de',
				'path' => '../tmp/media/testfolder1',
				'title' => 'Testfolder1',
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
			[
				'id' => 3,
				'parent_id' => null,
				'language_shortcode' => 'de',
				'path' => '../tmp/media/testfolder2',
				'title' => 'Testfolder2',
				'hidden' => 0,
				'system_order' => 2,
				'active' => 1,
				'deleted' => 1,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null,
			],
			[
				'id' => 4,
				'parent_id' => null,
				'language_shortcode' => 'de',
				'path' => '../tmp/media/testfolder3',
				'title' => 'Testfolder3',
				'hidden' => 1,
				'system_order' => 3,
				'active' => 0,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null,
			],
			[
				'id' => 5,
				'parent_id' => 2,
				'language_shortcode' => 'de',
				'path' => '../tmp/media/testfolder1/subfolder1',
				'title' => 'Subfolder1',
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
			[
				'id' => 6,
				'parent_id' => 5,
				'language_shortcode' => 'de',
				'path' => '../tmp/media/testfolder1/subfolder1/subfolder2',
				'title' => 'Subfolder2',
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
		$table->truncate();
		$table->insert($data)->save();
	}
}
