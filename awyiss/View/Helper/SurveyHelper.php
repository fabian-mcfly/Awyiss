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
	public function nextQuestion(array $questions, string $identifier): SurveySurveyQuestion|false {
		$lb_currentQuestionFound = false;

		foreach ($questions as $lo_question) {
			if ($lo_question->identifier === $identifier) {
				$lb_currentQuestionFound = true;
				continue;
			}

			if (!$lb_currentQuestionFound) {
				continue;
			}

			return $lo_question;
		}

		return false;
	}

	/**
	 * @param array<\Awyiss\Model\Entity\SurveySurveyQuestion> $questions
	 * @param string $identifier
	 * @return \Awyiss\Model\Entity\SurveySurveyQuestion|false
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


	/**
	 * @param array $progress
	 * @param string $identifier
	 * @return string|false
	 * @noinspection PhpUnused
	 */
	public function nextAnsweredQuestion(array $progress, string $identifier): string|false {
		$lb_currentQuestionFound = false;

		foreach ($progress as $ls_identifier => $la_data) {
			if ($ls_identifier === $identifier) {
				$lb_currentQuestionFound = true;
				continue;
			}

			if (!$lb_currentQuestionFound) {
				continue;
			}

			return $ls_identifier;
		}

		return false;
	}


	/**
	 * @param array<string>|string $label
	 * @return string
	 * @noinspection PhpUnused
	 */
	public function linkLabel(array|string $label): string {
		$ls_customLabel = '';

		if (is_array($label) && count($label) === 1) {
			if (array_key_exists('custom', $label)) {
				$ls_customLabel = '<em>' . __('custom_answer') . '</em><br>';
			}

			/** @noinspection PhpVariableNamingConventionInspection */
			$label = array_shift($label);
		}

		if (is_string($label)) {
			return '<span class="Label" title="' . htmlentities($label, ENT_QUOTES | ENT_HTML5, 'UTF-8', false) . '">' .
				$ls_customLabel .
				$this->safeSubstr(htmlentities($label, ENT_COMPAT, 'UTF-8', false), 50) .
				'</span>';
		}

		$ls_return = '<ul>';

		foreach ($label as $lx_key => $ls_singleLabel) {
			$ls_return .= '<li>';

			$ls_customLabel = $lx_key === 'custom' ? '<em>' . __('custom_answer') . '</em><br>' : '';
			$ls_return .= '<span class="Label" title="' . htmlentities($ls_singleLabel, ENT_QUOTES | ENT_HTML5, 'UTF-8', false) . '">';
			$ls_return .= $ls_customLabel . $this->safeSubstr(htmlentities($ls_singleLabel, ENT_COMPAT, 'UTF-8', false), 50) . '</span>';
			$ls_return .= '</li>';
		}

		return $ls_return . '</ul>';
	}


	/**
	 * @param string $string
	 * @param int $length
	 * @return string
	 */
	protected function safeSubstr(string $string, int $length): string {
		$la_parts = explode(' ', $string);

		if ($length <= 0) {
			return '';
		}

		$ls_result = '';
		while ($la_parts) {
			$ls_result .= array_shift($la_parts);
			$ls_result .= ' ';

			if (mb_strlen($ls_result) > $length) {
				$ls_result = trim($ls_result) . '...';
				break;
			}
		}

		return trim($ls_result);
	}
}
