<?php declare(strict_types=1);


namespace Awyiss\View\Widget;


use Cake\View\Form\ContextInterface;
use Cake\View\Widget\BasicWidget;


/**
 * Input widget class for generating a textarea control.
 *
 * This class is usually used internally by `Cake\View\Helper\FormHelper`,
 * it but can be used to generate standalone text areas.
 */
class TranslatableTextWidget extends BasicWidget {
	/**
	 * Data defaults.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaults = [
		'escape' => true,
		'input' => null,
		'name' => '',
		'templateVars' => [],
		'type' => null,
		'val' => '',
	];


	/**
	 * Render a text area form widget.
	 *
	 * Data supports the following keys:
	 *
	 * - `escape` - Set to `false` to disable HTML escaping.
	 * - `name` - Set the input name.
	 * - `val` - A string of the option to mark as selected.
	 *
	 * All other keys will be converted into HTML attributes.
	 *
	 * @param array<string, mixed> $data The data to build a textarea with.
	 * @param \Cake\View\Form\ContextInterface $context The current form context.
	 * @return string HTML elements.
	 */
	public function render(array $data, ContextInterface $context): string {
		$data += $this->mergeDefaults($data, $context);

		$data += ['readonly' => true];

		$data['templateVars']['controls'] = '';
		foreach ($data['controls'] as $control) {
			$data['templateVars']['controls'] .= $control;
		}

		$data['value'] = $data['val'];
		unset($data['val']);
		if ($data['value'] === false) {
			// explicitly convert to 0 to avoid empty string which is marshaled as null
			$data['value'] = '0';
		}

		$data['templateVars'] += [
			'buttonTitle' => __d('system', 'translations_button_title'),
			'dialogTitle' => __d('system', 'translations_dialog_title', $data['dialogTitle'] ?? __($data['name'])),
			'dialogApply' => __d('system', 'translations_dialog_apply'),
			'dialogCancel' => __d('system', 'translations_dialog_cancel'),
		];
		unset($data['dialogTitle']);

		return $this->_templates->format('translatableText', [
			'name' => $data['name'],
			'input' => $data['input'],
			'type' => $data['type'],
			'templateVars' => $data['templateVars'],
			'attrs' => $this->_templates->formatAttributes($data, ['name']),
		]);
	}
}
