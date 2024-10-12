<?php declare(strict_types=1);


use Migrations\AbstractMigration;


/**
 * Class AddDropdownSelectToAttributesCars
 */
class AddDropdownSelectToAttributesCars extends AbstractMigration {
	/**
	 * Migrate Up.
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$table = $this->table('attributes_cars');
		$table->addColumn('dropdown_select', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => false,
		]);
		$table->update();
	}
}
