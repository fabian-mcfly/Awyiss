<?php declare(strict_types=1);


namespace Customer\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptions\PagesConfigOptions as BasePagesConfigOptions;
use Awyiss\Configuration\ConfigOptionType;


/**
 * Provides all configuration options for the Pages scope
 */
class PagesConfigOptions extends BasePagesConfigOptions {
	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions(): void {
		parent::initializeConfigOptions();

		$this->add(Awyiss::REALM_BACKEND, [
			new ConfigOption(
				defaultValue: true,
				identifier: 'sampleEntry',
				localizable: false,
				nullable: false,
				type: ConfigOptionType::Bool,
			),
		]);
	}
}
