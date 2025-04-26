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
		$la_data = $this->mergeDefaults($data, $context);

		//Render the options
		$la_listItems = $this->renderListItems($la_data);
		$ls_defaultlistItem = $this->renderListItems($la_data, true, count($la_listItems));

		return implode(PHP_EOL, $la_listItems) . PHP_EOL . $ls_defaultlistItem;
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
			$ls_input = $this->_templates->format('input', [
				'type' => 'text',
				'name' => $data['name'] . '[' . $offset . '][key]',
			]);
			$ls_input .= PHP_EOL . $this->_templates->format('input', [
				'type' => 'text',
				'name' => $data['name'] . '[' . $offset . '][value]',
			]);

			return $this->_templates->format('inputListItemDefault', [
				'content' => $ls_input,
			]);
		}

		if (!$data['val']) {
			return [];
		}

		if (!is_array($data['val'])) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$data['val'] = json_decode($data['val'], true) ?: [];
		}

		$la_listItems = [];

		foreach ($data['val'] as $lx_key => $lx_value) {
			$ls_input = $this->_templates->format('input', [
				'type' => 'text',
				'name' => $data['name'] . '[' . $lx_key . '][key]',
				'attrs' => $this->_templates->formatAttributes(['value' => $lx_key]),
			]);

			$ls_input .= PHP_EOL . $this->_templates->format('input', [
				'type' => 'text',
				'name' => $data['name'] . '[' . $lx_key . '][value]',
				'attrs' => $this->_templates->formatAttributes(['value' => $lx_value]),
			]);

			$la_listItems[] = $this->_templates->format('inputListItem', [
				'content' => $ls_input,
			]);
		}

		return $la_listItems;
	}
}
