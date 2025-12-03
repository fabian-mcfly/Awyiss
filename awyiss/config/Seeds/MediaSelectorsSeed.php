<?php declare(strict_types=1);

use Migrations\AbstractSeed;

/**
 * MediaSelectors seed.
 */
class MediaSelectorsSeed extends AbstractSeed {
	/**
	 * {@inheritDoc}
	 */
	public function run(): void {
		$data = [
            [
                'id' => 1,
                'title' => 'Einzeldatei',
                'identifier' => 'single_file',
                'active' => 1,
                'deleted' => 0,
                'created_by' => 1,
                'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
                'changed_by' => NULL,
                'changed_on' => NULL,
                'deleted_by' => NULL,
                'deleted_on' => NULL,
            ],
            [
                'id' => 2,
                'title' => 'Mehrfachauswahl',
                'identifier' => 'multi_file',
                'active' => 1,
                'deleted' => 0,
                'created_by' => 1,
                'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
                'changed_by' => NULL,
                'changed_on' => NULL,
                'deleted_by' => NULL,
                'deleted_on' => NULL,
            ],
            [
                'id' => 3,
                'title' => 'Ordnerauswahl',
                'identifier' => 'folder',
                'active' => 1,
                'deleted' => 0,
                'created_by' => 1,
                'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
                'changed_by' => NULL,
                'changed_on' => NULL,
                'deleted_by' => NULL,
                'deleted_on' => NULL,
            ],
        ];

		$table = $this->table('media_selectors');
		$table->insert($data)->save();

		$data = [
			[
				'locale' => 'de',
				'model' => 'media_selectors',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'Einzeldatei',
			],
			[
				'locale' => 'en',
				'model' => 'media_selectors',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'Single file',
			],
			[
				'locale' => 'de',
				'model' => 'media_selectors',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'Mehrfachauswahl',
			],
			[
				'locale' => 'en',
				'model' => 'media_selectors',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'Multi file',
			],
			[
				'locale' => 'de',
				'model' => 'media_selectors',
				'foreign_key' => 3,
				'field' => 'title',
				'content' => 'Ordnerauswahl',
			],
			[
				'locale' => 'en',
				'model' => 'media_selectors',
				'foreign_key' => 3,
				'field' => 'title',
				'content' => 'Folder selection',
			],
		];

		$table = $this->table('i18n');
		$table->insert($data)->save();
	}
}
