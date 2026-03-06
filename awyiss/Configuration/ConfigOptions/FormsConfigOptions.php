<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptions\Trait\TableFieldsTrait;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Form\Protection\FormProtectionProvider;
use Awyiss\Utility\Inflector;


/**
 * Provides all configuration options for the forms scope
 */
class FormsConfigOptions extends AbstractConfigOptions {
	use TableFieldsTrait;


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions(): void {
		$this->add(Awyiss::REALM_BACKEND, [
			'overview' => [
				new ConfigOption(
					defaultValue: [
						'identifier',
					],
					identifier: 'displayedFields',
					localizable: false,
					personalizable: true,
					type: ConfigOptionType::ValueCollection,
					values: function () {
						$fields = $this->getTableFields();

						unset($fields['id'], $fields['title']);

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
			'publicationData' => [
				new ConfigOption(
					defaultValue: true,
					identifier: 'enabled',
					nullable: false,
					localizable: false,
					type: ConfigOptionType::Bool,
				),
			],
		]);

		$this->add(Awyiss::REALM_FRONTEND, [
			'protection' => [
				new ConfigOption(
					defaultValue: ['altcha', 'duplicateCheck', 'ipCheck', 'hiddenInput'],
					identifier: 'methods',
					localizable: false,
					nullable: true,
					type: ConfigOptionType::ValueCollection,
					values: function () {
						$protectionMethods = FormProtectionProvider::getFormProtectionFiles();

						foreach ($protectionMethods as $identifier => $class) {
							$protectionMethods[ $identifier ] = __d('Forms', 'protection_method_' . Inflector::underscore($identifier));
						}

						asort($protectionMethods);

						return $protectionMethods;
					},
				),
			],
		]);
	}
}
