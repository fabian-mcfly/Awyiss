<?php declare(strict_types=1);


namespace Awyiss\View\Widget;


use Cake\View\StringTemplate;
use Traversable;


class LinkSelectWidget implements \Cake\View\Widget\WidgetInterface {
	protected array $defaults = [
		'aggregationLabel' => 'all',
		'aggregationKey' => 'all',
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
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function render (array $aa_data, \Cake\View\Form\ContextInterface $ao_context): string {
		$la_data = $aa_data + $this->defaults;

		$la_listItems = $this->renderOptions($la_data);
		$ls_name = $la_data['escape'] ? h($la_data['name']) : $la_data['name'];

		if ($la_data['includeUnassigned']) {
			array_unshift($la_listItems, $this->renderUnassignedOption($la_data));
		}

		if ($la_data['includeAggregation']) {
			array_unshift($la_listItems, $this->renderAggregationOption($la_data));
		}

		$ls_selectedOption = '-';
		if (array_key_exists($la_data['val'], $la_data['options'])) {
			$lx_selectedOption = $la_data['options'][ $la_data['val'] ];
			if (is_array($lx_selectedOption) && isset($lx_selectedOption['title'])) $lx_selectedOption = $lx_selectedOption['title'];
			$ls_selectedOption = $la_data['escape'] ? h($lx_selectedOption) : $lx_selectedOption;
		}
		elseif ($la_data['includeUnassigned'] && $this->isSelected((string) $la_data['unassignedKey'], $la_data['val'])) {
			$ls_selectedOption = $la_data['unassignedLabel'];
		}
		elseif ($la_data['includeAggregation'] && $this->isSelected((string) $la_data['aggregationKey'], $la_data['val'])) {
			$ls_selectedOption = $la_data['aggregationLabel'];
		}

		if (isset($la_data['label']) && empty($la_data['templateVars']['label'])) {
			$la_data['templateVars']['label'] = $la_data['label'];
		}

		unset($la_data['name'], $la_data['options'], $la_data['escape'], $la_data['disabled'], $la_data['label'], $la_data['val'],
			$la_data['aggregationLabel'], $la_data['aggregationKey'], $la_data['unassignedLabel'], $la_data['unassignedKey']);
		if (isset($la_data['disabled']) && is_array($la_data['disabled'])) {
			unset($la_data['disabled']);
		}

		$la_data = $this->templates->addClass($la_data, 'CustomSelect');

		$la_attributes = $this->templates->formatAttributes($la_data);

		return $this->templates->format('linkSelect', [
			'attrs' => $la_attributes,
			'name' => $ls_name,
			'options' => implode('', $la_listItems),
			'selectedOption' => $ls_selectedOption,
			'templateVars' => $la_data['templateVars'],
		]);
	}


	/**
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function secureFields (array $aa_data): array {
		return [];
	}


	/**
	 * Helper method for deciding what options are selected.
	 *
	 * @param string $as_key The key to test.
	 * @param string[]|string|int|false|null $ax_selected The selected values.
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
	 * @param string[]|null $la_disabled The disabled values.
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


	protected function renderUnassignedOption ($aa_data): string {
		$la_data = $aa_data;

		$lx_escape = $la_data['escape'] ?? TRUE;

		$la_attributes = [
			'class' => 'Item',
			'templateVars' => [
				'name' => $la_data['name'],
			]
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


	protected function renderAggregationOption ($aa_data): string {
		$la_data = $aa_data;

		$lx_escape = $la_data['escape'] ?? TRUE;

		$la_attributes = [
			'class' => 'Item',
			'templateVars' => [
				'name' => $la_data['name'],
			]
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


	protected function renderOptions ($aa_data): array {
		$la_data = $aa_data;

		if ($la_data['options'] instanceof Traversable) {
			$la_data['options'] = iterator_to_array($la_data['options']);
		}

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
			// Basic options
			$la_optionAttributes = [
				'templateVars' => [],
				'title' => $lx_value,
				'value' => $lx_key,
			];

			if (is_array($lx_value) && isset($lx_value['title'])) {
				$la_optionAttributes = $lx_value;

				if (isset($lx_value['value'])) {
					$lx_key = $la_optionAttributes['value'];
				}
				else {
					$la_optionAttributes['value'] = $lx_key;
				}
			}

			if (!isset($la_optionAttributes['templateVars'])) {
				$la_optionAttributes['templateVars'] = [];
			}
			$la_optionAttributes['templateVars']['name'] = $la_data['name'];

			$la_optionAttributes = $this->templates->addClass($la_optionAttributes, 'Item');
			if ($this->isSelected((string) $lx_key, $lx_selected)) {
				$la_optionAttributes = $this->templates->addClass($la_optionAttributes, $la_data['selectedClass'] ?? 'Active');
			}

			$ls_template = 'option';
			if ($this->isDisabled((string) $lx_key, $lx_disabled)) {
				$ls_template = 'optionDisabled';
				$la_optionAttributes = $this->templates->addClass($la_optionAttributes, $la_data['disabledClass'] ?? 'Disabled');
			}

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