<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Page;
use Awyiss\Utility\Form\FormRenderer;
use Awyiss\View\Cell\Frontend\Trait\ContentElementTrait;
use Cake\Http\Exception\RedirectException;
use Cake\View\Cell;
use Error;
use Exception;


/**
 * Form cell
 */
class FormCell extends Cell {
	use ContentElementTrait;


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
		$this->viewBuilder()->setTemplatePath('Frontend/cell/Form');

		$la_options = $this->initCellOptions($options);

		$this->page = $page;

		/** @noinspection PhpParamsInspection */
		$lo_formRenderer = new FormRenderer($this->createView('Frontend'));

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

		// Set the view variables
		$this->set([
			'contents' => $lo_formRenderer->getFormBody($la_options),
			'form' => $lo_form,
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
