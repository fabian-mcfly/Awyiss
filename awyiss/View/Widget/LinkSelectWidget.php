<?php declare(strict_types=1);


namespace Awyiss\View\Widget;


use Awyiss\Utility\Inflector;
use Cake\Utility\Text;
use Cake\View\Form\ContextInterface;
use Cake\View\Widget\BasicWidget;
use Traversable;


/**
 * Input widget for creating a link select with links instead of dropdown-options.
 * This class is usually used internally by `\Awyiss\View\Helper\CategoriesHelper`,
 * it but can be used to generate standalone link selects.
 */
class LinkSelectWidget extends BasicWidget {
	/**
	 * @var array<string, mixed>
	 */
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
		$data = $this->mergeDefaults($data, $context);

		//Render the options
		$listItems = $this->renderOptions($data);

		//Escape the name if required
		$name = $data['escape'] ? h($data['identifier']) : $data['identifier'];

		$selectedOption = '-';

		//If the provided value is a key of the provided options, it's the currently selected option
		if (array_key_exists($data['val'], $data['options'])) {
			$selectedOption = $this->renderSelectedOption($data, $data['escape']);
		}

		//Add the label to the templateVars, if it does not already exist
		if (isset($data['label']) && empty($data['templateVars']['label'])) {
			$data['templateVars']['label'] = $data['label'];
		}

		if (isset($data['disabled']) && is_array($data['disabled'])) {
			unset($data['disabled']);
		}

		//Add a new class
		$data = $this->_templates->addClass($data, 'LinkSelect');
		$data = $this->_templates->addClass($data, 'LinkSelect-' . Inflector::camelize(Inflector::underscore($name)));

		//Format the attributes
		$attributes = [
			'class' => $data['class'],
			'id' => $data['id'],
		];
		if (!empty($data['attributes'])) {
			$attributes['attributes'] = $data['attributes'];
		}
		$attributes = $this->_templates->formatAttributes($attributes);


		//Return the formatted template
		return $this->_templates->format('linkSelect', [
			'attrs' => $attributes,
			'identifier' => $name,
			'options' => implode('', $listItems),
			'selectedOption' => $selectedOption,
			'templateVars' => $data['templateVars'],
		]);
	}


	/**
	 * @inheritDoc
	 * @param array $data
	 * @return array<string>
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
	 */
	protected function isSelected(string $key, mixed $selected): bool {
		if ($selected === null) {
			return false;
		}

		if (!is_array($selected)) {
			$selected = $selected === false ? '0' : $selected;


			return $key === (string)$selected;
		}

		$strict = !is_numeric($key);


		return in_array($key, $selected, $strict);
	}


	/**
	 * Helper method for deciding what options are disabled.
	 *
	 * @param string $key The key to test.
	 * @param array|bool|null $disabled The disabled values.
	 * @return bool
	 */
	protected function isDisabled(string $key, array|bool|null $disabled): bool {
		if (!$disabled) {
			return false;
		}

		if ($disabled === true) {
			return true;
		}

		$strict = !is_numeric($key);


		return in_array($key, $disabled, $strict);
	}


	/**
	 * Returns an array of rendered templates for every option in `$data['options']`
	 *
	 * @param array $data
	 * @return array
	 */
	protected function renderOptions(array $data): array {
		//Make sure the options are an array
		if ($data['options'] instanceof Traversable) {
			$data['options'] = iterator_to_array($data['options']);
		}

		//No options? Return an empty array
		if (empty($data['options'])) {
			return [];
		}

		$disabled = null;
		if (
			isset($data['disabled'])
			&& (
				is_array($data['disabled'])
				|| is_bool($data['disabled'])
			)
		) {
			$disabled = $data['disabled'];
		}


		return $this->buildOptions($data, $data['val'] ?? null, $disabled, $data['escape'] ?? true);
	}


	/**
	 * Returns the rendered selected option
	 *
	 * @param array $data
	 * @param bool $escape
	 * @return string
	 */
	protected function renderSelectedOption(array $data, bool $escape = true): string {
		$selectedOption = (array)$data['options'][ $data['val'] ];
		if ($escape) {
			$selectedOption['title'] = h($selectedOption['title']);
		}

		//Append the formatted template for this option
		return $this->_templates->format('linkSelectSelectedOption', $selectedOption);
	}


	/**
	 * @param array $data
	 * @param array $optionAttributes
	 * @return array
	 */
	protected function setGroupLabelTitle(array $data, array $optionAttributes): array {
		if (!empty($data['groupLabels'][ $optionAttributes['title'] ?: 'general' ])) {
			$groupLabel = $data['groupLabels'][ $optionAttributes['title'] ?: 'general' ];
		}
		else {
			$groupLabel = __(Inflector::underscore($data['identifier']) . '_grouplabel_' . ($optionAttributes['title'] ?: 'general'));
		}

		$optionAttributes['title'] = $groupLabel;


		return $optionAttributes;
	}


	/**
	 * Builds the options for the select widget.
	 *
	 * @param array $data
	 * @param mixed $selected
	 * @param array|bool|null $disabled
	 * @param bool $escape
	 * @return array
	 */
	protected function buildOptions(array $data, mixed $selected, array|bool|null $disabled, bool $escape): array {
		$options = [];
		foreach ($data['options'] as $key => $value) {
			$optionAttributes = $this->createOptionAttributes($key, $value, $data);
			$optionAttributes = $this->addClassesToOption($optionAttributes, $key, $selected, $disabled, $data);
			$options[] = $this->formatOption($optionAttributes, $escape, $key, $disabled);
		}

		return $options;
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
		$optionAttributes = [
			'templateVars' => [],
			'title' => $value,
			'label' => null,
			'levelPrefix' => '',
			'value' => $key,
		];

		if (is_array($value) && isset($value['title'])) {
			$optionAttributes = $value;
			if (isset($value['value'])) {
				$key = $optionAttributes['value'];
			}
			else {
				$optionAttributes['value'] = $key;
			}

			if ($optionAttributes['isGroupLabel'] ?? null === true) {
				$optionAttributes = $this->setGroupLabelTitle($data, $optionAttributes);
			}
		}

		if (!isset($optionAttributes['templateVars'])) {
			$optionAttributes['templateVars'] = [];
		}
		$optionAttributes['templateVars']['identifier'] = $data['identifier'];

		return $optionAttributes;
	}


	/**
	 * Adds classes to the option attributes.
	 *
	 * @param array $optionAttributes
	 * @param mixed $key
	 * @param mixed $selected
	 * @param array|bool|null $disabled
	 * @param array $data
	 * @return array
	 */
	protected function addClassesToOption(
		array $optionAttributes,
		mixed $key,
		mixed $selected,
		array|bool|null $disabled,
		array $data
	): array {
		$optionAttributes = $this->_templates->addClass($optionAttributes, 'Item');
		$classText = 'Item-' . Text::slug(Inflector::camelize($optionAttributes['title']), ['replacement' => '']);
		$optionAttributes = $this->_templates->addClass($optionAttributes, $classText);

		if (($optionAttributes['id'] ?? false) !== false) {
			$classText = 'Item-' . Text::slug(Inflector::camelize((string)$optionAttributes['id']), ['replacement' => '']);
			$optionAttributes = $this->_templates->addClass($optionAttributes, $classText);

			if (!empty($data['id'])) {
				$optionAttributes['id'] = $data['id'] . $classText;
			}
		}

		if ($this->isSelected((string)$key, $selected)) {
			$optionAttributes = $this->_templates->addClass($optionAttributes, $data['selectedClass'] ?? 'Active');
		}

		if ($this->isDisabled((string)$key, $disabled)) {
			$optionAttributes = $this->_templates->addClass($optionAttributes, $data['disabledClass'] ?? 'Disabled');
		}

		if ($optionAttributes['isGroupLabel'] ?? null === true) {
			$optionAttributes = $this->_templates->addClass($optionAttributes, 'GroupLabel');
			$optionAttributes = $this->_templates->addClass($optionAttributes, 'GroupLabel' . $classText);
		}
		elseif ($optionAttributes['isGrouped'] ?? false) {
			$optionAttributes = $this->_templates->addClass($optionAttributes, 'IsGrouped');
		}

		unset($optionAttributes['isGrouped']);

		return $optionAttributes;
	}


	/**
	 * Formats the option for output.
	 *
	 * @param array $optionAttributes
	 * @param bool $escape
	 * @param mixed $key
	 * @param array|bool|null $disabled
	 * @return string
	 */
	protected function formatOption(array $optionAttributes, bool $escape, mixed $key, array|bool|null $disabled): string {
		$templateName = 'linkSelectOption';
		if ($this->isDisabled((string)$key, $disabled)) {
			$templateName = 'linkSelectOptionDisabled';
		}

		if ($optionAttributes['isGroupLabel'] ?? null === true) {
			$templateName = 'linkSelectGroupLabel';
		}

		$optionAttributes += [
			'link' => '',
			'levelPrefix' => '',
			'isGroupLabel' => false,
			'groupLabels' => [],
		];

		return $this->_templates->format($templateName, [
			'attrs' => $this->_templates->formatAttributes(
				$optionAttributes,
				['title', 'value', 'link', 'levelPrefix', 'isGroupLabel', 'groupLabels']
			),
			'templateVars' => $optionAttributes['templateVars'],
			'title' => $escape ? h($optionAttributes['title']) : $optionAttributes['title'],
			'levelPrefix' => $escape ? h($optionAttributes['levelPrefix']) : $optionAttributes['levelPrefix'],
			'value' => $escape ? h($optionAttributes['value']) : $optionAttributes['value'],
			'link' => $optionAttributes['link'],
		]);
	}
}
