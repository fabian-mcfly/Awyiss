<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


class FlashHelper extends \Cake\View\Helper {
	public function render (string $as_key = '*', array $aa_options = []): ?string {
		if ($as_key === '*') {
			$ls_key = $this->_View->getRequest()->getFlash()->getConfig('key');
			$la_controllerMessage = $this->_View->getRequest()->getFlash()->consume($ls_key);
			$la_globalMessages = $this->_View->getRequest()->getFlash()->consume($as_key);
			$la_messages = array_merge($la_controllerMessage ?? [], $la_globalMessages ?? []);
		}
		else {
			$la_messages = $this->_View->getRequest()->getFlash()->consume($as_key);
		}

		if ( ! $la_messages) {
			return NULL;
		}

		$ls_messages = '';
		foreach ($la_messages as $la_message) {
			$la_message = $aa_options + $la_message;

			if ( ! isset($la_message['params']['escape']) || $la_message['params']['escape'] !== FALSE) {
				$la_message['message'] = h($la_message['message']);
			}

			$la_message['class'] = '';
			if ( ! empty($la_message['params']['class'])) {
				$la_message['class'] .= ' ' . $la_message['params']['class'];
				unset($la_message['params']['class']);
			}

			$ls_messages .= $this->_View->element($la_message['element'], [
				'as_message' => $la_message['message'],
				'aa_params' => $la_message['params'] ?? [],
				'as_classes' => $la_message['class'],
			]);
		}

		return $ls_messages;
	}
}
