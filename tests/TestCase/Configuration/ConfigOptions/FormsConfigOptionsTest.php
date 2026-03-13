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
		$this->assertSame('Forms::identifier', $configOptions['Backend.overview.displayedFields']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Backend.overview.displayedFields']->getType());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getTypecast());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getValidate());
		$this->assertIsCallable($configOptions['Backend.overview.displayedFields']->getValues());
		$this->assertSame([
			'identifier' => 'Forms::identifier',
			'sendEmail' => 'Forms::send_email',
			'emailTemplateId' => 'Forms::email_template_id',
			'sendConfirmationEmail' => 'Forms::send_confirmation_email',
			'confirmationEmailTemplateId' => 'Forms::confirmation_email_template_id',
			'ownerEmail' => 'Forms::owner_email',
			'ownerName' => 'Forms::owner_name',
			'userEmail' => 'Forms::user_email',
			'userName' => 'Forms::user_name',
			'cc' => 'Forms::cc',
			'bcc' => 'Forms::bcc',
			'subject' => 'Forms::subject',
			'subjectConfirmation' => 'Forms::subject_confirmation',
			'salutation' => 'Forms::salutation',
			'salutationConfirmation' => 'Forms::salutation_confirmation',
			'summarizeErrors' => 'Forms::summarize_errors',
			'successMessage' => 'Forms::success_message',
			'multistep' => 'Forms::multistep',
			'conditionalRecipientsStrategy' => 'Forms::conditional_recipients_strategy',
			'transportProfile' => 'Forms::transport_profile',
			'active' => 'Forms::active',
			'createdBy' => 'Forms::created_by',
			'createdOn' => 'Forms::created_on',
			'changedBy' => 'Forms::changed_by',
			'changedOn' => 'Forms::changed_on',
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
			'duplicateCheck',
			'ipCheck',
			'hiddenInput',
		], $configOptions['Frontend.protection.methods']->getDefaultValue());
		$this->assertSame('Forms::protection_method_altcha, Forms::protection_method_duplicate_check, Forms::protection_method_ip_check, Forms::protection_method_hidden_input', $configOptions['Frontend.protection.methods']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Frontend.protection.methods']->getType());
		$this->assertNull($configOptions['Frontend.protection.methods']->getTypecast());
		$this->assertNull($configOptions['Frontend.protection.methods']->getValidate());
		$this->assertIsCallable($configOptions['Frontend.protection.methods']->getValues());
		$this->assertSame([
			'altcha' => 'Forms::protection_method_altcha',
			'dummy' => 'Forms::protection_method_dummy',
			'dummyStopsFormEntry' => 'Forms::protection_method_dummy_stops_form_entry',
			'duplicateCheck' => 'Forms::protection_method_duplicate_check',
			'hiddenInput' => 'Forms::protection_method_hidden_input',
			'ipCheck' => 'Forms::protection_method_ip_check',
		], $configOptions['Frontend.protection.methods']->getValues(true));
	}
}
