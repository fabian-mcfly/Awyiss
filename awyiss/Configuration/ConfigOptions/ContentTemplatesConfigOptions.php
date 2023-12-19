<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;


class ContentTemplatesConfigOptions extends AbstractConfigOptions {
	protected static string $scope = 'content_templates';


	public function initializeConfigOptions (): void {
		$this->add([
			'backend' => [
				'pagination' => [
					new ConfigOption([
						'defaultValue' => 20,
						'localizable' => FALSE,
						'name' => 'limit',
						'nullable' => FALSE,
						'type' => ConfigOption::TYPE_INTEGER,
					]),
				]
			]
		]);
	}
}