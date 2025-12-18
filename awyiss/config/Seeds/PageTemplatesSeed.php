<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * PageTemplates seed.
 */
class PageTemplatesSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'page_role_id' => 1,
				'title' => 'Standard',
				'file_name' => 'standard',
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
			[
				'id' => 2,
				'page_role_id' => 1,
				'title' => 'Mit Seitenteaser',
				'file_name' => 'with_page_teaser',
				'system_order' => 2,
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

		$table = $this->table('page_templates');
		$table->insert($data)->save();

		$data = [
			[
				'locale' => 'de',
				'model' => 'page_templates',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'Standard',
			],
			[
				'locale' => 'en',
				'model' => 'page_templates',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'Standard',
			],
		];

		$table = $this->table('i18n');
		$table->insert($data)->save();
	}
}
