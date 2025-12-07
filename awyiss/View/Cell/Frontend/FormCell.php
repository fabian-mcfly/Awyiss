<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Core\App;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Page;
use Awyiss\Utility\DebugTimer;
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
		DebugTimer::start('FormCell::display', sprintf('FormCell::display: Rendering form "%s" on page %d', $identifier, $page->id));

		$this->View = $view;

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Frontend/cell/Form');

		$options = $this->initCellOptions($options);

		$this->page = $page;

		/** @var class-string<\Awyiss\Utility\Form\FormRenderer> $className */
		$className = App::className('FormRenderer', 'Utility/Form');

		$formRenderer = new $className($this->createView('Frontend'));

		$formRenderer
			->initForm(
				$identifier,
				$this->request->getData(),
				$this->page
			);

		$form = $formRenderer->getForm();
		if (!$form) {
			DebugTimer::stop('FormCell::display');
			return;
		}

		$formRenderer->process(
			$this->request->getParam('formEntry'),
			$options
		);

		// Set the view variables
		$this->set([
			'contents' => $formRenderer->getFormBody($options),
			'form' => $form,
			'formElements' => $form->getLinearFormElements(),
			'formElementsChecksum' => $form->getFormElementsChecksum(),
			'page' => $this->page,
			'sent' => $formRenderer->isSent(),
			'fullWidth' => $options['fullWidth'],
			'includeWrapper' => $options['includeWrapper'],
			'singleColumnBreakpoint' => $options['singleColumnBreakpoint'],
		]);

		DebugTimer::stop('FormCell::display');
	}


	/**
	 * @inheritDoc
	 */
	protected function renderElement(Entity $entity, string $children): string {
		// Not used in here.
		return '';
	}
}
