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
                'media_element_id' => 2,
                'media_selector_id' => 1,
                'title' => 'Titelbild',
                'identifier' => 'title_media',
                'column_span' => '6/12',
                'required' => 0,
            ],
            [
                'id' => 2,
                'media_element_id' => 2,
                'media_selector_id' => 1,
                'title' => 'Alternatives Teaserbild',
                'identifier' => 'teaser_media',
                'column_span' => '6/12',
                'required' => 0,
            ],
            [
                'id' => 3,
                'media_element_id' => 1,
                'media_selector_id' => 1,
                'title' => 'Datei',
                'identifier' => 'media',
                'column_span' => '6/12',
                'required' => 0,
            ],
            [
                'id' => 4,
                'media_element_id' => 1,
                'media_selector_id' => 1,
                'title' => 'Lightbox-Datei',
                'identifier' => 'lightbox_media',
                'column_span' => '6/12',
                'required' => 0,
            ],
            [
                'id' => 5,
                'media_element_id' => 3,
                'media_selector_id' => 2,
                'title' => 'Galerie',
                'identifier' => 'media',
                'column_span' => '12/12',
                'required' => 0,
            ],
        ];

		$lo_table = $this->table('media_element_selectors');
		$lo_table->insert($la_data)->save();
	}
}
