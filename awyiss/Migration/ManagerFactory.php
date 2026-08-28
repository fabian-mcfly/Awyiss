<?php /** @noinspection PhpInternalEntityUsedInspection */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Migration;


use Migrations\Config\ConfigInterface;
use Migrations\Migration\ManagerFactory as BaseManagerFactory;


/**
 * Custom ManagerFactory to override path resolution logic
 */
class ManagerFactory extends BaseManagerFactory {
	/**
	 * Re-implemented to correctly parse customer paths to migrations and seeds
	 *
	 * @inheritDoc
	 */
	public function createConfig(): ConfigInterface {
		$config = parent::createConfig();
		$folder = (string)$this->getOption('source');

		if (!defined('CUSTOM_CONFIG')) {
			return $config;
		}

		if (str_starts_with($folder, CUSTOM_DIR . DS)) {
			$config->offsetSet('paths', [
				'migrations' => CUSTOM_CONFIG . 'Migrations',
				'seeds' => CUSTOM_CONFIG . 'Seeds',
			]);
		}

		return $config;
	}
}
