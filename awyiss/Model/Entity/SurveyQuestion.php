<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Model\Enum\Survey\QuestionType;


/**
 * SurveyQuestion Entity
 *
 * @property int $id
 * @property \Awyiss\Model\Enum\Survey\QuestionType $type
 * @property string|null $title
 * @property string|null $subtitle
 * @property string|null $text
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\SurveyAnswer[]|\Cake\Collection\CollectionInterface $surveyAnswers
 * @property \Awyiss\Model\Entity\SurveySurveyQuestion[]|\Cake\Collection\CollectionInterface $surveySurveyQuestions
 */
class SurveyQuestion extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'survey_answers' => 'surveyAnswers',
		'survey_survey_questions' => 'surveySurveyQuestions',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'type' => true,
		'title' => true,
		'subtitle' => true,
		'text' => true,
		'active' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected array $defaultValues = [
		'type' => QuestionType::SingleChoice,
	];
}
