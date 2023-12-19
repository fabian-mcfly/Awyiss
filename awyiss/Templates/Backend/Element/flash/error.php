<?php

/**
 * @var \Cake\View\View $this
 * @var array $params
 * @var string $message
 */
if ( ! isset($params['escape']) || $params['escape'] !== FALSE) {
	$message = h($message);
}
?>
<div class="message error" onclick="this.classList.add('hidden');"><?=$message?></div>
