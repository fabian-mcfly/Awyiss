<?php declare(strict_types=1);


use Migrations\BaseSeed;


/**
 * Surveys seed.
 */
class SurveysCustomSeed extends BaseSeed {
	/**
	 * {@inheritDoc}
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'type' => 'configurator',
				'title' => 'Dummy Survey',
				'identifier' => 'dummySurvey',
				'successMessage' => '<p>Success</p>',
				'failureMessage' => '<p>Failure</p>',
				'finalAction' => 'saveAndEnd',
				'formId' => 1,
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
				'type' => 'configurator',
				'title' => 'Dummy Survey (Inactive)',
				'identifier' => 'dummySurvey2',
				'successMessage' => '<p>Success</p>',
				'failureMessage' => '<p>Failure</p>',
				'finalAction' => 'saveAndEnd',
				'formId' => null,
				'active' => 0,
				'deleted' => 0,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 3,
				'type' => 'configurator',
				'title' => 'Dummy Survey (Inline Image)',
				'identifier' => 'dummySurvey3',
				'successMessage' => '<p>Success with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"4"}</awyiss-responsive-image></p><p>between two paragraphs</p>',
				'failureMessage' => '<p>Failure with inline img tag</p><p><awyiss-responsive-image>{"mediaId":"4"}</awyiss-responsive-image></p><p>between two paragraphs</p>',
				'finalAction' => 'saveAndEnd',
				'formId' => null,
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
				'type' => 'configurator',
				'title' => 'Dummy Survey (Survey Results)',
				'identifier' => 'dummySurvey4',
				'successMessage' => '<p>Success</p>',
				'failureMessage' => '<p>Failure</p>',
				'finalAction' => 'saveAndEnd',
				'formId' => null,
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

		$table = $this->table('surveys');
		$table->insert($data)->save();
	}
}
