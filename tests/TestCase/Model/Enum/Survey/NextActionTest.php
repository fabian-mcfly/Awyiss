<?php


/**
 * @noinspection PhpMultipleClassDeclarationsInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Test\TestCase\Model\Enum\Survey;


use Awyiss\Model\Enum\Survey\NextAction;
use Awyiss\Test\TestSuite\TestCase;
use ValueError;


/**
 * NextAction Test Case
 *
 * @see \Awyiss\Model\Enum\Survey\NextAction
 */
class NextActionTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\Survey\NextAction
	 */
	public function testEnumCases(): void {
		$this->assertEquals('nextQuestion', NextAction::NextQuestion->value);
		$this->assertEquals('specificQuestion', NextAction::SpecificQuestion->value);
		$this->assertEquals('saveAndEnd', NextAction::SaveAndEnd->value);
		$this->assertEquals('showForm', NextAction::ShowForm->value);
		$this->assertEquals('saveAndShowForm', NextAction::SaveAndShowForm->value);
		$this->assertEquals('showFormAndSave', NextAction::ShowFormAndSave->value);
		$this->assertEquals('abort', NextAction::Abort->value);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\Survey\NextAction
	 */
	public function testEnumFromMethod(): void {
		$this->assertEquals(NextAction::NextQuestion, NextAction::from('nextQuestion'));
		$this->assertEquals(NextAction::SpecificQuestion, NextAction::from('specificQuestion'));
		$this->assertEquals(NextAction::SaveAndEnd, NextAction::from('saveAndEnd'));
		$this->assertEquals(NextAction::ShowForm, NextAction::from('showForm'));
		$this->assertEquals(NextAction::SaveAndShowForm, NextAction::from('saveAndShowForm'));
		$this->assertEquals(NextAction::ShowFormAndSave, NextAction::from('showFormAndSave'));
		$this->assertEquals(NextAction::Abort, NextAction::from('abort'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\Survey\NextAction
	 * @throws \ValueError
	 */
	public function testEnumFromMethodThrowsExceptionForInvalidValue(): void {
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('"invalid" is not a valid backing value for enum Awyiss\Model\Enum\Survey\NextAction');

		/** @noinspection PhpCaseWithValueNotFoundInEnumInspection, PhpExpressionResultUnusedInspection */
		NextAction::from('invalid');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\Survey\NextAction
	 */
	public function testEnumTryFromMethodValid(): void {
		$this->assertEquals(NextAction::NextQuestion, NextAction::tryFrom('nextQuestion'));
		$this->assertEquals(NextAction::SpecificQuestion, NextAction::tryFrom('specificQuestion'));
		$this->assertEquals(NextAction::SaveAndEnd, NextAction::tryFrom('saveAndEnd'));
		$this->assertEquals(NextAction::ShowForm, NextAction::tryFrom('showForm'));
		$this->assertEquals(NextAction::SaveAndShowForm, NextAction::tryFrom('saveAndShowForm'));
		$this->assertEquals(NextAction::ShowFormAndSave, NextAction::tryFrom('showFormAndSave'));
		$this->assertEquals(NextAction::Abort, NextAction::tryFrom('abort'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\Survey\NextAction
	 * @noinspection PhpCaseWithValueNotFoundInEnumInspection
	 */
	public function testEnumTryFromMethodInvalid(): void {
		$this->assertNull(NextAction::tryFrom('invalid'));
		$this->assertNull(NextAction::tryFrom(''));
		$this->assertNull(NextAction::tryFrom('NextQuestion'));
		$this->assertNull(NextAction::tryFrom('next-question'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\Survey\NextAction::label()
	 */
	public function testLabel(): void {
		$label = NextAction::NextQuestion->label();
		$this->assertIsString($label);
		$this->assertSame('surveys::next_action_next_question', $label);

		$label = NextAction::SaveAndEnd->label();
		$this->assertIsString($label);
		$this->assertSame('surveys::next_action_save_and_end', $label);

		$label = NextAction::Abort->label();
		$this->assertIsString($label);
		$this->assertSame('surveys::next_action_abort', $label);
	}
}
