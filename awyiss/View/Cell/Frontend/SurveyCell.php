<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Core\App;
use Awyiss\Model\Entity\Page;
use Awyiss\Utility\DebugTimer;
use Awyiss\View\Cell\Frontend\Trait\FrontendRenderingTrait;
use Awyiss\View\Cell\Frontend\Trait\PreviewTrait;
use Awyiss\View\Cell\Frontend\Trait\RedirectAwareTrait;
use Awyiss\View\Cell\Frontend\Trait\RenderTrimmedTrait;
use Awyiss\View\FrontendView;
use Cake\View\Cell;


/**
 * Surveys cell
 */
class SurveyCell extends Cell {
	use FrontendRenderingTrait;
	use PreviewTrait;
	use RedirectAwareTrait;
	use RenderTrimmedTrait;


	/**
	 * @var \Awyiss\Model\Entity\Page
	 */
	protected Page $page;


	/**
	 * @param string|int $identifier
	 * @param \Awyiss\Model\Entity\Page $page
	 * @param \Awyiss\View\FrontendView $view
	 * @param array $options
	 * @return void
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function display(string|int $identifier, Page $page, FrontendView $view, array $options = []): void {
		DebugTimer::start('SurveyCell::display', sprintf('SurveyCell::display: Rendering survey "%s" on page %d', $identifier, $page->id));

		$this->View = $view;

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Frontend/cell/Survey');

		$options = $this->initCellOptions($options);
		$this->setViewVars($options);

		$this->page = $page;

		/** @var class-string<\Awyiss\Utility\Survey\SurveyRenderer> $className */
		$className = App::className('SurveyRenderer', 'Utility/Survey');
		$surveyRenderer = new $className($this->getView());

		$requestData = $this->request->getData();
		$surveyRenderer->initSurvey(
			$identifier,
			$requestData,
			$this->page
		);

		$survey = $surveyRenderer->getSurvey();
		if (!$survey) {
			DebugTimer::stop('SurveyCell::display');
			return;
		}

		if (($requestData['survey'][ $survey->identifier ]['action'] ?? null) === 'goBack') {
			$survey->goToStep($requestData['survey'][ $survey->identifier ]['lastAction']);
		}

		$surveyRenderer->process(
			$this->request->getParam('surveyEntry'),
			$this->request->getParam('formEntry')
		);

		$this->set([
			'contents' => $surveyRenderer->getSurveyBody($options),
			'survey' => $survey,
		] + $options);

		DebugTimer::stop('SurveyCell::display');
	}
}
