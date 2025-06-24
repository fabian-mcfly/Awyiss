<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Core\App;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Model\Entity\Page;
use Awyiss\View\Cell\Frontend\Trait\ContentElementTrait;
use Awyiss\View\Cell\Frontend\Trait\RedirectAwareTrait;
use Cake\Core\Configure;
use Cake\View\Cell;


/**
 * Form cell
 */
class FormCell extends Cell {
	use ContentElementTrait;
	use RedirectAwareTrait;


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
	 * @throws \Exception
	 */
	public function display(string|int $identifier, Page $page, array $options = []): void {
		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Frontend/cell/Form');

		$la_options = $this->initCellOptions($options);

		$this->page = $page;

		/** @var class-string<\Awyiss\Utility\Form\FormRenderer> $ls_className */
		$ls_className = App::className('FormRenderer', 'Utility/Form');

		$lo_formRenderer = new $ls_className($this->createView('Frontend'));

		$lo_formRenderer
		->initForm(
			$identifier,
			$this->request->getData(),
			$this->page
		);

		$lo_form = $lo_formRenderer->getForm();
		if (!$lo_form) {
			return;
		}

		$lo_formRenderer->process();

		if (!$lo_form->isSubmitted() && !$lo_formRenderer->isSent() && $this->request->getParam('formEntry')) {
			$lo_formRenderer->processFormEntryFromHash($this->request->getParam('formEntry'));
		}

		$la_formElements = $lo_formRenderer->getForm()->getFormElements()->listNested()->filter(function (FormElement $element): bool {
			return !empty($element->identifier);
		})->indexBy('identifier')->toArray();

		if ($lo_formRenderer->isSent()) {
			/**
			 * @var \Awyiss\Utility\Media\MediaRenderOptions $lo_mediaRenderOptions
			 * @noinspection PhpPossiblePolymorphicInvocationInspection
			 */
			$lo_mediaRenderOptions = $this->View->helpers()->get('Media')->mediaRenderOptions(
				baseWidth: $la_options['fullWidth'],
				breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints', []),
				columnWidth: $la_options['columnWidth'],
				selector: '#Form' . $lo_form->id,
				singleColumnBreakpoint: $la_options['singleColumnBreakpoint'],
			);

			// Parse the custom image tags
			$this->parseAwyissImageTags($lo_form, $lo_mediaRenderOptions);

			// Parse the module
			$this->parseModule($lo_form, $lo_mediaRenderOptions);
		}

		// Set the view variables
		$this->set([
			'contents' => $lo_formRenderer->getFormBody($la_options),
			'form' => $lo_form,
			'formElements' => $la_formElements,
			'formElementsChecksum' => $lo_form->getFormElementsChecksum(),
			'page' => $this->page,
			'sent' => $lo_formRenderer->isSent(),
			'fullWidth' => $la_options['fullWidth'],
			'includeWrapper' => $la_options['includeWrapper'],
			'singleColumnBreakpoint' => $la_options['singleColumnBreakpoint'],
		]);
	}


	/**
	 * @inheritDoc
	 */
	protected function renderElement(Entity $entity, string $children): string {
		// Not used in here.
		return '';
	}
}
