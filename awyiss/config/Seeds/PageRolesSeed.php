<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * PageRoles seed.
 */
class PageRolesSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'identifier' => 'page',
				'title' => 'Seite',
				'includeInLinklist' => 1,
				'systemOrder' => 1,
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

		$table = $this->table('page_roles');
		$table->insert($data)->save();
	}
}
