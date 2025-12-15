<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * User Configuration seed.
 */
class UserConfigurationCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 2,
				'user_id' => 1,
				'scope' => 'newscategories',
				'identifier' => 'paginate.limit',
				'value' => '20',
			],
			[
				'id' => 4,
				'user_id' => 1,
				'scope' => 'newscategories',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 8,
				'user_id' => 1,
				'scope' => 'usergroups',
				'identifier' => 'paginate.limit',
				'value' => '20',
			],
			[
				'id' => 9,
				'user_id' => 1,
				'scope' => 'menu_entries',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 10,
				'user_id' => 1,
				'scope' => 'menu_entries',
				'identifier' => 'paginate.limit',
				'value' => '20',
			],
			[
				'id' => 14,
				'user_id' => 1,
				'scope' => 'pages',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 15,
				'user_id' => 1,
				'scope' => 'pages',
				'identifier' => 'paginate.limit',
				'value' => '100',
			],
			[
				'id' => 16,
				'user_id' => 1,
				'scope' => 'news',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 20,
				'user_id' => 1,
				'scope' => 'contents',
				'identifier' => 'overview.column_view.enabled',
				'value' => '1',
			],
			[
				'id' => 21,
				'user_id' => 1,
				'scope' => 'datatables',
				'identifier' => 'paginate.limit',
				'value' => '20',
			],
			[
				'id' => 22,
				'user_id' => 1,
				'scope' => 'employers',
				'identifier' => 'paginate.enabled',
				'value' => '1',
			],
			[
				'id' => 23,
				'user_id' => 1,
				'scope' => 'employers',
				'identifier' => 'paginate.limit',
				'value' => '20',
			],
			[
				'id' => 24,
				'user_id' => 1,
				'scope' => 'news',
				'identifier' => 'paginate.limit',
				'value' => '5',
			],
			[
				'id' => 25,
				'user_id' => 1,
				'scope' => 'languages',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 26,
				'user_id' => 1,
				'scope' => 'menus',
				'identifier' => 'paginate.limit',
				'value' => '20',
			],
			[
				'id' => 27,
				'user_id' => 1,
				'scope' => 'attributes',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 28,
				'user_id' => 1,
				'scope' => 'content_templates',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 29,
				'user_id' => 1,
				'scope' => 'page_roles',
				'identifier' => 'paginate.enabled',
				'value' => '1',
			],
			[
				'id' => 30,
				'user_id' => 1,
				'scope' => 'page_templates',
				'identifier' => 'paginate.enabled',
				'value' => '1',
			],
			[
				'id' => 31,
				'user_id' => 1,
				'scope' => 'url_history',
				'identifier' => 'paginate.limit',
				'value' => '50',
			],
			[
				'id' => 32,
				'user_id' => 1,
				'scope' => 'media_folders',
				'identifier' => 'paginate.enabled',
				'value' => '1',
			],
			[
				'id' => 37,
				'user_id' => 1,
				'scope' => 'attributes',
				'identifier' => 'paginate.limit',
				'value' => '1',
			],
			[
				'id' => 40,
				'user_id' => 1,
				'scope' => 'media',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 41,
				'user_id' => 1,
				'scope' => 'media',
				'identifier' => 'paginate.limit',
				'value' => '20',
			],
			[
				'id' => 42,
				'user_id' => 1,
				'scope' => 'content_templates',
				'identifier' => 'paginate.limit',
				'value' => '100',
			],
			[
				'id' => 46,
				'user_id' => 1,
				'scope' => 'media_elements',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 49,
				'user_id' => 1,
				'scope' => 'global_content_templates',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 50,
				'user_id' => 1,
				'scope' => 'global_contents',
				'identifier' => 'overview.column_view.enabled',
				'value' => '1',
			],
			[
				'id' => 52,
				'user_id' => 1,
				'scope' => 'urls_not_found',
				'identifier' => 'paginate.limit',
				'value' => '20',
			],
			[
				'id' => 63,
				'user_id' => 1,
				'scope' => 'forms',
				'identifier' => 'paginate.limit',
				'value' => '20',
			],
			[
				'id' => 64,
				'user_id' => 1,
				'scope' => 'form_elements',
				'identifier' => 'overview.column_view.enabled',
				'value' => '0',
			],
			[
				'id' => 65,
				'user_id' => 1,
				'scope' => 'system',
				'identifier' => 'interface.dark_mode',
				'value' => '1',
			],
			[
				'id' => 66,
				'user_id' => 2,
				'scope' => 'system',
				'identifier' => 'interface.dark_mode',
				'value' => '1',
			],
		];

		$table = $this->table('user_configuration');
		$table->insert($data)->save();
	}
}
