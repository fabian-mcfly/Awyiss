<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptions\Trait\TableFieldsTrait;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Utility\Inflector;


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
	 * @var string Create historical paths disabled
	 */
	final public const string CREATE_HISTORICAL_PATHS_DISABLED = 'disabled';
	/**
	 * @var string Create historical paths on file name change
	 */
	final public const string CREATE_HISTORICAL_PATHS_FILE_NAME_CHANGE = 'file_name_change';
	/**
	 * @var string Create historical paths on folder name change
	 */
	final public const string CREATE_HISTORICAL_PATHS_FOLDER_NAME_CHANGE = 'folder_name_change';
	/**
	 * @var string Create historical paths when file or folder name changes
	 */
	final public const string CREATE_HISTORICAL_PATHS_ALWAYS = 'always';
	/**
	 * @var string Resize media file type match source
	 */
	final public const string RESIZE_MEDIA_FILE_TYPE_MATCH_SOURCE = 'match_source';
	/**
	 * @var string Resize media file type avif
	 */
	final public const string RESIZE_MEDIA_FILE_TYPE_AVIF = 'avif';
	/**
	 * @var string Resize media file type webp
	 */
	final public const string RESIZE_MEDIA_FILE_TYPE_WEBP = 'webp';


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

					$cleanValues = $values;
					if (!is_array($cleanValues)) {
						$cleanValues = json_decode($cleanValues, true);
					}

					if (!is_array($cleanValues)) {
						$cleanValues = [$values];
					}

					$cleanValues = array_filter(array_map('intval', $cleanValues));

					rsort($cleanValues);

					return $cleanValues ?: null;
				}
			),
			'resizing' => [
				new ConfigOption(
					defaultValue: 'imagick',
					identifier: 'driver',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::ListKey,
					values: function (): array {
						$drivers = [];

						if (extension_loaded('Imagick')) {
							$drivers['imagick'] = __d(Inflector::underscore(static::$scope), 'driver_imagick');
						}

						if (extension_loaded('gd')) {
							$drivers['gd'] = __d(Inflector::underscore(static::$scope), 'driver_gd');
						}

						return $drivers;
					}
				),
				new ConfigOption(
					defaultValue: static::RESIZE_MEDIA_FILE_TYPE_AVIF,
					identifier: 'fileType',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::ListKey,
					values: [
						static::RESIZE_MEDIA_FILE_TYPE_MATCH_SOURCE => __d(Inflector::underscore(static::$scope), 'resize_media_file_type_match_source'),
						static::RESIZE_MEDIA_FILE_TYPE_AVIF => __d(Inflector::underscore(static::$scope), 'resize_media_file_type_avif'),
						static::RESIZE_MEDIA_FILE_TYPE_WEBP => __d(Inflector::underscore(static::$scope), 'resize_media_file_type_webp'),
					],
				),
				new ConfigOption(
					defaultValue: 70,
					identifier: 'quality',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Integer,
					validate: function (int|string $value): bool {
						$value = (int)$value;
						return $value > 0 && $value <= 100;
					},
				),
			],
		]);

		$this->add(Awyiss::REALM_BACKEND, [
			new ConfigOption(
				defaultValue: 'disabled',
				identifier: 'createHistoricalPaths',
				localizable: false,
				nullable: false,
				type: ConfigOptionType::ListKey,
				values: [
					static::CREATE_HISTORICAL_PATHS_DISABLED => __d(Inflector::underscore(static::$scope), 'create_historical_paths_disabled'),
					static::CREATE_HISTORICAL_PATHS_FILE_NAME_CHANGE => __d(Inflector::underscore(static::$scope), 'create_historical_paths_file_name_change'),
					static::CREATE_HISTORICAL_PATHS_FOLDER_NAME_CHANGE => __d(Inflector::underscore(static::$scope), 'create_historical_paths_folder_name_change'),
					static::CREATE_HISTORICAL_PATHS_ALWAYS => __d(Inflector::underscore(static::$scope), 'create_historical_paths_always'),
				],
			),
			new ConfigOption(
				defaultValue: true,
				identifier: 'handleImagesInHtml',
				localizable: false,
				nullable: false,
				type: ConfigOptionType::Bool,
			),
			'overview' => [
				new ConfigOption(
					defaultValue: [
						'usageCount',
					],
					identifier: 'displayedFields',
					localizable: false,
					personalizable: true,
					type: ConfigOptionType::ValueCollection,
					values: function () {
						$fields = $this->getTableFields();

						unset($fields['id'], $fields['title']);

						$fields['usageCount'] = __d('media', 'usage_count');

						return $fields;
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
