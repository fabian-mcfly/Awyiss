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
		$la_data = [
			[
				'id' => 1,
				'parent_id' => 'media',
				'insert_after_id' => null,
				'title' => 'Database Entry 1',
				'link' => 'https://example.com/database-entry-1',
				'access' => null,
				'external' => 0,
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
			[
				'id' => 2,
				'parent_id' => 1,
				'insert_after_id' => null,
				'title' => 'Database Entry 2',
				'link' => 'https://example.com/database-entry-2',
				'access' => null,
				'external' => 0,
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
			[
				'id' => 3,
				'parent_id' => null,
				'insert_after_id' => 'dummy_entry2',
				'title' => 'Database Entry 3',
				'link' => 'https://example.com/database-entry-3',
				'access' => null,
				'external' => 0,
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
			[
				'id' => 4,
				'parent_id' => null,
				'insert_after_id' => 'dummy_entry2',
				'title' => 'Database Entry 4',
				'link' => 'https://example.com/database-entry-4',
				'access' => null,
				'external' => 0,
				'system_order' => 2,
				'active' => 0,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null,
			],
			[
				'id' => 5,
				'parent_id' => null,
				'insert_after_id' => 'dummy_entry2',
				'title' => 'Database Entry 5',
				'link' => 'https://example.com/database-entry-5',
				'access' => null,
				'external' => 0,
				'system_order' => 3,
				'active' => 0,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null,
			],
			[
				'id' => 6,
				'parent_id' => 2,
				'insert_after_id' => null,
				'title' => 'Database Entry 2 -> Sub 1',
				'link' => 'https://example.com/database-entry-2/sub-1',
				'access' => null,
				'external' => 0,
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
			[
				'id' => 7,
				'parent_id' => 6,
				'insert_after_id' => null,
				'title' => 'Database Entry 2 -> Sub 1 -> Sub 1',
				'link' => 'https://example.com/database-entry-2/sub-1/sub-1',
				'access' => null,
				'external' => 0,
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

		$lo_table = $this->table('backend_menu_entries');
		$lo_table->truncate();
		$lo_table->insert($la_data)->save();
	}
}
