<?php declare(strict_types=1);


use Migrations\BaseMigration;


/**
 * Class CreateAttributesPages
 */
class CreateAttributesPages extends BaseMigration {
	/**
	 * Migrate Up.
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$this->table('attributes_pages')->addColumn('pageId', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => false,
		])->addColumn('date', 'date', [
			'default' => null,
			'null' => true,
		])->create();
	}
}
