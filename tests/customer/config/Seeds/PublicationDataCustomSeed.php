<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * PublicationData seed.
 */
class PublicationDataCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'scope' => 'global_contents',
				'foreign_key' => 9,
				'type' => 'end',
				'date_time' => '2022-06-01 18:59:59',
			],
			[
				'id' => 2,
				'scope' => 'global_contents',
				'foreign_key' => 11,
				'type' => 'start',
				'date_time' => '2057-07-18 04:04:04',
			],
			[
				'id' => 3,
				'scope' => 'menu_entries',
				'foreign_key' => 27,
				'type' => 'end',
				'date_time' => '2022-06-01 18:59:59',
			],
			[
				'id' => 4,
				'scope' => 'menu_entries',
				'foreign_key' => 22,
				'type' => 'start',
				'date_time' => '2057-07-18 04:04:04',
			],
			[
				'id' => 5,
				'scope' => 'menus',
				'foreign_key' => 3,
				'type' => 'end',
				'date_time' => '2022-06-01 18:59:59',
			],
			[
				'id' => 6,
				'scope' => 'menus',
				'foreign_key' => 3,
				'type' => 'start',
				'date_time' => '2057-07-18 04:04:04',
			],
			[
				'id' => 7,
				'scope' => 'contents',
				'foreign_key' => 25,
				'type' => 'end',
				'date_time' => '2022-06-01 18:59:59',
			],
			[
				'id' => 8,
				'scope' => 'contents',
				'foreign_key' => 25,
				'type' => 'start',
				'date_time' => '2057-07-18 04:04:04',
			],
		];

		$table = $this->table('publication_data');
		$table->truncate();
		$table->insert($data)->save();
	}
}
