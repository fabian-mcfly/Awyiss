<?php declare(strict_types=1);


use Migrations\BaseSeed;


/**
 * BackendMenuEntries seed.
 */
class BackendMenuEntriesCustomSeed extends BaseSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'parentId' => 'media',
				'insertAfterId' => null,
				'title' => 'Database Entry 1',
				'link' => 'https://example.com/database-entry-1',
				'access' => null,
				'external' => 0,
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
			[
				'id' => 2,
				'parentId' => 1,
				'insertAfterId' => null,
				'title' => 'Database Entry 2',
				'link' => 'https://example.com/database-entry-2',
				'access' => null,
				'external' => 0,
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
			[
				'id' => 3,
				'parentId' => null,
				'insertAfterId' => 'dummyEntry2',
				'title' => 'Database Entry 3',
				'link' => 'https://example.com/database-entry-3',
				'access' => null,
				'external' => 0,
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
			[
				'id' => 4,
				'parentId' => null,
				'insertAfterId' => 'dummyEntry2',
				'title' => 'Database Entry 4',
				'link' => 'https://example.com/database-entry-4',
				'access' => null,
				'external' => 0,
				'systemOrder' => 2,
				'active' => 0,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 5,
				'parentId' => null,
				'insertAfterId' => 'dummyEntry2',
				'title' => 'Database Entry 5',
				'link' => 'https://example.com/database-entry-5',
				'access' => null,
				'external' => 0,
				'systemOrder' => 3,
				'active' => 0,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 6,
				'parentId' => 2,
				'insertAfterId' => null,
				'title' => 'Database Entry 2 -> Sub 1',
				'link' => 'https://example.com/database-entry-2/sub-1',
				'access' => null,
				'external' => 0,
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
			[
				'id' => 7,
				'parentId' => 6,
				'insertAfterId' => null,
				'title' => 'Database Entry 2 -> Sub 1 -> Sub 1',
				'link' => 'https://example.com/database-entry-2/sub-1/sub-1',
				'access' => null,
				'external' => 0,
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

		$table = $this->table('backend_menu_entries');
		$table->truncate();
		$table->insert($data)->save();
	}
}
