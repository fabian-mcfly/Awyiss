<?php


/**
 * @noinspection PhpMultipleClassDeclarationsInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Test\TestCase\Model\Enum\Survey;


use Awyiss\Model\Enum\Survey\QuestionType;
use Awyiss\Test\TestSuite\TestCase;
use ValueError;


/**
 * QuestionType Test Case
 *
 * @see \Awyiss\Model\Enum\Survey\QuestionType
 */
class QuestionTypeTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\Survey\QuestionType
	 */
	public function testEnumCases(): void {
		$this->assertEquals('singleChoice', QuestionType::SingleChoice->value);
		$this->assertEquals('multipleChoice', QuestionType::MultiChoice->value);
		$this->assertEquals('freeText', QuestionType::FreeText->value);
		$this->assertEquals('infoText', QuestionType::InfoText->value);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\Survey\QuestionType
	 */
	public function testEnumFromMethod(): void {
		$this->assertEquals(QuestionType::SingleChoice, QuestionType::from('singleChoice'));
		$this->assertEquals(QuestionType::MultiChoice, QuestionType::from('multipleChoice'));
		$this->assertEquals(QuestionType::FreeText, QuestionType::from('freeText'));
		$this->assertEquals(QuestionType::InfoText, QuestionType::from('infoText'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\Survey\QuestionType
	 * @throws \ValueError
	 */
	public function testEnumFromMethodThrowsExceptionForInvalidValue(): void {
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('"invalid" is not a valid backing value for enum Awyiss\Model\Enum\Survey\QuestionType');

		/** @noinspection PhpCaseWithValueNotFoundInEnumInspection, PhpExpressionResultUnusedInspection */
		QuestionType::from('invalid');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\Survey\QuestionType
	 */
	public function testEnumTryFromMethodValid(): void {
		$this->assertEquals(QuestionType::SingleChoice, QuestionType::tryFrom('singleChoice'));
		$this->assertEquals(QuestionType::MultiChoice, QuestionType::tryFrom('multipleChoice'));
		$this->assertEquals(QuestionType::FreeText, QuestionType::tryFrom('freeText'));
		$this->assertEquals(QuestionType::InfoText, QuestionType::tryFrom('infoText'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\Survey\QuestionType
	 * @noinspection PhpCaseWithValueNotFoundInEnumInspection
	 */
	public function testEnumTryFromMethodInvalid(): void {
		$this->assertNull(QuestionType::tryFrom('invalid'));
		$this->assertNull(QuestionType::tryFrom(''));
		$this->assertNull(QuestionType::tryFrom('SingleChoice'));
		$this->assertNull(QuestionType::tryFrom('single-choice'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\Survey\QuestionType::label()
	 */
	public function testLabel(): void {
		$label = QuestionType::SingleChoice->label();
		$this->assertIsString($label);
		$this->assertSame('SurveyQuestions::question_type_single_choice', $label);

		$label = QuestionType::MultiChoice->label();
		$this->assertIsString($label);
		$this->assertSame('SurveyQuestions::question_type_multiple_choice', $label);

		$label = QuestionType::FreeText->label();
		$this->assertIsString($label);
		$this->assertSame('SurveyQuestions::question_type_free_text', $label);

		$label = QuestionType::InfoText->label();
		$this->assertIsString($label);
		$this->assertSame('SurveyQuestions::question_type_info_text', $label);
	}
}
