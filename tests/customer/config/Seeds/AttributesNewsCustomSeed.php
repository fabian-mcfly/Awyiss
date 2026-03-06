<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * AttributesNews seed.
 */
class AttributesNewsCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'pageId' => 38,
				'date' => '2020-02-02',
				'teaser' => '<p><awyiss-responsive-image>{"mediaId":"4"}</awyiss-responsive-image></p>',
				'text' => null,
			],
		];

		$table = $this->table('attributes_news');
		$table->truncate();
		$table->insert($data)->save();
	}
}
