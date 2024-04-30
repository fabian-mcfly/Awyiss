<?php declare(strict_types=1);


namespace Awyiss\View\Widget;


use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\View\Form\ContextInterface;
use Cake\View\Widget\BasicWidget;
use Traversable;


/**
 * Input widget for creating a custom select with links instead of dropdown-options.
 * This class is usually used internally by `\Awyiss\View\Helper\CategoriesHelper`,
 * it but can be used to generate standalone custom selects.
 */
class LinkSelectWidget extends BasicWidget {
	protected array $defaults = [
		'class' => '',
		'disabled' => false,
		'escape' => true,
		'label' => '',
		'identifier' => '',
		'options' => [],
		'templateVars' => [],
		'val' => null,
	];


	/**
	 * @inheritDoc
	 * @param array $aa_data
	 * @param ContextInterface $ao_context
	 * @return string
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function render(array $aa_data, ContextInterface $ao_context): string {
		$la_data = $this->mergeDefaults($aa_data, $ao_context);

		//Render the options
		$la_listItems = $this->renderOptions($la_data);

		//Escape the name if required
		$ls_name = $la_data['escape'] ? h($la_data['identifier']) : $la_data['identifier'];

		$ls_selectedOption = '-';

		//If the provided value is a key of the provided options, it's the currently selected option
		if (array_key_exists($la_data['val'], $la_data['options'])) {
			$ls_selectedOption = $this->renderSelectedOption($la_data);
		}

		//Add the label to the templateVars, if it does not already exist
		if (isset($la_data['label']) && empty($la_data['templateVars']['label'])) {
			$la_data['templateVars']['label'] = $la_data['label'];
		}

		if (isset($la_data['disabled']) && is_array($la_data['disabled'])) {
			unset($la_data['disabled']);
		}

		//Add a new class
		$la_data = $this->_templates->addClass($la_data, 'LinkSelect');
		$la_data = $this->_templates->addClass($la_data, 'LinkSelect-' . Inflector::camelize(Inflector::underscore($ls_name)));

		//Format the attributes
		$la_attributes = [
			'class' => $la_data['class'],
			'id' => $la_data['id'],
		];
		if (!empty($la_data['attributes'])) {
			$la_attributes['attributes'] = $la_data['attributes'];
		}
		$la_attributes = $this->_templates->formatAttributes($la_attributes);


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
	 * @param array $aa_data
	 * @return array|array<string>
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function secureFields(array $aa_data): array {
		return [];
	}


	/**
	 * Helper method for deciding what options are selected.
	 *
	 * @param string $as_key
	 * @param mixed $ax_selected
	 * @return bool
	 */
	protected function isSelected(string $as_key, mixed $ax_selected): bool {
		if ($ax_selected === null) {
			return false;
		}

		if (!is_array($ax_selected)) {
			$ax_selected = $ax_selected === false ? '0' : $ax_selected;


			return $as_key === (string)$ax_selected;
		}

		$lb_strict = !is_numeric($as_key);


		return in_array($as_key, $ax_selected, $lb_strict);
	}


	/**
	 * Helper method for deciding what options are disabled.
	 *
	 * @param string $as_key The key to test.
	 * @param array<string>|null $aa_disabled The disabled values.
	 * @return bool
	 */
	protected function isDisabled(string $as_key, ?array $aa_disabled): bool {
		if ($aa_disabled === null) {
			return false;
		}

		$lb_strict = !is_numeric($as_key);


		return in_array($as_key, $aa_disabled, $lb_strict);
	}


	/**
	 * Returns an array of rendered templates for every option in `$aa_data['options']`
	 *
	 * @param array $aa_data
	 * @return array
	 */
	protected function renderOptions(array $aa_data): array {
		$la_data = $aa_data;

		//Make sure the options are an array
		if ($la_data['options'] instanceof Traversable) {
			$la_data['options'] = iterator_to_array($la_data['options']);
		}

		//No options? Return an empty array
		if (empty($la_data['options'])) {
			return [];
		}

		$lx_disabled = null;
		if (isset($la_data['disabled']) && is_array($la_data['disabled'])) {
			$lx_disabled = $la_data['disabled'];
		}


		return $this->buildOptions($la_data, $la_data['val'] ?? null, $lx_disabled, $la_data['escape'] ?? true);
	}


	/**
	 * Returns the rendered selected option
	 *
	 * @param array $aa_data
	 * @return string
	 */
	protected function renderSelectedOption(array $aa_data): string {
		$la_data = $aa_data;

		$la_selectedOption = (array)$la_data['options'][ $la_data['val'] ];


		//Append the formatted template for this option
		return $this->_templates->format('selectedOption', $la_selectedOption);
	}


	/**
	 * @param array $aa_data
	 * @param array $aa_optionAttributes
	 * @return array
	 */
	protected function setGroupLabelTitle(array $aa_data, array $aa_optionAttributes): array {
		$la_optionAttributes = $aa_optionAttributes;

		if (!empty($aa_data['groupLabels'][ $la_optionAttributes['title'] ?: 'general' ])) {
			$ls_groupLabel = $aa_data['groupLabels'][ $la_optionAttributes['title'] ?: 'general' ];
		}
		else {
			$ls_groupLabel = __($aa_data['identifier'] . '_grouplabel_' . ($la_optionAttributes['title'] ?: 'general'));
		}

		$la_optionAttributes['title'] = $ls_groupLabel;


		return $la_optionAttributes;
	}


	/**
	 * Builds the options for the select widget.
	 *
	 * @param array $aa_data
	 * @param mixed $ax_selected
	 * @param mixed $ax_disabled
	 * @param bool $ax_escape
	 * @return array
	 */
	protected function buildOptions(array $aa_data, mixed $ax_selected, mixed $ax_disabled, bool $ax_escape): array {
		$la_options = [];
		foreach ($aa_data['options'] as $lx_key => $lx_value) {
			$la_optionAttributes = $this->createOptionAttributes($lx_key, $lx_value, $aa_data);
			$la_optionAttributes = $this->addClassesToOption($la_optionAttributes, $lx_key, $ax_selected, $ax_disabled, $aa_data);
			$la_options[] = $this->formatOption($la_optionAttributes, $ax_escape, $lx_key, $ax_disabled);
		}

		return $la_options;
	}


	/**
	 * Creates the basic option attributes.
	 *
	 * @param mixed $ax_key
	 * @param mixed $ax_value
	 * @param array $aa_data
	 * @return array
	 */
	protected function createOptionAttributes(mixed &$ax_key, mixed $ax_value, array $aa_data): array {
		$la_optionAttributes = [
			'templateVars' => [],
			'title' => $ax_value,
			'label' => null,
			'levelPrefix' => '',
			'value' => $ax_key,
		];

		if (is_array($ax_value) && isset($ax_value['title'])) {
			$la_optionAttributes = $ax_value;
			if (isset($ax_value['value'])) {
				$ax_key = $la_optionAttributes['value'];
			}
			else {
				$la_optionAttributes['value'] = $ax_key;
			}

			if ($la_optionAttributes['isGroupLabel'] ?? null === true) {
				$la_optionAttributes = $this->setGroupLabelTitle($aa_data, $la_optionAttributes);
			}
		}

		if (!isset($la_optionAttributes['templateVars'])) {
			$la_optionAttributes['templateVars'] = [];
		}
		$la_optionAttributes['templateVars']['identifier'] = $aa_data['identifier'];

		return $la_optionAttributes;
	}


	/**
	 * Adds classes to the option attributes.
	 *
	 * @param array $aa_optionAttributes
	 * @param mixed $ax_key
	 * @param mixed $ax_selected
	 * @param mixed $ax_disabled
	 * @param array $aa_data
	 * @return array
	 */
	protected function addClassesToOption(array $aa_optionAttributes, mixed $ax_key, mixed $ax_selected, mixed $ax_disabled, array $aa_data): array {
		$la_optionAttributes = $this->_templates->addClass($aa_optionAttributes, 'Item');
		$ls_classText = 'Item-' . Text::slug(Inflector::camelize($la_optionAttributes['title']), ['replacement' => '']);
		$la_optionAttributes = $this->_templates->addClass($la_optionAttributes, $ls_classText);

		if (($la_optionAttributes['id'] ?? false) !== false) {
			$ls_classText = 'Item-' . Text::slug(Inflector::camelize((string)$la_optionAttributes['id']), ['replacement' => '']);
			$la_optionAttributes = $this->_templates->addClass($la_optionAttributes, $ls_classText);

			if (!empty($aa_data['id'])) {
				$la_optionAttributes['id'] = $aa_data['id'] . $ls_classText;
			}
		}

		if ($this->isSelected((string)$ax_key, $ax_selected)) {
			$la_optionAttributes = $this->_templates->addClass($la_optionAttributes, $aa_data['selectedClass'] ?? 'Active');
		}

		if ($this->isDisabled((string)$ax_key, $ax_disabled)) {
			$la_optionAttributes = $this->_templates->addClass($la_optionAttributes, $aa_data['disabledClass'] ?? 'Disabled');
		}

		if ($la_optionAttributes['isGroupLabel'] ?? null === true) {
			$la_optionAttributes = $this->_templates->addClass($la_optionAttributes, 'GroupLabel');
			$la_optionAttributes = $this->_templates->addClass($la_optionAttributes, 'GroupLabel' . $ls_classText);
		}
		elseif ($la_optionAttributes['isGrouped'] ?? false) {
			$la_optionAttributes = $this->_templates->addClass($la_optionAttributes, 'IsGrouped');
		}

		unset($la_optionAttributes['isGrouped']);

		return $la_optionAttributes;
	}


	/**
	 * Formats the option for output.
	 *
	 * @param array $aa_optionAttributes
	 * @param bool $ax_escape
	 * @param mixed $ax_key
	 * @param mixed $ax_disabled
	 * @return string
	 */
	protected function formatOption(array $aa_optionAttributes, bool $ax_escape, mixed $ax_key, mixed $ax_disabled): string {
		$ls_template = 'option';
		if ($this->isDisabled((string)$ax_key, $ax_disabled)) {
			$ls_template = 'optionDisabled';
		}

		if ($aa_optionAttributes['isGroupLabel'] ?? null === true) {
			$ls_template = 'groupLabel';
		}

		return $this->_templates->format($ls_template, [
			'attrs' => $this->_templates->formatAttributes($aa_optionAttributes, ['title', 'value', 'link', 'levelPrefix', 'isGroupLabel', 'groupLabels']),
			'templateVars' => $aa_optionAttributes['templateVars'],
			'title' => $ax_escape ? h($aa_optionAttributes['title']) : $aa_optionAttributes['title'],
			'levelPrefix' => $ax_escape ? h($aa_optionAttributes['levelPrefix']) : $aa_optionAttributes['levelPrefix'],
			'value' => $ax_escape ? h($aa_optionAttributes['value']) : $aa_optionAttributes['value'],
			'link' => $aa_optionAttributes['link'],
		]);
	}
}
