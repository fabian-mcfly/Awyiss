<?php declare(strict_types=1);


namespace Awyiss\Survey;


use Awyiss\Model\Entity\Survey;
use Awyiss\Utility\Media\MediaRenderOptions;
use Cake\View\View;


/**
 * AbstractSurveyResults class
 * This class is a placeholder for survey results.
 */
abstract class AbstractSurveyResults {
	/**
	 * @param \Awyiss\Model\Entity\Survey $survey
	 * @param \Cake\View\View $view
	 * @param array $progress
	 * @param array $customAnswers
	 */
	public function __construct(
		protected Survey $survey,
		protected readonly View $view,
		protected array $progress = [],
		protected array $customAnswers = []
	) {
	}


	/**
	 * @return array
	 */
	public function getProgress(): array {
		return $this->progress;
	}


	/**
	 * @param array $progress
	 * @return $this
	 */
	public function setProgress(array $progress): static {
		$this->progress = $progress;

		return $this;
	}


	/**
	 * @return array
	 */
	public function getCustomAnswers(): array {
		return $this->customAnswers;
	}


	/**
	 * @param array $customAnswers
	 * @return $this
	 */
	public function setCustomAnswers(array $customAnswers): static {
		$this->customAnswers = $customAnswers;

		return $this;
	}


	/**
	 * Returns the final success message that will be shown after
	 * the survey has reached one of the following actions:
	 *
	 * - \Awyiss\Model\Enum\Survey\NextAction::SaveAndEnd
	 * - \Awyiss\Model\Enum\Survey\NextAction::SaveAndShowForm (when no form was found)
	 *
	 * @param string|null $successMessage The success message defined in the survey configuration.
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions The media render options to use for rendering the result
	 * @return string The final result message to be displayed. Modify or discard the success message
	 * @see \Awyiss\Model\Enum\Survey\NextAction::SaveAndEnd
	 * @see \Awyiss\Model\Enum\Survey\NextAction::SaveAndShowForm
	 */
	abstract public function getFinalResult(?string $successMessage, MediaRenderOptions $mediaRenderOptions): string;


	/**
	 * Returns the results of a specific step in the survey.
	 * This method is called for every question of type `InfoText`
	 *
	 * @param string $identifier The identifier of the current question
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions The media render options to use for rendering the result
	 * @return string|null The result of the step. Either the `text`-property of the question or a custom result
	 * @see \Awyiss\Model\Enum\Survey\QuestionType::InfoText
	 */
	abstract public function getStepResult(string $identifier, MediaRenderOptions $mediaRenderOptions): ?string;
}
