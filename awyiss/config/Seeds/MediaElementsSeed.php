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
		$data = [
            [
                'id' => 1,
                'title' => 'Hidden Folder',
                'identifier' => 'hidden_folder',
                'column_span' => '12/12',
                'internal' => 1,
                'system_order' => 0,
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
                'title' => 'Standard',
                'identifier' => 'standard',
                'column_span' => '12/12',
				'internal' => 0,
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
                'id' => 3,
                'title' => 'Titel- & Teaserbild',
                'identifier' => 'title_and_teaser_image',
                'column_span' => '12/12',
				'internal' => 0,
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
                'id' => 4,
                'title' => 'Galerie',
                'identifier' => 'gallery',
                'column_span' => '12/12',
				'internal' => 0,
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
            [
                'id' => 5,
                'title' => 'Inline Image Tag',
                'identifier' => 'inline_img_tag',
                'column_span' => '12/12',
				'internal' => 1,
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
        ];

		$table = $this->table('media_elements');
		$table->insert($data)->save();
	}
}
