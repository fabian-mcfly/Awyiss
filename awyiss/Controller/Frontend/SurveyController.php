<?php declare(strict_types=1);


namespace Awyiss\Controller\Frontend;


use Awyiss\Controller\AppController;
use Awyiss\Core\App;
use Awyiss\View\Cell\Frontend\Trait\FrontendRenderingTrait;
use Awyiss\View\FrontendView;
use Cake\Controller\ComponentRegistry;
use Cake\Event\EventManagerInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\ServerRequest;
use Cake\ORM\Locator\LocatorAwareTrait;


/**
 * The Survey Controller handles AJAX survey submissions
 */
class SurveyController extends AppController {
	use FrontendRenderingTrait;
	use LocatorAwareTrait;


	/**
	 * @var \Awyiss\View\FrontendView
	 */
	protected FrontendView $View;


	/**
	 * @inheritDoc
	 */
	public function __construct(
		ServerRequest $request,
		?string $name = null,
		?EventManagerInterface $eventManager = null,
		?ComponentRegistry $components = null
	) {
		parent::__construct($request, $name, $eventManager, $components);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->View = $this->createView('Frontend');
	}


	/**
	 * Handle AJAX survey step submission
	 *
	 * @param string $identifier The survey identifier
	 * @param string $hash The survey hash for validation
	 * @return void
	 * @throws \ReflectionException
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function ajaxStep(string $identifier, string $hash): void {
		$this->viewBuilder()->setClassName('Frontend');

		if (!$this->request->is('post')) {
			throw new BadRequestException('Only POST requests are allowed.');
		}

		if (!$this->request->is('ajax')) {
			throw new BadRequestException('Only AJAX requests are allowed.');
		}

		// Get the options for rendering
		$options = $this->getOptions();

		/** @var class-string<\Awyiss\Utility\Survey\SurveyRenderer> $className */
		$className = App::className('SurveyRenderer', 'Utility/Survey');
		$surveyRenderer = new $className($this->View);

		$requestData = $this->request->getData();

		// Initialize the survey
		$surveyRenderer->initSurvey(
			$identifier,
			$requestData,
			null // No page context for AJAX requests
		);

		$survey = $surveyRenderer->getSurvey();

		if (!$survey) {
			throw new NotFoundException('Survey not found.');
		}

		// Validate the hash
		$surveyHash = md5(json_encode($survey->toArray()));

		if ($surveyHash !== $hash) {
			throw new BadRequestException('Invalid survey hash.');
		}

		// Handle go back action
		if (($requestData['survey'][ $survey->identifier ]['action'] ?? null) === 'goBack') {
			$survey->goToStep($requestData['survey'][ $survey->identifier ]['lastAction']);
		}

		// Process the survey
		$surveyRenderer->process(
			$this->request->getParam('surveyEntry'),
			$this->request->getParam('formEntry')
		);

		// Render the survey body
		$contents = $surveyRenderer->getSurveyBody($options);

		// Set the view variables
		$this->set(
			[
				'contents' => $contents,
				'survey' => $survey,
			] + $options
		);

		$this
			->viewBuilder()
			->disableAutoLayout()
			->setTemplate('ajax_step')
		;
	}


	/**
	 * Attempt to find the width of the page by
	 * - checking the view vars for a page width
	 *
	 * @param array $options
	 * @return float|null
	 */
	protected function findFullWidth(array $options): ?float {
		if (isset($options['viewVars']['fullWidth'])) {
			return (float)$options['viewVars']['fullWidth'];
		}

		if (isset($options['viewVars']['designSettings']['pageWidth'])) {
			return (float)$options['viewVars']['designSettings']['pageWidth'];
		}

		return null;
	}


	/**
	 * Attempt to find the single column breakpoint by
	 * - checking the view vars for a single column breakpoint setting
	 *
	 * @param array $options
	 * @return float|null
	 */
	protected function findSingleColumnBreakpoint(array $options): ?float {
		if (isset($options['viewVars']['singleColumnBreakpoint'])) {
			return (float)$options['viewVars']['singleColumnBreakpoint'];
		}

		if (isset($options['viewVars']['designSettings']['singleColumnBreakpoint'])) {
			return (float)$options['viewVars']['designSettings']['singleColumnBreakpoint'];
		}

		return null;
	}


	/**
	 * @return array
	 */
	protected function getOptions(): array {
		$options = [
			'columnWidth' => 60.00,
			'viewVars' => $this->View->getTwig()->getGlobals(),
		];

		$options['fullWidth'] = $this->findFullWidth($options);
		$options['singleColumnBreakpoint'] = $this->findSingleColumnBreakpoint($options);

		return $options;
	}
}
