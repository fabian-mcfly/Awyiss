<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * ContentAreas seed.
 */
class ContentAreasSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'identifier' => 'ContentArea',
				'title' => 'Content Area',
				'active' => 1,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null,
			],
		];

		$table = $this->table('content_areas');
		$table->insert($data)->save();
	}
}
