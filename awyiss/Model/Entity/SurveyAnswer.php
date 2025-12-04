<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * SurveyAnswer Entity
 *
 * @property int $id
 * @property int $surveyQuestionId
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
 * @property \Awyiss\Model\Entity\SurveyQuestion $surveyQuestion
 * @property \Awyiss\Model\Entity\SurveySurveyAnswer[]|\Cake\Collection\CollectionInterface $surveySurveyAnswers
 * @property \Awyiss\Model\Entity\SurveySurveyQuestion $surveySurveyQuestion
 */
class SurveyAnswer extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'survey_question_id' => 'surveyQuestionId',
		'system_order' => 'systemOrder',
		'survey_question' => 'surveyQuestion',
		'survey_survey_answers' => 'surveySurveyAnswers',
		'survey_survey_question' => 'surveySurveyQuestion',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'surveyQuestionId' => true,
		'title' => true,
		'subtitle' => true,
		'text' => true,
		'systemOrder' => true,
		'active' => true,
	];
}
