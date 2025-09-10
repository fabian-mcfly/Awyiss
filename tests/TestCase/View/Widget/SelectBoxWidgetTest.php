<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Widget;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\StringTemplate;
use Awyiss\View\Widget\SelectBoxWidget;
use Cake\View\Form\ContextInterface;


/**
 * SelectBoxWidgetTest class
 */
class SelectBoxWidgetTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\View\Widget\SelectBoxWidget::_renderOptions()
	 * @see \Awyiss\View\Widget\SelectBoxWidget::_renderOptgroup()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRenderWithTextLimitation(): void {
		$stringTemplate = new StringTemplate();
		$stringTemplate->load('form_templates_backend');

		$context = $this->createMock(ContextInterface::class);
		$widget = new SelectBoxWidget($stringTemplate);

		// Create a long text that exceeds 100 characters
		$longText = trim(str_repeat('Long Option Text ', 10));

		$data = [
			'name' => 'test',
			'options' => [
				'1' => $longText,
			],
		];

		$result = $widget->render($data, $context);

		// Verify that the text is limited to 100 characters
		$this->assertStringContainsString('>' . mb_substr($longText, 0, 100) . '</option>', $result);
		$this->assertStringNotContainsString('>' . $longText . '</option>', $result);

		// Verify that the title attribute contains the full text
		$this->assertStringContainsString('title="' . $longText . '"', $result);
	}

	/**
	 * @return void
	 * @see \Awyiss\View\Widget\SelectBoxWidget::_renderOptions()
	 * @see \Awyiss\View\Widget\SelectBoxWidget::_renderOptgroup()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRenderWithOptionGroups(): void {
		$stringTemplate = new StringTemplate();
		$stringTemplate->load('form_templates_backend');

		$context = $this->createMock(ContextInterface::class);
		$widget = new SelectBoxWidget($stringTemplate);

		$longGroup = trim(str_repeat('Long Option Group Text ', 10));
		$longText = trim(str_repeat('Long Option Text ', 10));

		$data = [
			'name' => 'test',
			'options' => [
				$longGroup => [
					'1' => 'Option 1',
					'2' => $longText,
				],
				'Group 2' => [
					'3' => 'Option 3',
					'4' => 'Option 4',
				],
			],
		];

		$result = $widget->render($data, $context);

		$this->assertStringContainsString('label="' . mb_substr($longGroup, 0, 100) . '"', $result);
		$this->assertStringContainsString('title="' . $longGroup . '"', $result);

		$this->assertStringContainsString('Option 1', $result);

		$this->assertStringContainsString('>' . mb_substr($longText, 0, 100) . '</option>', $result);
		$this->assertStringNotContainsString('>' . $longText . '</option>', $result);
		$this->assertStringContainsString('title="' . $longText . '"', $result);

		$this->assertStringContainsString('<optgroup label="Group 2"', $result);
		$this->assertStringContainsString('Option 3', $result);
		$this->assertStringContainsString('Option 4', $result);
	}

	/**
	 * @return void
	 * @see \Awyiss\View\Widget\SelectBoxWidget::_renderOptions()
	 * @see \Awyiss\View\Widget\SelectBoxWidget::_renderOptgroup()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRenderWithCustomAttributes(): void {
		$stringTemplate = new StringTemplate();
		$stringTemplate->load('form_templates_backend');

		$context = $this->createMock(ContextInterface::class);
		$widget = new SelectBoxWidget($stringTemplate);

		$data = [
			'name' => 'test',
			'options' => [
				'1' => [
					'text' => 'Option 1',
					'value' => '1',
					'title' => 'Custom Title',
					'class' => 'custom-class',
				],
			],
		];

		$result = $widget->render($data, $context);

		// Verify that custom attributes are rendered correctly
		$this->assertStringContainsString('title="Custom Title"', $result);
		$this->assertStringContainsString('class="custom-class"', $result);
	}
}
