<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * MediaElementAssignmentsCustomSeed   seed.
 */
class MediaElementAssignmentsCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 3,
				'media_element_id' => 3,
				'scope' => 'cars',
				'foreign_key' => null,
			],
			[
				'id' => 2,
				'media_element_id' => 2,
				'scope' => 'global_content_templates',
				'foreign_key' => 1,
			],
			[
				'id' => 4,
				'media_element_id' => 4,
				'scope' => 'content_templates',
				'foreign_key' => 2,
			],
			[
				'id' => 5,
				'media_element_id' => 4,
				'scope' => 'page_templates',
				'foreign_key' => 2,
			],
		];

		$table = $this->table('media_element_assignments');
		$table->insert($data)->save();
	}
}
