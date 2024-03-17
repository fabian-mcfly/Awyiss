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
		$la_data = [
			[
				'id' => 1,
				'identifier' => 'page',
				'title' => 'Seite',
				'include_in_linklist' => 1,
				'system_order' => 1,
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

		$lo_table = $this->table('page_roles');
		$lo_table->insert($la_data)->save();
	}
}
