<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionType;


/**
 * Provides all configuration options for the ContentTemplates scope
 */
class PageRolesConfigOptions extends AbstractConfigOptions {
	/**
	 * @var string Scope of these options
	 */
	protected static string $scope = 'PageRoles';


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions(): void {
		$this->add(Awyiss::REALM_BACKEND, [
			new ConfigOption([
				'defaultValue' => true,
				'identifier' => 'autoCreateMenuEntries',
				'localizable' => false,
				'nullable' => false,
				'type' => ConfigOptionType::BOOL,
			]),
		]);
	}
}
