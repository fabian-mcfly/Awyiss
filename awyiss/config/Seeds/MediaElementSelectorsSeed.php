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
		$data = [
            [
                'id' => 1,
                'mediaElementId' => 1,
                'mediaSelectorId' => 3,
                'title' => 'Hidden Folder',
                'identifier' => 'hiddenFolder',
                'columnSpan' => '12/12',
                'required' => 0,
                'systemOrder' => 0,
            ],
            [
                'id' => 2,
                'mediaElementId' => 3,
                'mediaSelectorId' => 1,
                'title' => 'Titelbild',
                'identifier' => 'titleMedia',
                'columnSpan' => '6/12',
                'required' => 0,
                'systemOrder' => 1,
            ],
            [
                'id' => 3,
                'mediaElementId' => 3,
                'mediaSelectorId' => 1,
                'title' => 'Alternatives Teaserbild',
                'identifier' => 'teaserMedia',
                'columnSpan' => '6/12',
                'required' => 0,
				'systemOrder' => 2,
            ],
            [
                'id' => 4,
                'mediaElementId' => 2,
                'mediaSelectorId' => 1,
                'title' => 'Datei',
                'identifier' => 'media',
                'columnSpan' => '6/12',
                'required' => 0,
				'systemOrder' => 1,
            ],
            [
                'id' => 5,
                'mediaElementId' => 2,
                'mediaSelectorId' => 1,
                'title' => 'Lightbox-Datei',
                'identifier' => 'lightboxMedia',
                'columnSpan' => '6/12',
                'required' => 0,
				'systemOrder' => 2,
            ],
            [
                'id' => 6,
                'mediaElementId' => 4,
                'mediaSelectorId' => 2,
                'title' => 'Galerie',
                'identifier' => 'media',
                'columnSpan' => '12/12',
                'required' => 0,
				'systemOrder' => 1,
            ],
            [
                'id' => 7,
                'mediaElementId' => 5,
                'mediaSelectorId' => 1,
                'title' => 'Inline Image Tag',
                'identifier' => 'inlineImgTag',
                'columnSpan' => '12/12',
                'required' => 0,
				'systemOrder' => 0,
            ],
        ];

		$table = $this->table('media_element_selectors');
		$table->insert($data)->save();
	}
}
