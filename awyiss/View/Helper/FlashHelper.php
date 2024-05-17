<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Cake\View\Helper;


/**
 * FlashHelper class to render flash messages.
 * After setting messages in your controllers with FlashComponent, you can use
 * this class to output your flash messages in your views.
 *
 * @see \Cake\View\Helper\FlashHelper
 */
class FlashHelper extends Helper {
	/**
	 * When calling this method with '*' as `$key`, it will return all flash messages, no matter they key they
	 * have been set with.
	 *
	 * This allows the backend to display messages after redirecting to a different controller.
	 *
	 * @param string $key
	 * @param array $options
	 * @return string|null
	 * @see Helper\FlashHelper::render
	 */
	public function render(string $key = '*', array $options = []): ?string {
		$lo_flash = $this->_View->getRequest()->getFlash();

		if ($key === '*') {
			$la_controllerMessage = [];
			foreach (($this->_View->getRequest()->getSession()->read('Flash') ?? []) as $ls_key => $la_messages) {
				$la_controllerMessage += $lo_flash->consume($ls_key);
			}

			$la_globalMessages = $lo_flash->consume($key) ?? [];
			$la_messages = array_filter(array_merge($la_controllerMessage, $la_globalMessages));
		}
		else {
			$la_messages = $lo_flash->consume($key);
		}

		if (!$la_messages) {
			return null;
		}

		$ls_messages = '';
		foreach ($la_messages as $la_message) {
			$la_message = $options + $la_message;

			if (!isset($la_message['params']['escape']) || $la_message['params']['escape'] !== false) {
				$la_message['message'] = h($la_message['message']);
			}

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
