<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionType;


/**
 * Provides all configuration options for the Media scope
 */
class MediaConfigOptions extends AbstractConfigOptions {
	/**
	 * @var string Scope of these options
	 */
	protected static string $scope = 'Media';


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions(): void {
		$this->add(Awyiss::REALM_FRONTEND, [
			new ConfigOption([
				'defaultValue' => [2560, 1920, 1680, 1280, 1024, 768, 640, 480, 360],
				'identifier' => 'defaultBreakpoints',
				'localizable' => FALSE,
				'nullable' => FALSE,
				'type' => ConfigOptionType::JSON,
			]),
		]);
	}
}
