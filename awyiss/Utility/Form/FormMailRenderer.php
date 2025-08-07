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
		$la_rendered = [];
		$ls_template = $this->viewBuilder()->getTemplate();

		if (!$ls_template) {
			foreach ($types as $ls_type) {
				$la_rendered[ $ls_type ] = $content;
			}

			return $la_rendered;
		}

		$ls_className = App::className('Frontend', 'View', 'View');
		$lo_view = $this->createView($ls_className);

		if ($lo_view->get('content') === null) {
			$lo_view->set('content', $content);
		}

		foreach ($types as $ls_type) {
			/**
			 * If the type is `text`, no additional rendering is done,
			 * as the content is already plain text.
			 */
			$la_rendered[ $ls_type ] = $ls_type === 'text' ? $lo_view->get('textPlain') : $lo_view->render();
		}

		return $la_rendered;
	}
}
