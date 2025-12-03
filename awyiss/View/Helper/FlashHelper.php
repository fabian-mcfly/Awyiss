<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Cake\View\Helper;


/**
 * FlashHelper class to render flash messages.
 *
 * @see \Cake\View\Helper\FlashHelper
 */
class FlashHelper extends Helper {
	/**
	 * With the default key being set to '*',
	 * and the backend controller having '*' as the default key,
	 * this will render all flash messages, no matter
	 * where they came from.
	 *
	 * If you want to render only a specific key,
	 * you can pass that key as the first argument and
	 * pass the `key` option to the Flash component.
	 *
	 * @param string $key
	 * @param array $options
	 * @return string|null
	 * @see Helper\FlashHelper::render
	 */
	public function render(string $key = '*', array $options = []): ?string {
		$flash = $this->_View->getRequest()->getFlash();

		$messages = $flash->consume($key);

		if (!$messages) {
			return null;
		}

		$renderedMessages = '';
		foreach ($messages as $message) {
			$message = $options + $message;

			if (!isset($message['params']['escape']) || $message['params']['escape'] !== false) {
				$message['message'] = h($message['message']);
			}

			/**
			 * This is a bit of a hack to make the classes work.
			 *
			 * CakePHP does not pass the options set via the Flash component
			 * to the FlashHelper, so the only way to pass the class is
			 * to set it in the params array.
			 *
			 * @see \Cake\Http\FlashMessage::set()
			 */
			$message['class'] = '';
			if (!empty($message['params']['class'])) {
				$message['class'] .= ' ' . $message['params']['class'];
			}
			// Unset the class param, even if it's empty
			if ($message['params'] ?? []) {
				unset($message['params']['class']);
			}

			$renderedMessages .= $this->_View->element($message['element'], [
				'message' => $message['message'],
				'params' => $message['params'] ?? [],
				'classes' => $message['class'],
			]);
		}


		return $renderedMessages;
	}
}
