<?php declare(strict_types=1);


use Migrations\AbstractMigration;


/**
 * Class AlterDateOnAttributesNewsV2
 */
class AlterDateOnAttributesNewsV2 extends AbstractMigration {
	/**
	 * Migrate Up.
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$this->table('attributes_news')->changeColumn('date', 'date', [
			'default' => null,
			'null' => false,
		])->update();
	}
}
