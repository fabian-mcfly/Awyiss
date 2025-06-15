<?php declare(strict_types=1);


namespace FoobarCustomer\Survey;


use Awyiss\Survey\AbstractSurveyResults;
use Awyiss\Utility\Media\MediaRenderOptions;
use Cake\Datasource\FactoryLocator;


/**
 * WeinkonfiguratorSurveyResults class
 */
class WeinkonfiguratorSurveyResults extends AbstractSurveyResults {
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
		// No changes to the final result, just return the success message
		return $successMessage;
	}


	/**
	 * @inheritDoc
	 */
	public function getStepResult(string $identifier, MediaRenderOptions $mediaRenderOptions): ?string {
		$ls_currentPath = $this->survey->buildResultPath();
		$la_result = $this->paths[ $ls_currentPath ] ?? null;

		if (!$la_result) {
			return null;
		}

		$lo_winesTable = FactoryLocator::get('Table')->get('Wines');
		$lo_wines = $lo_winesTable->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true)
			->find('active')
			->where(['AttributesWines.identifier IN' => $la_result])
			->all();

		return $this->view->element('survey/results/weinkonfigurator', [
			'wines' => $lo_wines,
			'mediaRenderOptions' => $mediaRenderOptions,
		]);
	}
}
