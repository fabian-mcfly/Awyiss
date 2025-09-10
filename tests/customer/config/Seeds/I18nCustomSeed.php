<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * AttributesNews seed.
 */
class I18nCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$la_data = [
			[
				'id' => 1,
				'locale' => 'es',
				'model' => 'widgets',
				'foreign_key' => 18,
				'field' => 'text',
				'content' => '<p>Widget with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"2"}</awyiss-responsive-image></p><p>between two paragraphs</p>',
			],
			[
				'id' => 2,
				'locale' => 'it',
				'model' => 'widgets',
				'foreign_key' => 18,
				'field' => 'text',
				'content' => '<p>Widget with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"4"}</awyiss-responsive-image></p><p>between two paragraphs</p>',
			],
			[
				'id' => 3,
				'locale' => 'de',
				'model' => 'widgets',
				'foreign_key' => 7,
				'field' => 'title',
				'content' => 'Translated title',
			],
		];

		$lo_table = $this->table('i18n');
		$lo_table->truncate();
		$lo_table->insert($la_data)->save();
	}
}
