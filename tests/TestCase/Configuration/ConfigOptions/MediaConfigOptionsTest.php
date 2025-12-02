<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration\ConfigOptions;


use Awyiss\Configuration\ConfigOptions\MediaConfigOptions;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;


/**
 * MediaConfigOptions Test Case
 *
 * @see \Awyiss\Configuration\ConfigOptions\MediaConfigOptions
 */
class MediaConfigOptionsTest extends TestCase {
	use FlattenConfigOptionsTrait;


	/**
	 * @var \Awyiss\Configuration\ConfigOptions\MediaConfigOptions
	 */
	protected MediaConfigOptions $configOptions;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configOptions = new MediaConfigOptions();
	}


	/**
	 * @return void
	 */
	public function testInitializeConfigOptions(): void {
		$configOptions = $this->flattenConfigOptions($this->configOptions->getConfigOptions());

		$this->assertCount(10, $configOptions);

		$this->assertArrayHasKey('Frontend.defaultBreakpoints', $configOptions);
		$this->assertFalse($configOptions['Frontend.defaultBreakpoints']->isLocalizable());
		$this->assertFalse($configOptions['Frontend.defaultBreakpoints']->isNullable());
		$this->assertFalse($configOptions['Frontend.defaultBreakpoints']->isPersonalizable());
		$this->assertSame([2560, 1920, 1680, 1280, 1024, 768, 640, 480, 375], $configOptions['Frontend.defaultBreakpoints']->getDefaultValue());
		$this->assertSame('2560, 1920, 1680, 1280, 1024, 768, 640, 480, 375', $configOptions['Frontend.defaultBreakpoints']->getPrintableValue());
		$this->assertSame(ConfigOptionType::List, $configOptions['Frontend.defaultBreakpoints']->getType());
		$this->assertIsCallable($configOptions['Frontend.defaultBreakpoints']->getTypecast());
		$this->assertNull($configOptions['Frontend.defaultBreakpoints']->getValidate());
		$this->assertNull($configOptions['Frontend.defaultBreakpoints']->getValues());

		$this->assertArrayHasKey('Frontend.resizing.driver', $configOptions);
		$this->assertFalse($configOptions['Frontend.resizing.driver']->isLocalizable());
		$this->assertFalse($configOptions['Frontend.resizing.driver']->isNullable());
		$this->assertFalse($configOptions['Frontend.resizing.driver']->isPersonalizable());
		$this->assertSame('imagick', $configOptions['Frontend.resizing.driver']->getDefaultValue());
		$this->assertSame('media::driver_imagick', $configOptions['Frontend.resizing.driver']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ListKey, $configOptions['Frontend.resizing.driver']->getType());
		$this->assertNull($configOptions['Frontend.resizing.driver']->getTypecast());
		$this->assertNull($configOptions['Frontend.resizing.driver']->getValidate());
		$this->assertIsCallable($configOptions['Frontend.resizing.driver']->getValues());
		$this->assertSame([
			'imagick' => 'media::driver_imagick',
			'gd' => 'media::driver_gd',
		], $configOptions['Frontend.resizing.driver']->getValues(true));

		$this->assertArrayHasKey('Frontend.resizing.fileType', $configOptions);
		$this->assertFalse($configOptions['Frontend.resizing.fileType']->isLocalizable());
		$this->assertFalse($configOptions['Frontend.resizing.fileType']->isNullable());
		$this->assertFalse($configOptions['Frontend.resizing.fileType']->isPersonalizable());
		$this->assertSame(MediaConfigOptions::RESIZE_MEDIA_FILE_TYPE_AVIF, $configOptions['Frontend.resizing.fileType']->getDefaultValue());
		$this->assertSame('media::resize_media_file_type_avif', $configOptions['Frontend.resizing.fileType']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ListKey, $configOptions['Frontend.resizing.fileType']->getType());
		$this->assertNull($configOptions['Frontend.resizing.fileType']->getTypecast());
		$this->assertNull($configOptions['Frontend.resizing.fileType']->getValidate());
		$this->assertIsArray($configOptions['Frontend.resizing.fileType']->getValues());
		$this->assertSame([
			MediaConfigOptions::RESIZE_MEDIA_FILE_TYPE_MATCH_SOURCE => 'media::resize_media_file_type_match_source',
			MediaConfigOptions::RESIZE_MEDIA_FILE_TYPE_AVIF => 'media::resize_media_file_type_avif',
			MediaConfigOptions::RESIZE_MEDIA_FILE_TYPE_WEBP => 'media::resize_media_file_type_webp',
		], $configOptions['Frontend.resizing.fileType']->getValues());

		$this->assertArrayHasKey('Frontend.resizing.quality', $configOptions);
		$this->assertFalse($configOptions['Frontend.resizing.quality']->isLocalizable());
		$this->assertFalse($configOptions['Frontend.resizing.quality']->isNullable());
		$this->assertFalse($configOptions['Frontend.resizing.quality']->isPersonalizable());
		$this->assertSame(70, $configOptions['Frontend.resizing.quality']->getDefaultValue());
		$this->assertSame(70, $configOptions['Frontend.resizing.quality']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Integer, $configOptions['Frontend.resizing.quality']->getType());
		$this->assertNull($configOptions['Frontend.resizing.quality']->getTypecast());
		$this->assertIsCallable($configOptions['Frontend.resizing.quality']->getValidate());
		$this->assertNull($configOptions['Frontend.resizing.quality']->getValues());

		$this->assertArrayHasKey('Backend.createHistoricalPaths', $configOptions);
		$this->assertFalse($configOptions['Backend.createHistoricalPaths']->isLocalizable());
		$this->assertFalse($configOptions['Backend.createHistoricalPaths']->isNullable());
		$this->assertFalse($configOptions['Backend.createHistoricalPaths']->isPersonalizable());
		$this->assertSame('disabled', $configOptions['Backend.createHistoricalPaths']->getDefaultValue());
		$this->assertSame('media::create_historical_paths_disabled', $configOptions['Backend.createHistoricalPaths']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ListKey, $configOptions['Backend.createHistoricalPaths']->getType());
		$this->assertNull($configOptions['Backend.createHistoricalPaths']->getTypecast());
		$this->assertNull($configOptions['Backend.createHistoricalPaths']->getValidate());
		$this->assertIsArray($configOptions['Backend.createHistoricalPaths']->getValues());
		$this->assertSame([
			MediaConfigOptions::CREATE_HISTORICAL_PATHS_DISABLED => 'media::create_historical_paths_disabled',
			MediaConfigOptions::CREATE_HISTORICAL_PATHS_FILE_NAME_CHANGE => 'media::create_historical_paths_file_name_change',
			MediaConfigOptions::CREATE_HISTORICAL_PATHS_FOLDER_NAME_CHANGE => 'media::create_historical_paths_folder_name_change',
			MediaConfigOptions::CREATE_HISTORICAL_PATHS_ALWAYS => 'media::create_historical_paths_always',
		], $configOptions['Backend.createHistoricalPaths']->getValues());

		$this->assertArrayHasKey('Backend.handleImagesInHtml', $configOptions);
		$this->assertFalse($configOptions['Backend.handleImagesInHtml']->isLocalizable());
		$this->assertFalse($configOptions['Backend.handleImagesInHtml']->isNullable());
		$this->assertFalse($configOptions['Backend.handleImagesInHtml']->isPersonalizable());
		$this->assertSame(true, $configOptions['Backend.handleImagesInHtml']->getDefaultValue());
		$this->assertSame('true', $configOptions['Backend.handleImagesInHtml']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.handleImagesInHtml']->getType());
		$this->assertNull($configOptions['Backend.handleImagesInHtml']->getTypecast());
		$this->assertNull($configOptions['Backend.handleImagesInHtml']->getValidate());
		$this->assertNull($configOptions['Backend.handleImagesInHtml']->getValues());

		$this->assertArrayHasKey('Backend.overview.displayedFields', $configOptions);
		$this->assertFalse($configOptions['Backend.overview.displayedFields']->isLocalizable());
		$this->assertTrue($configOptions['Backend.overview.displayedFields']->isNullable());
		$this->assertTrue($configOptions['Backend.overview.displayedFields']->isPersonalizable());
		$this->assertSame(['usageCount'], $configOptions['Backend.overview.displayedFields']->getDefaultValue());
		$this->assertSame('media::usage_count', $configOptions['Backend.overview.displayedFields']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Backend.overview.displayedFields']->getType());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getTypecast());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getValidate());
		$this->assertIsCallable($configOptions['Backend.overview.displayedFields']->getValues());
		$this->assertSame([
			'media_folder_id' => 'media::media_folder_id',
			'mime_type' => 'media::mime_type',
			'name' => 'media::name',
			'path' => 'media::path',
			'alt' => 'media::alt',
			'width' => 'media::width',
			'height' => 'media::height',
			'meta_data' => 'media::meta_data',
			'average_color' => 'media::average_color',
			'preview' => 'media::preview',
			'avif' => 'media::avif',
			'webp' => 'media::webp',
			'crop' => 'media::crop',
			'focus_point' => 'media::focus_point',
			'system_order' => 'media::system_order',
			'created_by' => 'media::created_by',
			'created_on' => 'media::created_on',
			'changed_by' => 'media::changed_by',
			'changed_on' => 'media::changed_on',
			'usageCount' => 'media::usage_count',
		], $configOptions['Backend.overview.displayedFields']->getValues(true));

		$this->assertArrayHasKey('Backend.paginate.enabled', $configOptions);
		$this->assertFalse($configOptions['Backend.paginate.enabled']->isLocalizable());
		$this->assertFalse($configOptions['Backend.paginate.enabled']->isNullable());
		$this->assertTrue($configOptions['Backend.paginate.enabled']->isPersonalizable());
		$this->assertSame(false, $configOptions['Backend.paginate.enabled']->getDefaultValue());
		$this->assertSame('false', $configOptions['Backend.paginate.enabled']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.paginate.enabled']->getType());
		$this->assertNull($configOptions['Backend.paginate.enabled']->getTypecast());
		$this->assertNull($configOptions['Backend.paginate.enabled']->getValidate());
		$this->assertNull($configOptions['Backend.paginate.enabled']->getValues());

		$this->assertArrayHasKey('Backend.paginate.limit', $configOptions);
		$this->assertFalse($configOptions['Backend.paginate.limit']->isLocalizable());
		$this->assertFalse($configOptions['Backend.paginate.limit']->isNullable());
		$this->assertTrue($configOptions['Backend.paginate.limit']->isPersonalizable());
		$this->assertSame(20, $configOptions['Backend.paginate.limit']->getDefaultValue());
		$this->assertSame(20, $configOptions['Backend.paginate.limit']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Integer, $configOptions['Backend.paginate.limit']->getType());
		$this->assertNull($configOptions['Backend.paginate.limit']->getTypecast());
		$this->assertNull($configOptions['Backend.paginate.limit']->getValidate());
		$this->assertNull($configOptions['Backend.paginate.limit']->getValues());

		$this->assertArrayHasKey('Backend.upload.autoOverwrite', $configOptions);
		$this->assertFalse($configOptions['Backend.upload.autoOverwrite']->isLocalizable());
		$this->assertFalse($configOptions['Backend.upload.autoOverwrite']->isNullable());
		$this->assertTrue($configOptions['Backend.upload.autoOverwrite']->isPersonalizable());
		$this->assertSame(true, $configOptions['Backend.upload.autoOverwrite']->getDefaultValue());
		$this->assertSame('true', $configOptions['Backend.upload.autoOverwrite']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.upload.autoOverwrite']->getType());
		$this->assertNull($configOptions['Backend.upload.autoOverwrite']->getTypecast());
		$this->assertNull($configOptions['Backend.upload.autoOverwrite']->getValidate());
		$this->assertNull($configOptions['Backend.upload.autoOverwrite']->getValues());
	}


	/**
	 * @return void
	 */
	public function testTypecastDefaultBreakpoints(): void {
		$configOption = $this->configOptions->getConfigOption('Frontend', 'defaultBreakpoints');

		$this->assertSame([2560, 1920, 1680, 1280, 1024, 768, 640, 480, 375], $configOption->typecastConfigValue([2560, 1920, 1680, 1280, 1024, 768, 640, 480, 375]));
		$this->assertSame([2560], $configOption->typecastConfigValue('2560,1920,1680,1280,1024,768,640,480,375'));
		$this->assertSame([2560, 1920, 1680, 1280, 1024, 768, 640, 480, 375], $configOption->typecastConfigValue('[2560,1920,1680,1280,1024,768,640,480,375]'));
		$this->assertSame([1000, 500, 100], $configOption->typecastConfigValue([1000, 500, 100]));
		$this->assertSame([500], $configOption->typecastConfigValue('500, 100'));
		$this->assertNull($configOption->typecastConfigValue(null));
		$this->assertNull($configOption->typecastConfigValue([]));
		$this->assertNull($configOption->typecastConfigValue(''));
	}


	/**
	 * @return void
	 */
	public function testValidateQuality(): void {
		$configOption = $this->configOptions->getConfigOption('Frontend', 'resizing.quality');

		$this->assertTrue($configOption->validateConfigValue(1));
		$this->assertTrue($configOption->validateConfigValue(50));
		$this->assertTrue($configOption->validateConfigValue(100));
		$this->assertFalse($configOption->validateConfigValue(0));
		$this->assertFalse($configOption->validateConfigValue(-1));
		$this->assertFalse($configOption->validateConfigValue(101));
	}
}
