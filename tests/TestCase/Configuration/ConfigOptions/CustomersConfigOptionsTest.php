<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration\ConfigOptions;


use Awyiss\Configuration\ConfigOptions\CustomersConfigOptions;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Test\TestSuite\TestCase;


/**
 * CustomersConfigOptions Test Case
 *
 * @see \Awyiss\Configuration\ConfigOptions\CustomersConfigOptions
 */
class CustomersConfigOptionsTest extends TestCase {
	use FlattenConfigOptionsTrait;


	/**
	 * @var \Awyiss\Configuration\ConfigOptions\CustomersConfigOptions
	 */
	protected CustomersConfigOptions $configOptions;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configOptions = new CustomersConfigOptions();
	}


	/**
	 * @return void
	 * @see \Awyiss\Configuration\ConfigOptions\CustomersConfigOptions::initializeConfigOptions()
	 */
	public function testInitializeConfigOptions(): void {
		$configOptions = $this->flattenConfigOptions($this->configOptions->getConfigOptions());

		$this->assertCount(16, $configOptions);

		$this->assertArrayHasKey('Backend.overview.displayedFields', $configOptions);
		$this->assertFalse($configOptions['Backend.overview.displayedFields']->isLocalizable());
		$this->assertTrue($configOptions['Backend.overview.displayedFields']->isNullable());
		$this->assertTrue($configOptions['Backend.overview.displayedFields']->isPersonalizable());
		$this->assertSame([
			'lastLogin',
		], $configOptions['Backend.overview.displayedFields']->getDefaultValue());
		$this->assertSame('Customers::last_login', $configOptions['Backend.overview.displayedFields']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Backend.overview.displayedFields']->getType());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getTypecast());
		$this->assertNull($configOptions['Backend.overview.displayedFields']->getValidate());
		$this->assertIsCallable($configOptions['Backend.overview.displayedFields']->getValues());
		$this->assertSame([
			'email' => 'Customers::email',
			'firstname' => 'Customers::firstname',
			'lastname' => 'Customers::lastname',
			'lastLogin' => 'Customers::last_login',
			'failedAttempts' => 'Customers::failed_attempts',
			'verified' => 'Customers::verified',
			'verifiedOn' => 'Customers::verified_on',
			'verificationCode' => 'Customers::verification_code',
			'passwordResetCode' => 'Customers::password_reset_code',
			'passwordResetOn' => 'Customers::password_reset_on',
			'active' => 'Customers::active',
			'createdBy' => 'Customers::created_by',
			'createdOn' => 'Customers::created_on',
			'changedBy' => 'Customers::changed_by',
			'changedOn' => 'Customers::changed_on',
		], $configOptions['Backend.overview.displayedFields']->getValues(true));

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

		$this->assertArrayHasKey('Frontend.emails.senderName', $configOptions);
		$this->assertTrue($configOptions['Frontend.emails.senderName']->isLocalizable());
		$this->assertTrue($configOptions['Frontend.emails.senderName']->isNullable());
		$this->assertFalse($configOptions['Frontend.emails.senderName']->isPersonalizable());
		$this->assertNull($configOptions['Frontend.emails.senderName']->getDefaultValue());
		$this->assertNull($configOptions['Frontend.emails.senderName']->getPrintableValue());
		$this->assertSame(ConfigOptionType::String, $configOptions['Frontend.emails.senderName']->getType());
		$this->assertNull($configOptions['Frontend.emails.senderName']->getTypecast());
		$this->assertNull($configOptions['Frontend.emails.senderName']->getValidate());
		$this->assertNull($configOptions['Frontend.emails.senderName']->getValues());

		$this->assertArrayHasKey('Frontend.emails.senderEmail', $configOptions);
		$this->assertTrue($configOptions['Frontend.emails.senderEmail']->isLocalizable());
		$this->assertTrue($configOptions['Frontend.emails.senderEmail']->isNullable());
		$this->assertFalse($configOptions['Frontend.emails.senderEmail']->isPersonalizable());
		$this->assertNull($configOptions['Frontend.emails.senderEmail']->getDefaultValue());
		$this->assertNull($configOptions['Frontend.emails.senderEmail']->getPrintableValue());
		$this->assertSame(ConfigOptionType::String, $configOptions['Frontend.emails.senderEmail']->getType());
		$this->assertNull($configOptions['Frontend.emails.senderEmail']->getTypecast());
		$this->assertIsCallable($configOptions['Frontend.emails.senderEmail']->getValidate());
		$this->assertNull($configOptions['Frontend.emails.senderEmail']->getValues());

		$this->assertArrayHasKey('Frontend.emails.transportProfile', $configOptions);
		$this->assertFalse($configOptions['Frontend.emails.transportProfile']->isLocalizable());
		$this->assertTrue($configOptions['Frontend.emails.transportProfile']->isNullable());
		$this->assertFalse($configOptions['Frontend.emails.transportProfile']->isPersonalizable());
		$this->assertNull($configOptions['Frontend.emails.transportProfile']->getDefaultValue());
		$this->assertNull($configOptions['Frontend.emails.transportProfile']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ListKey, $configOptions['Frontend.emails.transportProfile']->getType());
		$this->assertNull($configOptions['Frontend.emails.transportProfile']->getTypecast());
		$this->assertNull($configOptions['Frontend.emails.transportProfile']->getValidate());
		$this->assertIsCallable($configOptions['Frontend.emails.transportProfile']->getValues());

		$this->assertArrayHasKey('Frontend.login.enabled', $configOptions);
		$this->assertFalse($configOptions['Frontend.login.enabled']->isLocalizable());
		$this->assertFalse($configOptions['Frontend.login.enabled']->isNullable());
		$this->assertFalse($configOptions['Frontend.login.enabled']->isPersonalizable());
		$this->assertFalse($configOptions['Frontend.login.enabled']->getDefaultValue());
		$this->assertSame('false', $configOptions['Frontend.login.enabled']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Frontend.login.enabled']->getType());

		$this->assertArrayHasKey('Frontend.registration.enabled', $configOptions);
		$this->assertFalse($configOptions['Frontend.registration.enabled']->isLocalizable());
		$this->assertFalse($configOptions['Frontend.registration.enabled']->isNullable());
		$this->assertFalse($configOptions['Frontend.registration.enabled']->isPersonalizable());
		$this->assertFalse($configOptions['Frontend.registration.enabled']->getDefaultValue());
		$this->assertSame('false', $configOptions['Frontend.registration.enabled']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Frontend.registration.enabled']->getType());

		$this->assertArrayHasKey('Frontend.registration.requiresVerification', $configOptions);
		$this->assertFalse($configOptions['Frontend.registration.requiresVerification']->isLocalizable());
		$this->assertFalse($configOptions['Frontend.registration.requiresVerification']->isNullable());
		$this->assertFalse($configOptions['Frontend.registration.requiresVerification']->isPersonalizable());
		$this->assertTrue($configOptions['Frontend.registration.requiresVerification']->getDefaultValue());
		$this->assertSame('true', $configOptions['Frontend.registration.requiresVerification']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Frontend.registration.requiresVerification']->getType());

		$this->assertArrayHasKey('Frontend.registration.activeOnRegistration', $configOptions);
		$this->assertFalse($configOptions['Frontend.registration.activeOnRegistration']->isLocalizable());
		$this->assertFalse($configOptions['Frontend.registration.activeOnRegistration']->isNullable());
		$this->assertFalse($configOptions['Frontend.registration.activeOnRegistration']->isPersonalizable());
		$this->assertTrue($configOptions['Frontend.registration.activeOnRegistration']->getDefaultValue());
		$this->assertSame('true', $configOptions['Frontend.registration.activeOnRegistration']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Frontend.registration.activeOnRegistration']->getType());

		$this->assertArrayHasKey('Frontend.registration.verificationCodeValidity', $configOptions);
		$this->assertFalse($configOptions['Frontend.registration.verificationCodeValidity']->isLocalizable());
		$this->assertFalse($configOptions['Frontend.registration.verificationCodeValidity']->isNullable());
		$this->assertFalse($configOptions['Frontend.registration.verificationCodeValidity']->isPersonalizable());
		$this->assertSame(86400, $configOptions['Frontend.registration.verificationCodeValidity']->getDefaultValue());
		$this->assertSame(86400, $configOptions['Frontend.registration.verificationCodeValidity']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Integer, $configOptions['Frontend.registration.verificationCodeValidity']->getType());

		$this->assertArrayHasKey('Frontend.registration.defaultGroups', $configOptions);
		$this->assertFalse($configOptions['Frontend.registration.defaultGroups']->isLocalizable());
		$this->assertFalse($configOptions['Frontend.registration.defaultGroups']->isNullable());
		$this->assertFalse($configOptions['Frontend.registration.defaultGroups']->isPersonalizable());
		$this->assertSame([], $configOptions['Frontend.registration.defaultGroups']->getDefaultValue());
		$this->assertSame('', $configOptions['Frontend.registration.defaultGroups']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ValueCollection, $configOptions['Frontend.registration.defaultGroups']->getType());
		$this->assertNull($configOptions['Frontend.registration.defaultGroups']->getTypecast());
		$this->assertNull($configOptions['Frontend.registration.defaultGroups']->getValidate());
		$this->assertIsCallable($configOptions['Frontend.registration.defaultGroups']->getValues());
		$this->assertSame([
			3 => 'Basic',
			1 => 'Premium',
			2 => 'Standard',
		], $configOptions['Frontend.registration.defaultGroups']->getValues(true));

		$this->assertArrayHasKey('Frontend.registration.deleteUnverifiedAccounts', $configOptions);
		$this->assertFalse($configOptions['Frontend.registration.deleteUnverifiedAccounts']->isLocalizable());
		$this->assertFalse($configOptions['Frontend.registration.deleteUnverifiedAccounts']->isNullable());
		$this->assertFalse($configOptions['Frontend.registration.deleteUnverifiedAccounts']->isPersonalizable());
		$this->assertTrue($configOptions['Frontend.registration.deleteUnverifiedAccounts']->getDefaultValue());
		$this->assertSame('true', $configOptions['Frontend.registration.deleteUnverifiedAccounts']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Frontend.registration.deleteUnverifiedAccounts']->getType());

		$this->assertArrayHasKey('Frontend.passwordReset.enabled', $configOptions);
		$this->assertFalse($configOptions['Frontend.passwordReset.enabled']->isLocalizable());
		$this->assertFalse($configOptions['Frontend.passwordReset.enabled']->isNullable());
		$this->assertFalse($configOptions['Frontend.passwordReset.enabled']->isPersonalizable());
		$this->assertTrue($configOptions['Frontend.passwordReset.enabled']->getDefaultValue());
		$this->assertSame('true', $configOptions['Frontend.passwordReset.enabled']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Frontend.passwordReset.enabled']->getType());

		$this->assertArrayHasKey('Frontend.passwordReset.codeValidity', $configOptions);
		$this->assertFalse($configOptions['Frontend.passwordReset.codeValidity']->isLocalizable());
		$this->assertFalse($configOptions['Frontend.passwordReset.codeValidity']->isNullable());
		$this->assertFalse($configOptions['Frontend.passwordReset.codeValidity']->isPersonalizable());
		$this->assertSame(3600, $configOptions['Frontend.passwordReset.codeValidity']->getDefaultValue());
		$this->assertSame(3600, $configOptions['Frontend.passwordReset.codeValidity']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Integer, $configOptions['Frontend.passwordReset.codeValidity']->getType());
		$this->assertNull($configOptions['Frontend.passwordReset.codeValidity']->getTypecast());
		$this->assertNull($configOptions['Frontend.passwordReset.codeValidity']->getValidate());
		$this->assertNull($configOptions['Frontend.passwordReset.codeValidity']->getValues());

		$this->assertArrayHasKey('Frontend.navigation.menuIdentifier', $configOptions);
		$this->assertTrue($configOptions['Frontend.navigation.menuIdentifier']->isLocalizable());
		$this->assertTrue($configOptions['Frontend.navigation.menuIdentifier']->isNullable());
		$this->assertFalse($configOptions['Frontend.navigation.menuIdentifier']->isPersonalizable());
		$this->assertNull($configOptions['Frontend.navigation.menuIdentifier']->getDefaultValue());
		$this->assertNull($configOptions['Frontend.navigation.menuIdentifier']->getPrintableValue());
		$this->assertSame(ConfigOptionType::ListKey, $configOptions['Frontend.navigation.menuIdentifier']->getType());
		$this->assertNull($configOptions['Frontend.navigation.menuIdentifier']->getTypecast());
		$this->assertNull($configOptions['Frontend.navigation.menuIdentifier']->getValidate());
		$this->assertSame([
			'main' => 'Hauptmenü',
			'legal' => 'Rechtliches',
			'socialMedia' => 'Social Media',
		], $configOptions['Frontend.navigation.menuIdentifier']->getValues(true));

		$this->assertArrayHasKey('Frontend.profile.emailChangeAllowed', $configOptions);
		$this->assertFalse($configOptions['Frontend.profile.emailChangeAllowed']->isLocalizable());
		$this->assertFalse($configOptions['Frontend.profile.emailChangeAllowed']->isNullable());
		$this->assertFalse($configOptions['Frontend.profile.emailChangeAllowed']->isPersonalizable());
		$this->assertFalse($configOptions['Frontend.profile.emailChangeAllowed']->getDefaultValue());
		$this->assertSame('false', $configOptions['Frontend.profile.emailChangeAllowed']->getPrintableValue());
		$this->assertSame(ConfigOptionType::Bool, $configOptions['Frontend.profile.emailChangeAllowed']->getType());
	}
}
