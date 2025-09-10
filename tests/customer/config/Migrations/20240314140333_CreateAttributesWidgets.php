<?php declare(strict_types=1);


use Migrations\AbstractMigration;


/**
 * Class CreateAttributesWidgets
 */
class CreateAttributesWidgets extends AbstractMigration {
	/**
	 * Migrate Up.
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$this->table('attributes_widgets')->addColumn('widget_id', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => false,
		])->addColumn('teaser', 'text', [
			'default' => null,
			'null' => true,
		])->addColumn('free_text', 'text', [
			'default' => null,
			'null' => true,
		])->addColumn('free_text_inactive', 'text', [
			'default' => null,
			'null' => true,
		])->create();
	}
}
