<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * Datatables seed.
 */
class DatatablesCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'title' => 'Mitarbeiter',
				'identifier' => 'Employees',
				'active' => 1,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 2,
				'title' => 'Arbeitgeber',
				'identifier' => 'Employers',
				'active' => 1,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 3,
				'title' => 'Autos',
				'identifier' => 'Cars',
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

		$table = $this->table('datatables');
		$table->truncate();
		$table->insert($data)->save();
	}
}
