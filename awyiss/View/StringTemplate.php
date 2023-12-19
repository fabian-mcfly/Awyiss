<?php declare(strict_types=1);


namespace Awyiss\View;


use Awyiss\Core\Configure\Engine\PhpConfig;
use Cake\Core\Exception\CakeException;


class StringTemplate extends \Cake\View\StringTemplate {
	public function load (string $as_file): void {
		if ($as_file === '') {
			throw new CakeException('String template filename cannot be an empty string');
		}

		$lo_loader = new PhpConfig();
		$la_templates = $lo_loader->read($as_file);
		$this->add($la_templates);
	}
}