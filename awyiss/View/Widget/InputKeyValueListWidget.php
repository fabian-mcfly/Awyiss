<?php declare(strict_types=1);


namespace Awyiss\View\Widget;


use Cake\View\Form\ContextInterface;
use Cake\View\Widget\BasicWidget;


/**
 * Input widget for creating a list of key-value inputs.
 * Items should be draggable to reorder.
 */
class InputKeyValueListWidget extends BasicWidget {
	/**
	 * @inheritDoc
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
	 */
	public function render(array $data, ContextInterface $context): string {
		$data = $this->mergeDefaults($data, $context);

		//Render the options
		$listItems = $this->renderListItems($data);
		$defaultlistItem = $this->renderListItems($data, true, count($listItems));

		return implode(PHP_EOL, $listItems) . PHP_EOL . $defaultlistItem;
	}


	/**
	 * Render the list items
	 *
	 * @param array $data
	 * @param bool $default
	 * @param int $offset
	 * @return array|string
	 */
	public function renderListItems(array $data, bool $default = false, int $offset = 0): array|string {
		if ($default) {
			$input = $this->_templates->format('input', [
				'type' => 'text',
				'name' => $data['name'] . '[' . $offset . '][key]',
			]);
			$input .= PHP_EOL . $this->_templates->format('input', [
				'type' => 'text',
				'name' => $data['name'] . '[' . $offset . '][value]',
			]);

			return $this->_templates->format('inputListItemDefault', [
				'content' => $input,
			]);
		}

		if (!$data['val']) {
			return [];
		}

		if (!is_array($data['val'])) {
			$data['val'] = json_decode($data['val'], true) ?: [];
		}

		$listItems = [];

		foreach ($data['val'] as $key => $value) {
			$input = $this->_templates->format('input', [
				'type' => 'text',
				'name' => $data['name'] . '[' . $key . '][key]',
				'attrs' => $this->_templates->formatAttributes(['value' => $key]),
			]);

			$input .= PHP_EOL . $this->_templates->format('input', [
				'type' => 'text',
				'name' => $data['name'] . '[' . $key . '][value]',
				'attrs' => $this->_templates->formatAttributes(['value' => $value]),
			]);

			$listItems[] = $this->_templates->format('inputListItem', [
				'content' => $input,
			]);
		}

		return $listItems;
	}
}
