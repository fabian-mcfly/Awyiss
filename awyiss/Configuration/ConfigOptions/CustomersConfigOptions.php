<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptions\Trait\TableFieldsTrait;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Utility\Arrays;
use Cake\Mailer\TransportFactory;
use Cake\ORM\Locator\LocatorAwareTrait;


/**
 * Provides all configuration options for the Customers scope
 */
class CustomersConfigOptions extends AbstractConfigOptions {
	use LocatorAwareTrait;
	use TableFieldsTrait;


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions(): void {
		$this->add(Awyiss::REALM_BACKEND, [
			'overview' => [
				new ConfigOption(
					defaultValue: [
						'last_login',
					],
					identifier: 'displayedFields',
					localizable: false,
					personalizable: true,
					type: ConfigOptionType::ValueCollection,
					values: function (): array {
						$fields = $this->getTableFields();

						unset($fields['id'], $fields['password']);

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

		$this->add(Awyiss::REALM_FRONTEND, [
			'emails' => [
				new ConfigOption(
					defaultValue: null,
					identifier: 'senderName',
					localizable: true,
					nullable: true,
					type: ConfigOptionType::String,
				),
				new ConfigOption(
					defaultValue: null,
					identifier: 'senderEmail',
					localizable: true,
					nullable: true,
					type: ConfigOptionType::String,
					validate: function (mixed $value): bool {
						if (empty($value)) {
							return true;
						}

						return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
					},
				),
				new ConfigOption(
					defaultValue: null,
					identifier: 'transportProfile',
					localizable: false,
					nullable: true,
					type: ConfigOptionType::ListKey,
					values: function (): array {
						$profiles = [];

						foreach (TransportFactory::configured() ?: [] as $profile) {
							$config = TransportFactory::get($profile)->getConfig();
							unset($config['url'], $config['password']);

							$label = __d('forms', 'transport_profile_' . $profile, $config);
							$profiles[ $profile ] = str_contains($label, '::') ? $profile : $label;
						}

						return $profiles;
					},
				),
			],
			'login' => [
				new ConfigOption(
					defaultValue: false,
					identifier: 'enabled',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool,
				),
			],
			'navigation' => [
				new ConfigOption(
					defaultValue: null,
					identifier: 'menuIdentifier',
					localizable: true,
					nullable: true,
					type: ConfigOptionType::ListKey,
					values: function (): array {
						/** @var \Awyiss\Model\Table\MenusTable $menusTable */
						$menusTable = $this->getTableLocator()->get('Menus');

						$menus = $menusTable->find('list', keyField: 'identifier')
							->where(['active' => true])
							->toArray();

						Arrays::naturalSort($menus);

						return $menus;
					},
				),
			],
			'registration' => [
				new ConfigOption(
					defaultValue: false,
					identifier: 'enabled',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool,
				),
				new ConfigOption(
					defaultValue: true,
					identifier: 'requiresVerification',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool,
				),
				new ConfigOption(
					defaultValue: true,
					identifier: 'activeOnRegistration',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool,
				),
				new ConfigOption(
					defaultValue: 60 * 60 * 24,
					identifier: 'verificationCodeValidity',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Integer,
				),
				new ConfigOption(
					defaultValue: [],
					identifier: 'defaultGroups',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::ValueCollection,
					values: function (): array {
						/** @var \Awyiss\Model\Table\CustomerGroupsTable $customerGroupsTable */
						$customerGroupsTable = $this->getTableLocator()->get('CustomerGroups');

						$customerGroups = $customerGroupsTable->find('list')
							->where(['active' => true])
							->toArray();

						Arrays::naturalSort($customerGroups);

						return $customerGroups;
					},
				),
				new ConfigOption(
					defaultValue: true,
					identifier: 'deleteUnverifiedAccounts',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool,
				),
			],
			'passwordReset' => [
				new ConfigOption(
					defaultValue: true,
					identifier: 'enabled',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool,
				),
				new ConfigOption(
					defaultValue: 3600,
					identifier: 'codeValidity',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Integer,
				),
			],
			'profile' => [
				new ConfigOption(
					defaultValue: false,
					identifier: 'emailChangeAllowed',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool,
				),
			],
		]);
	}
}
