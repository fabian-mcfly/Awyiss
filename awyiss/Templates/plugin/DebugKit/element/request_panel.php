<?php

if (!empty($data)) {
	array_walk($data, function (&$value, $key) {
		if (in_array($key, ['password', 'password_confirm', 'pass'])) {
			$value = '****** (value hidden)';
		}
	});
}

include ROOT . DS . 'vendor' . DS . 'cakephp/debug_kit/templates/element/request_panel.php';