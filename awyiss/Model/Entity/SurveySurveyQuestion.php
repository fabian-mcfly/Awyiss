<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * SurveySurveyQuestion Entity
 *
 * @property int $id
 * @property int $surveyId
 * @property int $surveyQuestionId
 * @property string $identifier
 * @property string|null $title
 * @property string|null $subtitle
 * @property string|null $text
 * @property \Awyiss\Model\Enum\Survey\NextAction|null $nextAction
 * @property string|null $nextActionTarget
 * @property bool|null $allowCustomAnswer
 * @property string|null $customAnswerTitle
 * @property int|null $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\Survey $survey
 * @property \Awyiss\Model\Entity\SurveyQuestion $surveyQuestion
 * @property \Awyiss\Model\Entity\SurveySurveyAnswer[]|\Cake\Collection\CollectionInterface $surveySurveyAnswers
 */
class SurveySurveyQuestion extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'survey_id' => 'surveyId',
		'survey_question_id' => 'surveyQuestionId',
		'next_action' => 'nextAction',
		'next_action_target' => 'nextActionTarget',
		'allow_custom_answer' => 'allowCustomAnswer',
		'custom_answer_title' => 'customAnswerTitle',
		'system_order' => 'systemOrder',
		'survey_survey_answers' => 'surveySurveyAnswers',
		'survey_question' => 'surveyQuestion',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'surveyId' => true,
		'surveyQuestionId' => true,
		'identifier' => true,
		'title' => true,
		'subtitle' => true,
		'text' => true,
		'nextAction' => true,
		'nextActionTarget' => true,
		'allowCustomAnswer' => true,
		'customAnswerTitle' => true,
		'systemOrder' => true,
		'active' => true,
	];


	/**
	 * @return string
	 */
	protected function _getLabel(): string {
		$title = $this->title;

		$inactive = '';

		if (empty($title) && isset($this->surveyQuestion)) {
			$title = $this->surveyQuestion->title;

			if (!$this->surveyQuestion->active) {
				$inactive = __d('survey_questions', 'inactive') . ' ';
			}
		}

		return $inactive . $title . ' (' . $this->identifier . ')';
	}
}
