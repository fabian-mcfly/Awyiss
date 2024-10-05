<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Model\Entity;
use Awyiss\Utility\Form\FormRenderer;
use Awyiss\View\Cell\Frontend\Trait\ContentElementTrait;
use Cake\Http\Exception\RedirectException;
use Cake\View\Cell;


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
