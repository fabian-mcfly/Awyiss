<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionType;


/**
 * Provides all configuration options for the Users scope
 */
class UsersConfigOptions extends AbstractConfigOptions {
	/**
	 * @var string Scope of these options
	 */
	protected static string $scope = 'Users';


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions (): void {
		$this->add(Awyiss::REALM_BACKEND, [
			new ConfigOption([
				'defaultValue' => TRUE,
				'identifier' => 'search',
				'localizable' => FALSE,
				'type' => ConfigOptionType::TYPE_BOOL,
			]),
			'paginate' => [
				new ConfigOption([
					'defaultValue' => 20,
					'identifier' => 'limit',
					'localizable' => FALSE,
					'nullable' => FALSE,
					'type' => ConfigOptionType::TYPE_INTEGER,
				]),
			],
		]);
	}
}