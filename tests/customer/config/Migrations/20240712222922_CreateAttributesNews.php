<?php declare(strict_types=1);


use Migrations\AbstractMigration;


/**
 * Class CreateAttributesNews
 */
class CreateAttributesNews extends AbstractMigration {
	/**
	 * Migrate Up.
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$this->table('attributes_news')->addColumn('page_id', 'integer', [
			'default' => null,
			'limit' => 11,
			'null' => false,
		])->addColumn('date', 'date', [
			'default' => null,
			'null' => false,
		])->create();
	}
}
