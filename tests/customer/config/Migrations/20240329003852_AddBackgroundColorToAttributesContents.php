<?php declare(strict_types=1);


use Migrations\AbstractMigration;


/**
 * Class AddBackgroundColorToAttributesContents
 */
class AddBackgroundColorToAttributesContents extends AbstractMigration {
	/**
	 * Migrate Up.
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$this->table('attributes_contents')->addColumn('background_color', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => true,
		])->update();
	}
}
