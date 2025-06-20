<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Model\Entity\SurveySurveyQuestion;
use Cake\View\Helper;


/**
 * Helper class that provides methods related to the Survey-logic in the views
 */
class SurveyHelper extends Helper {
	/**
	 * @param array<\Awyiss\Model\Entity\SurveySurveyQuestion> $questions
	 * @param string $identifier
	 * @return \Awyiss\Model\Entity\SurveySurveyQuestion|false
	 * @noinspection PhpUnused
	 */
	public function realNextQuestion(array $questions, string $identifier): SurveySurveyQuestion|false {
		$lb_currentQuestionFound = false;
		$lb_isActive = false;

		foreach ($questions as $lo_question) {
			if ($lo_question->identifier === $identifier) {
				$lb_isActive = $lo_question->surveyQuestion->active;
				$lb_currentQuestionFound = true;
				continue;
			}

			if (!$lb_currentQuestionFound) {
				continue;
			}

			if ($lo_question->surveyQuestion->active || $lo_question->surveyQuestion->active === $lb_isActive) {
				return $lo_question;
			}
		}

		return false;
	}
}
