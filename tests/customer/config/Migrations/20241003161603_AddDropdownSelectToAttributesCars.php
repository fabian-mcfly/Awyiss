<?php declare(strict_types=1);


use Migrations\BaseMigration;


/**
 * Class AddDropdownSelectToAttributesCars
 */
class AddDropdownSelectToAttributesCars extends BaseMigration {
	/**
	 * Migrate Up.
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$table = $this->table('attributes_cars');
		$table->addColumn('dropdownSelect', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => false,
		]);
		$table->update();
	}
}
