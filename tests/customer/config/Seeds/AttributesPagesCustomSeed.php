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
		$la_data = [
			[
				'id' => 1,
				'page_id' => 2,
				'date' => '2020-02-02',
			],
		];

		$lo_table = $this->table('attributes_pages');
		$lo_table->truncate();
		$lo_table->insert($la_data)->save();
	}
}
