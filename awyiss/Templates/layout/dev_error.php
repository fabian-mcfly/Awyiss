<?php declare(strict_types=1);

if (PHP_SAPI === 'cli') {
	echo $this->fetch('title') . PHP_EOL;
}
else {
	include CAKE . '../templates/layout/dev_error.php';
}

