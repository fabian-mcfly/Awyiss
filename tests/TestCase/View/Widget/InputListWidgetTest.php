<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Widget;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\StringTemplate;
use Awyiss\View\Widget\InputListWidget;
use Cake\View\Form\ContextInterface;


/**
 * InputListWidgetTest class
 */
class InputListWidgetTest extends TestCase {
	/**
	 * Test rendering with empty value
	 *
	 * @return void
	 * @see \Awyiss\View\Widget\InputListWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownAttribute
	 * @noinspection HtmlUnknownTarget
	 */
	public function testRenderWithEmptyValue(): void {
		$stringTemplate = new StringTemplate();
		$stringTemplate->load('form_templates_backend');

		$context = $this->createMock(ContextInterface::class);
		$widget = new InputListWidget($stringTemplate);

		$data = [
			'name' => 'test',
			'val' => null,
		];

		$expected = '<div class="FormInputType-ListItem FormInputType-ListItem-Default"><input type="text" name="test[0]"></div>';

		$result = trim($widget->render($data, $context));

		$this->assertSame($expected, $result);
	}


	/**
	 * Test rendering with array value
	 *
	 * @return void
	 * @see \Awyiss\View\Widget\InputListWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownAttribute
	 * @noinspection HtmlUnknownTarget
	 */
	public function testRenderWithArrayValue(): void {
		$stringTemplate = new StringTemplate();
		$stringTemplate->load('form_templates_backend');

		$context = $this->createMock(ContextInterface::class);
		$widget = new InputListWidget($stringTemplate);

		$data = [
			'name' => 'dummy',
			'val' => ['Item 1', 'Item 2', 'Item 3'],
		];

		$expected = '<div class="FormInputType-ListItem"><input type="text" name="dummy[0]" value="Item 1"></div>' . PHP_EOL .
					'<div class="FormInputType-ListItem"><input type="text" name="dummy[1]" value="Item 2"></div>' .	PHP_EOL .
					'<div class="FormInputType-ListItem"><input type="text" name="dummy[2]" value="Item 3"></div>' .	PHP_EOL .
					'<div class="FormInputType-ListItem FormInputType-ListItem-Default"><input type="text" name="dummy[3]"></div>';

		$result = $widget->render($data, $context);

		$this->assertSame($expected, $result);
	}


	/**
	 * Test rendering with JSON string value
	 *
	 * @return void
	 * @see \Awyiss\View\Widget\InputListWidget::render()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownAttribute
	 * @noinspection HtmlUnknownTarget
	 */
	public function testRenderWithJsonValue(): void {
		$stringTemplate = new StringTemplate();
		$stringTemplate->load('form_templates_backend');

		$context = $this->createMock(ContextInterface::class);
		$widget = new InputListWidget($stringTemplate);

		$data = [
			'name' => 'test',
			'val' => json_encode(['Item 1', 'Item 2', 'Item 3']),
		];

		$expected = '<div class="FormInputType-ListItem"><input type="text" name="test[0]" value="Item 1"></div>' .	PHP_EOL .
					'<div class="FormInputType-ListItem"><input type="text" name="test[1]" value="Item 2"></div>' .	PHP_EOL .
					'<div class="FormInputType-ListItem"><input type="text" name="test[2]" value="Item 3"></div>' .	PHP_EOL .
					'<div class="FormInputType-ListItem FormInputType-ListItem-Default"><input type="text" name="test[3]"></div>';

		$result = $widget->render($data, $context);

		$this->assertSame($expected, $result);
	}


	/**
	 * Test renderListItems method with default item
	 *
	 * @return void
	 * @see \Awyiss\View\Widget\InputListWidget::render()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownAttribute
	 * @noinspection HtmlUnknownTarget
	 */
	public function testRenderListItemsDefault(): void {
		$stringTemplate = new StringTemplate();
		$stringTemplate->load('form_templates_backend');

		$widget = new InputListWidget($stringTemplate);

		$data = [
			'name' => 'test',
		];

		$expected = '<div class="FormInputType-ListItem FormInputType-ListItem-Default"><input type="text" name="test[5]"></div>';

		$result = $widget->renderListItems($data, true, 5);

		$this->assertSame($expected, $result);
	}


	/**
	 * Test renderListItems method with empty value
	 *
	 * @return void
	 * @see \Awyiss\View\Widget\InputListWidget::render()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownAttribute
	 * @noinspection HtmlUnknownTarget
	 */
	public function testRenderListItemsWithEmptyValue(): void {
		$stringTemplate = new StringTemplate();
		$stringTemplate->load('form_templates_backend');

		$widget = new InputListWidget($stringTemplate);

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
	 * @see \Awyiss\View\Widget\InputListWidget::render()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownAttribute
	 * @noinspection HtmlUnknownTarget
	 */
	public function testRenderListItemsWithArrayValue(): void {
		$stringTemplate = new StringTemplate();
		$stringTemplate->load('form_templates_backend');

		$widget = new InputListWidget($stringTemplate);

		$data = [
			'name' => 'test',
			'val' => ['Item 1', 'Item 2'],
		];

		$expected = [
			'<div class="FormInputType-ListItem"><input type="text" name="test[0]" value="Item 1"></div>',
			'<div class="FormInputType-ListItem"><input type="text" name="test[1]" value="Item 2"></div>',
		];

		$result = $widget->renderListItems($data);

		$this->assertSame($expected, $result);
	}


	/**
	 * Test renderListItems method with JSON string value
	 *
	 * @return void
	 * @see \Awyiss\View\Widget\InputListWidget::render()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownAttribute
	 * @noinspection HtmlUnknownTarget
	 */
	public function testRenderListItemsWithJsonValue(): void {
		$stringTemplate = new StringTemplate();
		$stringTemplate->load('form_templates_backend');

		$widget = new InputListWidget($stringTemplate);

		$data = [
			'name' => 'test',
			'val' => json_encode(['Item 1', 'Item 2']),
		];

		$expected = [
			'<div class="FormInputType-ListItem"><input type="text" name="test[0]" value="Item 1"></div>',
			'<div class="FormInputType-ListItem"><input type="text" name="test[1]" value="Item 2"></div>',
		];

		$result = $widget->renderListItems($data);

		$this->assertSame($expected, $result);
	}
}
