<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Core\App;
use Awyiss\Utility\Route\RoutingServiceInterface;
use Awyiss\Utility\Translation\TranslationServiceInterface;
use DateTimeZone;


/**
 * Provides all configuration options for the System scope
 */
class SystemConfigOptions extends AbstractConfigOptions {
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
				personalizable: true,
				type: ConfigOptionType::Bool,
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
			'publicationData' => [
				new ConfigOption(
					defaultValue: true,
					identifier: 'checkAncestorPagesPublicationStatus',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool,
				),
			],
			'route' => [
				new ConfigOption(
					defaultValue: null,
					identifier: 'orsApiKey',
					localizable: false,
					nullable: true,
					type: ConfigOptionType::String,
				),
				new ConfigOption(
					defaultValue: null,
					identifier: 'routingService',
					localizable: false,
					type: ConfigOptionType::ListKey,
					values: $this->getRoutingServices(...),
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
					$timezones = DateTimeZone::listIdentifiers();
					$timezones = array_combine($timezones, $timezones);

					return ['auto' => __d('System', 'timezone_automatic')] + $timezones;
				},
			),
		]);


		$this->add(Awyiss::REALM_BACKEND, [
			'autoTranslate' => [
				new ConfigOption(
					defaultValue: null,
					identifier: 'deeplApiKey',
					localizable: false,
					nullable: true,
					type: ConfigOptionType::String,
				),
				new ConfigOption(
					defaultValue: null,
					identifier: 'googleApiKey',
					localizable: false,
					nullable: true,
					type: ConfigOptionType::String,
				),
				new ConfigOption(
					defaultValue: null,
					identifier: 'openAiApiKey',
					localizable: false,
					nullable: true,
					type: ConfigOptionType::String,
				),
				new ConfigOption(
					defaultValue: null,
					identifier: 'openAiModel',
					localizable: false,
					nullable: true,
					type: ConfigOptionType::String,
				),
				new ConfigOption(
					defaultValue: 'disabled',
					identifier: 'mode',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::ListKey,
					values: function () {
						return [
							'disabled' => __d('System', 'auto_translate_disabled'),
							'auto' => __d('System', 'auto_translate_automatic'),
						];
					},
				),
				new ConfigOption(
					defaultValue: null,
					identifier: 'translationService',
					localizable: false,
					type: ConfigOptionType::ListKey,
					values: $this->getTranslationServices(...),
				),
			],
			new ConfigOption(
				defaultValue: 'strict',
				identifier: 'htmlCleaning',
				localizable: false,
				nullable: false,
				personalizable: false,
				type: ConfigOptionType::ListKey,
				values: [
					'none' => __d('System', 'html_cleaning_none'),
					'moderate' => __d('System', 'html_cleaning_moderate'),
					'strict' => __d('System', 'html_cleaning_strict'),
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
					defaultValue: 'plain',
					identifier: 'editor',
					localizable: false,
					nullable: false,
					personalizable: true,
					type: ConfigOptionType::ListKey,
					values: [
						'plain' => __d('System', 'interface_editor_plain'),
						'jodit' => __d('System', 'interface_editor_jodit'),
						'tinymce' => __d('System', 'interface_editor_tinymce'),
					],
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
					defaultValue: 'regular',
					identifier: 'scale',
					localizable: false,
					nullable: false,
					personalizable: true,
					type: ConfigOptionType::ListKey,
					values: [
						'small' => __d('System', 'interface_scale_small'),
						'medium' => __d('System', 'interface_scale_medium'),
						'regular' => __d('System', 'interface_scale_regular'),
					],
				),
				new ConfigOption(
					defaultValue: false,
					identifier: 'sidebarMode',
					localizable: false,
					nullable: false,
					personalizable: true,
					type: ConfigOptionType::Bool,
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
					defaultValue: true,
					identifier: 'sessionBased',
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
					$timezones = DateTimeZone::listIdentifiers();
					$timezones = array_combine($timezones, $timezones);

					return ['auto' => __d('System', 'timezone_automatic')] + $timezones;
				},
			),
			new ConfigOption(
				defaultValue: true,
				identifier: 'typographyFixing',
				localizable: false,
				nullable: false,
				personalizable: false,
				type: ConfigOptionType::Bool,
			),
		]);
	}


	/**
	 * Get all available route classes
	 *
	 * @return array
	 */
	protected function getRoutingServices(): array {
		$classes = [];

		// Traverse both namespaces
		foreach (App::classes('*', 'Utility/Route', 'RoutingService', RoutingServiceInterface::class) as $classPath) {
			$classes[ $classPath ] = $classPath;
		}

		return $classes;
	}


	/**
	 * Get all available translation service classes
	 *
	 * @return array
	 */
	protected function getTranslationServices(): array {
		$classes = [];

		// Traverse both namespaces
		foreach (App::classes('*', 'Utility/Translation', 'TranslationService', TranslationServiceInterface::class) as $classPath) {
			$classes[ $classPath ] = $classPath;
		}

		return $classes;
	}
}
