<?php declare(strict_types=1);

use Migrations\AbstractSeed;

/**
 * MediaCompositeAssignments seed.
 */
class MediaCompositeAssignmentsSeed extends AbstractSeed {
	/**
	 * {@inheritDoc}
	 */
	public function run(): void {
		$la_data = [
            [
                'id' => 1,
                'media_composite_id' => 1,
                'scope' => 'content_templates',
                'foreign_key' => 1,
            ],
        ];

		$lo_table = $this->table('media_composite_assignments');
		$lo_table->insert($la_data)->save();
	}
}
