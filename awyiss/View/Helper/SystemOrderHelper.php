<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Model\Behavior\SystemOrderBehavior;
use Awyiss\Model\Entity;
use Awyiss\Utility\Inflector;
use Cake\Core\Exception\CakeException;
use Cake\Utility\Hash;
use Cake\View\Helper;
use Cake\View\StringTemplate;
use Cake\View\StringTemplateTrait;
use RuntimeException;
use Traversable;


/**
 * Helper functions for the view that are related to SystemOrder-logic
 *
 * @property \Awyiss\View\Helper\FormHelper $Form
 */
class SystemOrderHelper extends Helper {
	use StringTemplateTrait;


	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [
		'after' => null,
		'empty' => false,
		'field' => 'systemOrder',
		'first' => null,
		'includeFirst' => true,
		'options' => [],
		'templateClass' => StringTemplate::class,
		'templates' => [
			'titleOption' => '{{after}} {{label}}',
			'titleOptionCurrent' => '{{label}}',
			'titleOptionSelected' => '-> {{after}} {{label}}',
			'titleFirst' => '{{first}}',
			'titleFirstCurrent' => '{{first}}',
			'titleFirstSelected' => '-> {{first}}',
		],
	];
	/**
	 * @inheritDoc
	 */
	protected array $helpers = ['Form'];


	/**
	 * @inheritDoc
	 * @param array $config
	 * @return void
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->_templater = new StringTemplate();
	}


	/**
	 * Returns an input (default: select) containing all possible positions for an entity
	 * ### Options
	 * - `entity` The entity the system order is for
	 * - `options` All possible options. If empty, try fetching the `systemOrderRecords`-var from the view
	 * - `relatedColumns` The columns related to the system order. If empty, try fetching the `systemOrderRelatedColumns`-var from the view
	 * For more options, see FormHelper::control()
	 *
	 * @param string|null $fieldName
	 * @param array $attributes
	 * @return string
	 * @throws \Exception
	 * @see FormHelper::control
	 */
	public function control(?string $fieldName = null, array $attributes = []): string {
		if (Inflector::variable($this->getConfig('field', 'systemOrder')) !== 'systemOrder') {
			return '';
		}

		//Add the provided attributes to the config, so both will be merged
		$attributes = Hash::merge($this->getConfig(), $attributes);

		// No entity? That's a big problem.
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $attributes['entity'] ?? $this->Form->context()?->entity() ?? null;
		if (!$entity) {
			throw new CakeException('Missing entity for SystemOrderHelper::control');
		}

		//If the given entity is not an instance of Entity, we can't continue
		if (!($entity instanceof Entity)) {
			$type = is_object($entity) ? get_class($entity) : gettype($entity);
			$message = sprintf('Entity provided must be an instance of `%s`, `%s` given.', Entity::class, $type);

			throw new RuntimeException($message);
		}

		$attributes['options'] = $this->buildSystemOrderOptions($attributes['options'], $attributes, $entity);

		//Default input type, if none was provided
		if (!array_key_exists('type', $attributes)) {
			$attributes['type'] = 'select';
		}

		//Unset attributes that shouldn't be part of the generated select
		unset(
			$attributes['field'],
			$attributes['entity'],
			$attributes['includeFirst'],
			$attributes['relatedColumns'],
			$attributes['templateClass'],
			$attributes['templates'],
			$attributes['titleFirst'],
		);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		return $this->Form->control(
			$fieldName ?? 'system_order',
			$attributes + [
				'disabled' => $this->_View->getRequest()->getData('save_as_copy') ? false : [SystemOrderBehavior::CURRENT_VALUE_PLACEHOLDER],
				'val' => $entity->systemOrder,
			]
		);
	}


	/**
	 * Transform the given options into an array, usable as options in `FormHelper::control()`
	 *
	 * @param iterable|null $options
	 * @param array $attributes
	 * @param Entity $entity
	 * @return array
	 */
	protected function buildSystemOrderOptions(?iterable $options, array $attributes, Entity $entity): array {
		$systemOrderOptions = [];
		$isNew = $entity->isNew();
		$dirtyRelatedColumns = array_intersect($entity->getDirty(), $attributes['relatedColumns'] ?? []);

		//If the option `first`-option should be part of the options, add it
		if ($attributes['includeFirst']) {
			$firstOrder = $this->_View->getRequest()->getData('save_as_copy') ? 0 : 1;
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$systemOrderOptions[ $firstOrder ] = $this->formatFirstTitle(
				$attributes + [
					'isOriginalSystemOrder' => !$isNew && $entity->hasOriginal('systemOrder') && $entity->getOriginal('systemOrder') === 1,
					'isSelectedSystemOrder' => $entity->systemOrder === $firstOrder,
				]
			);
		}

		if ($options instanceof Traversable) {
			$options = iterator_to_array($options);
		}

		$reachedOriginalSystemOrder = false;
		foreach ($options as $option) {
			/*
			 * The option is the original when
			 * - the entity is not new AND
			 * - no system order related columns are dirty AND
			 * - the `system order`-property of the option equals the entity's original
			 */
			$isOriginalSystemOrder = false;
			if (!$isNew && !$dirtyRelatedColumns) {
				if ($entity->hasOriginal('systemOrder') && ($option['systemOrder'] == $entity->getOriginal('systemOrder'))) {
					$isOriginalSystemOrder = true;
				}
				elseif (!$entity->hasOriginal('systemOrder') && ($option['systemOrder'] == $entity->get('systemOrder'))) {
					$isOriginalSystemOrder = true;
				}
			}

			//Remember that we reached the original position
			if ($isOriginalSystemOrder/* && ! $reachedOriginalSystemOrder*/) {
				$reachedOriginalSystemOrder = true;
			}

			//The value should be the `system_order`-property of the option
			$systemOrder = $option['systemOrder'];
			if (!$reachedOriginalSystemOrder) {
				/**
				 * As long as we haven't reached the original system order in this loop,
				 * increase the value by one.
				 *
				 * In our example we will use an entity with system order #4
				 *
				 * If we select the second option, it's value needs to be '3' because it's labeled "AFTER #2".
				 *
				 * If we reached the option #4 in the loop, it's no longer necessary to increase the value.
				 * Selecting the sixth option, it's value needs to be '6', since the options #5 and #6 move one forward.
				 * This means option #6 is the now at system order #5.
				 * And since the value says 6, it's the correct value
				 * for "AFTER #5".
				 */
				$systemOrder++;
			}

			/**
			 * If the current option is the original, replace the value with a placeholder.
			 *
			 * This makes the SystemOrder behavior ignore the property when marshalling data
			 *
			 * @see SystemOrderBehavior::beforeMarshal
			 */
			if ($isOriginalSystemOrder && !$this->_View->getRequest()->getData('save_as_copy')) {
				$systemOrder = SystemOrderBehavior::CURRENT_VALUE_PLACEHOLDER;
			}

			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			//Append a new option with the system_order as its value.
			$systemOrderOptions[ $systemOrder ] = $this->formatTitle(
				$option,
				$attributes + [
					'isOriginalSystemOrder' => $isOriginalSystemOrder,
					'isSelectedSystemOrder' => $systemOrder == $entity->systemOrder,
				]
			);
		}


		return $systemOrderOptions;
	}


	/**
	 * Returns a formatted title for the `first`-option.
	 *
	 * @param array $config
	 * @return string
	 */
	protected function formatFirstTitle(array $config): string {
		$templateName = 'titleFirst';

		// If the first position is the current system order of the entity, use the `titleFirstCurrent`-template
		if ($config['isOriginalSystemOrder']) {
			$templateName = 'titleFirstCurrent';
		}

		// If the first position is the selected system order of the entity, use the `titleFirstSelected`-template
		if ($config['isSelectedSystemOrder']) {
			$templateName = 'titleFirstSelected';
		}

		// Get the template
		$template = $config['templates'][ $templateName ];

		// In case the template is a string
		if (is_string($template)) {
			//Add the template with its name to the templater
			$this->templater()->add([$templateName => $template]);

			/*
			 * Format the given template, using the `first`-option from the config as a label,
			 * with a fallback to the translation of '::system_order_first'
			 */
			$title = $this->formatTemplate(
				$templateName,
				[
					'first' => is_null($config['first']) ? __('system_order_first') : $config['first'],
				] + $config
			);
		}
		//If the template is a callable, call it and use its return value as the title
		elseif (is_callable($template)) {
			$title = call_user_func($template, $config);
		}


		return $title ?? '';
	}


	/**
	 * @param mixed $data
	 * @param array $config
	 * @return string
	 */
	protected function formatTitle(mixed $data, array $config): string {
		$title = '';
		$templateName = 'titleOption';

		// If the position is the current system order of the entity, use the `titleOptionCurrent`-template
		if ($config['isOriginalSystemOrder']) {
			$templateName = 'titleOptionCurrent';
		}

		// If the position is the selected system order of the entity, use the `titleOptionSelected`-template
		if ($config['isSelectedSystemOrder']) {
			$templateName = 'titleOptionSelected';
		}

		$template = $config['templates'][ $templateName ];

		// In case the template is a string
		if (is_string($template)) {
			// Make sure the data is an array
			if ($data instanceof Entity) {
				$data = $data->toArray();
			}
			elseif (!is_array($data)) {
				$data = (array)$data;
			}

			//Add the template with its name to the templater
			$this->templater()->add([$templateName => $template]);
			/*
			 * Format the given template, using the `after`-option from the config as a label,
			 * with a fallback to the translation of '::system_order_after'
			 */
			$title = $this->formatTemplate(
				$templateName,
				$data + [
					'after' => is_null($config['after']) ? __('system_order_after') : $config['after'],
				]
			);
		}
		//If the template is a callable, call it and use its return value as the title
		elseif (is_callable($template)) {
			$title = call_user_func($template, $data, $config);
		}


		return $title ?? '';
	}
}
