<?php declare(strict_types=1);


use Migrations\BaseSeed;


/**
 * User Configuration seed.
 */
class UserConfigurationCustomSeed extends BaseSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 2,
				'userId' => 1,
				'scope' => 'Newscategories',
				'identifier' => 'paginate.limit',
				'value' => '20',
			],
			[
				'id' => 4,
				'userId' => 1,
				'scope' => 'Newscategories',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 8,
				'userId' => 1,
				'scope' => 'Usergroups',
				'identifier' => 'paginate.limit',
				'value' => '20',
			],
			[
				'id' => 9,
				'userId' => 1,
				'scope' => 'MenuEntries',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 10,
				'userId' => 1,
				'scope' => 'MenuEntries',
				'identifier' => 'paginate.limit',
				'value' => '20',
			],
			[
				'id' => 14,
				'userId' => 1,
				'scope' => 'Pages',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 15,
				'userId' => 1,
				'scope' => 'Pages',
				'identifier' => 'paginate.limit',
				'value' => '100',
			],
			[
				'id' => 16,
				'userId' => 1,
				'scope' => 'News',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 20,
				'userId' => 1,
				'scope' => 'Contents',
				'identifier' => 'overview.columnView.enabled',
				'value' => '1',
			],
			[
				'id' => 21,
				'userId' => 1,
				'scope' => 'Datatables',
				'identifier' => 'paginate.limit',
				'value' => '20',
			],
			[
				'id' => 22,
				'userId' => 1,
				'scope' => 'Employers',
				'identifier' => 'paginate.enabled',
				'value' => '1',
			],
			[
				'id' => 23,
				'userId' => 1,
				'scope' => 'Employers',
				'identifier' => 'paginate.limit',
				'value' => '20',
			],
			[
				'id' => 24,
				'userId' => 1,
				'scope' => 'News',
				'identifier' => 'paginate.limit',
				'value' => '5',
			],
			[
				'id' => 25,
				'userId' => 1,
				'scope' => 'Languages',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 26,
				'userId' => 1,
				'scope' => 'Menus',
				'identifier' => 'paginate.limit',
				'value' => '20',
			],
			[
				'id' => 27,
				'userId' => 1,
				'scope' => 'Attributes',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 28,
				'userId' => 1,
				'scope' => 'ContentTemplates',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 29,
				'userId' => 1,
				'scope' => 'PageRoles',
				'identifier' => 'paginate.enabled',
				'value' => '1',
			],
			[
				'id' => 30,
				'userId' => 1,
				'scope' => 'PageTemplates',
				'identifier' => 'paginate.enabled',
				'value' => '1',
			],
			[
				'id' => 31,
				'userId' => 1,
				'scope' => 'UrlHistory',
				'identifier' => 'paginate.limit',
				'value' => '50',
			],
			[
				'id' => 32,
				'userId' => 1,
				'scope' => 'MediaFolders',
				'identifier' => 'paginate.enabled',
				'value' => '1',
			],
			[
				'id' => 37,
				'userId' => 1,
				'scope' => 'Attributes',
				'identifier' => 'paginate.limit',
				'value' => '1',
			],
			[
				'id' => 40,
				'userId' => 1,
				'scope' => 'Media',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 41,
				'userId' => 1,
				'scope' => 'Media',
				'identifier' => 'paginate.limit',
				'value' => '20',
			],
			[
				'id' => 42,
				'userId' => 1,
				'scope' => 'ContentTemplates',
				'identifier' => 'paginate.limit',
				'value' => '100',
			],
			[
				'id' => 46,
				'userId' => 1,
				'scope' => 'MediaElements',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 49,
				'userId' => 1,
				'scope' => 'GlobalContentTemplates',
				'identifier' => 'paginate.enabled',
				'value' => '0',
			],
			[
				'id' => 50,
				'userId' => 1,
				'scope' => 'GlobalContents',
				'identifier' => 'overview.columnView.enabled',
				'value' => '1',
			],
			[
				'id' => 52,
				'userId' => 1,
				'scope' => 'UrlsNotFound',
				'identifier' => 'paginate.limit',
				'value' => '20',
			],
			[
				'id' => 63,
				'userId' => 1,
				'scope' => 'Forms',
				'identifier' => 'paginate.limit',
				'value' => '20',
			],
			[
				'id' => 64,
				'userId' => 1,
				'scope' => 'FormElements',
				'identifier' => 'overview.columnView.enabled',
				'value' => '0',
			],
			[
				'id' => 65,
				'userId' => 1,
				'scope' => 'System',
				'identifier' => 'interface.darkMode',
				'value' => '1',
			],
			[
				'id' => 66,
				'userId' => 2,
				'scope' => 'System',
				'identifier' => 'interface.darkMode',
				'value' => '1',
			],
		];

		$table = $this->table('user_configuration');
		$table->insert($data)->save();
	}
}
