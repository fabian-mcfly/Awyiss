<?php declare(strict_types=1);
/**
 * @var \Awyiss\View\AppView $this
 * @var mixed $data
 * @var mixed $key
 */

if (!empty($la_data)) {
	array_walk($la_data, function (&$as_value, $as_key) {
		if (in_array($as_key, ['password', 'password_confirm', 'pass'])) {
			$as_value = '****** (value hidden)';
		}
	});
}

include ROOT . DS . 'vendor' . DS . 'cakephp/debug_kit/templates/element/request_panel.php';