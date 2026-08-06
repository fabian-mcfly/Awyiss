<?php declare(strict_types=1);


use Migrations\BaseMigration;


/**
 * Class AddTeaserToAttributesNews
 */
class AddTeaserToAttributesNews extends BaseMigration {
	/**
	 * Migrate Up.
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$this->table('attributes_news')->addColumn('teaser', 'text', [
			'default' => null,
			'null' => true,
		])->update();
	}
}
