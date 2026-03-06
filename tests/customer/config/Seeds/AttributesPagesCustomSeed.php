<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * AttributesPages seed.
 */
class AttributesPagesCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'pageId' => 2,
				'date' => '2020-02-02',
			],
		];

		$table = $this->table('attributes_pages');
		$table->truncate();
		$table->insert($data)->save();
	}
}
