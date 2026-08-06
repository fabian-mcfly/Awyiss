<?php declare(strict_types=1);


use Migrations\BaseMigration;


/**
 * Class CreateAttributesContents
 */
class CreateAttributesContents extends BaseMigration {
	/**
	 * Migrate Up.
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$this->table('attributes_contents')->addColumn('contentId', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => false,
		])->addColumn('teaser', 'text', [
			'default' => null,
			'null' => true,
		])->addColumn('freeText', 'text', [
			'default' => null,
			'null' => true,
		])->create();
	}
}
