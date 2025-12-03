<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * DashboardElements seed.
 */
class DashboardElementsSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'scope' => 'form_entries',
				'title' => 'Neue Formulareinträge',
				'access' => '{"scope":"FormEntries","identifier":"read"}',
				'settings' => '{"fields":["form_id","language_shortcode","subject","created_on"],"filter":{"created_on":{"active":"1","operator":"since_last_login"}},"sort":[{"field":"created_on","direction":"desc"}]}',
				'system_order' => 1,
				'active' => 1,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null
			],
			[
				'id' => 2,
				'scope' => 'urls_not_found',
				'title' => 'Neue 404 Fehler',
				'access' => '{"scope":"UrlsNotFound","identifier":"read"}',
				'settings' => '{"fields":["url","is_robot","created_on"],"filter":{"created_on":{"active":"1","operator":"since_last_login"}},"sort":[{"field":"created_on","direction":"desc"}]}',
				'system_order' => 2,
				'active' => 1,
				'deleted' => 0,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null
			]
		];

		$table = $this->table('dashboard_elements');
		$table->insert($data)->save();

		$data = [
			[
				'locale' => 'de',
				'model' => 'dashboard_elements',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'Neue Formulareinträge',
			],
			[
				'locale' => 'en',
				'model' => 'dashboard_elements',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'New Form Entries',
			],
			[
				'locale' => 'de',
				'model' => 'dashboard_elements',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'Neue 404 Fehler',
			],
			[
				'locale' => 'en',
				'model' => 'dashboard_elements',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'New 404 Errors',
			],
		];

		$table = $this->table('i18n');
		$table->insert($data)->save();
	}
}
