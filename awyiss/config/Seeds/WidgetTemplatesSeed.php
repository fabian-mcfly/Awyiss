<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * WidgetTemplates seed.
 */
class WidgetTemplatesSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'title' => 'Standard',
				'file_name' => 'standard',
				'in_content_row' => 1,
				'system_order' => 1,
				'active' => 1,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null,
			],
		];

		$table = $this->table('widget_templates');
		$table->insert($data)->save();

		$data = [
			[
				'locale' => 'de',
				'model' => 'widget_templates',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'Standard',
			],
			[
				'locale' => 'en',
				'model' => 'widget_templates',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'Standard',
			],
		];

		$table = $this->table('i18n');
		$table->insert($data)->save();
	}
}
