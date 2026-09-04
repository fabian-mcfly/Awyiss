<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptions\Trait\TableFieldsTrait;
use Awyiss\Configuration\ConfigOptionType;


/**
 * Provides all configuration options for the Users scope
 */
class UsersConfigOptions extends AbstractConfigOptions {
	use TableFieldsTrait;


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions(): void {
		$this->add(Awyiss::REALM_BACKEND, [
			new ConfigOption(
				defaultValue: false,
				identifier: 'forceTwoFactor',
				localizable: false,
				nullable: false,
				personalizable: false,
				type: ConfigOptionType::Bool,
			),
			'overview' => [
				new ConfigOption(
					defaultValue: [
						'email',
						'lastLogin',
					],
					identifier: 'displayedFields',
					localizable: false,
					personalizable: true,
					type: ConfigOptionType::ValueCollection,
					values: function () {
						$fields = $this->getTableFields();

						unset($fields['id'], $fields['username'], $fields['password'], $fields['twoFactorSecret']);

						return $fields;
					},
				),
			],
			'paginate' => [
				new ConfigOption(
					defaultValue: 20,
					identifier: 'limit',
					localizable: false,
					nullable: false,
					personalizable: true,
					type: ConfigOptionType::Integer,
				),
			],
		]);
	}
}
