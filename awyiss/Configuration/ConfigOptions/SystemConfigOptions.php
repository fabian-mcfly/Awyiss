<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionTypes;


/**
 * Provides all configuration options for the System scope
 */
class SystemConfigOptions extends AbstractConfigOptions {
	/**
	 * @var string Scope of these options
	 */
	protected static string $scope = 'system';


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions (): void {
		$this->add([
			'frontend' => [
				new ConfigOption([
					'defaultValue' => TRUE,
					'localizable' => FALSE,
					'name' => 'editlinks',
					'nullable' => FALSE,
					'type' => ConfigOptionTypes::TYPE_BOOL,
				]),
				'meta' => [
					new ConfigOption([
						'defaultValue' => 'Firma',
						'name' => 'title_appendix',
					]),
					new ConfigOption([
						'defaultValue' => ' | ',
						'name' => 'title_separator',
					]),
				]
			]
		]);


		$this->add([
			'backend' => [
				new ConfigOption([
					'defaultValue' => 600,
					'localizable' => FALSE,
					'name' => 'lock_timeout',
					'nullable' => FALSE,
					'type' => ConfigOptionTypes::TYPE_INTEGER,
				]),
				'meta' => [
					new ConfigOption([
						'defaultValue' => 'Firma',
						'name' => 'title_appendix',
					]),
					new ConfigOption([
						'defaultValue' => ' | ',
						'name' => 'title_separator',
					]),
				]
			]
		]);
	}
}