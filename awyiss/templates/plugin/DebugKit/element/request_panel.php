<?php declare(strict_types=1);

/**
 * @var \DebugKit\View\AjaxView $this
 * @var array $headers
 * @var array $attributes
 * @var \Cake\Error\Debug\NodeInterface $data
 * @var \Cake\Error\Debug\NodeInterface $query
 * @var \Cake\Error\Debug\NodeInterface $cookie
 * @var string $matchedRoute
 */

if (!empty($data)) {
	array_walk($data, function (&$value, $key) {
		if (in_array($key, ['password', 'passwordConfirm', 'pass'])) {
			$value = '****** (value hidden)';
		}
	});
}

include ROOT . DS . 'vendor' . DS . 'cakephp/debug_kit/templates/element/request_panel.php';