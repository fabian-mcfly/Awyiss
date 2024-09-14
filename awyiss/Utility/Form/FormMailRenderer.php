<?php declare(strict_types=1);


namespace Awyiss\Utility\Form;


use Awyiss\Core\App;
use Cake\Mailer\Renderer;


/**
 * Custom implementation of the CakePHP Mailer\Renderer class.
 */
class FormMailRenderer extends Renderer {
	/**
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
			if ($ls_type === 'text') {
				$la_rendered[ $ls_type ] = $lo_view->get('textPlain');
			}
			else {
				$la_rendered[ $ls_type ] = $lo_view->render();
			}
		}

		return $la_rendered;
	}
}
