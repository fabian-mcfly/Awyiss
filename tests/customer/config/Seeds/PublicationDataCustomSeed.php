<?php declare(strict_types=1);


use Migrations\BaseSeed;


/**
 * PublicationData seed.
 */
class PublicationDataCustomSeed extends BaseSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'scope' => 'GlobalContents',
				'foreignKey' => 9,
				'type' => 'end',
				'dateTime' => '2022-06-01 18:59:59',
			],
			[
				'id' => 2,
				'scope' => 'GlobalContents',
				'foreignKey' => 11,
				'type' => 'start',
				'dateTime' => '2057-07-18 04:04:04',
			],
			[
				'id' => 3,
				'scope' => 'MenuEntries',
				'foreignKey' => 27,
				'type' => 'end',
				'dateTime' => '2022-06-01 18:59:59',
			],
			[
				'id' => 4,
				'scope' => 'MenuEntries',
				'foreignKey' => 22,
				'type' => 'start',
				'dateTime' => '2057-07-18 04:04:04',
			],
			[
				'id' => 5,
				'scope' => 'Menus',
				'foreignKey' => 3,
				'type' => 'end',
				'dateTime' => '2022-06-01 18:59:59',
			],
			[
				'id' => 6,
				'scope' => 'Menus',
				'foreignKey' => 3,
				'type' => 'start',
				'dateTime' => '2057-07-18 04:04:04',
			],
			[
				'id' => 7,
				'scope' => 'Contents',
				'foreignKey' => 25,
				'type' => 'end',
				'dateTime' => '2022-06-01 18:59:59',
			],
			[
				'id' => 8,
				'scope' => 'Contents',
				'foreignKey' => 25,
				'type' => 'start',
				'dateTime' => '2057-07-18 04:04:04',
			],
		];

		$table = $this->table('publication_data');
		$table->truncate();
		$table->insert($data)->save();
	}
}
