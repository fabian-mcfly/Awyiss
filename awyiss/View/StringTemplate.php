<?php

declare(strict_types=1);


namespace Awyiss\View;


use Awyiss\Core\Configure\Engine\PhpConfig;
use Cake\Core\Exception\CakeException;


class StringTemplate extends \Cake\View\StringTemplate {
	public function load (string $file): void {
		if ($file === '') {
			throw new CakeException('String template filename cannot be an empty string');
		}

		$loader = new PhpConfig();
		$templates = $loader->read($file);
		$this->add($templates);
	}
}