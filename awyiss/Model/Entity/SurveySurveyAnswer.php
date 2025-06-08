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
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
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
		$ls_title = $this->title;

		$ls_inactive = '';

		if (empty($ls_title) && isset($this->surveyAnswer)) {
			$ls_title = $this->surveyAnswer->title;

			if (!$this->surveyAnswer->active) {
				$ls_inactive = __d('survey_answers', 'inactive') . ' ';
			}
		}

		if (!$this->active) {
			$ls_inactive = __d('survey_answers', 'inactive') . ' ';
		}

		return $ls_inactive . $ls_title;
	}
}
