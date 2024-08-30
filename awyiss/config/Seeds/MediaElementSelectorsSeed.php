<?php declare(strict_types=1);

use Migrations\AbstractSeed;

/**
 * MediaElementSelectors seed.
 */
class MediaElementSelectorsSeed extends AbstractSeed {
	/**
	 * {@inheritDoc}
	 */
	public function run(): void {
		$la_data = [
            [
                'id' => 1,
                'media_element_id' => 0,
                'media_selector_id' => 3,
                'title' => 'Hidden Folder',
                'identifier' => 'hidden_folder',
                'column_span' => '12/12',
                'required' => 0,
                'system_order' => 0,
            ],
            [
                'id' => 2,
                'media_element_id' => 2,
                'media_selector_id' => 1,
                'title' => 'Titelbild',
                'identifier' => 'title_media',
                'column_span' => '6/12',
                'required' => 0,
                'system_order' => 1,
            ],
            [
                'id' => 3,
                'media_element_id' => 2,
                'media_selector_id' => 1,
                'title' => 'Alternatives Teaserbild',
                'identifier' => 'teaser_media',
                'column_span' => '6/12',
                'required' => 0,
				'system_order' => 2,
            ],
            [
                'id' => 4,
                'media_element_id' => 1,
                'media_selector_id' => 1,
                'title' => 'Datei',
                'identifier' => 'media',
                'column_span' => '6/12',
                'required' => 0,
				'system_order' => 1,
            ],
            [
                'id' => 5,
                'media_element_id' => 1,
                'media_selector_id' => 1,
                'title' => 'Lightbox-Datei',
                'identifier' => 'lightbox_media',
                'column_span' => '6/12',
                'required' => 0,
				'system_order' => 2,
            ],
            [
                'id' => 6,
                'media_element_id' => 3,
                'media_selector_id' => 2,
                'title' => 'Galerie',
                'identifier' => 'media',
                'column_span' => '12/12',
                'required' => 0,
				'system_order' => 1,
            ],
        ];

		$lo_table = $this->table('media_element_selectors');
		$lo_table->insert($la_data)->save();


		$la_data = [
			[
				'locale' => 'de',
				'model' => 'media_element_selectors',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'Titelbild',
			],
			[
				'locale' => 'en',
				'model' => 'media_element_selectors',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'Title image',
			],
			[
				'locale' => 'de',
				'model' => 'media_element_selectors',
				'foreign_key' => 3,
				'field' => 'title',
				'content' => 'Alternatives Teaserbild',
			],
			[
				'locale' => 'en',
				'model' => 'media_element_selectors',
				'foreign_key' => 3,
				'field' => 'title',
				'content' => 'Alternative Teaser image',
			],
			[
				'locale' => 'de',
				'model' => 'media_element_selectors',
				'foreign_key' => 4,
				'field' => 'title',
				'content' => 'Datei',
			],
			[
				'locale' => 'en',
				'model' => 'media_element_selectors',
				'foreign_key' => 4,
				'field' => 'title',
				'content' => 'File',
			],
			[
				'locale' => 'de',
				'model' => 'media_element_selectors',
				'foreign_key' => 5,
				'field' => 'title',
				'content' => 'Lightbox-Datei',
			],
			[
				'locale' => 'en',
				'model' => 'media_element_selectors',
				'foreign_key' => 5,
				'field' => 'title',
				'content' => 'Lightbox file',
			],
			[
				'locale' => 'de',
				'model' => 'media_element_selectors',
				'foreign_key' => 6,
				'field' => 'title',
				'content' => 'Galerie',
			],
			[
				'locale' => 'en',
				'model' => 'media_element_selectors',
				'foreign_key' => 6,
				'field' => 'title',
				'content' => 'Gallery',
			],
		];

		$lo_table = $this->table('i18n');
		$lo_table->insert($la_data)->save();
	}
}
