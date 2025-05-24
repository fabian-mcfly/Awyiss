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
		'system_order' => 'systemOrder',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
		'survey_survey_answers' => 'surveySurveyAnswers',
		'survey_question' => 'surveyQuestion',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'surveyId' => true,
		'surveyQuestionId' => true,
		'identifier' => true,
		'title' => true,
		'subtitle' => true,
		'text' => true,
		'systemOrder' => true,
		'active' => true,
	];
}
