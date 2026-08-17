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
		$currentQuestionFound = false;

		foreach ($questions as $question) {
			if ($question->identifier === $identifier) {
				$currentQuestionFound = true;
				continue;
			}

			if (!$currentQuestionFound) {
				continue;
			}

			return $question;
		}

		return false;
	}


	/**
	 * @param array<\Awyiss\Model\Entity\SurveySurveyQuestion> $questions
	 * @param string $identifier
	 * @return \Awyiss\Model\Entity\SurveySurveyQuestion|false
	 */
	public function realNextQuestion(array $questions, string $identifier): SurveySurveyQuestion|false {
		$currentQuestionFound = false;
		$isActive = false;

		foreach ($questions as $question) {
			if ($question->identifier === $identifier) {
				$isActive = $question->surveyQuestion->active;
				$currentQuestionFound = true;
				continue;
			}

			if (!$currentQuestionFound) {
				continue;
			}

			if ($question->surveyQuestion->active || $question->surveyQuestion->active === $isActive) {
				return $question;
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
		$currentQuestionFound = false;

		foreach (array_keys($progress) as $questionIdentifier) {
			if ($questionIdentifier === $identifier) {
				$currentQuestionFound = true;
				continue;
			}

			if (!$currentQuestionFound) {
				continue;
			}

			return $questionIdentifier;
		}

		return false;
	}


	/**
	 * @param array<string>|string $label
	 * @return string
	 * @noinspection PhpUnused
	 */
	public function linkLabel(array|string $label): string {
		$customLabel = '';

		if (is_array($label) && count($label) === 1) {
			if (array_key_exists('custom', $label)) {
				$customLabel = '<em>' . __('custom_answer') . '</em><br>';
			}

			$label = array_shift($label);
		}

		if (is_string($label)) {
			return '<span class="Label" title="' . htmlentities($label, ENT_QUOTES | ENT_HTML5, 'UTF-8', false) . '">'
				. $customLabel
				. $this->safeSubstr(htmlentities($label, ENT_COMPAT, 'UTF-8', false), 50)
				. '</span>'
			;
		}

		$return = '<ul>';

		foreach ($label as $key => $singleLabel) {
			$return .= '<li>';

			$customLabel = $key === 'custom' ? '<em>' . __('custom_answer') . '</em><br>' : '';
			$return .= '<span class="Label" title="' . htmlentities($singleLabel, ENT_QUOTES | ENT_HTML5, 'UTF-8', false) . '">';
			$return .= $customLabel . $this->safeSubstr(htmlentities($singleLabel, ENT_COMPAT, 'UTF-8', false), 50) . '</span>';
			$return .= '</li>';
		}

		return $return . '</ul>';
	}


	/**
	 * @param string $string
	 * @param int $length
	 * @return string
	 */
	protected function safeSubstr(string $string, int $length): string {
		$parts = explode(' ', $string);

		if ($length <= 0) {
			return '';
		}

		$result = '';
		while ($parts) {
			$result .= array_shift($parts);
			$result .= ' ';

			if (mb_strlen($result) > $length) {
				$result = trim($result) . '...';
				break;
			}
		}

		return trim($result);
	}
}
