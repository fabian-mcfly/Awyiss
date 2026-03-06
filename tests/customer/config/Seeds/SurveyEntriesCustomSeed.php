<?php declare(strict_types=1);


use Migrations\BaseSeed;


/**
 * SurveyEntries seed.
 */
class SurveyEntriesCustomSeed extends BaseSeed {
	/**
	 * {@inheritDoc}
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'surveyId' => 1,
				'pageId' => 1,
				'data' => 'eJx1UD0PgjAQnTHxP5BzJcSWtoCbo6MzYUCpxgRBKcSB8N+9A/xoAkvT9+7uvXvXrVcOPOrqWmtjYOd2EEkuci01AuG5cFHxiSkRIUwiz41T5MJcSSGEQg7OrWmqOxDLt1JcWIgsY71HwmNxX5qXrkk9SQe61lmenQqySBA7HTxbbZpbVR5y5DiKZcPMAGmLT50cj9Pf3TD4dlJh9EHaZzD4Ox09jq0eeCP55zAFs5ttKw7WGA19/bjPaZEfDCCl5mkH217a4cq2KBbzCTvfdOpZWWXLMrYoKuePJjFEj8K4ef8GD+CSEw==',
				'ipHash' => 'f528764d624db129b32c21fbca0cb8d6',
				'postHash' => '2d12c01d690373e2932f49b983cd726ff3d10822',
				'identifier' => '419840e6c9eae0682dec94a92e065136',
				'deleted' => 0,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'deletedBy' => null,
				'deletedOn' => null,
			],
		];

		$table = $this->table('survey_entries');
		$table->insert($data)->save();
	}
}
