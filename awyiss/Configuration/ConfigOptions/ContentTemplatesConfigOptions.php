<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionTypes;


/**
 * Provides all configuration options for the ContentTemplates scope
 */
class ContentTemplatesConfigOptions extends AbstractConfigOptions {
	/**
	 * @var string Scope of these options
	 */
	protected static string $scope = 'content_templates';


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions (): void {
		$this->add([
			'backend' => [
				'pagination' => [
					new ConfigOption([
						'defaultValue' => 20,
						'localizable' => FALSE,
						'name' => 'limit',
						'nullable' => FALSE,
						'type' => ConfigOptionTypes::TYPE_INTEGER,
					]),
				]
			]
		]);
	}
}