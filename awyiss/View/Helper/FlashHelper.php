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
		$lo_flash = $this->_View->getRequest()->getFlash();

		$la_messages = $lo_flash->consume($key);

		if (!$la_messages) {
			return null;
		}

		$ls_messages = '';
		foreach ($la_messages as $la_message) {
			$la_message = $options + $la_message;

			if (!isset($la_message['params']['escape']) || $la_message['params']['escape'] !== false) {
				$la_message['message'] = h($la_message['message']);
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
			$la_message['class'] = '';
			if (!empty($la_message['params']['class'])) {
				$la_message['class'] .= ' ' . $la_message['params']['class'];
				unset($la_message['params']['class']);
			}

			$ls_messages .= $this->_View->element($la_message['element'], [
				'message' => $la_message['message'],
				'params' => $la_message['params'] ?? [],
				'classes' => $la_message['class'],
			]);
		}


		return $ls_messages;
	}
}
