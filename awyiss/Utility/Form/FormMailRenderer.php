<?php declare(strict_types=1);


namespace Awyiss\Utility\Form;


use Awyiss\Core\App;
use Cake\Mailer\Renderer;


/**
 * Custom implementation of the CakePHP Mailer\Renderer class.
 */
class FormMailRenderer extends Renderer {
	/**
	 * Re-implemented to:
	 *
	 * - load the FrontendView class using the App::className finder.
	 * - render the content based on the provided types.
	 *
	 * @inheritDoc
	 */
	public function render(string $content, array $types = []): array {
		$rendered = [];
		$template = $this->viewBuilder()->getTemplate();

		if (!$template) {
			foreach ($types as $type) {
				$rendered[ $type ] = $content;
			}

			return $rendered;
		}

		$className = App::className('Frontend', 'View', 'View');
		$view = $this->createView($className);

		if ($view->get('content') === null) {
			$view->set('content', $content);
		}

		foreach ($types as $type) {
			/**
			 * If the type is `text`, no additional rendering is done,
			 * as the content is already plain text.
			 */
			$rendered[ $type ] = $type === 'text' ? $view->get('textPlain') : $view->render();
		}

		return $rendered;
	}
}
