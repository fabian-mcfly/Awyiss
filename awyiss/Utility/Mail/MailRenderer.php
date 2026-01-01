<?php declare(strict_types=1);


namespace Awyiss\Utility\Mail;


use Awyiss\Core\App;
use Cake\Mailer\Renderer;


/**
 * Custom implementation of the CakePHP Mailer\Renderer class for mail.
 */
class MailRenderer extends Renderer {
	/**
	 * Re-implemented to:
	 *
	 * - load the FrontendView class using the App::className finder.
	 * - render the content based on the provided types.
	 *
	 * @inheritDoc
	 * @noinspection DuplicatedCode
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

		$templatePath = rtrim($this->viewBuilder()->getTemplatePath(), DIRECTORY_SEPARATOR);
		foreach ($types as $type) {
			$view->setTemplatePath($templatePath . DIRECTORY_SEPARATOR . $type);

			if ($type === 'text') {
				$view->disableAutoLayout();
			}

			$rendered[ $type ] = $view->render();
		}

		return $rendered;
	}
}
