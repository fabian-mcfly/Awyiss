<?php declare(strict_types=1);


namespace Awyiss\View;


use Awyiss\Core\Configure\Engine\PhpConfig;
use Cake\Core\Exception\CakeException;
use Cake\View\StringTemplate as BaseStringTemplate;


/**
 * @inheritDoc
 */
class StringTemplate extends BaseStringTemplate {
	/**
	 * {@inheritDoc}
	 *
	 * Used to load \Awyiss\Core\Configure\Engine\PhpConfig instead of the CakePHP one
	 */
	public function load(string $file): void {
		if ($file === '') {
			throw new CakeException('String template filename cannot be an empty string');
		}

		$loader = new PhpConfig();
		$this->add($loader->read($file));
	}
}
