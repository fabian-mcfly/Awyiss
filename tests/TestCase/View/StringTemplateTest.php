<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View;


use Awyiss\View\StringTemplate;
use Cake\Core\Exception\CakeException;
use Cake\TestSuite\TestCase;


/**
 * StringTemplateTest class
 */
class StringTemplateTest extends TestCase {
	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLoadValidFile(): void {
		$file = 'form_templates_backend';

		$stringTemplate = new StringTemplate();
		$stringTemplate->load($file);

		$result = $stringTemplate->getConfig();

		$expectedTemplates = [
			'checkboxWrapper' => '<figure>Totally different Checkbox-Wrapper</figure>',
			'inputContainer' => '<div class="FormInput FormInputType-{{type}} FormInputName-{{identifier}}{{required}}{{columnSpan}} {{containerClass}}"{{containerAttrs}}>{{content}}{{additionalContent}}</div>',
			'translatableText' => '<div class="TranslatableTexts" data-button-title="{{buttonTitle}}" data-dialog-title="{{dialogTitle}}" data-dialog-apply="{{dialogApply}}" data-dialog-cancel="{{dialogCancel}}">{{controls}}</div>',
		];

		foreach ($expectedTemplates as $key => $expectedTemplate) {
			$this->assertArrayHasKey($key, $result);
			$this->assertSame($expectedTemplate, $result[$key]);
		}
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLoadEmptyFileThrowsException(): void {
		$this->expectException(CakeException::class);
		$this->expectExceptionMessage('String template filename cannot be an empty string');

		$stringTemplate = new StringTemplate();
		$stringTemplate->load('');
	}
}
