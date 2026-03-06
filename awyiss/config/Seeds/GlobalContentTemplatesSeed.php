<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * GlobalContentTemplates seed.
 */
class GlobalContentTemplatesSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'title' => 'Standard',
				'fileName' => 'standard',
				'inContentRow' => 1,
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
		];

		$table = $this->table('global_content_templates');
		$table->insert($data)->save();
	}
}
