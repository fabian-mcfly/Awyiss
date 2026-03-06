<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * PageTemplates seed.
 */
class PageTemplatesCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 3,
				'pageRoleId' => 3,
				'title' => 'Standard',
				'fileName' => 'news',
				'systemOrder' => 1,
				'active' => 1,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 4,
				'pageRoleId' => 1,
				'title' => 'Unused',
				'fileName' => 'unused',
				'systemOrder' => 2,
				'active' => 2,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
		];

		$table = $this->table('page_templates');
		$table->insert($data)->save();
	}
}
