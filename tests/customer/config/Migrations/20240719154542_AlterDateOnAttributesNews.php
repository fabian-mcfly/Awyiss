<?php declare(strict_types=1);


use Migrations\BaseMigration;


/**
 * Class AlterDateOnAttributesNews
 */
class AlterDateOnAttributesNews extends BaseMigration {
	/**
	 * Migrate Up.
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$this->table('attributes_news')->changeColumn('date', 'datetime', [
			'default' => null,
			'null' => false,
		])->update();
	}
}
