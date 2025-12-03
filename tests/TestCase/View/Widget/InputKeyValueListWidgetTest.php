<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Widget;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\StringTemplate;
use Awyiss\View\Widget\InputKeyValueListWidget;
use Cake\View\Form\ContextInterface;


/**
 * InputKeyValueListWidgetTest class
 */
class InputKeyValueListWidgetTest extends TestCase {
	/**
	 * Test rendering with empty value
	 *
	 * @return void
	 * @see \Awyiss\View\Widget\InputKeyValueListWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection HtmlUnknownAttribute
	 * @noinspection HtmlUnknownTarget
	 */
	public function testRenderWithEmptyValue(): void {
		$stringTemplate = new StringTemplate();
		$stringTemplate->load('form_templates_backend');

		$context = $this->createMock(ContextInterface::class);
		$widget = new InputKeyValueListWidget($stringTemplate);

		$data = [
			'name' => 'test',
			'val' => null,
		];

		$expected = '<div class="FormInputType-ListItem FormInputType-ListItem-Default"><input type="text" name="test[0][key]">' . PHP_EOL .
					'<input type="text" name="test[0][value]"></div>';

		$result = trim($widget->render($data, $context));

		$this->assertSame($expected, $result);
	}


	/**
	 * Test rendering with array value
	 *
	 * @return void
	 * @see \Awyiss\View\Widget\InputKeyValueListWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection HtmlUnknownAttribute
	 * @noinspection HtmlUnknownTarget
	 */
	public function testRenderWithArrayValue(): void {
		$stringTemplate = new StringTemplate();
		$stringTemplate->load('form_templates_backend');

		$context = $this->createMock(ContextInterface::class);
		$widget = new InputKeyValueListWidget($stringTemplate);

		$data = [
			'name' => 'dummy',
			'val' => [
				'key1' => 'value1',
				'key2' => 'value2',
				'key3' => 'value3',
			],
		];

		$expected = '<div class="FormInputType-ListItem"><input type="text" name="dummy[key1][key]" value="key1">' . PHP_EOL .
					'<input type="text" name="dummy[key1][value]" value="value1"></div>' . PHP_EOL .
					'<div class="FormInputType-ListItem"><input type="text" name="dummy[key2][key]" value="key2">' . PHP_EOL .
					'<input type="text" name="dummy[key2][value]" value="value2"></div>' . PHP_EOL .
					'<div class="FormInputType-ListItem"><input type="text" name="dummy[key3][key]" value="key3">' . PHP_EOL .
					'<input type="text" name="dummy[key3][value]" value="value3"></div>' . PHP_EOL .
					'<div class="FormInputType-ListItem FormInputType-ListItem-Default"><input type="text" name="dummy[3][key]">' . PHP_EOL .
					'<input type="text" name="dummy[3][value]"></div>';

		$result = $widget->render($data, $context);

		$this->assertSame($expected, $result);
	}


	/**
	 * Test rendering with JSON string value
	 *
	 * @return void
	 * @see \Awyiss\View\Widget\InputKeyValueListWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection HtmlUnknownAttribute
	 * @noinspection HtmlUnknownTarget
	 */
	public function testRenderWithJsonValue(): void {
		$stringTemplate = new StringTemplate();
		$stringTemplate->load('form_templates_backend');

		$context = $this->createMock(ContextInterface::class);
		$widget = new InputKeyValueListWidget($stringTemplate);

		$data = [
			'name' => 'test',
			'val' => json_encode([
				'key1' => 'value1',
				'key2' => 'value2',
				'key3' => 'value3',
			]),
		];

		$expected = '<div class="FormInputType-ListItem"><input type="text" name="test[key1][key]" value="key1">' . PHP_EOL .
					'<input type="text" name="test[key1][value]" value="value1"></div>' . PHP_EOL .
					'<div class="FormInputType-ListItem"><input type="text" name="test[key2][key]" value="key2">' . PHP_EOL .
					'<input type="text" name="test[key2][value]" value="value2"></div>' . PHP_EOL .
					'<div class="FormInputType-ListItem"><input type="text" name="test[key3][key]" value="key3">' . PHP_EOL .
					'<input type="text" name="test[key3][value]" value="value3"></div>' . PHP_EOL .
					'<div class="FormInputType-ListItem FormInputType-ListItem-Default"><input type="text" name="test[3][key]">' . PHP_EOL .
					'<input type="text" name="test[3][value]"></div>';

		$result = $widget->render($data, $context);

		$this->assertSame($expected, $result);
	}


	/**
	 * Test renderListItems method with default item
	 *
	 * @return void
	 * @see \Awyiss\View\Widget\InputKeyValueListWidget::render()
	 * @noinspection HtmlUnknownAttribute
	 * @noinspection HtmlUnknownTarget
	 */
	public function testRenderListItemsDefault(): void {
		$stringTemplate = new StringTemplate();
		$stringTemplate->load('form_templates_backend');

		$widget = new InputKeyValueListWidget($stringTemplate);

		$data = [
			'name' => 'test',
		];

		$expected = '<div class="FormInputType-ListItem FormInputType-ListItem-Default"><input type="text" name="test[5][key]">' . PHP_EOL .
					'<input type="text" name="test[5][value]"></div>';

		$result = $widget->renderListItems($data, true, 5);

		$this->assertSame($expected, $result);
	}


	/**
	 * Test renderListItems method with empty value
	 *
	 * @return void
	 * @see \Awyiss\View\Widget\InputKeyValueListWidget::render()
	 * @noinspection HtmlUnknownAttribute
	 * @noinspection HtmlUnknownTarget
	 */
	public function testRenderListItemsWithEmptyValue(): void {
		$stringTemplate = new StringTemplate();
		$stringTemplate->load('form_templates_backend');

		$widget = new InputKeyValueListWidget($stringTemplate);

		$data = [
			'name' => 'test',
			'val' => null,
		];

		$result = $widget->renderListItems($data);

		$this->assertSame([], $result);
	}


	/**
	 * Test renderListItems method with array value
	 *
	 * @return void
	 * @see \Awyiss\View\Widget\InputKeyValueListWidget::render()
	 * @noinspection HtmlUnknownAttribute
	 * @noinspection HtmlUnknownTarget
	 */
	public function testRenderListItemsWithArrayValue(): void {
		$stringTemplate = new StringTemplate();
		$stringTemplate->load('form_templates_backend');

		$widget = new InputKeyValueListWidget($stringTemplate);

		$data = [
			'name' => 'test',
			'val' => [
				'key1' => 'value1',
				'key2' => 'value2',
			],
		];

		$expected = [
			'<div class="FormInputType-ListItem"><input type="text" name="test[key1][key]" value="key1">' . PHP_EOL .
				'<input type="text" name="test[key1][value]" value="value1"></div>',
			'<div class="FormInputType-ListItem"><input type="text" name="test[key2][key]" value="key2">' . PHP_EOL .
				'<input type="text" name="test[key2][value]" value="value2"></div>',
		];

		$result = $widget->renderListItems($data);

		$this->assertSame($expected, $result);
	}


	/**
	 * Test renderListItems method with JSON string value
	 *
	 * @return void
	 * @see \Awyiss\View\Widget\InputKeyValueListWidget::render()
	 * @noinspection HtmlUnknownAttribute
	 * @noinspection HtmlUnknownTarget
	 */
	public function testRenderListItemsWithJsonValue(): void {
		$stringTemplate = new StringTemplate();
		$stringTemplate->load('form_templates_backend');

		$widget = new InputKeyValueListWidget($stringTemplate);

		$data = [
			'name' => 'test',
			'val' => json_encode([
				'key1' => 'value1',
				'key2' => 'value2',
			]),
		];

		$expected = [
			'<div class="FormInputType-ListItem"><input type="text" name="test[key1][key]" value="key1">' . PHP_EOL .
			'<input type="text" name="test[key1][value]" value="value1"></div>',
			'<div class="FormInputType-ListItem"><input type="text" name="test[key2][key]" value="key2">' . PHP_EOL .
			'<input type="text" name="test[key2][value]" value="value2"></div>',
		];

		$result = $widget->renderListItems($data);

		$this->assertSame($expected, $result);
	}
}
