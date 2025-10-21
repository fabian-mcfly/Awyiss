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
		$la_attributes = Hash::merge($this->getConfig(), $attributes);

		//No entity? That's a big problem.
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$lo_entity = $la_attributes['entity'] ?? $this->Form->context()?->entity() ?? null;
		if (!$lo_entity) {
			throw new CakeException('Missing entity for SystemOrderHelper::control');
		}

		//If the given entity is not an instance of Entity, we can't continue
		if (!($lo_entity instanceof Entity)) {
			$ls_type = is_object($lo_entity) ? get_class($lo_entity) : gettype($lo_entity);
			$ls_message = sprintf('Entity provided must be an instance of `%s`, `%s` given.', Entity::class, $ls_type);

			throw new RuntimeException($ls_message);
		}

		$la_attributes['options'] = $this->buildSystemOrderOptions($la_attributes['options'], $la_attributes, $lo_entity);

		//Default input type, if none was provided
		if (!array_key_exists('type', $la_attributes)) {
			$la_attributes['type'] = 'select';
		}

		//Unset attributes that shouldn't be part of the generated select
		unset(
			$la_attributes['field'],
			$la_attributes['entity'],
			$la_attributes['includeFirst'],
			$la_attributes['relatedColumns'],
			$la_attributes['templateClass'],
			$la_attributes['templates'],
			$la_attributes['titleFirst'],
		);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		return $this->Form->control(
			$fieldName ?? 'system_order',
			$la_attributes + [
				'disabled' => $this->_View->getRequest()->getData('save_as_copy') ? false : [SystemOrderBehavior::CURRENT_VALUE_PLACEHOLDER],
				'val' => $lo_entity->systemOrder,
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
		$la_options = [];
		$lb_isNew = $entity->isNew();
		$la_dirtyRelatedColumns = array_intersect($entity->getDirty(), $attributes['relatedColumns'] ?? []);

		//If the option `first`-option should be part of the options, add it
		if ($attributes['includeFirst']) {
			$li_firstOrder = $this->_View->getRequest()->getData('save_as_copy') ? 0 : 1;
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$la_options[ $li_firstOrder ] = $this->formatFirstTitle(
				$attributes + [
					'isOriginalSystemOrder' => !$lb_isNew && $entity->hasOriginal('systemOrder') && $entity->getOriginal('systemOrder') === 1,
					'isSelectedSystemOrder' => $entity->systemOrder === $li_firstOrder,
				]
			);
		}

		if ($options instanceof Traversable) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$options = iterator_to_array($options);
		}

		$lb_reachedOriginalSystemOrder = false;
		foreach ($options as $lx_option) {
			/*
			 * The option is the original when
			 * - the entity is not new AND
			 * - no system order related columns are dirty AND
			 * - the `system order`-property of the option equals the entity's original
			 */
			$lb_isOriginalSystemOrder = false;
			if (!$lb_isNew && !$la_dirtyRelatedColumns) {
				if ($entity->hasOriginal('systemOrder') && ($lx_option['systemOrder'] == $entity->getOriginal('systemOrder'))) {
					$lb_isOriginalSystemOrder = true;
				}
				elseif (!$entity->hasOriginal('systemOrder') && ($lx_option['systemOrder'] == $entity->get('systemOrder'))) {
					$lb_isOriginalSystemOrder = true;
				}
			}

			//Remember that we reached the original position
			if ($lb_isOriginalSystemOrder/* && ! $lb_reachedOriginalSystemOrder*/) {
				$lb_reachedOriginalSystemOrder = true;
			}

			//The value should be the `system_order`-property of the option
			$li_systemOrder = $lx_option['systemOrder'];
			if (!$lb_reachedOriginalSystemOrder) {
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
				$li_systemOrder++;
			}

			/**
			 * If the current option is the original, replace the value with a placeholder.
			 *
			 * This makes the SystemOrder behavior ignore the property when marshalling data
			 *
			 * @see SystemOrderBehavior::beforeMarshal
			 */
			if ($lb_isOriginalSystemOrder && !$this->_View->getRequest()->getData('save_as_copy')) {
				$li_systemOrder = SystemOrderBehavior::CURRENT_VALUE_PLACEHOLDER;
			}

			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			//Append a new option with the system_order as its value.
			$la_options[ $li_systemOrder ] = $this->formatTitle(
				$lx_option,
				$attributes + [
					'isOriginalSystemOrder' => $lb_isOriginalSystemOrder,
					'isSelectedSystemOrder' => $li_systemOrder == $entity->systemOrder,
				]
			);
		}


		return $la_options;
	}


	/**
	 * Returns a formatted title for the `first`-option.
	 *
	 * @param array $config
	 * @return string
	 */
	protected function formatFirstTitle(array $config): string {
		$ls_template = 'titleFirst';
		$la_config = $config;

		//If the first position is the current system order of the entity, use the `titleFirstCurrent`-template
		if ($la_config['isOriginalSystemOrder']) {
			$ls_template = 'titleFirstCurrent';
		}

		//If the first position is the selected system order of the entity, use the `titleFirstSelected`-template
		if ($la_config['isSelectedSystemOrder']) {
			$ls_template = 'titleFirstSelected';
		}

		//Get the template
		$lx_template = $la_config['templates'][ $ls_template ];

		//In case the template is a string
		if (is_string($lx_template)) {
			//Add the template with its name to the templater
			$this->templater()->add([$ls_template => $lx_template]);

			/*
			 * Format the given template, using the `first`-option from the config as a label,
			 * with a fallback to the translation of '::system_order_first'
			 */
			$ls_title = $this->formatTemplate(
				$ls_template,
				[
					'first' => is_null($la_config['first']) ? __('system_order_first') : $la_config['first'],
				] + $la_config
			);
		}
		//If the template is a callable, call it and use its return value as the title
		elseif (is_callable($lx_template)) {
			$ls_title = call_user_func($lx_template, $la_config);
		}


		return $ls_title ?? '';
	}


	/**
	 * @param mixed $data
	 * @param array $config
	 * @return string
	 */
	protected function formatTitle(mixed $data, array $config): string {
		$ls_title = '';
		$ls_template = 'titleOption';
		$la_config = $config;

		//If the position is the current system order of the entity, use the `titleOptionCurrent`-template
		if ($la_config['isOriginalSystemOrder']) {
			$ls_template = 'titleOptionCurrent';
		}

		//If the position is the selected system order of the entity, use the `titleOptionSelected`-template
		if ($la_config['isSelectedSystemOrder']) {
			$ls_template = 'titleOptionSelected';
		}

		$lx_template = $la_config['templates'][ $ls_template ];

		//In case the template is a string
		if (is_string($lx_template)) {
			//Make sure the data is an array
			$la_data = $data;
			if ($la_data instanceof Entity) {
				$la_data = $la_data->toArray();
			}
			elseif (!is_array($data)) {
				$la_data = (array)$la_data;
			}

			//Add the template with its name to the templater
			$this->templater()->add([$ls_template => $lx_template]);
			/*
			 * Format the given template, using the `after`-option from the config as a label,
			 * with a fallback to the translation of '::system_order_after'
			 */
			$ls_title = $this->formatTemplate(
				$ls_template,
				$la_data + [
					'after' => is_null($la_config['after']) ? __('system_order_after') : $la_config['after'],
				]
			);
		}
		//If the template is a callable, call it and use its return value as the title
		elseif (is_callable($lx_template)) {
			$ls_title = call_user_func($lx_template, $data, $la_config);
		}


		return $ls_title ?? '';
	}
}
