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
		$la_data = [
            [
                'id' => 1,
                'media_element_id' => 2,
                'scope' => 'content_templates',
                'foreign_key' => 1,
            ],
        ];

		$lo_table = $this->table('media_element_assignments');
		$lo_table->insert($la_data)->save();
	}
}
