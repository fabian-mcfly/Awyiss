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
		$la_data = [
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

		$lo_table = $this->table('media_selectors');
		$lo_table->insert($la_data)->save();
	}
}
