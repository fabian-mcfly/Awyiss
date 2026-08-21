<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Content\Typography;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\Typography\Rule\ApostropheRule;
use Awyiss\Utility\Content\Typography\Rule\DashRule;
use Awyiss\Utility\Content\Typography\Rule\CallableProxyRule;
use Awyiss\Utility\Content\Typography\TypographyRuleInterface;
use Awyiss\Utility\Content\Typography\TypographyRuleRegistry;
use PHPUnit\Framework\Attributes\DataProvider;


/**
 * Tests for TypographyRuleRegistry
 *
 * @covers \Awyiss\Utility\Content\Typography\TypographyRuleRegistry
 */
class TypographyRuleRegistryTest extends TestCase {
	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();
		TypographyRuleRegistry::reset();
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		TypographyRuleRegistry::reset();
		parent::tearDown();
	}


	/**
	 * @covers \Awyiss\Utility\Content\Typography\TypographyRuleRegistry::register
	 * @covers \Awyiss\Utility\Content\Typography\Rule\CallableProxyRule::apply
	 */
	public function testRegisterWrapsCallableInProxyRule(): void {
		TypographyRuleRegistry::register('xx', static fn(string $text): string => strtoupper($text));

		$rules = TypographyRuleRegistry::getRulesForLanguage('xx');

		$this->assertCount(1, $rules);
		$this->assertInstanceOf(CallableProxyRule::class, $rules[0]);
		$this->assertSame('HELLO', $rules[0]->apply('hello'));
	}


	/**
	 * Provides non-callable rule instances.
	 *
	 * @return array<string, array{0: \Awyiss\Utility\Content\Typography\TypographyRuleInterface}>
	 */
	public static function nonCallableRuleProvider(): array {
		return [
			'concrete_apostrophe_rule' => [new ApostropheRule()],
			'concrete_dash_rule' => [new DashRule('–')],
			'anonymous_rule' => [new class implements TypographyRuleInterface {
				/**
				 * @param string $text
				 * @return string
				 */
				public function apply(string $text): string {
					return '[' . $text . ']';
				}
			}],
		];
	}


	/**
	 * @covers \Awyiss\Utility\Content\Typography\TypographyRuleRegistry::register
	 */
	#[DataProvider('nonCallableRuleProvider')]
	public function testRegisterKeepsProvidedNonCallableRuleInstance(TypographyRuleInterface $rule): void {
		TypographyRuleRegistry::register('yy', $rule);

		$rules = TypographyRuleRegistry::getRulesForLanguage('yy');

		$this->assertCount(1, $rules);
		$this->assertNotInstanceOf(CallableProxyRule::class, $rules[0]);
		$this->assertSame($rule, $rules[0]);
	}


	/**
	 * @covers \Awyiss\Utility\Content\Typography\TypographyRuleRegistry::register
	 */
	public function testRegisterKeepsNonCallableRuleOrder(): void {
		$firstRule = new ApostropheRule();
		$secondRule = new DashRule('–');

		TypographyRuleRegistry::register('zz', $firstRule);
		TypographyRuleRegistry::register('zz', $secondRule);

		$rules = TypographyRuleRegistry::getRulesForLanguage('zz');

		$this->assertCount(2, $rules);
		$this->assertNotInstanceOf(CallableProxyRule::class, $rules[0]);
		$this->assertNotInstanceOf(CallableProxyRule::class, $rules[1]);
		$this->assertSame($firstRule, $rules[0]);
		$this->assertSame($secondRule, $rules[1]);
	}


	/**
	 * @covers \Awyiss\Utility\Content\Typography\TypographyRuleRegistry::register
	 */
	public function testRegisterKeepsNonCallableRulesSeparatedByLanguage(): void {
		$deRule = new ApostropheRule();
		$frRule = new DashRule('–');

		TypographyRuleRegistry::register('de', $deRule);
		TypographyRuleRegistry::register('fr', $frRule);

		$deRules = TypographyRuleRegistry::getRulesForLanguage('de');
		$frRules = TypographyRuleRegistry::getRulesForLanguage('fr');

		$this->assertCount(1, $deRules);
		$this->assertCount(1, $frRules);
		$this->assertNotInstanceOf(CallableProxyRule::class, $deRules[0]);
		$this->assertNotInstanceOf(CallableProxyRule::class, $frRules[0]);
		$this->assertSame($deRule, $deRules[0]);
		$this->assertSame($frRule, $frRules[0]);
	}


	/**
	 * @covers \Awyiss\Utility\Content\Typography\TypographyRuleRegistry::clear
	 */
	public function testClearRemovesOnlyRulesOfGivenLanguage(): void {
		$deRule = new ApostropheRule();
		$frRule = new DashRule('–');

		TypographyRuleRegistry::register('de', $deRule);
		TypographyRuleRegistry::register('fr', $frRule);
		TypographyRuleRegistry::clear('de');

		$deRules = TypographyRuleRegistry::getRulesForLanguage('de');
		$frRules = TypographyRuleRegistry::getRulesForLanguage('fr');

		$this->assertSame([], $deRules);
		$this->assertCount(1, $frRules);
		$this->assertSame($frRule, $frRules[0]);
	}


	/**
	 * @covers \Awyiss\Utility\Content\Typography\TypographyRuleRegistry::reset
	 */
	public function testResetClearsAllRegisteredRules(): void {
		TypographyRuleRegistry::register('de', new ApostropheRule());
		TypographyRuleRegistry::register('fr', new DashRule('–'));

		TypographyRuleRegistry::reset();

		$this->assertSame([], TypographyRuleRegistry::getRulesForLanguage('de'));
		$this->assertSame([], TypographyRuleRegistry::getRulesForLanguage('fr'));
	}


	/**
	 * @covers \Awyiss\Utility\Content\Typography\TypographyRuleRegistry::registerDefaults
	 */
	public function testRegisterDefaultsRegistersRulesAndIsIdempotent(): void {
		$languages = ['de', 'en', 'fr', 'es', 'it'];

		TypographyRuleRegistry::registerDefaults();

		$countsAfterFirstCall = [];

		foreach ($languages as $language) {
			$rules = TypographyRuleRegistry::getRulesForLanguage($language);
			$this->assertNotEmpty($rules);
			$countsAfterFirstCall[ $language ] = count($rules);
		}

		TypographyRuleRegistry::registerDefaults();

		foreach ($languages as $language) {
			$this->assertCount($countsAfterFirstCall[ $language ], TypographyRuleRegistry::getRulesForLanguage($language));
		}
	}


	/**
	 * @covers \Awyiss\Utility\Content\Typography\TypographyRuleRegistry::reset
	 * @covers \Awyiss\Utility\Content\Typography\TypographyRuleRegistry::registerDefaults
	 */
	public function testResetAllowsDefaultsToBeRegisteredAgain(): void {
		TypographyRuleRegistry::registerDefaults();
		$initialCount = count(TypographyRuleRegistry::getRulesForLanguage('en'));

		TypographyRuleRegistry::reset();
		$this->assertSame([], TypographyRuleRegistry::getRulesForLanguage('en'));

		TypographyRuleRegistry::registerDefaults();
		$this->assertCount($initialCount, TypographyRuleRegistry::getRulesForLanguage('en'));
	}
}

