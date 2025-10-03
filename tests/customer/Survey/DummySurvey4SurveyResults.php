<?php declare(strict_types=1);


namespace Customer\Survey;


use Awyiss\Model\Entity\Survey;
use Awyiss\Survey\SurveyResultsInterface;
use Awyiss\Utility\Media\MediaRenderOptions;
use Cake\View\View;


/**
 * WeinkonfiguratorSurveyResults class
 */
class DummySurvey4SurveyResults implements SurveyResultsInterface {
	/**
	 * All possible paths for the Weinkonfigurator survey.
	 *
	 * @var array
	 */
	protected array $paths = [

	];


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
	 * @inheritDoc
	 */
	public function getFinalResult(?string $successMessage, MediaRenderOptions $mediaRenderOptions): string {
		return 'Alternative Success Message from DummySurvey4SurveyResults';
	}


	/**
	 * @inheritDoc
	 */
	public function getStepResult(string $identifier, MediaRenderOptions $mediaRenderOptions): ?string {
		if ($identifier === 'da147ac8') {
			return 'Alternative Step Result from DummySurvey4SurveyResults';
		}

		return null;
	}
}
