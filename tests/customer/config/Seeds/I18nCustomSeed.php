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
		$data = [
			[
				'id' => 1,
				'locale' => 'es',
				'model' => 'GlobalContents',
				'foreignKey' => 18,
				'field' => 'text',
				'content' => '<p>Global Content with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"2"}</awyiss-responsive-image></p><p>between two paragraphs</p>',
			],
			[
				'id' => 2,
				'locale' => 'it',
				'model' => 'GlobalContents',
				'foreignKey' => 18,
				'field' => 'text',
				'content' => '<p>Global Content with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"4"}</awyiss-responsive-image></p><p>between two paragraphs</p>',
			],
			[
				'id' => 3,
				'locale' => 'de',
				'model' => 'GlobalContents',
				'foreignKey' => 7,
				'field' => 'title',
				'content' => 'Translated title',
			],
		];

		$table = $this->table('i18n');
		$table->truncate();
		$table->insert($data)->save();
	}
}
