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
						$la_fields = $this->getTableFields();

						unset($la_fields['id'], $la_fields['title']);

						return $la_fields;
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
					defaultValue: ['duplicate_check', 'ip_check', 'hidden_input'],
					identifier: 'methods',
					localizable: false,
					nullable: true,
					type: ConfigOptionType::ValueCollection,
					values: function () {
						$la_protectionMethod = FormProtectionProvider::getFormProtectionFiles();

						foreach ($la_protectionMethod as $ls_identifier => $ls_class) {
							$la_protectionMethods[ $ls_identifier ] = __d('forms', 'protection_method_' . Inflector::underscore($ls_identifier));
						}

						asort($la_protectionMethods);

						return $la_protectionMethods;
					},
				),
			],
		]);
	}
}
