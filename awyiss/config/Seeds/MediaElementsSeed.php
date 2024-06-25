<?php declare(strict_types=1);

use Migrations\AbstractSeed;

/**
 * MediaElements seed.
 */
class MediaElementsSeed extends AbstractSeed {
	/**
	 * {@inheritDoc}
	 */
	public function run(): void {
		$la_data = [
            [
                'id' => 1,
                'title' => 'Standard',
                'identifier' => 'standard',
                'column_span' => '12/12',
                'system_order' => 1,
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
                'title' => 'Titel- & Teaserbild',
                'identifier' => 'title_and_teaser_image',
                'column_span' => '12/12',
                'system_order' => 2,
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
                'title' => 'Galerie',
                'identifier' => 'gallery',
                'column_span' => '12/12',
                'system_order' => 3,
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

		$lo_table = $this->table('media_elements');
		$lo_table->insert($la_data)->save();
	}
}
