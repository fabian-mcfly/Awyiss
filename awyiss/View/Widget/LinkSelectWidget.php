<?php declare(strict_types=1);


namespace Awyiss\View\Widget;


use Cake\Utility\Inflector;
use Cake\View\Form\ContextInterface;
use Cake\View\StringTemplate;
use Cake\View\Widget\BasicWidget;
use Traversable;


/**
 * Input widget for creating a custom select with links instead of dropdown-options.
 *
 * This class is usually used internally by `\Awyiss\View\Helper\CategoriesHelper`,
 * it but can be used to generate standalone custom selects.
 */
class LinkSelectWidget extends BasicWidget {
	//protected array $defaults = [ //as soon as BasicWidget uses a type definition
	protected array $defaults = [
		'aggregationLabel' => 'all',
		'aggregationLink' => '',
		'aggregationKey' => 'all',
		'class' => '',
		'disabled' => FALSE,
		'escape' => TRUE,
		'includeAggregation' => TRUE,
		'includeUnassigned' => FALSE,
		'label' => '',
		'identifier' => '',
		'options' => [],
		'templateVars' => [],
		'unassignedLabel' => 'all',
		'unassignedLink' => '',
		'unassignedKey' => 'all',
		'val' => NULL,
	];


	/**
	 * @inheritDoc
	 *
	 * @param array            $aa_data
	 * @param ContextInterface $ao_context
	 *
	 * @return string
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function render (array $aa_data, ContextInterface $ao_context): string {
		$la_data = $this->mergeDefaults($aa_data, $ao_context);

		//Render the options
		$la_listItems = $this->renderOptions($la_data);

		//Escape the name if required
		$ls_name = $la_data['escape'] ? h($la_data['identifier']) : $la_data['identifier'];

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
			$ls_selectedOption = $this->renderSelectedOption($la_data);
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
		$la_blocklistedAttributes = [
			'aggregationLabel',
			'aggregationLink',
			'aggregationKey',
			'combinator',
			'escape',
			'identifier',
			'label',
			'options',
			'unassignedLabel',
			'unassignedLink',
			'unassignedKey',
			'printer',
			'val',
		];
		$la_data = array_diff_key($la_data, array_flip($la_blocklistedAttributes));
		if (isset($la_data['disabled']) && is_array($la_data['disabled'])) {
			unset($la_data['disabled']);
		}

		//Add a new class
		$la_data = $this->_templates->addClass($la_data, 'CustomSelect');
		$la_data = $this->_templates->addClass($la_data, 'CustomSelect-' . Inflector::camelize(Inflector::underscore($ls_name)));

		//Format the attributes
		$la_attributes = $this->_templates->formatAttributes($la_data);

		//Return the formatted template
		return $this->_templates->format('linkSelect', [
			'attrs' => $la_attributes,
			'identifier' => $ls_name,
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
	 * @param string        $as_key      The key to test.
	 * @param string[]|NULL $aa_disabled The disabled values.
	 *
	 * @return bool
	 */
	protected function isDisabled (string $as_key, ?array $aa_disabled): bool {
		if ($aa_disabled === NULL) {
			return FALSE;
		}

		$lb_strict = ! is_numeric($as_key);

		return in_array($as_key, $aa_disabled, $lb_strict);
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
				'identifier' => $la_data['identifier'],
				'link' => $la_data['unassignedLink'],
			],
		];

		$la_attributes = $this->_templates->addClass($la_attributes, 'Item');
		$la_attributes = $this->_templates->addClass($la_attributes, 'Item-Unassigned');
		if ($this->isSelected((string) $la_data['unassignedKey'], $la_data['val'])) {
			$la_attributes = $this->_templates->addClass($la_attributes, $la_data['selectedClass'] ?? 'Active');
		}

		return $this->_templates->format('unassignedOption', [
			'attrs' => $this->_templates->formatAttributes($la_attributes),
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
				'identifier' => $la_data['identifier'],
				'link' => $la_data['aggregationLink'],
			],
		];

		$la_attributes = $this->_templates->addClass($la_attributes, 'Item');
		$la_attributes = $this->_templates->addClass($la_attributes, 'Item-Aggregation');
		if ($this->isSelected((string) $la_data['aggregationKey'], $la_data['val'])) {
			$la_attributes = $this->_templates->addClass($la_attributes, $la_data['selectedClass'] ?? 'Active');
		}

		return $this->_templates->format('aggregationOption', [
			'attrs' => $this->_templates->formatAttributes($la_attributes),
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
				'label' => NULL,
				'levelPrefix' => '',
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
			$la_optionAttributes['templateVars']['identifier'] = $la_data['identifier'];

			//Add a class 'Item' and, if the value of the option is selected, 'Active' as well
			$la_optionAttributes = $this->_templates->addClass($la_optionAttributes, 'Item');
			$la_optionAttributes = $this->_templates->addClass($la_optionAttributes, 'Item-' . Inflector::camelize($la_optionAttributes['title']));
			if ($this->isSelected((string) $lx_key, $lx_selected)) {
				$la_optionAttributes = $this->_templates->addClass($la_optionAttributes, $la_data['selectedClass'] ?? 'Active');
			}

			//Depending on the status, use the `option`- or `optionDisabled`-template to render the option
			$ls_template = 'option';
			if ($this->isDisabled((string) $lx_key, $lx_disabled)) {
				$ls_template = 'optionDisabled';
				//If the option is disabled, add 'Disabled' to the class
				$la_optionAttributes = $this->_templates->addClass($la_optionAttributes, $la_data['disabledClass'] ?? 'Disabled');
			}

			//Append the formatted template for this option
			$la_options[] = $this->_templates->format($ls_template, [
				'attrs' => $this->_templates->formatAttributes($la_optionAttributes, ['title', 'value', 'link', 'levelPrefix']),
				'templateVars' => $la_optionAttributes['templateVars'],
				'title' => $lx_escape ? h($la_optionAttributes['title']) : $la_optionAttributes['title'],
				'levelPrefix' => $lx_escape ? h($la_optionAttributes['levelPrefix']) : $la_optionAttributes['levelPrefix'],
				'value' => $lx_escape ? h($la_optionAttributes['value']) : $la_optionAttributes['value'],
				'link' => $la_optionAttributes['link'],
			]);
		}

		return $la_options;
	}


	/**
	 * Returns the rendered selected option
	 *
	 * @param array $aa_data
	 *
	 * @return string
	 */
	protected function renderSelectedOption (array $aa_data): string {
		$la_data = $aa_data;

		$la_selectedOption = (array) $la_data['options'][ $la_data['val'] ];

		//Append the formatted template for this option
		return $this->_templates->format('selectedOption', $la_selectedOption);
	}
}
