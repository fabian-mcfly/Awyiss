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
		'name' => '',
		'templateVars' => [],
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
	 * @param array<string, mixed> $aa_data The data to build a textarea with.
	 * @param \Cake\View\Form\ContextInterface $ao_context The current form context.
	 * @return string HTML elements.
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function render(array $aa_data, ContextInterface $ao_context): string {
		$la_data = $aa_data + $this->mergeDefaults($aa_data, $ao_context);

		$la_data += ['readonly' => true];

		$la_data['templateVars']['controls'] = '';
		foreach ($la_data['controls'] as $ls_control) {
			$la_data['templateVars']['controls'] .= $ls_control;
		}

		$la_data['value'] = $la_data['val'];
		unset($la_data['val']);
		if ($la_data['value'] === false) {
			// explicitly convert to 0 to avoid empty string which is marshaled as null
			$la_data['value'] = '0';
		}

		$la_data['templateVars'] += [
			'buttonTitle' => __d('system', 'translations_button_title'),
			'dialogTitle' => __d('system', 'translations_dialog_title'),
			'dialogApply' => __d('system', 'translations_dialog_apply'),
			'dialogCancel' => __d('system', 'translations_dialog_cancel'),
		];

		return $this->_templates->format('translatableText', [
			'name' => $la_data['name'],
			'input' => $la_data['input'],
			'type' => $la_data['type'],
			'templateVars' => $la_data['templateVars'],
			'attrs' => $this->_templates->formatAttributes($la_data, ['name']),
		]);
	}
}
