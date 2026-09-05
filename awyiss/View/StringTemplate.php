<?php declare(strict_types=1);


namespace Awyiss\View;


use Awyiss\Awyiss;
use Awyiss\Core\Configure\Engine\PhpConfig;
use Awyiss\Middleware\LocaleMiddleware;
use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\I18n\DateTime;
use Cake\View\StringTemplate as BaseStringTemplate;


/**
 * @inheritDoc
 */
class StringTemplate extends BaseStringTemplate {
	/**
	 * Used to load \Awyiss\Core\Configure\Engine\PhpConfig instead of the CakePHP one
	 *
	 * @inheritDoc
	 */
	public function load(string $file): void {
		if ($file === '') {
			throw new CakeException('String template filename cannot be an empty string');
		}

		$loader = new PhpConfig();
		$this->add($loader->read($file));
	}


	/**
	 * Re-implemented to handle DateTime objects and convert them to the correct timezone before formatting
	 *
	 * @inheritDoc
	 */
	public function formatAttributes(?array $options, ?array $exclude = null): string {
		if (isset($options['value']) && $options['value'] instanceof DateTime) {
			$timezone = $options['timezone'] ?? null
				?: Configure::read('Awyiss.System.' . Awyiss::getRealm() . '.timezone')
					?: date_default_timezone_get(); // phpcs:ignore

			if ($timezone === 'auto') {
				$language = $options['language'] ?? LocaleMiddleware::getLanguage(null);
				$timezone = $language->timezone;
			}

			if ($timezone) {
				$options['value'] = $options['value']->setTimezone($timezone);
			}

			$options['value'] = $options['value']->format('Y-m-d\TH:i:s');
		}

		return parent::formatAttributes($options, $exclude);
	}
}
