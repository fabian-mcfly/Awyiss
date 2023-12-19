<?php declare(strict_types=1);

use Migrations\AbstractSeed;

/**
 * PageRoles seed.
 */
class PageRolesSeed extends AbstractSeed {
	/**
	 * {@inheritDoc}
	 */
	public function run(): void {
		$la_data = [
            [
                'id' => 4,
                'identifier' => 'news',
                'title' => 'News',
                'include_in_linklist' => 1,
                'system_order' => 1,
                'active' => 1,
                'deleted' => 0,
                'created_by' => 1,
                'created_on' => '2023-06-15 17:51:47',
                'changed_by' => 1,
                'changed_on' => '2023-08-05 21:12:33',
                'deleted_by' => NULL,
                'deleted_on' => NULL,
            ],
            [
                'id' => 3,
                'identifier' => 'promotion',
                'title' => 'Promotion',
                'include_in_linklist' => 1,
                'system_order' => 2,
                'active' => 1,
                'deleted' => 0,
                'created_by' => 1,
                'created_on' => '2023-06-11 11:22:25',
                'changed_by' => 1,
                'changed_on' => '2023-11-27 18:07:51',
                'deleted_by' => NULL,
                'deleted_on' => NULL,
            ],
            [
                'id' => 1,
                'identifier' => 'page',
                'title' => 'Seite!',
                'include_in_linklist' => 1,
                'system_order' => 3,
                'active' => 1,
                'deleted' => 0,
                'created_by' => 1,
                'created_on' => '2023-05-30 16:40:04',
                'changed_by' => NULL,
                'changed_on' => NULL,
                'deleted_by' => NULL,
                'deleted_on' => NULL,
            ],
            [
                'id' => 5,
                'identifier' => 'event',
                'title' => 'Termin',
                'include_in_linklist' => 1,
                'system_order' => 4,
                'active' => 1,
                'deleted' => 0,
                'created_by' => 1,
                'created_on' => '2023-11-05 13:26:41',
                'changed_by' => NULL,
                'changed_on' => NULL,
                'deleted_by' => NULL,
                'deleted_on' => NULL,
            ],
        ];

		$lo_table = $this->table('page_roles');
		$lo_table->truncate();
		$lo_table->insert($la_data)->save();
	}
}
