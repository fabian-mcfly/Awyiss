<?php

/**
 * @var \Awyiss\View\BackendView $this
 * @var array $params
 * @var string $message
 */
if ( ! isset($params['escape']) || $params['escape'] !== FALSE) {
	$message = h($message);
}
?>
<div class="message success" onclick="this.classList.add('hidden')"><?=$message?></div>
