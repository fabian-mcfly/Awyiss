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
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
		];

		$table = $this->table('content_areas');
		$table->insert($data)->save();
	}
}
