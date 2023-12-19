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

		if ($la_messages === NULL || ! $la_messages) {
			return NULL;
		}

		$ls_messages = '';
		foreach ($la_messages as $ls_message) {
			$ls_message = $aa_options + $ls_message;
			$ls_messages .= $this->_View->element($ls_message['element'], $ls_message);
		}

		return $ls_messages;
	}
}
