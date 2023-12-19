<?php declare(strict_types=1);


namespace Awyiss\View;


use Awyiss\Core\Configure\Engine\PhpConfig;
use Cake\Core\Exception\CakeException;


class StringTemplate extends \Cake\View\StringTemplate {
	/**
	 * @inheritDoc
	 *
	 * Used to load Awyiss\Core\Configure\Engine\PhpConfig instead of the CakePHP one
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function load (string $as_file): void {
		if ($as_file === '') {
			throw new CakeException('String template filename cannot be an empty string');
		}

		$lo_loader = new PhpConfig();
		$la_templates = $lo_loader->read($as_file);
		$this->add($la_templates);
	}
}