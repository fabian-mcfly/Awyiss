<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionType;
use DateTimeZone;


/**
 * Provides all configuration options for the System scope
 */
class SystemConfigOptions extends AbstractConfigOptions {
	/**
	 * @var string Scope of these options
	 */
	protected static string $scope = 'System';


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions(): void {
		$this->add(Awyiss::REALM_FRONTEND, [
			new ConfigOption(
				defaultValue: true,
				identifier: 'editor',
				localizable: false,
				nullable: false,
				type: ConfigOptionType::Bool,
			),
			new ConfigOption(
				defaultValue: null,
				identifier: 'orsApiKey',
				localizable: false,
				nullable: true,
				type: ConfigOptionType::String,
			),
			'meta' => [
				new ConfigOption(
					defaultValue: 'Firma',
					identifier: 'titleAppendix',
				),
				new ConfigOption(
					defaultValue: ' | ',
					identifier: 'titleSeparator',
				),
			],
		]);


		$this->add(Awyiss::REALM_BACKEND, [
			new ConfigOption(
				defaultValue: 'strict',
				identifier: 'htmlCleaning',
				localizable: false,
				nullable: false,
				personalizable: false,
				type: ConfigOptionType::ListKey,
				values: [
					'none' => __d('system', 'html_cleaning_none'),
					'moderate' => __d('system', 'html_cleaning_moderate'),
					'strict' => __d('system', 'html_cleaning_strict'),
				],
			),
			'interface' => [
				new ConfigOption(
					defaultValue: false,
					identifier: 'darkMode',
					localizable: false,
					nullable: false,
					personalizable: true,
					type: ConfigOptionType::Bool,
				),
				new ConfigOption(
					defaultValue: false,
					identifier: 'disctractionFreeMode',
					localizable: false,
					nullable: false,
					personalizable: true,
					type: ConfigOptionType::Bool,
				),
				new ConfigOption(
					defaultValue: null,
					identifier: 'highlightColor',
					localizable: false,
					nullable: null,
					personalizable: true,
					type: ConfigOptionType::Color,
				),
				new ConfigOption(
					defaultValue: 'plain',
					identifier: 'editor',
					localizable: false,
					nullable: false,
					personalizable: true,
					type: ConfigOptionType::ListKey,
					values: [
						'plain' => __d('system', 'interface_editor_plain'),
						'jodit' => __d('system', 'interface_editor_jodit'),
						'tinymce' => __d('system', 'interface_editor_tinymce'),
					],
				),
				new ConfigOption(
					defaultValue: 'regular',
					identifier: 'scale',
					localizable: false,
					nullable: false,
					personalizable: true,
					type: ConfigOptionType::ListKey,
					values: [
						'small' => __d('system', 'interface_scale_small'),
						'medium' => __d('system', 'interface_scale_medium'),
						'regular' => __d('system', 'interface_scale_regular'),
					],
				),
			],
			'lock' => [
				new ConfigOption(
					defaultValue: true,
					identifier: 'enabled',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool,
				),
				new ConfigOption(
					defaultValue: 1200,
					identifier: 'timeout',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Integer,
				),
			],
			'meta' => [
				new ConfigOption(
					defaultValue: 'Awyiss Backend',
					identifier: 'titleAppendix',
				),
				new ConfigOption(
					defaultValue: ' | ',
					identifier: 'titleSeparator',
				),
			],
			new ConfigOption(
				defaultValue: 'auto',
				identifier: 'timezone',
				localizable: false,
				nullable: false,
				personalizable: true,
				type: ConfigOptionType::ListKey,
				values: function () {
					$la_timezones = DateTimeZone::listIdentifiers();
					$la_timezones = array_combine($la_timezones, $la_timezones);

					return ['auto' => __d('system', 'timezone_automatic')] + $la_timezones;
				},
			),
		]);
	}
}
