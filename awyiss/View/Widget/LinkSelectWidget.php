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
	 * @param array $data
	 * @param ContextInterface $context
	 * @return string
	 */
	public function render(array $data, ContextInterface $context): string {
		$la_data = $this->mergeDefaults($data, $context);

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
	 * @param array $data
	 * @return array|array<string>
	 */
	public function secureFields(array $data): array {
		return [];
	}


	/**
	 * Helper method for deciding what options are selected.
	 *
	 * @param string $key
	 * @param mixed $selected
	 * @return bool
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function isSelected(string $key, mixed $selected): bool {
		if ($selected === null) {
			return false;
		}

		if (!is_array($selected)) {
			$selected = $selected === false ? '0' : $selected;


			return $key === (string)$selected;
		}

		$lb_strict = !is_numeric($key);


		return in_array($key, $selected, $lb_strict);
	}


	/**
	 * Helper method for deciding what options are disabled.
	 *
	 * @param string $key The key to test.
	 * @param array<string>|null $disabled The disabled values.
	 * @return bool
	 */
	protected function isDisabled(string $key, ?array $disabled): bool {
		if ($disabled === null) {
			return false;
		}

		$lb_strict = !is_numeric($key);


		return in_array($key, $disabled, $lb_strict);
	}


	/**
	 * Returns an array of rendered templates for every option in `$data['options']`
	 *
	 * @param array $data
	 * @return array
	 */
	protected function renderOptions(array $data): array {
		$la_data = $data;

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
	 * @param array $data
	 * @return string
	 */
	protected function renderSelectedOption(array $data): string {
		$la_data = $data;

		$la_selectedOption = (array)$la_data['options'][ $la_data['val'] ];


		//Append the formatted template for this option
		return $this->_templates->format('selectedOption', $la_selectedOption);
	}


	/**
	 * @param array $data
	 * @param array $optionAttributes
	 * @return array
	 */
	protected function setGroupLabelTitle(array $data, array $optionAttributes): array {
		$la_optionAttributes = $optionAttributes;

		if (!empty($data['groupLabels'][ $la_optionAttributes['title'] ?: 'general' ])) {
			$ls_groupLabel = $data['groupLabels'][ $la_optionAttributes['title'] ?: 'general' ];
		}
		else {
			$ls_groupLabel = __($data['identifier'] . '_grouplabel_' . ($la_optionAttributes['title'] ?: 'general'));
		}

		$la_optionAttributes['title'] = $ls_groupLabel;


		return $la_optionAttributes;
	}


	/**
	 * Builds the options for the select widget.
	 *
	 * @param array $data
	 * @param mixed $selected
	 * @param mixed $disabled
	 * @param bool $escape
	 * @return array
	 */
	protected function buildOptions(array $data, mixed $selected, mixed $disabled, bool $escape): array {
		$la_options = [];
		foreach ($data['options'] as $lx_key => $lx_value) {
			$la_optionAttributes = $this->createOptionAttributes($lx_key, $lx_value, $data);
			$la_optionAttributes = $this->addClassesToOption($la_optionAttributes, $lx_key, $selected, $disabled, $data);
			$la_options[] = $this->formatOption($la_optionAttributes, $escape, $lx_key, $disabled);
		}

		return $la_options;
	}


	/**
	 * Creates the basic option attributes.
	 *
	 * @param mixed $key
	 * @param mixed $value
	 * @param array $data
	 * @return array
	 */
	protected function createOptionAttributes(mixed &$key, mixed $value, array $data): array {
		$la_optionAttributes = [
			'templateVars' => [],
			'title' => $value,
			'label' => null,
			'levelPrefix' => '',
			'value' => $key,
		];

		if (is_array($value) && isset($value['title'])) {
			$la_optionAttributes = $value;
			if (isset($value['value'])) {
				/** @noinspection PhpVariableNamingConventionInspection */
				$key = $la_optionAttributes['value'];
			}
			else {
				$la_optionAttributes['value'] = $key;
			}

			if ($la_optionAttributes['isGroupLabel'] ?? null === true) {
				$la_optionAttributes = $this->setGroupLabelTitle($data, $la_optionAttributes);
			}
		}

		if (!isset($la_optionAttributes['templateVars'])) {
			$la_optionAttributes['templateVars'] = [];
		}
		$la_optionAttributes['templateVars']['identifier'] = $data['identifier'];

		return $la_optionAttributes;
	}


	/**
	 * Adds classes to the option attributes.
	 *
	 * @param array $optionAttributes
	 * @param mixed $key
	 * @param mixed $selected
	 * @param mixed $disabled
	 * @param array $data
	 * @return array
	 */
	protected function addClassesToOption(array $optionAttributes, mixed $key, mixed $selected, mixed $disabled, array $data): array {
		$la_optionAttributes = $this->_templates->addClass($optionAttributes, 'Item');
		$ls_classText = 'Item-' . Text::slug(Inflector::camelize($la_optionAttributes['title']), ['replacement' => '']);
		$la_optionAttributes = $this->_templates->addClass($la_optionAttributes, $ls_classText);

		if (($la_optionAttributes['id'] ?? false) !== false) {
			$ls_classText = 'Item-' . Text::slug(Inflector::camelize((string)$la_optionAttributes['id']), ['replacement' => '']);
			$la_optionAttributes = $this->_templates->addClass($la_optionAttributes, $ls_classText);

			if (!empty($data['id'])) {
				$la_optionAttributes['id'] = $data['id'] . $ls_classText;
			}
		}

		if ($this->isSelected((string)$key, $selected)) {
			$la_optionAttributes = $this->_templates->addClass($la_optionAttributes, $data['selectedClass'] ?? 'Active');
		}

		if ($this->isDisabled((string)$key, $disabled)) {
			$la_optionAttributes = $this->_templates->addClass($la_optionAttributes, $data['disabledClass'] ?? 'Disabled');
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
	 * @param array $optionAttributes
	 * @param bool $escape
	 * @param mixed $key
	 * @param mixed $disabled
	 * @return string
	 */
	protected function formatOption(array $optionAttributes, bool $escape, mixed $key, mixed $disabled): string {
		$ls_template = 'option';
		if ($this->isDisabled((string)$key, $disabled)) {
			$ls_template = 'optionDisabled';
		}

		if ($optionAttributes['isGroupLabel'] ?? null === true) {
			$ls_template = 'groupLabel';
		}

		return $this->_templates->format($ls_template, [
			'attrs' => $this->_templates->formatAttributes($optionAttributes, ['title', 'value', 'link', 'levelPrefix', 'isGroupLabel', 'groupLabels']),
			'templateVars' => $optionAttributes['templateVars'],
			'title' => $escape ? h($optionAttributes['title']) : $optionAttributes['title'],
			'levelPrefix' => $escape ? h($optionAttributes['levelPrefix']) : $optionAttributes['levelPrefix'],
			'value' => $escape ? h($optionAttributes['value']) : $optionAttributes['value'],
			'link' => $optionAttributes['link'],
		]);
	}
}
