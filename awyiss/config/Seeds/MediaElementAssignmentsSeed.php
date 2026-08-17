<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Migrations\BaseSeed;


/**
 * MediaElementAssignments seed.
 */
class MediaElementAssignmentsSeed extends BaseSeed {
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
		$table
			->insert($data)
			->save()
		;
	}
}
