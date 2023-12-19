<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionType;


/**
 * Provides all configuration options for the System scope
 */
class SystemConfigOptions extends AbstractConfigOptions {
	/**
	 * @var string Scope of these options
	 */
	protected static string $scope = 'System';


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions (): void {
		$this->add(Awyiss::REALM_FRONTEND, [
			new ConfigOption([
				'defaultValue' => TRUE,
				'identifier' => 'editlinks',
				'localizable' => FALSE,
				'nullable' => FALSE,
				'type' => ConfigOptionType::BOOL,
			]),
			'meta' => [
				new ConfigOption([
					'defaultValue' => 'Firma',
					'identifier' => 'titleAppendix',
				]),
				new ConfigOption([
					'defaultValue' => ' | ',
					'identifier' => 'titleSeparator',
				]),
			]
		]);


		$this->add(Awyiss::REALM_BACKEND, [
			new ConfigOption([
				'defaultValue' => 600,
				'identifier' => 'lockTimeout',
				'localizable' => FALSE,
				'nullable' => FALSE,
				'type' => ConfigOptionType::INTEGER,
			]),
			'meta' => [
				new ConfigOption([
					'defaultValue' => 'Firma',
					'identifier' => 'titleAppendix',
				]),
				new ConfigOption([
					'defaultValue' => ' | ',
					'identifier' => 'titleSeparator',
				]),
			]
		]);
	}
}