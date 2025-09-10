<?php declare(strict_types=1);


namespace Customer\Survey;


use Awyiss\Survey\AbstractSurveyResults;
use Awyiss\Utility\Media\MediaRenderOptions;


/**
 * WeinkonfiguratorSurveyResults class
 */
class DummySurvey4SurveyResults extends AbstractSurveyResults {
	/**
	 * All possible paths for the Weinkonfigurator survey.
	 *
	 * @var array
	 */
	protected array $paths = [

	];


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
