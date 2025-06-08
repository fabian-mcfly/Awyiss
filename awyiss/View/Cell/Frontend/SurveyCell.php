<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Core\App;
use Awyiss\Model\Entity\Page;
use Awyiss\View\Cell\Frontend\Trait\FrontendRenderingTrait;
use Awyiss\View\Cell\Frontend\Trait\PreviewTrait;
use Cake\Http\Exception\RedirectException;
use Cake\View\Cell;
use Error;
use Exception;


/**
 * Surveys cell
 */
class SurveyCell extends Cell {
	use FrontendRenderingTrait;
	use PreviewTrait;


	/**
	 * @var \Awyiss\Model\Entity\Page
	 */
	protected Page $page;



	/**
	 * @param string|int $identifier
	 * @param \Awyiss\Model\Entity\Page $page
	 * @param array $options
	 * @return void
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

		$lo_surveyRenderer->initSurvey(
			$identifier,
			$this->request->getData(),
			$this->page
		);

		$lo_survey = $lo_surveyRenderer->getSurvey();
		if (!$lo_survey) {
			return;
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


	/**
	 * Catch the redirect exception and redirect the user
	 *
	 * @inheritDoc
	 */
	public function __toString(): string {
		try {
			return $this->render();
		}
		catch (RedirectException $ex) {
			// Redirects are handled by the middleware
			header('Location: ' . $ex->getMessage(), true, $ex->getCode());
			exit;
		}
		catch (Exception $ex) {
			trigger_error(
				sprintf('Could not render cell - %s [%s, line %d]', $ex->getMessage(), $ex->getFile(), $ex->getLine()),
				E_USER_WARNING
			);

			return '';
			/** @phpstan-ignore-next-line */
		}
		catch (Error $ex) {
			throw new Error(
				sprintf('Could not render cell - %s [%s, line %d]', $ex->getMessage(), $ex->getFile(), $ex->getLine()),
				0,
				$ex
			);
		}
	}
}
