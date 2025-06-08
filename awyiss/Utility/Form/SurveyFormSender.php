<?php declare(strict_types=1);


namespace Awyiss\Utility\Form;


use Awyiss\Model\Entity\Survey;


/**
 * FormHandler class
 * Works with the form data and sends the email(s) for a specific form
 */
class SurveyFormSender extends FormSender {
	/**
	 * @var array<string, string>
	 */
	protected array $customAnswers = [];
	/**
	 * @var array<string, array|int|string>
	 */
	protected array $progress = [];
	/**
	 * @var \Awyiss\Model\Entity\Survey
	 */
	protected Survey $survey;


	/**
	 * @param \Awyiss\Model\Entity\Survey $survey
	 * @param array $progress
	 * @param array $customAnswers
	 * @return $this
	 */
	public function setSurveyProgress(Survey $survey, array $progress, array $customAnswers = []): static {
		$this->survey = $survey;
		$this->progress = $progress;
		$this->customAnswers = $customAnswers;

		return $this;
	}


	/**
	 * @param string $type
	 * @return string
	 */
	protected function createDataString(string $type): string {
		$lo_view = $this->getView();

		$ls_fileName = 'survey_data';
		if ($type === 'text') {
			$ls_fileName .= '_plain';
		}

		return $lo_view->element('email/' . $ls_fileName, $this->templateData());
	}


	/**
	 * @return array
	 */
	protected function templateData(): array {
		$la_data = parent::templateData();

		$la_data += [
			'survey' => $this->survey,
			'surveyProgress' => $this->progress,
			'surveyCustomAnswers' => $this->customAnswers,
			'questionTypeEnum' => $this->survey->getQuestionTypeEnum(),
			'nextActionEnum' => $this->survey->getNextActionEnum(),
		];

		return $la_data;
	}
}
