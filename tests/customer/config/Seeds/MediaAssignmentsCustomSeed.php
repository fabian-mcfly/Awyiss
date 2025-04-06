<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * MediaAssignmentsCustomSeed seed.
 */
class MediaAssignmentsCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$la_data = [
			[
				'id' => 1,
				'media_element_id' => 2,
				'media_element_selector_identifier' => 'media',
				'media_id' => 4,
				'media_folder_id' => null,
				'scope' => 'widgets',
				'foreign_key' => 4,
				'system_order' => 1,
				'deleted' => 0,
			],
			[
				'id' => 2,
				'media_element_id' => 2,
				'media_element_selector_identifier' => 'lightbox_media',
				'media_id' => 3,
				'media_folder_id' => null,
				'scope' => 'widgets',
				'foreign_key' => 4,
				'system_order' => 1,
				'deleted' => 0,
			],
			[
				'id' => 3,
				'media_element_id' => 2,
				'media_element_selector_identifier' => 'media',
				'media_id' => 2,
				'media_folder_id' => null,
				'scope' => 'widgets',
				'foreign_key' => 6,
				'system_order' => 1,
				'deleted' => 0,
			],
			[
				'id' => 4,
				'media_element_id' => 2,
				'media_element_selector_identifier' => 'media',
				'media_id' => 2,
				'media_folder_id' => null,
				'scope' => 'widgets',
				'foreign_key' => 7,
				'system_order' => 1,
				'deleted' => 0,
			],
			[
				'id' => 5,
				'media_element_id' => 2,
				'media_element_selector_identifier' => 'media',
				'media_id' => 2,
				'media_folder_id' => null,
				'scope' => 'widgets',
				'foreign_key' => 16,
				'system_order' => 1,
				'deleted' => 0,
			],
			[
				'id' => 6,
				'media_element_id' => 2,
				'media_element_selector_identifier' => 'media',
				'media_id' => 4,
				'media_folder_id' => null,
				'scope' => 'contents',
				'foreign_key' => 18,
				'system_order' => 1,
				'deleted' => 0,
			],
			[
				'id' => 7,
				'media_element_id' => 2,
				'media_element_selector_identifier' => 'lightbox_media',
				'media_id' => 3,
				'media_folder_id' => null,
				'scope' => 'contents',
				'foreign_key' => 18,
				'system_order' => 1,
				'deleted' => 0,
			],
			[
				'id' => 8,
				'media_element_id' => 2,
				'media_element_selector_identifier' => 'media',
				'media_id' => 2,
				'media_folder_id' => null,
				'scope' => 'contents',
				'foreign_key' => 2,
				'system_order' => 1,
				'deleted' => 0,
			],
			[
				'id' => 9,
				'media_element_id' => 2,
				'media_element_selector_identifier' => 'media',
				'media_id' => 2,
				'media_folder_id' => null,
				'scope' => 'contents',
				'foreign_key' => 16,
				'system_order' => 1,
				'deleted' => 0,
			],
			[
				'id' => 10,
				'media_element_id' => 2,
				'media_element_selector_identifier' => 'media',
				'media_id' => 2,
				'media_folder_id' => null,
				'scope' => 'contents',
				'foreign_key' => 39,
				'system_order' => 1,
				'deleted' => 0,
			],
			[
				'id' => 11,
				'media_element_id' => 1,
				'media_element_selector_identifier' => 'hidden_folder',
				'media_id' => null,
				'media_folder_id' => 1,
				'scope' => 'news',
				'foreign_key' => 21,
				'system_order' => 1,
				'deleted' => 0,
			],
		];

		$lo_table = $this->table('media_assignments');
		$lo_table->truncate();
		$lo_table->insert($la_data)->save();
	}
}
