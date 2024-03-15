<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionType;


/**
 * Provides all configuration options for the MenuEntries scope
 */
class MenusConfigOptions extends AbstractConfigOptions {
	/**
	 * @var string Scope of these options
	 */
	protected static string $scope = 'Menus';


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions(): void {
		$this->add(Awyiss::REALM_BACKEND, [
			'publicationData' => [
				new ConfigOption(
					defaultValue: true,
					identifier: 'enabled',
					localizable: false,
					type: ConfigOptionType::Bool,
				),
			],
		]);
	}
}
