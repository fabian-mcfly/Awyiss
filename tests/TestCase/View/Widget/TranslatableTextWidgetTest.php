<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Widget;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\StringTemplate;
use Awyiss\View\Widget\TranslatableTextWidget;
use Cake\View\Form\ContextInterface;


/**
 * TranslatableTextWidgetTest class
 */
class TranslatableTextWidgetTest extends TestCase {
	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRender(): void {
		$template = new StringTemplate([
			'translatableText' => '<div class="TranslatableTexts" data-button-title="{{buttonTitle}}"  data-dialog-title="{{dialogTitle}}" data-dialog-apply="{{dialogApply}}" data-dialog-cancel="{{dialogCancel}}">{{controls}}</div>',
		]);

		$context = $this->createMock(ContextInterface::class);
		$widget = new TranslatableTextWidget($template);

		$data = [
			'name' => 'test',
			'val' => 'test value',
			'controls' => ['<button>Control</button>'],
			'templateVars' => [
				'buttonTitle' => 'Test button title',
				'dialogTitle' => 'Test dialog title',
				'dialogApply' => 'Test dialog apply',
				'dialogCancel' => 'Test dialog cancel',
			],
		];

		$expected = '<div class="TranslatableTexts" data-button-title="Test button title"  data-dialog-title="Test dialog title" data-dialog-apply="Test dialog apply" data-dialog-cancel="Test dialog cancel"><button>Control</button></div>';

		$result = $widget->render($data, $context);

		$this->assertSame($expected, $result);
	}
}
