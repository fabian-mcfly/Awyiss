<?php declare(strict_types=1);


namespace Awyiss\View\Widget;


use Cake\View\Form\ContextInterface;
use Cake\View\StringTemplate;
use Cake\View\Widget\WidgetInterface;
use Traversable;


/**
 * Input widget for creating a custom select with links instead of dropdown-options.
 *
 * This class is usually used internally by `\Awyiss\View\Helper\CategoriesHelper`,
 * it but can be used to generate standalone custom selects.
 */
class LinkSelectWidget implements WidgetInterface {
	protected array $defaults = [
		'aggregationLabel' => 'all',
		'aggregationKey' => 'all',
		'class' => '',
		'disabled' => FALSE,
		'escape' => TRUE,
		'includeAggregation' => TRUE,
		'includeUnassigned' => FALSE,
		'label' => '',
		'name' => '',
		'options' => [],
		'templateVars' => [],
		'unassignedLabel' => 'all',
		'unassignedKey' => 'all',
		'val' => NULL,
	];
	protected StringTemplate $templates;


	/**
	 * Constructor.
	 *
	 * @param \Cake\View\StringTemplate $ao_templates Templates list.
	 */
	public function __construct (StringTemplate $ao_templates) {
		$this->templates = $ao_templates;
	}


	/**
	 * @inheritDoc
	 *
	 * @param array $aa_data
	 * @param \Cake\View\Form\ContextInterface $ao_context
	 *
	 * @return string
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function render (array $aa_data, ContextInterface $ao_context): string {
		$la_data = $aa_data + $this->defaults;

		//Render the options
		$la_listItems = $this->renderOptions($la_data);

		//Escape the name if required
		$ls_name = $la_data['escape'] ? h($la_data['name']) : $la_data['name'];

		//Shall an option to select unassigned elements be included? Prepend it.
		if ($la_data['includeUnassigned']) {
			array_unshift($la_listItems, $this->renderUnassignedOption($la_data));
		}

		//Shall an option to select an aggregation be included? Prepend it
		if ($la_data['includeAggregation']) {
			array_unshift($la_listItems, $this->renderAggregationOption($la_data));
		}

		$ls_selectedOption = '-';

		//If the provided value is a key of the provided options, it's the currently selected option
		if (array_key_exists($la_data['val'], $la_data['options'])) {
			$lx_selectedOption = $la_data['options'][ $la_data['val'] ];

			if (is_array($lx_selectedOption) && isset($lx_selectedOption['title'])) {
				$lx_selectedOption = $lx_selectedOption['title'];
			}

			$ls_selectedOption = $la_data['escape'] ? h($lx_selectedOption) : $lx_selectedOption;
		}
		//If the `includeUnassigned`-option is TRUE and the provided value equals the value of the `unassignedKey`-option, it's the currently selected option
		elseif ($la_data['includeUnassigned'] && $this->isSelected((string) $la_data['unassignedKey'], $la_data['val'])) {
			//The text to display is the value of the `unassignedLabel`-option
			$ls_selectedOption = $la_data['unassignedLabel'];
		}
		//If the `includeAggregation`-option is TRUE and the provided value equals the value of the `aggregationKey`-option, it's the currently selected option
		elseif ($la_data['includeAggregation'] && $this->isSelected((string) $la_data['aggregationKey'], $la_data['val'])) {
			//The text to display is the value of the `aggregationLabel`-option
			$ls_selectedOption = $la_data['aggregationLabel'];
		}

		//Add the label to the templateVars, if it does not already exist
		if (isset($la_data['label']) && empty($la_data['templateVars']['label'])) {
			$la_data['templateVars']['label'] = $la_data['label'];
		}

		//Unset attributes that shouldn't be part of the generated input
		unset($la_data['name'], $la_data['options'], $la_data['escape'], $la_data['disabled'], $la_data['label'], $la_data['val'], $la_data['aggregationLabel'], $la_data['aggregationKey'], $la_data['unassignedLabel'], $la_data['unassignedKey']);
		if (isset($la_data['disabled']) && is_array($la_data['disabled'])) {
			unset($la_data['disabled']);
		}

		//Add a new class
		$la_data = $this->templates->addClass($la_data, 'CustomSelect');

		//Format the attributes
		$la_attributes = $this->templates->formatAttributes($la_data);

		//Return the formatted template
		return $this->templates->format('linkSelect', [
			'attrs' => $la_attributes,
			'name' => $ls_name,
			'options' => implode('', $la_listItems),
			'selectedOption' => $ls_selectedOption,
			'templateVars' => $la_data['templateVars'],
		]);
	}


	/**
	 * @inheritDoc
	 *
	 * @param array $aa_data
	 *
	 * @return array|string[]
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function secureFields (array $aa_data): array {
		return [];
	}


	/**
	 * Helper method for deciding what options are selected.
	 *
	 * @param string $as_key
	 * @param mixed $ax_selected
	 *
	 * @return bool
	 */
	protected function isSelected (string $as_key, mixed $ax_selected): bool {
		if ($ax_selected === NULL) {
			return FALSE;
		}

		if ( ! is_array($ax_selected)) {
			$ax_selected = $ax_selected === FALSE ? '0' : $ax_selected;

			return $as_key === (string) $ax_selected;
		}

		$lb_strict = ! is_numeric($as_key);

		return in_array($as_key, $ax_selected, $lb_strict);
	}


	/**
	 * Helper method for deciding what options are disabled.
	 *
	 * @param string $as_key The key to test.
	 * @param string[]|NULL $la_disabled The disabled values.
	 *
	 * @return bool
	 */
	protected function isDisabled (string $as_key, ?array $la_disabled): bool {
		if ($la_disabled === NULL) {
			return FALSE;
		}

		$lb_strict = ! is_numeric($as_key);

		return in_array($as_key, $la_disabled, $lb_strict);
	}


	/**
	 * Returns a rendered template for an option that allows the selection of unassigned entries.
	 *
	 * @param array $aa_data
	 *
	 * @return string
	 */
	protected function renderUnassignedOption (array $aa_data): string {
		$la_data = $aa_data;

		$lx_escape = $la_data['escape'] ?? TRUE;

		$la_attributes = [
			'class' => 'Item',
			'templateVars' => [
				'name' => $la_data['name'],
			],
		];

		$la_attributes = $this->templates->addClass($la_attributes, 'Item');
		if ($this->isSelected((string) $la_data['unassignedKey'], $la_data['val'])) {
			$la_attributes = $this->templates->addClass($la_attributes, $la_data['selectedClass'] ?? 'Active');
		}

		return $this->templates->format('unassignedOption', [
			'attrs' => $this->templates->formatAttributes($la_attributes),
			'templateVars' => $la_attributes['templateVars'],
			'title' => $lx_escape ? h($la_data['unassignedLabel']) : $la_data['unassignedLabel'],
			'value' => $lx_escape ? h($la_data['unassignedKey']) : $la_data['unassignedKey'],
		]);
	}


	/**
	 * Returns a rendered template for an option that allows the selection of an aggregation of entries.
	 *
	 * @param array $aa_data
	 *
	 * @return string
	 */
	protected function renderAggregationOption (array $aa_data): string {
		$la_data = $aa_data;

		$lx_escape = $la_data['escape'] ?? TRUE;

		$la_attributes = [
			'class' => 'Item',
			'templateVars' => [
				'name' => $la_data['name'],
			],
		];

		$la_attributes = $this->templates->addClass($la_attributes, 'Item');
		if ($this->isSelected((string) $la_data['aggregationKey'], $la_data['val'])) {
			$la_attributes = $this->templates->addClass($la_attributes, $la_data['selectedClass'] ?? 'Active');
		}

		return $this->templates->format('aggregationOption', [
			'attrs' => $this->templates->formatAttributes($la_attributes),
			'templateVars' => $la_attributes['templateVars'],
			'title' => $lx_escape ? h($la_data['aggregationLabel']) : $la_data['aggregationLabel'],
			'value' => $lx_escape ? h($la_data['aggregationKey']) : $la_data['aggregationKey'],
		]);
	}


	/**
	 * Returns an array of rendered templates for every option in `$aa_data['options']`
	 *
	 * @param array $aa_data
	 *
	 * @return array
	 */
	protected function renderOptions (array $aa_data): array {
		$la_data = $aa_data;

		//Make sure the options are an array
		if ($la_data['options'] instanceof Traversable) {
			$la_data['options'] = iterator_to_array($la_data['options']);
		}

		//No options? Return an empty array
		if (empty($la_data['options'])) {
			return [];
		}

		$lx_selected = $la_data['val'] ?? NULL;
		$lx_escape = $la_data['escape'] ?? TRUE;
		$lx_disabled = NULL;
		if (isset($la_data['disabled']) && is_array($la_data['disabled'])) {
			$lx_disabled = $la_data['disabled'];
		}

		$la_options = [];
		foreach ($la_data['options'] as $lx_key => $lx_value) {
			//Basic options
			$la_optionAttributes = [
				'templateVars' => [],
				'title' => $lx_value,
				'value' => $lx_key,
			];

			//If the value is an array and has a `title`-key
			if (is_array($lx_value) && isset($lx_value['title'])) {
				//Use this array as the option attributes
				$la_optionAttributes = $lx_value;

				//Make sure both the `value`-key and $lx_key have the same content
				if (isset($lx_value['value'])) {
					$lx_key = $la_optionAttributes['value'];
				}
				else {
					$la_optionAttributes['value'] = $lx_key;
				}
			}

			if ( ! isset($la_optionAttributes['templateVars'])) {
				$la_optionAttributes['templateVars'] = [];
			}
			$la_optionAttributes['templateVars']['name'] = $la_data['name'];

			//Add a class 'Item' and, if the value of the option is selected, 'Active' as well
			$la_optionAttributes = $this->templates->addClass($la_optionAttributes, 'Item');
			if ($this->isSelected((string) $lx_key, $lx_selected)) {
				$la_optionAttributes = $this->templates->addClass($la_optionAttributes, $la_data['selectedClass'] ?? 'Active');
			}

			//Depending on the status, use the `option`- or `optionDisabled`-template to render the option
			$ls_template = 'option';
			if ($this->isDisabled((string) $lx_key, $lx_disabled)) {
				$ls_template = 'optionDisabled';
				//If the option is disabled, add 'Disabled' to the class
				$la_optionAttributes = $this->templates->addClass($la_optionAttributes, $la_data['disabledClass'] ?? 'Disabled');
			}

			//Append the formatted template for this option
			$la_options[] = $this->templates->format($ls_template, [
				'attrs' => $this->templates->formatAttributes($la_optionAttributes, ['title', 'value', 'link']),
				'templateVars' => $la_optionAttributes['templateVars'],
				'title' => $lx_escape ? h($la_optionAttributes['title']) : $la_optionAttributes['title'],
				'value' => $lx_escape ? h($la_optionAttributes['value']) : $la_optionAttributes['value'],
				'link' => $la_optionAttributes['link'],
			]);
		}

		return $la_options;
	}
}