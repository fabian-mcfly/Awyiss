<?php declare(strict_types=1);

use Migrations\AbstractSeed;

/**
 * MediaElementAssignments seed.
 */
class MediaElementAssignmentsSeed extends AbstractSeed {
	/**
	 * {@inheritDoc}
	 */
	public function run(): void {
		$data = [
            [
                'id' => 1,
                'mediaElementId' => 2,
                'scope' => 'ContentTemplates',
                'foreignKey' => 1,
            ],
        ];

		$table = $this->table('media_element_assignments');
		$table->insert($data)->save();
	}
}
