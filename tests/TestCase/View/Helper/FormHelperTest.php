<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Helper;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Awyiss\View\Helper\FormHelper;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\View\Form\EntityContext;
use ReflectionClass;
use RuntimeException;


/**
 * FormHelperTest class
 */
class FormHelperTest extends TestCase {
	/**
	 * @var \Awyiss\View\Helper\FormHelper
	 */
	protected FormHelper $formHelper;


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public static function setUpBeforeClass(): void {
		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);

		$lo_language = LocaleMiddleware::getLanguage(Awyiss::REALM_BACKEND);

		$lo_tableLocator = FactoryLocator::get('Table');
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$lo_tableLocator->setTranslateLanguage($lo_language);
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public static function tearDownAfterClass(): void {
		$reflection = new ReflectionClass(BackendView::class);
		$property = $reflection->getProperty('twig');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(null);

		$property = $reflection->getProperty('twigInitialized');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(false);

		$lo_tableLocator = FactoryLocator::get('Table');
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$lo_tableLocator->setTranslateLanguage(null);
	}


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		Awyiss::setRealm('Backend');

		// Clear the table locator to ensure a fresh instance
		$this->getTableLocator()->clear();

		$this->formHelper = new FormHelper(new BackendView(), [
			'autoSetCustomValidity' => false,
			'errorClass' => 'Error',
			'templates' => 'form_templates_backend',
		]);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDomId(): void {
		$result = $this->formHelper->label('username');
		$this->assertStringContainsString('for="Username"', $result);

		$result = $this->formHelper->label('userName');
		$this->assertStringContainsString('for="UserName"', $result);

		$result = $this->formHelper->label('user_name');
		$this->assertStringContainsString('for="UserName"', $result);

		$result = $this->formHelper->label('user.name');
		$this->assertStringContainsString('for="User-Name"', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelWithProvidedText(): void {
		$result = $this->formHelper->label('username', 'User Name');

		$this->assertSame('<label class="Label" for="Username">User Name</label>', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelWithoutProvidedText(): void {
		$result = $this->formHelper->label('username');

		$this->assertSame('<label class="Label" for="Username">username</label>', $result);

		$result = $this->formHelper->label('usergroups._ids');

		$this->assertSame('<label class="Label" for="Usergroups-Ids">usergroups</label>', $result);

		$result = $this->formHelper->label('usergroups.title');

		$this->assertSame('<label class="Label" for="Usergroups-Title">title</label>', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelWithOptions(): void {
		$options = ['class' => 'custom-class'];

		$result = $this->formHelper->label('username', 'User Name', $options);

		$this->assertSame('<label class="Label custom-class" for="Username">User Name</label>', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSelectReturnsSelectWithEmptyOption(): void {
		$result = $this->formHelper->select('category', ['Option 1', 'Option 2']);

		$this->assertStringContainsString('<select name="category"', $result);
		$this->assertStringContainsString('<option value=""', $result);
		$this->assertStringContainsString('<option value="0" title="Option 1">Option 1</option>', $result);
		$this->assertStringContainsString('<option value="1" title="Option 2">Option 2</option>', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSelectWithEmptyOptionDisabled(): void {
		$attributes = ['empty' => false];

		$result = $this->formHelper->select('category', ['Option 1', 'Option 2'], $attributes);

		$this->assertStringContainsString('<select', $result);
		$this->assertStringNotContainsString('<option value=""', $result);
		$this->assertStringContainsString('<option value="0" title="Option 1">Option 1</option>', $result);
		$this->assertStringContainsString('<option value="1" title="Option 2">Option 2</option>', $result);

		$attributes = ['empty' => null];

		$result = $this->formHelper->select('category', ['Option 1', 'Option 2'], $attributes);

		$this->assertStringContainsString('<select', $result);
		$this->assertStringNotContainsString('<option value=""', $result);
		$this->assertStringContainsString('<option value="0" title="Option 1">Option 1</option>', $result);
		$this->assertStringContainsString('<option value="1" title="Option 2">Option 2</option>', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSelectWithCustomOptionTitle(): void {
		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->fetchTable('ContentTemplates')->get(2);

		$this->formHelper->create($entity);

		$result = $this->formHelper->select('category', [
			[
				'text' => 'Option 1',
				'title' => 'Option 1',
				'value' => 1,
			],
			[
				'text' => 'Option 2',
				'title' => 'Custom-Option 2',
				'value' => 2,
			],
		]);

		$this->assertStringContainsString('<select name="category"', $result);
		$this->assertStringContainsString('<option value=""', $result);
		$this->assertStringContainsString('<option value="1" title="Option 1">Option 1</option>', $result);
		$this->assertStringContainsString('<option value="2" title="Custom-Option 2">Option 2</option>', $result);
	}

	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslatableText(): void {
		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->fetchTable('ContentTemplates')->get(2);

		$this->formHelper->create($entity);

		$result = $this->formHelper->translatableText('title');

		$this->assertStringContainsString('<div class="TranslatableTexts"', $result);
		$this->assertStringContainsString('<input type="text" name="_translations[de][title]"', $result);
		$this->assertStringContainsString('<input type="text" name="_translations[en][title]"', $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testTranslatableTextMarksOnlyFirstAsRequired(): void {
		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->fetchTable('ContentTemplates')->get(2);

		$this->formHelper->create($entity);

		$result = $this->formHelper->translatableText('title', ['required' => true]);

		$this->assertStringContainsString('id="Title-Translations[de]" placeholder="Inhaltsblock" required="required" value="', $result);
		$this->assertStringContainsString('id="Title-Translations[en]" placeholder="Inhaltsblock" value="', $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testTranslatableTextThrowsExceptionWhithoutCreateCall(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The language realm is not set.');

		$this->formHelper->translatableText('title');
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testTranslatableTextReturnsRegularInputWhenFieldNotTranslatable(): void {
		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->fetchTable('ContentTemplates')->get(2);

		$this->formHelper->create($entity);

		$result = $this->formHelper->translatableText('path');

		$this->assertStringNotContainsString('<div class="TranslatableTexts"', $result);
		$this->assertStringNotContainsString('name="_translations', $result);
		$this->assertStringContainsString('<input type="text" name="path"', $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testTranslatableTextReturnsRegularInputWhenRealmHasLessThanTwoLanguages(): void {
		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->fetchTable('ContentTemplates')->get(2);

		$this->formHelper->create($entity, ['languageRealm' => 'Dummy']);

		/** @var \Awyiss\Model\Table\LanguagesTable $languagesTable */
		$languagesTable = $this->fetchTable('Languages');
		$esperanto = $languagesTable->find('all')->where(['shortcode' => 'es'])->first();
		$esperanto->set('active', false);
		$languagesTable->save($esperanto, ['audit' => ['skip' => true]]);

		$result = $this->formHelper->translatableText('title');

		$esperanto->set('active', true);
		$languagesTable->save($esperanto, ['audit' => ['skip' => true]]);

		$this->assertStringNotContainsString('<div class="TranslatableTexts"', $result);
		$this->assertStringNotContainsString('name="_translations', $result);
		$this->assertStringContainsString('<input type="text" name="title"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlAddsColumnSpanWhenProvided(): void {
		$result = $this->formHelper->control('username', ['columnSpan' => 6]);

		$this->assertStringContainsString('ColumnSpan-6', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlNotAddsColumnSpanWhenNotProvided(): void {
		$result = $this->formHelper->control('username', ['columnSpan' => null]);

		$this->assertStringNotContainsString('ColumnSpan', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlCreatesTranslatableTextForTranslatableField(): void {
		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->fetchTable('ContentTemplates')->get(2);

		$this->formHelper->create($entity);

		$result = $this->formHelper->control('title');

		$this->assertStringContainsString('<div class="TranslatableTexts"', $result);
		$this->assertStringContainsString('<input type="text" name="_translations[de][title]"', $result);
		$this->assertStringContainsString('<input type="text" name="_translations[en][title]"', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlAddsTimezoneInfoWhenSet(): void {
		$result = $this->formHelper->control('created', ['type' => 'datetime', 'timezone' => 'America/New_York']);

		$this->assertStringContainsString('<span class="Timezone">', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlAddsTimezoneInfoWhenOmitted(): void {
		$result = $this->formHelper->control('created', ['type' => 'datetime']);

		$this->assertStringContainsString('<span class="Timezone">', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlSetsCorrectDateTimeValueForTimezone(): void {
		$now = new DateTime('now');
		$result = $this->formHelper->control('created', ['type' => 'datetime', 'val' => $now, 'timezone' => 'America/New_York']);

		$this->assertStringContainsString('value="' . $now->setTimezone('America/New_York')->format('Y-m-d\TH:i:s') . '"', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRemoveClassRemovesClassFromArray(): void {
		$options = ['foo' => 'bar', 'class' => ['class1', 'class2', 'class3']];

		$result = $this->formHelper->removeClass($options, 'class2');

		$this->assertEquals(['foo' => 'bar', 'class' => ['class1', 'class3']], $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRemoveClassRemovesClassFromString(): void {
		$options = ['foo' => 'bar', 'class' => 'class1 class2 class3'];

		$result = $this->formHelper->removeClass($options, 'class2');

		$this->assertEquals(['foo' => 'bar', 'class' => 'class1 class3'], $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testRemoveClassRemovesClassWhenOnlyOneClassInString(): void {
		$options = ['foo' => 'bar', 'class' => 'class1'];
		$result = $this->formHelper->removeClass($options, 'class1');
		$this->assertEquals(['foo' => 'bar'], $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testRemoveClassDoesNotRemoveClassIfNotPresent(): void {
		$options = ['foo' => 'bar', 'class' => 'class1 class2 class3'];
		$result = $this->formHelper->removeClass($options, 'class4');
		$this->assertEquals(['foo' => 'bar', 'class' => 'class1 class2 class3'], $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRemoveClassRemovesClassFromCustomKey(): void {
		$options = ['foo' => 'bar', 'customKey' => 'class1 class2 class3'];
		$result = $this->formHelper->removeClass($options, 'class2', 'customKey');
		$this->assertEquals(['foo' => 'bar', 'customKey' => 'class1 class3'], $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testRemoveClassRemovesClassFromEmptyString(): void {
		$options = ['foo' => 'bar', 'class' => ''];
		$result = $this->formHelper->removeClass($options, 'class1');
		$this->assertEquals(['foo' => 'bar'], $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetTranslatableField(): void {
		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->fetchTable('ContentTemplates')->get(2);

		$this->formHelper->create($entity);

		$result = $this->formHelper->translatableText('path');

		$this->assertStringNotContainsString('<div class="TranslatableTexts"', $result);
		$this->assertStringNotContainsString('name="_translations', $result);
		$this->assertStringContainsString('<input type="text" name="path"', $result);

		$this->formHelper->setTranslatableField('path');

		$result = $this->formHelper->translatableText('path');

		$this->assertStringContainsString('<div class="TranslatableTexts"', $result);
		$this->assertStringContainsString('name="_translations', $result);
		$this->assertStringNotContainsString('<input type="text" name="path"', $result);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetTranslatableFieldWithoutMerge(): void {
		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->fetchTable('ContentTemplates')->get(2);

		$this->formHelper->create($entity);

		$result = $this->formHelper->translatableText('title');

		$this->assertStringContainsString('<div class="TranslatableTexts"', $result);
		$this->assertStringContainsString('name="_translations', $result);
		$this->assertStringNotContainsString('<input type="text" name="title"', $result);

		$this->formHelper->setTranslatableField('path', false);

		$result = $this->formHelper->translatableText('path');

		$this->assertStringContainsString('<div class="TranslatableTexts"', $result);
		$this->assertStringContainsString('name="_translations', $result);
		$this->assertStringNotContainsString('<input type="text" name="path"', $result);

		$result = $this->formHelper->translatableText('title');

		$this->assertStringNotContainsString('<div class="TranslatableTexts"', $result);
		$this->assertStringNotContainsString('name="_translations', $result);
		$this->assertStringContainsString('<input type="text" name="title"', $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsFieldError(): void {
		$context = $this->createMock(EntityContext::class);
		$context->method('hasError')->with('field')->willReturn(true);

		$this->formHelper->context($context);

		$this->assertTrue($this->formHelper->isFieldError('field'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsFieldErrorWithDot(): void {
		$entity = $this->createMock(Entity::class);
		$entity->method('get')->with('association')->willReturn($entity);
		$entity->method('getError')->with('field')->willReturn(['error']);

		$context = $this->createMock(EntityContext::class);
		$context->method('entity')->willReturn($entity);

		$this->formHelper->context($context);

		$this->assertTrue($this->formHelper->isFieldError('association.field'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testIsFieldErrorWithDotNoAssociatedEntity(): void {
		$entity = $this->createMock(Entity::class);
		$entity->method('get')->with('association')->willReturn(null);

		$context = $this->createMock(EntityContext::class);
		$context->method('entity')->willReturn($entity);

		$this->formHelper->context($context);

		$this->assertFalse($this->formHelper->isFieldError('association.field'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testIsFieldErrorWithDotNoErrorInAssociatedEntity(): void {
		$entity = $this->createMock(Entity::class);
		$entity->method('get')->with('association')->willReturn($entity);
		$entity->method('getError')->with('field')->willReturn([]);

		$context = $this->createMock(EntityContext::class);
		$context->method('entity')->willReturn($entity);
		$this->formHelper->context($context);

		$this->assertFalse($this->formHelper->isFieldError('association.field'));
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testInputContainerTemplateContainsCorrectClasses(): void {
		$result = $this->formHelper->control('path', [
			'required' => true,
			'type' => 'weird_input',
			'id' => 'Foo-Bar',
		]);

		$this->assertStringContainsString('FormInputType-WeirdInput', $result);
		$this->assertStringContainsString('FormInputName-Bar Required', $result);
		$this->assertStringNotContainsString('FormInputName-Bar required', $result);
		$result = $this->formHelper->control('path', [
			'required' => true,
			'type' => 'weird_input',
			'id' => 'FooBar',
		]);

		$this->assertStringContainsString('FormInputType-WeirdInput', $result);
		$this->assertStringContainsString('FormInputName-FooBar Required', $result);
		$this->assertStringNotContainsString('FormInputName-FooBar required', $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testErrorWithError(): void {
		$context = $this->createMock(EntityContext::class);
		$context->method('hasError')->with('field')->willReturn(true);
		$context->method('error')->with('field')->willReturn(['Error message']);
		$this->formHelper->context($context);

		$this->assertEquals('<div class="Error">Error message</div>', $this->formHelper->error('field'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testErrorWithErrorWithMultipleErrorMessage(): void {
		$context = $this->createMock(EntityContext::class);
		$context->method('hasError')->with('field')->willReturn(true);
		$context->method('error')->with('field')->willReturn([
			'Error message key' => 'Error message value',
			'Error message key 2' => 'Error message value 2',
		]);
		$this->formHelper->context($context);

		$this->assertEquals(
			'<div class="Error"><ul class="ErrorMessages"><li class="ErrorMessage">Error message value</li><li class="ErrorMessage">Error message value 2</li></ul></div>',
			$this->formHelper->error('field')
		);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testErrorWithErrorWithNestedErrorMessage(): void {
		$context = $this->createMock(EntityContext::class);
		$context->method('hasError')->with('field')->willReturn(true);
		$context->method('error')->with('field')->willReturn(['Error message' => [
			'Error Field' => 'Nested error message',
		]]);
		$this->formHelper->context($context);

		$this->assertEquals('<div class="Error">Nested error message</div>', $this->formHelper->error('field'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testErrorWithErrorWithMultipleNestedErrorMessage(): void {
		$context = $this->createMock(EntityContext::class);
		$context->method('hasError')->with('field')->willReturn(true);
		$context->method('error')->with('field')->willReturn(['Error message' => [
			'Error Field' => 'Nested error message',
			'Error Field 2' => 'Nested error message 2',
		]]);
		$this->formHelper->context($context);

		$this->assertEquals('<div class="Error">Nested error messageNested error message 2</div>', $this->formHelper->error('field'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testErrorWithoutError(): void {
		$context = $this->createMock(EntityContext::class);
		$context->method('hasError')->with('field')->willReturn(false);
		$this->formHelper->context($context);

		$this->assertEquals('', $this->formHelper->error('field'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testErrorWithDotWithError(): void {
		$entity = $this->createMock(Entity::class);
		$entity->method('get')->with('association')->willReturn($entity);
		$entity->method('getError')->with('field')->willReturn(['Error message']);

		$context = $this->createMock(EntityContext::class);
		$context->method('entity')->willReturn($entity);
		$this->formHelper->context($context);

		$this->assertEquals('<div class="Error">Error message</div>', $this->formHelper->error('association.field'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testErrorWithDotWithoutError(): void {
		$entity = $this->createMock(Entity::class);
		$entity->method('get')->with('association')->willReturn($entity);
		$entity->method('getError')->with('field')->willReturn([]);

		$context = $this->createMock(EntityContext::class);
		$context->method('entity')->willReturn($entity);
		$this->formHelper->context($context);

		$this->assertEquals('', $this->formHelper->error('association.field'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testErrorWithDotNoAssociatedEntity(): void {
		$entity = $this->createMock(Entity::class);
		$entity->method('get')->with('association')->willReturn(null);

		$context = $this->createMock(EntityContext::class);
		$context->method('entity')->willReturn($entity);
		$this->formHelper->context($context);

		$this->assertEquals('', $this->formHelper->error('association.field'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCustomErrorTextMapping(): void {
		$context = $this->createMock(EntityContext::class);
		$context->method('hasError')->with('field')->willReturn(true);
		$context->method('error')->with('field')->willReturn(['Error message']);
		$this->formHelper->context($context);

		$customText = ['Error message' => 'Custom error message'];
		$this->assertEquals('<div class="Error">Custom error message</div>', $this->formHelper->error('field', $customText));
	}
}
