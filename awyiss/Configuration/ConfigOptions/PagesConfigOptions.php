<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptions\Trait\SystemOrderFieldsTrait;
use Awyiss\Configuration\ConfigOptionType;


/**
 * Provides all configuration options for the Pages scope
 */
class PagesConfigOptions extends AbstractConfigOptions {
	use SystemOrderFieldsTrait;


	/**
	 * @var string Scope of these options
	 */
	protected static string $scope = 'Pages';


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions(): void {
		$this->add(Awyiss::REALM_BACKEND, [
			'contents' => [
				new ConfigOption(
					defaultValue: true,
					identifier: 'enabled',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool,
				),
			],
		]);
	}
}
