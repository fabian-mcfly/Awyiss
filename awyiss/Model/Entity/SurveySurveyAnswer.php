<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * SurveySurveyAnswer Entity
 *
 * @property int $id
 * @property int $surveyAnswerId
 * @property int $surveySurveyQuestionId
 * @property string|null $title
 * @property string|null $subtitle
 * @property string|null $text
 * @property \Awyiss\Model\Enum\Survey\NextAction|null $nextAction
 * @property string|null $nextActionTarget
 * @property int|null $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\SurveyAnswer $surveyAnswer
 * @property \Awyiss\Model\Entity\SurveySurveyQuestion $surveySurveyQuestion
 */
class SurveySurveyAnswer extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'survey_answer_id' => 'surveyAnswerId',
		'survey_survey_question_id' => 'surveySurveyQuestionId',
		'next_action' => 'nextAction',
		'next_action_target' => 'nextActionTarget',
		'system_order' => 'systemOrder',
		'survey_answer' => 'surveyAnswer',
		'survey_survey_question' => 'surveySurveyQuestion',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'surveyAnswerId' => true,
		'surveySurveyQuestionId' => true,
		'title' => true,
		'subtitle' => true,
		'text' => true,
		'nextAction' => true,
		'nextActionTarget' => true,
		'systemOrder' => true,
		'active' => true,
	];


	/**
	 * @return string
	 */
	protected function _getLabel(): string {
		$title = $this->title;

		$inactive = '';

		if (empty($title) && isset($this->surveyAnswer)) {
			$title = $this->surveyAnswer->title;

			if (!$this->surveyAnswer->active) {
				$inactive = __d('survey_answers', 'inactive') . ' ';
			}
		}

		if (!$this->active) {
			$inactive = __d('survey_answers', 'inactive') . ' ';
		}

		return $inactive . $title;
	}
}
