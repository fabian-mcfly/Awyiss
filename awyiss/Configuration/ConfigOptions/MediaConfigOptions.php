<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;


class MediaConfigOptions extends AbstractConfigOptions {
	protected static string $scope = 'media';


	public function initializeConfigOptions (): void {
		$this->add([
			'frontend' => [
				new ConfigOption([
					'defaultValue' => [2560, 1920, 1680, 1280, 1024, 768, 640, 480, 360],
					'localizable' => FALSE,
					'name' => 'default_breakpoints',
					'nullable' => FALSE,
					'type' => ConfigOption::TYPE_JSON,
				]),
			]
		]);
	}
}