<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Utility\Inflector;
use Awyiss\View\StringTemplate;
use Cake\Collection\CollectionInterface;
use Cake\Collection\Iterator\TreeIterator;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Paging\PaginatedResultSet;
use Cake\View\Helper;
use Cake\View\StringTemplateTrait;
use Cake\View\View;
use Cake\View\Widget\WidgetLocator;
use RuntimeException;


/**
 * Helper class that provides methods related to the Categories-logic in the views
 *
 * @property \Awyiss\View\Helper\FormHelper $Form
 * @property \Awyiss\View\Helper\PaginatorHelper $Paginator
 * @property \Awyiss\View\Helper\UrlHelper $Url
 */
class CategoriesHelper extends Helper {
	use StringTemplateTrait;


	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [ // phpcs:ignore
		'templateClass' => StringTemplate::class,
		'templates' => [],
	];
	/**
	 * Default templates
	 *
	 * @var array<string, string>
	 */
	protected array $defaultTemplates = [
		// Link select element, used for selecting from a list of links.
		'linkSelect' => '<div{{attrs}}><label class="Label" tabindex="0"><strong>{{label}}:</strong> {{selectedOption}}</label>'
			. '<ul class="List">{{options}}</ul></div>',
		// Link select option element
		'linkSelectOption' => '<li{{attrs}}><a href="{{link}}" title="{{title}}">{{levelPrefix}}{{title}}</a></li>',
	];
	/**
	 * Default widgets
	 *
	 * @var array<string, array<string>>
	 */
	protected array $defaultWidgets = [
		'linkSelect' => ['LinkSelect'],
	];
	/**
	 * @inheritDoc
	 */
	protected array $helpers = ['Form', 'Url'];
	/**
	 * Locator for input widgets.
	 *
	 * @var WidgetLocator
	 */
	protected WidgetLocator $widgetLocator;


	/**
	 * @param View $view
	 * @param array $config
	 */
	public function __construct(View $view, array $config = []) {
		//Add the default widgets to those that may be present in the provided config
		$widgets = $this->defaultWidgets;
		if (isset($config['widgets'])) {
			if (is_string($config['widgets'])) {
				$config['widgets'] = (array)$config['widgets'];
			}
			$widgets = $config['widgets'] + $widgets;
			unset($config['widgets']);
		}

		parent::__construct($view, $config);

		$this->templater()->add($this->defaultTemplates);

		$widgetLocator = new WidgetLocator($this->templater(), $this->_View, $widgets);
		$this->setWidgetLocator($widgetLocator);
	}


	/**
	 * Get the widget locator.
	 *
	 * @return \Cake\View\Widget\WidgetLocator
	 */
	public function getWidgetLocator(): WidgetLocator {
		return $this->widgetLocator;
	}


	/**
	 * Set the widget locator the helper will use.
	 *
	 * @param WidgetLocator $widgetLocator The locator instance to set.
	 * @return $this
	 */
	public function setWidgetLocator(WidgetLocator $widgetLocator): static {
		$this->widgetLocator = $widgetLocator;


		return $this;
	}


	/**
	 * Render a named widget.
	 *
	 * This is a lower level method. For built-in widgets, you should be using
	 * methods like `control`, `select`, and `filter`.
	 *
	 * @param string $name The name of the widget. e.g. 'control'.
	 * @param array $data The data to render.
	 * @return string
	 */
	public function widget(string $name, array $data = []): string {
		$widget = $this->getWidgetLocator()->get($name);


		/**
		 * This call with $this->Form->context() is hacky but since the WidgetInterface requires a ContextInterface,
		 * there's no way around it; except setting an own context which is total overkill since this Helper doesn't require one itself.
		 * But hey, we're using the FormHelper and this has a context, so we don't care
		 *
		 *
		 *        ¯\_(ツ)_/¯
		 */
		return $widget->render($data, $this->Form->context());
	}


	/**
	 * Generates a form control element complete with label and wrapper div.
	 * ### Options
	 * See each field type method for more information. Any options that are part of
	 * $attributes or $options for the different **type** methods can be included in `$options` for control().
	 * Additionally, any unknown keys that are not in the list below, or part of the selected type's options
	 * will be treated as a regular HTML attribute for the generated input.
	 * - `groupBy` - If set to a field name, the options will be grouped by the value of that field.
	 * Provide the `groupLabels` option to customize the labels of the groups.
	 * - `groupLabels` - An array of group labels. The keys are the group values, the values are the labels.
	 *
	 * @param string $identifier
	 * @param array $attributes
	 * @return string
	 * @throws \Exception
	 * @see \Cake\View\Helper\FormHelper::control()
	 */
	public function control(string $identifier, array $attributes = []): string {
		$identifier = Inflector::variable($identifier);
		$config = $this->getConfiguration($identifier);

		if (empty($config) || !$config['enabled']) {
			return '';
		}

		if (empty($config['field'])) {
			throw new RuntimeException('Cannot build categories control without field.');
		}

		if (empty($config['identifier'])) {
			$config['identifier'] = $identifier;
		}

		$fieldName = Inflector::variable($config['field']);

		$config = ['field' => $fieldName] + $config;
		$attributes += [
			'isCategory' => true,
			'disabled' => false,
			'empty' => false,
			'groupBy' => null,
			'groupLabels' => [],
			'label' => $this->Form->labelTextFromFieldname($fieldName),
			'type' => 'select',
			'val' => null,
		];

		if (!isset($attributes['val'])) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$attributes['val'] = $this->Form
				->context()
				->entity()
				->get($fieldName) ?? $this->getSelectedCategory($identifier, true);
		}

		if (empty($attributes['options'])) {
			$attributes['options'] = $this->getCategories($config['identifier'], true);
		}

		$options = $attributes['options'];
		unset($attributes['options']);
		if ($options) {
			$groupBy = $attributes['groupBy'] ?? null;

			if ($groupBy === null && ($config['includeParentCategories'] ?? false) === true) {
				$groupBy = '_parents';
			}

			if ($groupBy) {
				if (!$options instanceof CollectionInterface) {
					$options = collection($options);
				}

				$groupedOptions = $this->groupOptions($options, $groupBy, $attributes);

				$attributes['options'] = [];
				foreach ($groupedOptions as $key => $groupOptions) {
					$groupLabel = $key ?: 'general';
					$groupLabel = $attributes['groupLabels'][ $groupLabel ] ?? __(
						Inflector::underscore($fieldName) . '_grouplabel_' . $groupLabel
					);

					$attributes['options'][ $groupLabel ] = $this->formatOptions($groupOptions, $attributes + ['buildNested' => true]);
				}
			}
			else {
				$attributes['options'] = $this->formatOptions($options, $attributes);
			}
		}

		unset($attributes['groupBy']);
		unset($attributes['groupLabels']);

		return $this->Form->control($fieldName, $attributes);
	}


	/**
	 * Returns a filter element, using the `linkSelect`-template with the
	 * category config set as view vars
	 *
	 * ### Options:
	 * - `aggregationLabel` The label of an additional option that's displayed when the `allowAggregation`-option is true.
	 * - `aggregationKey` The value of an additional option that's used for showing the aggregation of all categories.
	 * - `allowAggregation` Boolean value whether to include the aggregation of all categories.
	 * - `allowUnassigned` Boolean value whether to include an option to show items without any category.
	 * - `disabled` Boolean value or an array containing the values that should be disabled in the filter.
	 * - `escape` Boolean value whether to escape HTML entities.
	 * - `label` The label to display in the filter.
	 * - `name` The name to be used as a parameter in the resulting filter urls.
	 * - `templateVars` Additional template variables.
	 * - `unassignedLabel` The label of an additional option that's displayed when the `allowUnassigned`-option is true.
	 * - `unassignedKey` The value of an additional option that's used for showing items without any category.
	 * - `val` The value of the currently selected category.
	 *
	 * @param string $identifier
	 * @param iterable|null $options
	 * @param array $attributes
	 * @return string
	 */
	public function filter(string $identifier, ?iterable $options = null, array $attributes = []): string {
		$identifier = Inflector::variable($identifier);
		$config = $this->getConfiguration($identifier);

		if (empty($config) || ($config['enabled'] ?? false) === false) {
			return '';
		}

		$attributes += $config;
		$attributes += [
			'aggregationLabel' => __f(Inflector::underscore($identifier) . '_filter_all', __d('System', 'all')),
			'aggregationKey' => 'all',
			'allowAggregation' => false,
			'allowUnassigned' => false,
			'disabled' => false,
			'escape' => true,
			'id' => true,
			'identifier' => $identifier,
			'label' => __(Inflector::underscore($identifier) . '_filter_label'),
			'levelPrefix' => '- ',
			'unassignedLabel' => __f(Inflector::underscore($identifier) . '_filter_unassigned', __('unassigned')),
			'unassignedKey' => 'unassigned',
			'uriParam' => $identifier,
			'val' => $this->getSelectedCategory($identifier),
		];

		if (isset($attributes['id']) && $attributes['id'] === true) {
			$attributes['id'] = 'LinkSelect-' . Inflector::camelize($this->_domId($identifier), '-');
		}

		$attributes['options'] = $options;

		if (!$attributes['options']) {
			$attributes['options'] = $this->getCategories($identifier, true);
		}

		$attributes = $this->buildOptions($attributes, $config);

		return $this->widget('linkSelect', $attributes);
	}


	/**
	 * Creates a link select element using the `linkSelect`-template
	 * with only the provided options and attributes
	 *
	 *  ### Options:
	 *  - `aggregationLabel` The label of an additional option that's displayed when the `allowAggregation`-option is true.
	 *  - `aggregationKey` The value of an additional option that's used for showing the aggregation of all categories.
	 *  - `allowAggregation` Boolean value whether to include the aggregation of all categories.
	 *  - `allowUnassigned` Boolean value whether to include an option to show items without any category.
	 *  - `disabled` Boolean value or an array containing the values that should be disabled in the filter.
	 *  - `escape` Boolean value whether to escape HTML entities.
	 *  - `label` The label to display in the filter.
	 *  - `name` The name to be used as a parameter in the resulting filter urls.
	 *  - `templateVars` Additional template variables.
	 *  - `unassignedLabel` The label of an additional option that's displayed when the `allowUnassigned`-option is true.
	 *  - `unassignedKey` The value of an additional option that's used for showing items without any category.
	 *  - `val` The value of the currently selected category.
	 *
	 * @param string $identifier
	 * @param iterable|null $options
	 * @param array $attributes
	 * @return string
	 */
	public function linkSelect(string $identifier, ?iterable $options = null, array $attributes = []): string {
		$identifier = Inflector::variable($identifier);

		$attributes += [
			'aggregationLabel' => __(Inflector::underscore($identifier) . '_filter_all'),
			'aggregationKey' => 'all',
			'allowAggregation' => false,
			'allowUnassigned' => false,
			'disabled' => false,
			'escape' => true,
			'id' => true,
			'identifier' => $identifier,
			'label' => __(Inflector::underscore($identifier) . '_filter_label'),
			'levelPrefix' => '- ',
			'unassignedLabel' => __(Inflector::underscore($identifier) . '_filter_unassigned'),
			'unassignedKey' => 'unassigned',
			'uriParam' => $identifier,
			'val' => null,
		];


		if (isset($attributes['id']) && $attributes['id'] === true) {
			$attributes['id'] = 'LinkSelect-' . Inflector::camelize($this->_domId($identifier), '-');
		}

		$attributes['options'] = $options;
		$attributes = $this->buildOptions($attributes);

		return $this->widget('linkSelect', $attributes);
	}


	/**
	 * @param string $identifier
	 * @return array
	 */
	public function getConfiguration(string $identifier): array {
		$name = Inflector::variable(Inflector::pluralize($identifier));
		$categories = $this->getView()->get('_categories', [])[ $name ] ?? [];

		if ($categories instanceof PaginatedResultSet) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$categories = $categories->items()->toArray();
		}

		return $categories['config'] ?? [];
	}


	/**
	 * For a list of given names, return the first found `categories.raw` or `categories.simple`-value in the `categories`-view var
	 *
	 * @param string $identifier
	 * @param bool $preferRaw
	 * @return iterable|null
	 */
	public function getCategories(string $identifier, bool $preferRaw = false): ?iterable {
		$name = Inflector::variable(Inflector::pluralize($identifier));
		$categories = $this->getView()->get('_categories', [])[ $name ] ?? [];

		if ($preferRaw) {
			$return = $categories['raw'] ?? null;
		}

		//Empty means no preferred raw format or preferred raw but empty
		if (empty($return)) {
			$return = $categories['simple'] ?? null;
		}

		return $return ?? [];
	}


	/**
	 * For a list of given names, return the first found `selectedCategory`-value in the `categories`-view va
	 *
	 * @param string $identifier
	 * @param bool $useOriginalValue Whether to return the actual value of the selected category
	 *  instead of the one converted into variableCase
	 * @return mixed
	 */
	public function getSelectedCategory(string $identifier, bool $useOriginalValue = false): mixed {
		$name = Inflector::variable(Inflector::pluralize($identifier));
		$categories = $this->getView()->get('_categories', [])[ $name ] ?? [];

		if (!isset($categories['selected'])) {
			return null;
		}

		if (!$useOriginalValue || !isset($categories['simple'])) {
			return $categories['selected'];
		}

		return $categoryKeys[ $categories['selected'] ] ?? $categories['selected'];
	}


	/**
	 * @param array $attributes
	 * @param array $config
	 * @return array
	 */
	protected function buildOptions(array $attributes, array $config = []): array {
		$attributes += [
			'allowUnassigned' => false,
			'allowAggregation' => false,
		];

		$options = $attributes['options'];
		$attributes['options'] = [];

		if ($options) {
			$groupBy = $attributes['groupBy'] ?? null;

			if ($groupBy === null && ($config['includeParentCategories'] ?? false) === true) {
				$groupBy = '_parents';
			}

			if ($groupBy) {
				if (!$options instanceof CollectionInterface) {
					$options = collection($options);
				}

				$groupedOptions = $this->groupOptions($options, $groupBy, $attributes);

				$groupOrderKeys = array_keys($attributes['groupLabels'] ?? $groupedOptions);

				// Sort the groups according to the order of the provided group labels
				uksort($groupedOptions, function (string $a, string $b) use ($groupOrderKeys): int {
					return array_search($a ?: 'general', $groupOrderKeys, true) <=> array_search($b ?: 'general', $groupOrderKeys, true);
				});

				foreach ($groupedOptions as $key => $options) {
					$attributes['options'][] = [
						'id' => null,
						'title' => $key,
						'link' => null,
						'levelPrefix' => null,
						'isGroupLabel' => true,
					];
					$attributes['options'] += $this->formatOptions($options, $attributes + ['isGrouped' => true], true);
				}
			}
			else {
				$attributes['options'] = $this->formatOptions($options, $attributes, true);
			}
		}

		//Shall an option to select unassigned elements be included? Prepend it.
		if ($attributes['allowUnassigned']) {
			$attributes['options'] = [
				$attributes['unassignedKey'] => [
					'id' => null,
					'title' => $attributes['unassignedLabel'],
					'link' => $this->Url->build([
						'_name' => $attributes['routeName'] ?? null,
						$attributes['uriParam'] => $attributes['unassignedKey'],
					], ['withoutParams' => ['page']]),
					'levelPrefix' => null,
				],
			] + $attributes['options'];
		}

		//Shall an option to select unassigned elements be included? Prepend it.
		if ($attributes['allowAggregation']) {
			$attributes['options'] = [
				$attributes['aggregationKey'] => [
					'id' => null,
					'title' => $attributes['aggregationLabel'],
					'link' => $this->Url->build([
						'_name' => $attributes['routeName'] ?? null,
						$attributes['uriParam'] => $attributes['aggregationKey'],
					], ['withoutParams' => ['page']]),
					'levelPrefix' => null,
				],
			] + $attributes['options'];
		}

		return $attributes;
	}


	/**
	 * @param iterable $options
	 * @param array $attributes
	 * @param bool $forLinkSelect
	 * @return array
	 */
	protected function formatOptions(iterable $options, array $attributes, bool $forLinkSelect = false): array {
		if ($options instanceof TreeIterator) {
			$options = $this->formatTreeOptions($options, $attributes, $forLinkSelect);
		}
		elseif ($options instanceof CollectionInterface) {
			$options = $this->formatCollectionOptions($options, $attributes);
		}
		elseif (is_array($options)) {
			$options = $this->formatArrayOptions($options, $attributes, $forLinkSelect);
		}
		else {
			throw new RuntimeException(sprintf('Cannot build options for type `%s`.', gettype($options)));
		}

		if (!$forLinkSelect) {
			return $options;
		}

		return $this->formatOptionAttributes($options, $attributes);
	}


	/**
	 * @param array $options
	 * @param array $attributes
	 * @param bool $forLinkSelect
	 * @return array
	 */
	protected function formatArrayOptions(array $options, array $attributes, bool $forLinkSelect): array {
		if (($attributes['buildNested'] ?? null) === true) {
			$options = $this->formatOptions(collection($options), $attributes, $forLinkSelect);
		}

		if (!in_array($attributes['groupBy'] ?? null, ['label', 'title', 'value', 'id'])) {
			array_walk($options, function (mixed &$option) use ($attributes): void {
				if (is_array($option)) {
					unset($option[ $attributes['groupBy'] ]);
				}
			});
		}

		return $options;
	}


	/**
	 * @param \Cake\Collection\CollectionInterface $options
	 * @param array $attributes
	 * @return array
	 */
	protected function formatCollectionOptions(CollectionInterface $options, array $attributes): array {
		$combinator = array_values($attributes['combinator'] ?? ['id', 'label', null]);

		$collectionOptions = [];
		foreach ($options as $option) {
			if ($option instanceof EntityInterface) {
				$option = $option->extract();
			}

			$title = $option[ $combinator[1] ] ?? $option['label'] ?? $option['title'] ?? $option['value'] ?? $option['id'];

			if ($option['level'] ?? null) {
				$title = str_repeat($attributes['levelPrefix'] ?? '- ', $option['level']) . $title;
			}

			$collectionOptions[ $option[ $combinator[0] ] ] = $title;
		}

		return $collectionOptions;
	}


	/**
	 * @param \Cake\Collection\Iterator\TreeIterator $options
	 * @param array $attributes
	 * @param bool $forLinkSelect
	 * @return array
	 */
	protected function formatTreeOptions(TreeIterator $options, array $attributes, bool $forLinkSelect): array {
		if ($forLinkSelect) {
			$options = $options->toList();
			$options = array_column($options, null, 'id');
		}
		else {
			$options = $options->printer(...($attributes['printer'] ?? ['label', 'id', $attributes['levelPrefix'] ?? '- ']))->toArray();
		}

		return $options;
	}


	/**
	 * @param array $options
	 * @param array $attributes
	 * @return array
	 */
	protected function formatOptionAttributes(array $options, array $attributes): array {
		$formattedOptions = [];

		foreach ($options as $key => $option) {
			if (is_object($option)) {
				$data = [
					'id' => $option->id,
					'title' => $option->label ?? $option->title,
					'link' => $this->Url->build([
						'_name' => $attributes['routeName'] ?? null,
						$attributes['uriParam'] => $option->id,
					], ['withoutParams' => ['page']]),
					'levelPrefix' => str_repeat($attributes['levelPrefix'] ?? '', $option->level ?? 0),
					'isGrouped' => $attributes['isGrouped'] ?? false,
				];
				$formattedOptions[ $option->id ] = $data;
			}
			elseif (is_array($option)) {
				$formattedOptions[ $option['id'] ?? $key ] = $option + ['isGrouped' => $attributes['isGrouped'] ?? false];
			}
			else {
				$data = [
					'id' => null,
					'title' => $option,
					'link' => $this->Url->build([
						'_name' => $attributes['routeName'] ?? null,
						$attributes['uriParam'] => $key,
					], ['withoutParams' => ['page']]),
					'levelPrefix' => null,
					'isGrouped' => $attributes['isGrouped'] ?? false,
				];

				$formattedOptions[ $key ] = $data;
			}
		}


		return $formattedOptions;
	}


	/**
	 * @param \Cake\Collection\CollectionInterface $options
	 * @param string $groupBy
	 * @param array $attributes
	 * @return array
	 */
	protected function groupOptions(CollectionInterface $options, string $groupBy, array &$attributes): array {
		return $options
			->groupBy(function (mixed $element) use ($groupBy, &$attributes) {
				$group = $element->$groupBy ?? $element[ $groupBy ] ?? null;

				if (is_array($group)) {
					$path = implode(' - ', array_map(function (mixed $parent) {
						if (is_scalar($parent)) {
							return $parent;
						}

						if (!$parent instanceof EntityInterface) {
							throw new RuntimeException('Cannot group by non-scalars or non-entities.');
						}

						/** @noinspection PhpPossiblePolymorphicInvocationInspection */
						return $parent->label ?? $parent->title;
					}, $group));

					$attributes['groupLabels'][ $path ] ??= $path;

					return $path;
				}

				return $element[ $groupBy ] ?? '';
			})
			->toArray()
		;
	}


	/**
	 * Generate an ID suitable for use in an ID attribute.
	 *
	 * @param string $value The value to convert into an ID.
	 * @return string The generated id.
	 */
	protected function _domId(string $value): string {
		if (str_contains($value, '.')) {
			$parts = explode('.', $value);
			array_walk($parts, function (&$part): void {
				$part = Inflector::camelize($part);
			});
			$domId = implode('-', $parts);
		}
		else {
			$domId = Inflector::camelize($value);
		}

		return $domId;
	}
}
