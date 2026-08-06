<?php declare(strict_types=1);


use Migrations\BaseMigration;


/**
 * Class AddBackgroundColorToAttributesContents
 */
class AddBackgroundColorToAttributesContents extends BaseMigration {
	/**
	 * Migrate Up.
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$this->table('attributes_contents')->addColumn('backgroundColor', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => true,
		])->update();
	}
}
