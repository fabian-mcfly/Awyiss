<?php declare(strict_types=1);


use Migrations\AbstractMigration;


/**
 * Class AddTextToAttributesNews
 */
class AddTextToAttributesNews extends AbstractMigration {
	/**
	 * Migrate Up.
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$this->table('attributes_news')->addColumn('text', 'text', [
			'default' => null,
			'null' => true,
		])->update();
	}
}
