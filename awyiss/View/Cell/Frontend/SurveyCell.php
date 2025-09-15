<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Core\App;
use Awyiss\Model\Entity\Page;
use Awyiss\View\Cell\Frontend\Trait\FrontendRenderingTrait;
use Awyiss\View\Cell\Frontend\Trait\PreviewTrait;
use Awyiss\View\Cell\Frontend\Trait\RedirectAwareTrait;
use Awyiss\View\Cell\Frontend\Trait\RenderTrimmedTrait;
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
	 * @param array $options
	 * @return void
	 * @throws \ReflectionException
	 */
	public function display(string|int $identifier, Page $page, array $options = []): void {
		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Frontend/cell/Survey');

		$la_options = $this->initCellOptions($options);
		$this->setViewVars($la_options);

		$this->page = $page;

		/** @var class-string<\Awyiss\Utility\Survey\SurveyRenderer> $ls_className */
		$ls_className = App::className('SurveyRenderer', 'Utility/Survey');
		$lo_surveyRenderer = new $ls_className($this->getView());

		$la_requestData = $this->request->getData();
		$lo_surveyRenderer->initSurvey(
			$identifier,
			$la_requestData,
			$this->page
		);

		$lo_survey = $lo_surveyRenderer->getSurvey();
		if (!$lo_survey) {
			return;
		}

		if (($la_requestData['survey'][ $lo_survey->identifier ]['action'] ?? null) === 'go_back') {
			$lo_survey->goToStep($la_requestData['survey'][ $lo_survey->identifier ]['last_action']);
		}

		$lo_surveyRenderer->process(
			$this->request->getParam('surveyEntry'),
			$this->request->getParam('formEntry')
		);

		$this->set([
			'contents' => $lo_surveyRenderer->getSurveyBody($la_options),
			'survey' => $lo_survey,
		] + $la_options);
	}
}
