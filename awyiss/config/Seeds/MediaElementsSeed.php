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
                'identifier' => 'hiddenFolder',
                'columnSpan' => '12/12',
                'internal' => 1,
                'systemOrder' => 0,
                'active' => 1,
                'deleted' => 0,
                'createdBy' => 1,
                'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
                'changedBy' => NULL,
                'changedOn' => NULL,
                'deletedBy' => NULL,
                'deletedOn' => NULL,
            ],
            [
                'id' => 2,
                'title' => 'Standard',
                'identifier' => 'standard',
                'columnSpan' => '12/12',
				'internal' => 0,
                'systemOrder' => 1,
                'active' => 1,
                'deleted' => 0,
                'createdBy' => 1,
                'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
                'changedBy' => NULL,
                'changedOn' => NULL,
                'deletedBy' => NULL,
                'deletedOn' => NULL,
            ],
            [
                'id' => 3,
                'title' => 'Titel- & Teaserbild',
                'identifier' => 'titleAndTeaserImage',
                'columnSpan' => '12/12',
				'internal' => 0,
                'systemOrder' => 2,
                'active' => 1,
                'deleted' => 0,
                'createdBy' => 1,
                'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
                'changedBy' => NULL,
                'changedOn' => NULL,
                'deletedBy' => NULL,
                'deletedOn' => NULL,
            ],
            [
                'id' => 4,
                'title' => 'Galerie',
                'identifier' => 'gallery',
                'columnSpan' => '12/12',
				'internal' => 0,
                'systemOrder' => 3,
                'active' => 1,
                'deleted' => 0,
                'createdBy' => 1,
                'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
                'changedBy' => NULL,
                'changedOn' => NULL,
                'deletedBy' => NULL,
                'deletedOn' => NULL,
            ],
            [
                'id' => 5,
                'title' => 'Inline Image Tag',
                'identifier' => 'inlineImgTag',
                'columnSpan' => '12/12',
				'internal' => 1,
                'systemOrder' => 1,
                'active' => 1,
                'deleted' => 0,
                'createdBy' => 1,
                'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
                'changedBy' => NULL,
                'changedOn' => NULL,
                'deletedBy' => NULL,
                'deletedOn' => NULL,
            ],
        ];

		$table = $this->table('media_elements');
		$table->insert($data)->save();
	}
}
