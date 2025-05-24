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
		'systemOrder' => true,
		'active' => true,
	];
}
