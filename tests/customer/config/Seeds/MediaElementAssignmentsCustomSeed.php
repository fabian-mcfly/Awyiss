<?php declare(strict_types=1);


use Migrations\BaseSeed;


/**
 * MediaElementAssignmentsCustomSeed   seed.
 */
class MediaElementAssignmentsCustomSeed extends BaseSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 3,
				'mediaElementId' => 3,
				'scope' => 'Cars',
				'foreignKey' => null,
			],
			[
				'id' => 2,
				'mediaElementId' => 2,
				'scope' => 'GlobalContentTemplates',
				'foreignKey' => 1,
			],
			[
				'id' => 4,
				'mediaElementId' => 4,
				'scope' => 'ContentTemplates',
				'foreignKey' => 2,
			],
			[
				'id' => 5,
				'mediaElementId' => 4,
				'scope' => 'PageTemplates',
				'foreignKey' => 2,
			],
		];

		$table = $this->table('media_element_assignments');
		$table->insert($data)->save();
	}
}
