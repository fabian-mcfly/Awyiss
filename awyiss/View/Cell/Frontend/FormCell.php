<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Core\App;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Page;
use Awyiss\View\Cell\Frontend\Trait\ContentElementTrait;
use Awyiss\View\Cell\Frontend\Trait\RedirectAwareTrait;
use Awyiss\View\Cell\Frontend\Trait\RenderTrimmedTrait;
use Awyiss\View\FrontendView;
use Cake\View\Cell;


/**
 * Form cell
 */
class FormCell extends Cell {
	use ContentElementTrait;
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
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function display(string|int $identifier, Page $page, FrontendView $view, array $options = []): void {
		$this->View = $view;

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

		$lo_formRenderer->process(
			$this->request->getParam('formEntry'),
			$la_options
		);

		// Set the view variables
		$this->set([
			'contents' => $lo_formRenderer->getFormBody($la_options),
			'form' => $lo_form,
			'formElements' => $lo_form->getLinearFormElements(),
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
