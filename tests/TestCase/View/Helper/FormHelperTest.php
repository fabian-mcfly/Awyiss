<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Helper;


use Awyiss\Awyiss;
use Awyiss\Form\Protection\FormProtectionInterface;
use Awyiss\Model\Entity;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Awyiss\View\Helper\FormHelper;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\View\Form\EntityContext;
use DateTimeImmutable;
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
	 */
	protected function setUp(): void {
		parent::setUp();

		Awyiss::setRealm('Backend');

		// Clear the table locator to ensure a fresh instance
		$this->getTableLocator()->clear();

		$this->formHelper = new FormHelper(new BackendView(), [
			'autoSetCustomValidity' => false,
			'templates' => 'form_templates_backend',
		]);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::create()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateAddsLockAttributes() {
		$lockedUntil = new DateTimeImmutable('+1 day');
		$lockData = [
			'lockedUntil' => $lockedUntil,
			'isOwnLock' => false,
		];

		$result = $this->formHelper->create(null, ['lock' => $lockData, 'url' => '/foo']);
		$this->assertStringContainsString('data-locked-until="' . $lockedUntil->format('c') . '"', $result);
		$this->assertStringContainsString('data-locked="true"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::create()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateReplacesActionWithDataActionWhenNotOwnLock() {
		$lockedUntil = new DateTimeImmutable('+2 days');
		$lockData = [
			'lockedUntil' => $lockedUntil,
			'isOwnLock' => false,
		];

		$result = $this->formHelper->create(null, ['lock' => $lockData, 'url' => '/dummy']);
		$this->assertStringContainsString('data-action="/dummy"', $result);
		$this->assertStringNotContainsString(' action="/dummy"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::create()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateKeepsActionWhenOwnLock() {
		$lockedUntil = new DateTimeImmutable('+3 days');
		$lockData = [
			'lockedUntil' => $lockedUntil,
			'isOwnLock' => true,
		];

		$result = $this->formHelper->create(null, ['lock' => $lockData, 'url' => '/dummy']);
		$this->assertStringContainsString('action="/dummy"', $result);
		$this->assertStringNotContainsString('data-action="/dummy"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::create()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateReturnsFormWithoutLockAttributesWhenNoLock() {
		$result = $this->formHelper->create(null, ['url' => '/dummy']);
		$this->assertStringContainsString('<form', $result);
		$this->assertStringContainsString('action="/dummy"', $result);
		$this->assertStringNotContainsString('data-locked', $result);
		$this->assertStringNotContainsString('data-locked-until', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::_domId()
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
	 * @see \Awyiss\View\Helper\FormHelper::label()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelWithProvidedText(): void {
		$result = $this->formHelper->label('username', 'User Name');

		$this->assertSame('<label class="Label" for="Username">User Name</label>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::label()
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
	 * @see \Awyiss\View\Helper\FormHelper::label()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelAddsCustomClassAndTemplateVars(): void {
		$result = $this->formHelper->label('username', null, ['class' => 'foo', 'bar' => 'baz']);
		$this->assertStringContainsString('class="Label foo"', $result);
		$this->assertStringContainsString('bar="baz"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::label()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelHandlesEmptyClassOption(): void {
		$result = $this->formHelper->label('username', null, ['class' => '']);
		$this->assertStringContainsString('class="Label"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::labelTextFromFieldname()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelTextFromFieldnameHandlesMultipleDots(): void {
		$text = $this->formHelper->labelTextFromFieldname('user.profile.name');
		$this->assertSame('name', $text);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::labelTextFromFieldname()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelTextFromFieldnameHandlesIdsSuffix(): void {
		$text = $this->formHelper->labelTextFromFieldname('groups._ids');
		$this->assertSame('groups', $text);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::labelTextFromFieldname()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelTextFromFieldnameReturnsTranslatedString(): void {
		// Assuming __() returns the same string in test, but this checks the call path.
		$text = $this->formHelper->labelTextFromFieldname('email');
		$this->assertSame('email', $text);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::labelTextFromFieldname()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelWithOptions(): void {
		$options = ['class' => 'custom-class'];

		$result = $this->formHelper->label('username', 'User Name', $options);

		$this->assertSame('<label class="Label custom-class" for="Username">User Name</label>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::select()
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
	 * @see \Awyiss\View\Helper\FormHelper::select()
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
	 * @see \Awyiss\View\Helper\FormHelper::select()
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
	 * @see \Awyiss\View\Helper\FormHelper::select()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSelectCutsOffLongTitles(): void {
		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->fetchTable('ContentTemplates')->get(2);

		$this->formHelper->create($entity);

		$longText = trim(str_repeat('Long Option Text ', 10));

		$result = $this->formHelper->select('category', [
			[
				'text' => 'Option 1',
				'value' => 1,
			],
			[
				'text' => $longText,
				'value' => 2,
			],
			[
				'text' => 'Option 3',
				'value' => 3,
			],
		]);

		$this->assertStringContainsString('<select name="category"', $result);
		$this->assertStringContainsString('<option value=""', $result);
		$this->assertStringContainsString('<option value="1" title="Option 1">Option 1</option>', $result);
		$this->assertStringContainsString('<option value="2" title="' . $longText . '">' . mb_substr($longText, 0, 100) . '</option>', $result);
		$this->assertStringContainsString('<option value="3" title="Option 3">Option 3</option>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::select()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSelectCutsOffLongGroupTitles(): void {
		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->fetchTable('ContentTemplates')->get(2);

		$this->formHelper->create($entity);

		$longGroup = trim(str_repeat('Long Option Group Text ', 10));

		$result = $this->formHelper->select('category', [
			$longGroup => [
				'1' => 'Option 1',
				'2' => 'Option 2',
			],
			'Group 2' => [
				'3' => 'Option 3',
				'4' => 'Option 4',
			],
		]);

		$this->assertStringContainsString('<select name="category"', $result);
		$this->assertStringContainsString('<option value=""', $result);
		$this->assertStringContainsString('<optgroup label="' . mb_substr($longGroup, 0, 100) . '" title="' . $longGroup . '">', $result);
		$this->assertStringContainsString('<option value="1" title="Option 1">Option 1</option>', $result);
		$this->assertStringContainsString('<option value="2" title="Option 2">Option 2</option>', $result);
		$this->assertStringContainsString('<optgroup label="Group 2" title="Group 2">', $result);
		$this->assertStringContainsString('<option value="3" title="Option 3">Option 3</option>', $result);
		$this->assertStringContainsString('<option value="4" title="Option 4">Option 4</option>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::textarea()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTextareaWithoutEditorIgnoresInlineImageTags(): void {
		$table = $this->fetchTable('Contents');
		$entity = $table->findById(44)->first();

		$this->formHelper->create($entity);

		$result = $this->formHelper->textarea('text');

		$this->assertSame('<textarea name="text" rows="5">&lt;p&gt;&lt;awyiss-responsive-image&gt;{&quot;mediaId&quot;:&quot;2&quot;}&lt;/awyiss-responsive-image&gt;&lt;/p&gt;</textarea>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::textarea()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTextareaWithEditorRebuildsImageTags(): void {
		$table = $this->fetchTable('Contents');
		$entity = $table->findById(44)->find('mediaAssignments', includeElementSelector: true, useMediaEntity: true)->first();

		$this->formHelper->create($entity);

		$result = $this->formHelper->textarea('text', [
			'data-editor' => true,
		]);

		$this->assertSame('<textarea name="text" data-editor="1" rows="5">&lt;p&gt;&lt;img src=&quot;../awyiss/Command/Media/TestFiles/logo-awyiss.jpg&quot;&gt;&lt;/p&gt;</textarea>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::textarea()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTextareaWithEditorRemovesImageTagsIfNotFound(): void {
		$table = $this->fetchTable('Contents');
		$entity = $table->findById(44)->first();

		$this->formHelper->create($entity);

		$result = $this->formHelper->textarea('text', [
			'data-editor' => true,
		]);

		$this->assertSame('<textarea name="text" data-editor="1" rows="5">&lt;p&gt;&lt;/p&gt;</textarea>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::translatableText()
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
	 * @see \Awyiss\View\Helper\FormHelper::translatableText()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @see \Awyiss\View\Helper\FormHelper::translatableText()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslatableTextThrowsExceptionWhithoutCreateCall(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The language realm is not set.');

		$this->formHelper->translatableText('title');
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::translatableText()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @see \Awyiss\View\Helper\FormHelper::translatableText()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @see \Awyiss\View\Helper\FormHelper::control()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlAddsColumnSpanWhenProvided(): void {
		$result = $this->formHelper->control('username', ['columnSpan' => 6]);

		$this->assertStringContainsString('ColumnSpan-6', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::control()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlNotAddsColumnSpanWhenNotProvided(): void {
		$result = $this->formHelper->control('username', ['columnSpan' => null]);

		$this->assertStringNotContainsString('ColumnSpan', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::control()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @see \Awyiss\View\Helper\FormHelper::control()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlAddsTimezoneInfoWhenSet(): void {
		$result = $this->formHelper->control('created', ['type' => 'datetime', 'timezone' => 'America/New_York']);

		$this->assertStringContainsString('<span class="Timezone">America/New_York</span>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::control()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlAddsConfigTimezoneInfoWhenOmitted(): void {
		Configure::write('Awyiss.System.' . Awyiss::getRealm() . '.timezone', 'Africa/Abidjan');
		$result = $this->formHelper->control('created', ['type' => 'datetime']);

		$this->assertStringContainsString('<span class="Timezone">Africa/Abidjan</span>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::control()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlAddsDefaultTimezoneInfoWhenOmitted(): void {
		$result = $this->formHelper->control('created', ['type' => 'datetime']);

		$this->assertStringContainsString('<span class="Timezone">' . date_default_timezone_get() . '</span>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::control()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlSetsCorrectDateTimeValueForTimezone(): void {
		$now = new DateTime('now');
		$result = $this->formHelper->control('created', ['type' => 'datetime', 'val' => $now, 'timezone' => 'America/New_York']);

		$this->assertStringContainsString('value="' . $now->setTimezone('America/New_York')->format('Y-m-d\TH:i:s') . '"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::removeClass()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRemoveClassRemovesClassFromArray(): void {
		$options = ['foo' => 'bar', 'class' => ['class1', 'class2', 'class3']];

		$result = $this->formHelper->removeClass($options, 'class2');

		$this->assertEquals(['foo' => 'bar', 'class' => ['class1', 'class3']], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::removeClass()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRemoveClassRemovesClassFromString(): void {
		$options = ['foo' => 'bar', 'class' => 'class1 class2 class3'];

		$result = $this->formHelper->removeClass($options, 'class2');

		$this->assertEquals(['foo' => 'bar', 'class' => 'class1 class3'], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::removeClass()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRemoveClassRemovesClassWhenOnlyOneClassInString(): void {
		$options = ['foo' => 'bar', 'class' => 'class1'];
		$result = $this->formHelper->removeClass($options, 'class1');
		$this->assertEquals(['foo' => 'bar'], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::removeClass()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRemoveClassDoesNotRemoveClassIfNotPresent(): void {
		$options = ['foo' => 'bar', 'class' => 'class1 class2 class3'];
		$result = $this->formHelper->removeClass($options, 'class4');
		$this->assertEquals(['foo' => 'bar', 'class' => 'class1 class2 class3'], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::removeClass()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRemoveClassRemovesClassFromCustomKey(): void {
		$options = ['foo' => 'bar', 'customKey' => 'class1 class2 class3'];
		$result = $this->formHelper->removeClass($options, 'class2', 'customKey');
		$this->assertEquals(['foo' => 'bar', 'customKey' => 'class1 class3'], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::removeClass()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRemoveClassRemovesClassFromEmptyString(): void {
		$options = ['foo' => 'bar', 'class' => ''];
		$result = $this->formHelper->removeClass($options, 'class1');
		$this->assertEquals(['foo' => 'bar'], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::translatableText()
	 * @see \Awyiss\View\Helper\FormHelper::setTranslatableField()
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
	 * @see \Awyiss\View\Helper\FormHelper::translatableText()
	 * @see \Awyiss\View\Helper\FormHelper::setTranslatableField()
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
	 * @see \Awyiss\View\Helper\FormHelper::isFieldError()
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
	 * @see \Awyiss\View\Helper\FormHelper::isFieldError()
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
	 * @see \Awyiss\View\Helper\FormHelper::isFieldError()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @see \Awyiss\View\Helper\FormHelper::isFieldError()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @see \Awyiss\View\Helper\FormHelper::_inputContainerTemplate()
	 * @throws \Exception|\PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInputContainerTemplateContainsCorrectClasses(): void {
		$entity = $this->createMock(Entity::class);
		$entity->method('getSource')->willReturn('Foo');

		$context = $this->createMock(EntityContext::class);
		$context->method('entity')->willReturn($entity);

		$this->formHelper->context($context);

		$result = $this->formHelper->control('path', [
			'required' => true,
			'type' => 'weird_input',
			'id' => 'Foo-Bar',
		]);

		$this->assertStringContainsString('FormInputType-WeirdInput', $result);
		$this->assertStringContainsString('FormInputName-Bar Required', $result);

		$entity = $this->createMock(Entity::class);
		$entity->method('getSource')->willReturn('Bar');

		$context = $this->createMock(EntityContext::class);
		$context->method('entity')->willReturn($entity);

		$this->formHelper->context($context);

		$result = $this->formHelper->control('path', [
			'required' => true,
			'type' => 'weird_input',
			'id' => 'Foo-Bar',
		]);

		$this->assertStringContainsString('FormInputType-WeirdInput', $result);
		$this->assertStringContainsString('FormInputName-Foo-Bar Required', $result);

		$result = $this->formHelper->control('path', [
			'required' => true,
			'type' => 'weird_input',
			'id' => 'FooBar',
		]);

		$this->assertStringContainsString('FormInputType-WeirdInput', $result);
		$this->assertStringContainsString('FormInputName-FooBar Required', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::error()
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
	 * @see \Awyiss\View\Helper\FormHelper::error()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @see \Awyiss\View\Helper\FormHelper::error()
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
	 * @see \Awyiss\View\Helper\FormHelper::error()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @see \Awyiss\View\Helper\FormHelper::error()
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
	 * @see \Awyiss\View\Helper\FormHelper::error()
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
	 * @see \Awyiss\View\Helper\FormHelper::error()
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
	 * @see \Awyiss\View\Helper\FormHelper::error()
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
	 * @see \Awyiss\View\Helper\FormHelper::error()
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


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::renderFormProtection()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRenderFormProtectionRendersAllProtectionMethods(): void {
		$formsTable = $this->fetchTable('Forms');
		$form = $formsTable->get(1);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$form->initialize($this->formHelper->getView());

		/** @noinspection PhpParamsInspection */
		$result = $this->formHelper->renderFormProtection($form, 'before');

		$this->assertSame('', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::renderFormProtection()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpParamsInspection
	 */
	public function testRenderFormProtectionReturnsEmptyStringWhenNoProtectionMethods(): void {
		$formsTable = $this->fetchTable('Forms');
		$form = $formsTable->get(1);

		Configure::write('Awyiss.Forms.Frontend.protection.methods', [
			'hidden_input',
		]);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$form->initialize($this->formHelper->getView());

		$result = $this->formHelper->renderFormProtection($form, 'before');
		$this->assertSame('<div class="hidden-input-protection">This is a test for the before position.</div>' . PHP_EOL, $result);

		$result = $this->formHelper->renderFormProtection($form, FormProtectionInterface::POSITION_BEFORE);
		$this->assertSame('<div class="hidden-input-protection">This is a test for the before position.</div>' . PHP_EOL, $result);

		$result = $this->formHelper->renderFormProtection($form, 'before_submit');
		$this->assertSame('<div class="hidden-input-protection">This is a test for the before submit position.</div>' . PHP_EOL, $result);

		$result = $this->formHelper->renderFormProtection($form, FormProtectionInterface::POSITION_BEFORE_SUBMIT);
		$this->assertSame('<div class="hidden-input-protection">This is a test for the before submit position.</div>' . PHP_EOL, $result);

		$result = $this->formHelper->renderFormProtection($form, 'after');
		$this->assertSame('<div class="hidden-input-protection">This is a test for the after position.</div>' . PHP_EOL, $result);

		$result = $this->formHelper->renderFormProtection($form, FormProtectionInterface::POSITION_AFTER);
		$this->assertSame('<div class="hidden-input-protection">This is a test for the after position.</div>' . PHP_EOL, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::linkSelect()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLinkSelectWithSimpleOptions(): void {
		$options = [
			'<a href="/items?filter=option1">Option 1</a>',
			'<a href="/items?filter=option2">Option 2</a>',
			'<a href="/items?filter=option3">Option 3</a>',
		];

		$result = $this->formHelper->linkSelect('test_filter', $options);

		$this->assertStringContainsString('class="LinkSelect LinkSelect-TestFilter"', $result);
		$this->assertStringContainsString('id="LinkSelect-TestFilter"', $result);
		$this->assertStringContainsString('<a href="/items?filter=option1">Option 1</a>', $result);
		$this->assertStringContainsString('<a href="/items?filter=option2">Option 2</a>', $result);
		$this->assertStringContainsString('<a href="/items?filter=option3">Option 3</a>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::linkSelect()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLinkSelectWithCustomAttributes(): void {
		$options = [
			'<a href="/items?filter=option1">Option 1</a>',
			'<a href="/items?filter=option2">Option 2</a>',
			'<a href="/items?filter=option3">Option 3</a>',
		];

		$result = $this->formHelper->linkSelect('filter', $options, [
			'id' => 'CustomId',
		]);

		$this->assertStringContainsString('<div class="LinkSelect LinkSelect-Filter" id="CustomId">', $result);
		$this->assertStringNotContainsString('<label class="Label" tabindex="0"><strong>Custom Label</strong></label>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\FormHelper::linkSelect()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLinkSelectWithEmptyOptions(): void {
		$result = $this->formHelper->linkSelect('empty_filter', []);

		$this->assertStringContainsString('class="LinkSelect LinkSelect-EmptyFilter"', $result);
		$this->assertStringContainsString('id="LinkSelect-EmptyFilter"', $result);
	}
}
