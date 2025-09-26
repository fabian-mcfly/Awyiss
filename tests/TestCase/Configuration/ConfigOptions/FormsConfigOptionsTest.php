<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration\ConfigOptions;


use Awyiss\Configuration\ConfigOptions\FormsConfigOptions;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;


/**
 * FormsConfigOptions Test Case
 *
 * @see \Awyiss\Configuration\ConfigOptions\FormsConfigOptions
 */
class FormsConfigOptionsTest extends TestCase {
	use FlattenConfigOptionsTrait;


	/**
	 * @var \Awyiss\Configuration\ConfigOptions\FormsConfigOptions
	 */
	protected FormsConfigOptions $configOptions;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configOptions = new FormsConfigOptions();
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeConfigOptions(): void {
		$configOptions = $this->flattenConfigOptions($this->configOptions->getConfigOptions());

		$this->assertCount(4, $configOptions);

		$this->assertArrayHasKey('Backend.overview.displayedFields', $configOptions);
		$this->assertFalse($configOptions['Backend.overview.displayedFields']->isLocalizable());
		$this->assertTrue($configOptions['Backend.overview.displayedFields']->isNullable());
		$this->assertTrue($configOptions['Backend.overview.displayedFields']->isPersonalizable());
		$this->assertSame([
			'identifier',
		], $configOptions['Backend.overview.displayedFields']->getDefaultValue());
		$this->assertSame('forms::identifier', $configOptions['Backend.overview.displayedFields']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Backend.overview.displayedFields']->getType());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getTypecast());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getValidate());
		$this->assertIsCallable($configOptions['Backend.overview.displayedFields']->getValues());
		$this->assertSame([
			'identifier' => 'forms::identifier',
			'send_email' => 'forms::send_email',
			'email_template_id' => 'forms::email_template_id',
			'send_confirmation_email' => 'forms::send_confirmation_email',
			'confirmation_email_template_id' => 'forms::confirmation_email_template_id',
			'owner_email' => 'forms::owner_email',
			'owner_name' => 'forms::owner_name',
			'user_email' => 'forms::user_email',
			'user_name' => 'forms::user_name',
			'cc' => 'forms::cc',
			'bcc' => 'forms::bcc',
			'subject' => 'forms::subject',
			'subject_confirmation' => 'forms::subject_confirmation',
			'salutation' => 'forms::salutation',
			'salutation_confirmation' => 'forms::salutation_confirmation',
			'summarize_errors' => 'forms::summarize_errors',
			'success_message' => 'forms::success_message',
			'multistep' => 'forms::multistep',
			'conditional_recipients_strategy' => 'forms::conditional_recipients_strategy',
			'transport_profile' => 'forms::transport_profile',
			'active' => 'forms::active',
			'created_by' => 'forms::created_by',
			'created_on' => 'forms::created_on',
			'changed_by' => 'forms::changed_by',
			'changed_on' => 'forms::changed_on',
		], $configOptions['Backend.overview.displayedFields']->getValues(true));

		$this->assertArrayHasKey('Backend.publicationData.enabled', $configOptions);
		$this->assertFalse($configOptions['Backend.publicationData.enabled']->isLocalizable());
		$this->assertFalse($configOptions['Backend.publicationData.enabled']->isNullable());
		$this->assertFalse($configOptions['Backend.publicationData.enabled']->isPersonalizable());
		$this->assertSame(true, $configOptions['Backend.publicationData.enabled']->getDefaultValue());
		$this->assertSame('true', $configOptions['Backend.publicationData.enabled']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Backend.publicationData.enabled']->getType());
		$this->assertNull($configOptions['Backend.publicationData.enabled']->getTypecast());
		$this->assertNull($configOptions['Backend.publicationData.enabled']->getValidate());
		$this->assertNull($configOptions['Backend.publicationData.enabled']->getValues());

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

		$this->assertArrayHasKey('Frontend.protection.methods', $configOptions);
		$this->assertFalse($configOptions['Frontend.protection.methods']->isLocalizable());
		$this->assertTrue($configOptions['Frontend.protection.methods']->isNullable());
		$this->assertFalse($configOptions['Frontend.protection.methods']->isPersonalizable());
		$this->assertSame([
			'altcha',
			'duplicate_check',
			'ip_check',
			'hidden_input',
		], $configOptions['Frontend.protection.methods']->getDefaultValue());
		$this->assertSame('forms::protection_method_duplicate_check, forms::protection_method_ip_check, forms::protection_method_hidden_input', $configOptions['Frontend.protection.methods']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Frontend.protection.methods']->getType());
		$this->assertNull($configOptions['Frontend.protection.methods']->getTypecast());
		$this->assertNull($configOptions['Frontend.protection.methods']->getValidate());
		$this->assertIsCallable($configOptions['Frontend.protection.methods']->getValues());
		$this->assertSame([
			'altcha' => 'forms::protection_method_altcha',
			'dummy' => 'forms::protection_method_dummy',
			'dummy_stops_form_entry' => 'forms::protection_method_dummy_stops_form_entry',
			'duplicate_check' => 'forms::protection_method_duplicate_check',
			'hidden_input' => 'forms::protection_method_hidden_input',
			'ip_check' => 'forms::protection_method_ip_check',
		], $configOptions['Frontend.protection.methods']->getValues(true));
	}
}
