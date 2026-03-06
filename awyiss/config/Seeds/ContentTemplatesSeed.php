<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * ContentTemplates seed.
 */
class ContentTemplatesSeed extends AbstractSeed {
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
			[
				'id' => 2,
				'title' => 'Inhaltsblock',
				'fileName' => 'section',
				'inContentRow' => 0,
				'systemOrder' => 2,
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

		$table = $this->table('content_templates');
		$table->insert($data)->save();
	}
}
