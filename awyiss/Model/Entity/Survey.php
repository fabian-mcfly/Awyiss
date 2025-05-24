<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Model\Enum\SurveyType;


/**
 * Survey Entity
 *
 * @property int $id
 * @property \Awyiss\Model\Enum\SurveyType $type
 * @property string|null $title
 * @property int|null $formId
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\Form $form
 * @property \Awyiss\Model\Entity\SurveySurveyQuestion[]|\Cake\Collection\CollectionInterface $surveySurveyQuestions
 */
class Survey extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'form_id' => 'formId',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
		'survey_survey_questions' => 'surveySurveyQuestions',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'type' => true,
		'title' => true,
		'formId' => true,
		'active' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected array $defaultValues = [
		'type' => SurveyType::Linear,
	];
}
