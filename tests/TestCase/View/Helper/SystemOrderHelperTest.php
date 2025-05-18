<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Helper;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Awyiss\View\Helper\FormHelper;
use Awyiss\View\Helper\SystemOrderHelper;
use Awyiss\View\HelperRegistry;
use Customer\Model\Entity\News;
use Error;
use RuntimeException;
use stdClass;
use TypeError;


/**
 * SystemOrderHelperTest class
 */
class SystemOrderHelperTest extends TestCase {
	/**
	 * @var \Awyiss\View\Helper\SystemOrderHelper
	 */
	protected SystemOrderHelper $helper;
	/**
	 * @var \Awyiss\View\Helper\FormHelper
	 */
	protected FormHelper $formHelper;


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function setUp(): void {
		parent::setUp();

		$view = $this->getMockBuilder(BackendView::class)
			->disableOriginalConstructor()
			->enableAutoReturnValueGeneration()
			->getMock();

		$view->method('helpers')->willReturn(new HelperRegistry($view));

		$this->formHelper = new FormHelper($view, [
			'autoSetCustomValidity' => false,
			'templates' => 'form_templates_backend',
		]);

		$view->method('loadHelper')->willReturn($this->formHelper);

		$this->helper = new SystemOrderHelper($view, [
			'relatedColumns' => ['languageShortcode'],
			'templates' => [
				'titleOption' => function (mixed $option): string {
					return __('system_order_after') . ' ' . $option->label;
				},
				'titleOptionCurrent' => function (mixed $option): string {
					return $option->label;
				},
				'titleOptionSelected' => function (mixed $option): string {
					return '-> ' . __('system_order_after') . ' ' . $option->label;
				},
			],
		]);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithDefaultConfig(): void {
		$entity = new News(['systemOrder' => 1]);
		$entity->setSource('News');

		$result = $this->helper->control(null, ['entity' => $entity]);
		$this->assertStringContainsString('<select name="system_order" id="SystemOrder">', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithCustomFieldName(): void {
		$entity = new News(['systemOrder' => 1]);
		$entity->setSource('News');

		$result = $this->helper->control('custom_field', ['entity' => $entity]);
		$this->assertStringContainsString('<select name="custom_field" id="CustomField">', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithMissingEntity(): void {
		$this->expectException(Error::class);
		$this->expectExceptionMessage('Call to undefined method Cake\View\Form\NullContext::entity()');

		$this->helper->control();
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithInvalidEntity(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Entity provided must be an instance of `Awyiss\Model\Entity`, `stdClass` given.');

		$this->helper->control(null, ['entity' => new stdClass()]);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithOptions(): void {
		$entity = new News(['systemOrder' => 2]);

		$options = [
			new News(['systemOrder' => 1, 'title' => 'Option 1']),
			new News(['systemOrder' => 2, 'title' => 'Option 2']),
			new News(['systemOrder' => 3, 'title' => 'Option 3']),
			new News(['systemOrder' => 4, 'title' => 'Option 4']),
		];

		$result = $this->helper->control(null, ['entity' => $entity, 'options' => $options]);

		$this->assertStringContainsString('<option value="1" title="Option 2">', $result);
		$this->assertStringContainsString('<option value="2" title="Option 3" selected="selected">', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithCollectionAsOptions(): void {
		$entity = new News(['systemOrder' => 2]);

		$options = collection([
			new News(['systemOrder' => 1, 'title' => 'Option 1']),
			new News(['systemOrder' => 2, 'title' => 'Option 2']),
			new News(['systemOrder' => 3, 'title' => 'Option 3']),
			new News(['systemOrder' => 4, 'title' => 'Option 4']),
		]);

		$result = $this->helper->control(null, ['entity' => $entity, 'options' => $options]);

		$this->assertStringContainsString('<option value="1" title="system_order_first">', $result);
		$this->assertStringContainsString('<option value="2" title="-&gt; system_order_after Option 1" selected="selected">', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithExistingEntity(): void {
		$entity = new News(['systemOrder' => 2]);
		$entity->setNew(false);

		$options = collection([
			new News(['systemOrder' => 1, 'title' => 'Option 1']),
			new News(['systemOrder' => 2, 'title' => 'Option 2']),
			new News(['systemOrder' => 3, 'title' => 'Option 3']),
			new News(['systemOrder' => 4, 'title' => 'Option 4']),
		]);

		$result = $this->helper->control(null, ['entity' => $entity, 'options' => $options]);

		$this->assertStringContainsString('<option value="2" title="-&gt; system_order_after Option 1" selected="selected">-&gt; system_order_after Option 1</option>', $result);
		$this->assertStringContainsString('<option value="__CURRENT__" title="Option 2" disabled="disabled">Option 2</option>', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithNewEntity(): void {
		$entity = new News(['systemOrder' => 2]);
		$entity->setNew(true);

		$options = collection([
			new News(['systemOrder' => 1, 'title' => 'Option 1']),
			new News(['systemOrder' => 2, 'title' => 'Option 2']),
			new News(['systemOrder' => 3, 'title' => 'Option 3']),
			new News(['systemOrder' => 4, 'title' => 'Option 4']),
		]);

		$result = $this->helper->control(null, ['entity' => $entity, 'options' => $options]);

		$this->assertStringContainsString('<option value="2" title="-&gt; system_order_after Option 1" selected="selected">-&gt; system_order_after Option 1</option>', $result);
		$this->assertStringContainsString('<option value="3" title="system_order_after Option 2">system_order_after Option 2</option>', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlForDatabaseRecords(): void {
		$table = $this->fetchTable('MediaFolders');
		$entity = $table->findById(3)->find('withDeleted')->first();

		$records = $table->find('withDeleted')->where(['language_shortcode' => 'de']);

		$result = $this->helper->control(null, ['entity' => $entity, 'options' => $records]);

		$this->assertStringContainsString(
			'<option value="2" title="-&gt; system_order_after Testfolder1" selected="selected">-&gt; system_order_after Testfolder1</option>' .
			'<option value="__CURRENT__" title="Testfolder2" disabled="disabled">Testfolder2</option>' .
			'<option value="3" title="system_order_after media_folders::inactive Testfolder3">system_order_after media_folders::inactive Testfolder3</option>',
			$result
		);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlForDatabaseRecordsAndChangedSystemOrder(): void {
		$table = $this->fetchTable('MediaFolders');
		$entity = $table->findById(3)->find('withDeleted')->first();
		$entity->systemOrder = 3;

		$records = $table->find('withDeleted')->where(['language_shortcode' => 'de']);

		$result = $this->helper->control(null, ['entity' => $entity, 'options' => $records]);

		$this->assertStringContainsString(
			'<option value="2" title="system_order_after Testfolder1">system_order_after Testfolder1</option>' .
			'<option value="__CURRENT__" title="Testfolder2" disabled="disabled">Testfolder2</option>' .
			'<option value="3" title="-&gt; system_order_after media_folders::inactive Testfolder3" selected="selected">-&gt; system_order_after media_folders::inactive Testfolder3</option>',
			$result
		);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testControlForDatabaseRecordsAndChangedSystemOrderAndDirtyRelatedColumn(): void {
		$table = $this->fetchTable('MediaFolders');
		$entity = $table->newDefaultEntity(['languageShortcode' => 'de', 'systemOrder' => 3, 'title' => 'Testfolder New']);

		$records = $table->find('withDeleted')->where(['language_shortcode' => 'de']);

		$result = $this->helper->control(null, ['entity' => $entity, 'options' => $records]);

		$this->assertStringContainsString(
			'<option value="2" title="system_order_after Testfolder1">system_order_after Testfolder1</option>' .
			'<option value="3" title="-&gt; system_order_after Testfolder2" selected="selected">-&gt; system_order_after Testfolder2</option>' .
			'<option value="4" title="system_order_after media_folders::inactive Testfolder3">system_order_after media_folders::inactive Testfolder3</option>',
			$result
		);
	}

	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithIncludeFirstOption(): void {
		$entity = new News(['systemOrder' => 2]);

		$options = collection([
			new News(['systemOrder' => 1, 'title' => 'Option 1']),
			new News(['systemOrder' => 2, 'title' => 'Option 2']),
			new News(['systemOrder' => 3, 'title' => 'Option 3']),
			new News(['systemOrder' => 4, 'title' => 'Option 4']),
		]);

		$result = $this->helper->control(null, ['includeFirst' => true, 'entity' => $entity, 'options' => $options]);

		$this->assertStringContainsString('<option value="1" title="system_order_first">system_order_first</option>', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithCustomTemplates(): void {
		$entity = new News(['systemOrder' => 3]);

		$options = collection([
			new News(['systemOrder' => 1, 'title' => 'Option 1']),
			new News(['systemOrder' => 2, 'title' => 'Option 2']),
			new News(['systemOrder' => 3, 'title' => 'Option 3']),
			new News(['systemOrder' => 4, 'title' => 'Option 4']),
		]);

		$templates = [
			'titleOptionSelected' => 'Something Custom',
		];

		$result = $this->helper->control(null, ['entity' => $entity, 'options' => $options, 'templates' => $templates]);

		$this->assertStringContainsString('<option value="3" title="Something Custom" selected="selected">Something Custom</option>', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithDisabledOption(): void {
		$entity = new News(['systemOrder' => 1]);

		$result = $this->helper->control(null, ['entity' => $entity, 'disabled' => true]);
		$this->assertStringContainsString('<select name="system_order" disabled="disabled" id="SystemOrder">', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithMergedAttributes(): void {
		$entity = new News(['systemOrder' => 1]);

		$attributes = [
			'entity' => $entity,
			'class' => 'custom-class',
			'data-test' => 'test-value',
		];

		$result = $this->helper->control(null, $attributes);

		$this->assertStringContainsString('<select name="system_order" class="custom-class" data-test="test-value" id="SystemOrder">', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithEmptyOptions(): void {
		$entity = new News(['systemOrder' => 1]);

		$result = $this->helper->control(null, ['entity' => $entity, 'options' => []]);
		$this->assertStringContainsString('<select name="system_order" id="SystemOrder"></select>', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithNonArrayOptions(): void {
		$entity = new News(['systemOrder' => 1]);

		$this->expectException(TypeError::class);

		$this->helper->control(null, ['entity' => $entity, 'options' => 'invalid']);
	}
}
