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
	 * @param int $index
	 * @return \Awyiss\Model\Entity\SurveySurveyQuestion|false
	 * @noinspection PhpUnused
	 */
	public function realNextQuestion(array $questions, int $index): SurveySurveyQuestion|false {
		$lb_currentQuestionFound = false;
		$lb_isActive = false;

		foreach (array_values($questions) as $li_key => $lo_question) {
			if ($li_key === $index) {
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
