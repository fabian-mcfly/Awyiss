<?php declare(strict_types=1);


use Migrations\BaseMigration;


/**
 * Class CreateAttributesUsergroups
 */
class CreateAttributesUsergroups extends BaseMigration {
	/**
	 * Migrate Up.
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$this->table('attributes_usergroups')->addColumn('pageId', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => false,
		])->addColumn('date', 'date', [
			'default' => null,
			'null' => false,
		])->create();
	}
}
