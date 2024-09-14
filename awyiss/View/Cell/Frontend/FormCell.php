<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Model\Entity;
use Awyiss\Utility\Form\FormRenderer;
use Awyiss\View\Cell\Frontend\Trait\ContentElementTrait;
use Cake\Http\Exception\RedirectException;
use Cake\View\Cell;
use RuntimeException;


/**
 * Form cell
 */
class FormCell extends Cell {
	use ContentElementTrait;

	/**
	 * @param string|int $identifier
	 * @param string $languageShortcode
	 * @param array $options
	 * @return void
	 * @throws \ReflectionException
	 */
	public function display(string|int $identifier, string $languageShortcode, array $options = []): void {
		$la_options = $this->initCellOptions($options);

		/** @noinspection PhpParamsInspection */
		$lo_formRenderer = new FormRenderer($this->createView('Frontend'));

		$lo_formRenderer->initForm($identifier, $this->request->getData(), $languageShortcode)
		->process();

		if (!$lo_formRenderer->isFormSubmitted() && !$lo_formRenderer->isFormSent() && $this->request->getParam('formEntry')) {
			$lo_formRenderer->processFormDataForEntryHash($this->request->getParam('formEntry'));
		}

		// Set the view variables
		$this->set([
			'contents' => $lo_formRenderer->getFormBody($la_options),
			'form' => $lo_formRenderer->getForm(),
			'formElements' => $lo_formRenderer->getFormElements(),
			'formElementsChecksum' => $lo_formRenderer->getFormElementsChecksum(),
			'formData' => $lo_formRenderer->getFormData(),
			'formErrors' => $lo_formRenderer->getFormErrors(),
			'sent' => $lo_formRenderer->isFormSent(),
			'submitted' => $lo_formRenderer->isFormSubmitted(),
			'fullWidth' => $la_options['fullWidth'],
			'includeWrapper' => $la_options['includeWrapper'],
			'singleColumnBreakpoint' => $la_options['singleColumnBreakpoint'],
		]);

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Frontend/cell/Form');
	}


	/**
	 * @param array $options
	 * @param string $languageShortcode
	 * @return array
	 * @noinspection DuplicatedCode
	 */
	public function initCellOptions(array $options): array {
		$la_options = $options + [
			'columnWidth' => 100.00,
			'includeWrapper' => true,
			'viewVars' => [],
		];

		/** @noinspection DuplicatedCode */
		if (!isset($la_options['fullWidth'])) {
			$la_options['fullWidth'] = $this->findFullWidth($la_options);

			if ($la_options['fullWidth'] === null) {
				throw new RuntimeException('Cannot determine page width. Please provide a page width when rendering contents');
			}
		}
		else {
			$la_options['fullWidth'] = (float)$la_options['fullWidth'];
		}

		if (!array_key_exists('singleColumnBreakpoint', $la_options)) {
			$la_options['singleColumnBreakpoint'] = $this->findSingleColumnBreakpoint($la_options);
		}
		elseif ($la_options['singleColumnBreakpoint'] !== null) {
			$la_options['singleColumnBreakpoint'] = (float)$la_options['singleColumnBreakpoint'];
		}

		return $la_options;
	}


	/**
	 * Catch a redirect exception and redirect the user
	 *
	 * @param string|null $template
	 * @return string
	 */
	public function render(?string $template = null): string {
		try {
			return parent::render($template);
		}
		catch (RedirectException $ex) {
			// Redirects are handled by the middleware
			header('Location: ' . $ex->getMessage(), true, $ex->getCode());
			exit;
		}
	}


	/**
	 * @inheritDoc
	 */
	protected function renderElement(Entity $entity, string $children): string {
		// Not used in here.
		return '';
	}
}
