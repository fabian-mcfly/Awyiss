<?php declare(strict_types=1);


namespace awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionType;


/**
 * Provides all configuration options for the ContentTemplates scope
 */
class PageTemplatesConfigOptions extends AbstractConfigOptions {
	/**
	 * @var string Scope of these options
	 */
	protected static string $scope = 'PageTemplates';


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions (): void {
		$this->add(Awyiss::REALM_BACKEND, [
			'paginate' => [
				new ConfigOption([
					'defaultValue' => 20,
					'identifier' => 'limit',
					'localizable' => FALSE,
					'nullable' => FALSE,
					'type' => ConfigOptionType::INTEGER,
				]),
			],
		]);
	}
}