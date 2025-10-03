<?php declare(strict_types=1);


namespace FoobarCustomer\Survey;


use Awyiss\Model\Entity\Survey;
use Awyiss\Survey\SurveyResultsInterface;
use Awyiss\Utility\Media\MediaRenderOptions;
use Cake\Datasource\FactoryLocator;
use Cake\View\View;


/**
 * WeinkonfiguratorSurveyResults class
 */
class WeinkonfiguratorSurveyResults implements SurveyResultsInterface {
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
		/** @uses \Awyiss\Model\Table::findActive() */
		$lo_wines = $lo_winesTable->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true)->find('active')->where(
			['AttributesWines.identifier IN' => $la_result]
		)->all();

		return $this->view->element('survey/results/weinkonfigurator', [
			'wines' => $lo_wines,
			'mediaRenderOptions' => $mediaRenderOptions,
		]);
	}
}
