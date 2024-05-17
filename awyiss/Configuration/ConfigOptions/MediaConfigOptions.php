<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptions\Trait\TableFieldsTrait;
use Awyiss\Configuration\ConfigOptionType;


/**
 * Provides all configuration options for the Media scope
 */
class MediaConfigOptions extends AbstractConfigOptions {
	use TableFieldsTrait;

	/**
	 * @var string Scope of these options
	 */
	protected static string $scope = 'Media';


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions(): void {
		$this->add(Awyiss::REALM_FRONTEND, [
			new ConfigOption(
				defaultValue: [2560, 1920, 1680, 1280, 1024, 768, 640, 480, 375],
				identifier: 'defaultBreakpoints',
				localizable: false,
				nullable: false,
				type: ConfigOptionType::List,
				typecast: function (array|string|null $values): ?array {
					if ($values === null) {
						return null;
					}

					$la_values = $values;

					if (!is_array($la_values)) {
						$la_values = json_decode($la_values, true);
					}

					if (!is_array($la_values)) {
						$la_values = [$values];
					}

					$la_values = array_filter(array_map('intval', $la_values));

					rsort($la_values);

					return $la_values ?: null;
				}
			),
		]);

		$this->add(Awyiss::REALM_BACKEND, [
			'overview' => [
				new ConfigOption(
					defaultValue: [
						'path',
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
					defaultValue: false,
					identifier: 'enabled',
					localizable: false,
					nullable: false,
					personalizable: true,
					type: ConfigOptionType::Bool,
				),
				new ConfigOption(
					defaultValue: 20,
					identifier: 'limit',
					localizable: false,
					nullable: false,
					personalizable: true,
					type: ConfigOptionType::Integer,
				),
			],
			'upload' => [
				new ConfigOption(
					defaultValue: true,
					identifier: 'autoOverwrite',
					localizable: false,
					nullable: false,
					personalizable: true,
					type: ConfigOptionType::Bool,
				),
			],
		]);
	}
}
