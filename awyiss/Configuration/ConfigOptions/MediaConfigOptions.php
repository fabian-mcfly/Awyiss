<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionTypes;


/**
 * Provides all configuration options for the Media scope
 */
class MediaConfigOptions extends AbstractConfigOptions {
	/**
	 * @var string Scope of these options
	 */
	protected static string $scope = 'media';


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions (): void {
		$this->add([
			'frontend' => [
				new ConfigOption([
					'defaultValue' => [2560, 1920, 1680, 1280, 1024, 768, 640, 480, 360],
					'localizable' => FALSE,
					'name' => 'default_breakpoints',
					'nullable' => FALSE,
					'type' => ConfigOptionTypes::TYPE_JSON,
				]),
			]
		]);
	}
}