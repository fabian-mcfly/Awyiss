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
	protected array $_accessible = [ // phpcs:ignore
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
				$inactive = __d('SurveyAnswers', 'inactive') . ' ';
			}
		}

		if (!$this->active) {
			$inactive = __d('SurveyAnswers', 'inactive') . ' ';
		}

		return $inactive . $title;
	}
}
