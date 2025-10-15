<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration\ConfigOptions;


use Awyiss\Configuration\ConfigOptions\SystemConfigOptions;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;


/**
 * SystemConfigOptions Test Case
 *
 * @see \Awyiss\Configuration\ConfigOptions\SystemConfigOptions
 */
class SystemConfigOptionsTest extends TestCase {
	use FlattenConfigOptionsTrait;


	/**
	 * @var \Awyiss\Configuration\ConfigOptions\SystemConfigOptions
	 */
	protected SystemConfigOptions $configOptions;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configOptions = new SystemConfigOptions();
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeConfigOptions(): void {
		$configOptions = $this->flattenConfigOptions($this->configOptions->getConfigOptions());

		$this->assertCount(20, $configOptions);

		$this->assertArrayHasKey('Frontend.editor', $configOptions);
		$this->assertFalse($configOptions['Frontend.editor']->isLocalizable());
		$this->assertFalse($configOptions['Frontend.editor']->isNullable());
		$this->assertTrue($configOptions['Frontend.editor']->isPersonalizable());
		$this->assertSame(true, $configOptions['Frontend.editor']->getDefaultValue());
		$this->assertSame('true', $configOptions['Frontend.editor']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Frontend.editor']->getType());
		$this->assertNull($configOptions['Frontend.editor']->getTypecast());
		$this->assertNull($configOptions['Frontend.editor']->getValidate());
		$this->assertNull($configOptions['Frontend.editor']->getValues());

		$this->assertArrayHasKey('Frontend.meta.titleAppendix', $configOptions);
		$this->assertTrue($configOptions['Frontend.meta.titleAppendix']->isLocalizable());
		$this->assertTrue($configOptions['Frontend.meta.titleAppendix']->isNullable());
		$this->assertFalse($configOptions['Frontend.meta.titleAppendix']->isPersonalizable());
		$this->assertSame('Firma', $configOptions['Frontend.meta.titleAppendix']->getDefaultValue());
		$this->assertSame('Firma', $configOptions['Frontend.meta.titleAppendix']->getPrintableValue());
		$this->assertSame(ConfigOptionType::String, $configOptions['Frontend.meta.titleAppendix']->getType());
		$this->assertNull($configOptions['Frontend.meta.titleAppendix']->getTypecast());
		$this->assertNull($configOptions['Frontend.meta.titleAppendix']->getValidate());
		$this->assertNull($configOptions['Frontend.meta.titleAppendix']->getValues());

		$this->assertArrayHasKey('Frontend.meta.titleSeparator', $configOptions);
		$this->assertTrue($configOptions['Frontend.meta.titleSeparator']->isLocalizable());
		$this->assertTrue($configOptions['Frontend.meta.titleSeparator']->isNullable());
		$this->assertFalse($configOptions['Frontend.meta.titleSeparator']->isPersonalizable());
		$this->assertSame(' | ', $configOptions['Frontend.meta.titleSeparator']->getDefaultValue());
		$this->assertSame(' | ', $configOptions['Frontend.meta.titleSeparator']->getPrintableValue());
		$this->assertSame(ConfigOptionType::String, $configOptions['Frontend.meta.titleSeparator']->getType());
		$this->assertNull($configOptions['Frontend.meta.titleSeparator']->getTypecast());
		$this->assertNull($configOptions['Frontend.meta.titleSeparator']->getValidate());
		$this->assertNull($configOptions['Frontend.meta.titleSeparator']->getValues());

		$this->assertArrayHasKey('Frontend.publicationData.checkAncestorPagesPublicationStatus', $configOptions);
		$this->assertFalse($configOptions['Frontend.publicationData.checkAncestorPagesPublicationStatus']->isLocalizable());
		$this->assertFalse($configOptions['Frontend.publicationData.checkAncestorPagesPublicationStatus']->isNullable());
		$this->assertFalse($configOptions['Frontend.publicationData.checkAncestorPagesPublicationStatus']->isPersonalizable());
		$this->assertSame(true, $configOptions['Frontend.publicationData.checkAncestorPagesPublicationStatus']->getDefaultValue());
		$this->assertSame('true', $configOptions['Frontend.publicationData.checkAncestorPagesPublicationStatus']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Frontend.publicationData.checkAncestorPagesPublicationStatus']->getType());
		$this->assertNull($configOptions['Frontend.publicationData.checkAncestorPagesPublicationStatus']->getTypecast());
		$this->assertNull($configOptions['Frontend.publicationData.checkAncestorPagesPublicationStatus']->getValidate());
		$this->assertNull($configOptions['Frontend.publicationData.checkAncestorPagesPublicationStatus']->getValues());

		$this->assertArrayHasKey('Frontend.route.orsApiKey', $configOptions);
		$this->assertFalse($configOptions['Frontend.route.orsApiKey']->isLocalizable());
		$this->assertTrue($configOptions['Frontend.route.orsApiKey']->isNullable());
		$this->assertFalse($configOptions['Frontend.route.orsApiKey']->isPersonalizable());
		$this->assertNull($configOptions['Frontend.route.orsApiKey']->getDefaultValue());
		$this->assertNull($configOptions['Frontend.route.orsApiKey']->getPrintableValue());
		$this->assertSame(ConfigOptionType::String, $configOptions['Frontend.route.orsApiKey']->getType());
		$this->assertNull($configOptions['Frontend.route.orsApiKey']->getTypecast());
		$this->assertNull($configOptions['Frontend.route.orsApiKey']->getValidate());
		$this->assertNull($configOptions['Frontend.route.orsApiKey']->getValues());

		$this->assertArrayHasKey('Frontend.route.routingService', $configOptions);
		$this->assertFalse($configOptions['Frontend.route.routingService']->isLocalizable());
		$this->assertTrue($configOptions['Frontend.route.routingService']->isNullable());
		$this->assertFalse($configOptions['Frontend.route.routingService']->isPersonalizable());
		$this->assertNull($configOptions['Frontend.route.routingService']->getDefaultValue());
		$this->assertNull($configOptions['Frontend.route.routingService']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ListKey, $configOptions['Frontend.route.routingService']->getType());
		$this->assertNull($configOptions['Frontend.route.routingService']->getTypecast());
		$this->assertNull($configOptions['Frontend.route.routingService']->getValidate());
		$this->assertIsCallable($configOptions['Frontend.route.routingService']->getValues());
		$this->assertSame([
			'\Awyiss\Utility\Route\OrsRoutingService' => '\Awyiss\Utility\Route\OrsRoutingService',
		], $configOptions['Frontend.route.routingService']->getValues(true));

		$this->assertArrayHasKey('Frontend.timezone', $configOptions);
		$this->assertFalse($configOptions['Frontend.timezone']->isLocalizable());
		$this->assertFalse($configOptions['Frontend.timezone']->isNullable());
		$this->assertTrue($configOptions['Frontend.timezone']->isPersonalizable());
		$this->assertSame('auto', $configOptions['Frontend.timezone']->getDefaultValue());
		$this->assertSame('system::timezone_automatic', $configOptions['Frontend.timezone']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ListKey, $configOptions['Frontend.timezone']->getType());
		$this->assertNull($configOptions['Frontend.timezone']->getTypecast());
		$this->assertNull($configOptions['Frontend.timezone']->getValidate());
		$this->assertIsCallable($configOptions['Frontend.timezone']->getValues());
		$this->assertArrayHasKey('auto', $configOptions['Frontend.timezone']->getValues(true));

		$this->assertArrayHasKey('Backend.htmlCleaning', $configOptions);
		$this->assertFalse($configOptions['Backend.htmlCleaning']->isLocalizable());
		$this->assertFalse($configOptions['Backend.htmlCleaning']->isNullable());
		$this->assertFalse($configOptions['Backend.htmlCleaning']->isPersonalizable());
		$this->assertSame('strict', $configOptions['Backend.htmlCleaning']->getDefaultValue());
		$this->assertSame('system::html_cleaning_strict', $configOptions['Backend.htmlCleaning']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ListKey, $configOptions['Backend.htmlCleaning']->getType());
		$this->assertNull($configOptions['Backend.htmlCleaning']->getTypecast());
		$this->assertNull($configOptions['Backend.htmlCleaning']->getValidate());
		$this->assertIsArray($configOptions['Backend.htmlCleaning']->getValues());
		$this->assertSame([
			'none' => 'system::html_cleaning_none',
			'moderate' => 'system::html_cleaning_moderate',
			'strict' => 'system::html_cleaning_strict',
		], $configOptions['Backend.htmlCleaning']->getValues());

		$this->assertArrayHasKey('Backend.interface.darkMode', $configOptions);
		$this->assertFalse($configOptions['Backend.interface.darkMode']->isLocalizable());
		$this->assertFalse($configOptions['Backend.interface.darkMode']->isNullable());
		$this->assertTrue($configOptions['Backend.interface.darkMode']->isPersonalizable());
		$this->assertSame(false, $configOptions['Backend.interface.darkMode']->getDefaultValue());
		$this->assertSame('false', $configOptions['Backend.interface.darkMode']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.interface.darkMode']->getType());
		$this->assertNull($configOptions['Backend.interface.darkMode']->getTypecast());
		$this->assertNull($configOptions['Backend.interface.darkMode']->getValidate());
		$this->assertNull($configOptions['Backend.interface.darkMode']->getValues());

		$this->assertArrayHasKey('Backend.interface.disctractionFreeMode', $configOptions);
		$this->assertFalse($configOptions['Backend.interface.disctractionFreeMode']->isLocalizable());
		$this->assertFalse($configOptions['Backend.interface.disctractionFreeMode']->isNullable());
		$this->assertTrue($configOptions['Backend.interface.disctractionFreeMode']->isPersonalizable());
		$this->assertSame(false, $configOptions['Backend.interface.disctractionFreeMode']->getDefaultValue());
		$this->assertSame('false', $configOptions['Backend.interface.disctractionFreeMode']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.interface.disctractionFreeMode']->getType());
		$this->assertNull($configOptions['Backend.interface.disctractionFreeMode']->getTypecast());
		$this->assertNull($configOptions['Backend.interface.disctractionFreeMode']->getValidate());
		$this->assertNull($configOptions['Backend.interface.disctractionFreeMode']->getValues());

		$this->assertArrayHasKey('Backend.interface.highlightColor', $configOptions);
		$this->assertFalse($configOptions['Backend.interface.highlightColor']->isLocalizable());
		$this->assertTrue($configOptions['Backend.interface.highlightColor']->isNullable());
		$this->assertTrue($configOptions['Backend.interface.highlightColor']->isPersonalizable());
		$this->assertNull($configOptions['Backend.interface.highlightColor']->getDefaultValue());
		$this->assertNull($configOptions['Backend.interface.highlightColor']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Color, $configOptions['Backend.interface.highlightColor']->getType());
		$this->assertNull($configOptions['Backend.interface.highlightColor']->getTypecast());
		$this->assertNull($configOptions['Backend.interface.highlightColor']->getValidate());
		$this->assertNull($configOptions['Backend.interface.highlightColor']->getValues());

		$this->assertArrayHasKey('Backend.interface.editor', $configOptions);
		$this->assertFalse($configOptions['Backend.interface.editor']->isLocalizable());
		$this->assertFalse($configOptions['Backend.interface.editor']->isNullable());
		$this->assertTrue($configOptions['Backend.interface.editor']->isPersonalizable());
		$this->assertSame('plain', $configOptions['Backend.interface.editor']->getDefaultValue());
		$this->assertSame('system::interface_editor_plain', $configOptions['Backend.interface.editor']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ListKey, $configOptions['Backend.interface.editor']->getType());
		$this->assertNull($configOptions['Backend.interface.editor']->getTypecast());
		$this->assertNull($configOptions['Backend.interface.editor']->getValidate());
		$this->assertIsArray($configOptions['Backend.interface.editor']->getValues());
		$this->assertSame([
			'plain' => 'system::interface_editor_plain',
			'jodit' => 'system::interface_editor_jodit',
			'tinymce' => 'system::interface_editor_tinymce',
		], $configOptions['Backend.interface.editor']->getValues());

		$this->assertArrayHasKey('Backend.interface.scale', $configOptions);
		$this->assertFalse($configOptions['Backend.interface.scale']->isLocalizable());
		$this->assertFalse($configOptions['Backend.interface.scale']->isNullable());
		$this->assertTrue($configOptions['Backend.interface.scale']->isPersonalizable());
		$this->assertSame('regular', $configOptions['Backend.interface.scale']->getDefaultValue());
		$this->assertSame('system::interface_scale_regular', $configOptions['Backend.interface.scale']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ListKey, $configOptions['Backend.interface.scale']->getType());
		$this->assertNull($configOptions['Backend.interface.scale']->getTypecast());
		$this->assertNull($configOptions['Backend.interface.scale']->getValidate());
		$this->assertIsArray($configOptions['Backend.interface.scale']->getValues());
		$this->assertSame([
			'small' => 'system::interface_scale_small',
			'medium' => 'system::interface_scale_medium',
			'regular' => 'system::interface_scale_regular',
		], $configOptions['Backend.interface.scale']->getValues());

		$this->assertArrayHasKey('Backend.interface.sidebarMode', $configOptions);
		$this->assertFalse($configOptions['Backend.interface.sidebarMode']->isLocalizable());
		$this->assertFalse($configOptions['Backend.interface.sidebarMode']->isNullable());
		$this->assertTrue($configOptions['Backend.interface.sidebarMode']->isPersonalizable());
		$this->assertSame(false, $configOptions['Backend.interface.sidebarMode']->getDefaultValue());
		$this->assertSame('false', $configOptions['Backend.interface.sidebarMode']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.interface.sidebarMode']->getType());
		$this->assertNull($configOptions['Backend.interface.sidebarMode']->getTypecast());
		$this->assertNull($configOptions['Backend.interface.sidebarMode']->getValidate());
		$this->assertNull($configOptions['Backend.interface.sidebarMode']->getValues());

		$this->assertArrayHasKey('Backend.lock.enabled', $configOptions);
		$this->assertFalse($configOptions['Backend.lock.enabled']->isLocalizable());
		$this->assertFalse($configOptions['Backend.lock.enabled']->isNullable());
		$this->assertFalse($configOptions['Backend.lock.enabled']->isPersonalizable());
		$this->assertSame(true, $configOptions['Backend.lock.enabled']->getDefaultValue());
		$this->assertSame('true', $configOptions['Backend.lock.enabled']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.lock.enabled']->getType());
		$this->assertNull($configOptions['Backend.lock.enabled']->getTypecast());
		$this->assertNull($configOptions['Backend.lock.enabled']->getValidate());
		$this->assertNull($configOptions['Backend.lock.enabled']->getValues());

		$this->assertArrayHasKey('Backend.lock.sessionBased', $configOptions);
		$this->assertFalse($configOptions['Backend.lock.sessionBased']->isLocalizable());
		$this->assertFalse($configOptions['Backend.lock.sessionBased']->isNullable());
		$this->assertFalse($configOptions['Backend.lock.sessionBased']->isPersonalizable());
		$this->assertSame(true, $configOptions['Backend.lock.sessionBased']->getDefaultValue());
		$this->assertSame('true', $configOptions['Backend.lock.sessionBased']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.lock.sessionBased']->getType());
		$this->assertNull($configOptions['Backend.lock.sessionBased']->getTypecast());
		$this->assertNull($configOptions['Backend.lock.sessionBased']->getValidate());
		$this->assertNull($configOptions['Backend.lock.sessionBased']->getValues());

		$this->assertArrayHasKey('Backend.lock.timeout', $configOptions);
		$this->assertFalse($configOptions['Backend.lock.timeout']->isLocalizable());
		$this->assertFalse($configOptions['Backend.lock.timeout']->isNullable());
		$this->assertFalse($configOptions['Backend.lock.timeout']->isPersonalizable());
		$this->assertSame(1200, $configOptions['Backend.lock.timeout']->getDefaultValue());
		$this->assertSame(1200, $configOptions['Backend.lock.timeout']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Integer, $configOptions['Backend.lock.timeout']->getType());
		$this->assertNull($configOptions['Backend.lock.timeout']->getTypecast());
		$this->assertNull($configOptions['Backend.lock.timeout']->getValidate());
		$this->assertNull($configOptions['Backend.lock.timeout']->getValues());

		$this->assertArrayHasKey('Backend.meta.titleAppendix', $configOptions);
		$this->assertTrue($configOptions['Backend.meta.titleAppendix']->isLocalizable());
		$this->assertTrue($configOptions['Backend.meta.titleAppendix']->isNullable());
		$this->assertFalse($configOptions['Backend.meta.titleAppendix']->isPersonalizable());
		$this->assertSame('Awyiss Backend', $configOptions['Backend.meta.titleAppendix']->getDefaultValue());
		$this->assertSame('Awyiss Backend', $configOptions['Backend.meta.titleAppendix']->getPrintableValue());
		$this->assertSame(ConfigOptionType::String, $configOptions['Backend.meta.titleAppendix']->getType());
		$this->assertNull($configOptions['Backend.meta.titleAppendix']->getTypecast());
		$this->assertNull($configOptions['Backend.meta.titleAppendix']->getValidate());
		$this->assertNull($configOptions['Backend.meta.titleAppendix']->getValues());

		$this->assertArrayHasKey('Backend.meta.titleSeparator', $configOptions);
		$this->assertTrue($configOptions['Backend.meta.titleSeparator']->isLocalizable());
		$this->assertTrue($configOptions['Backend.meta.titleSeparator']->isNullable());
		$this->assertFalse($configOptions['Backend.meta.titleSeparator']->isPersonalizable());
		$this->assertSame(' | ', $configOptions['Backend.meta.titleSeparator']->getDefaultValue());
		$this->assertSame(' | ', $configOptions['Backend.meta.titleSeparator']->getPrintableValue());
		$this->assertSame(ConfigOptionType::String, $configOptions['Backend.meta.titleSeparator']->getType());
		$this->assertNull($configOptions['Backend.meta.titleSeparator']->getTypecast());
		$this->assertNull($configOptions['Backend.meta.titleSeparator']->getValidate());
		$this->assertNull($configOptions['Backend.meta.titleSeparator']->getValues());

		$this->assertArrayHasKey('Backend.timezone', $configOptions);
		$this->assertFalse($configOptions['Backend.timezone']->isLocalizable());
		$this->assertFalse($configOptions['Backend.timezone']->isNullable());
		$this->assertTrue($configOptions['Backend.timezone']->isPersonalizable());
		$this->assertSame('auto', $configOptions['Backend.timezone']->getDefaultValue());
		$this->assertSame('system::timezone_automatic', $configOptions['Backend.timezone']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ListKey, $configOptions['Backend.timezone']->getType());
		$this->assertNull($configOptions['Backend.timezone']->getTypecast());
		$this->assertNull($configOptions['Backend.timezone']->getValidate());
		$this->assertIsCallable($configOptions['Backend.timezone']->getValues());
		$this->assertArrayHasKey('auto', $configOptions['Backend.timezone']->getValues(true));
	}
}
