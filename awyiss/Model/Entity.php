<?php declare(strict_types=1);


namespace Awyiss\Model;


use Awyiss\Model\Trait\EntityAttributesTrait;
use Awyiss\Model\Trait\EntityFieldMapTrait;
use Awyiss\Utility\Inflector;
use Cake\Datasource\EntityInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Behavior\Translate\TranslateTrait;
use Cake\ORM\Entity as BaseEntity;


/**
 * Base Entity
 *
 * @property string $label
 * @property \Cake\Datasource\EntityInterface|null $attributes
 * @property array|null $mediaAssignments
 * @property array|null $mediaElementAssignments
 * @property \Cake\I18n\DateTime|null $publicationStart
 * @property \Cake\I18n\DateTime|null $publicationEnd
 */
class Entity extends BaseEntity {
	use EntityAttributesTrait {
		EntityAttributesTrait::get as getOrGetFromAttribute;
		EntityAttributesTrait::set as setOrSetAttribute;
		EntityAttributesTrait::patch as patchOrPatchAttribute;
	}
	use EntityFieldMapTrait;
	use TranslateTrait;


	/**
	 * @var bool Whether the entity allows tracking changes by AuditBehavior
	 */
	protected bool $_audit = true; // phpcs:ignore
	/**
	 * @var array Default values for the entity
	 */
	protected array $defaultValues = [];
	/**
	 * @inheritDoc
	 */
	protected array $_virtual = ['label']; // phpcs:ignore


	/**
	 * @param array $properties
	 * @param array $options
	 */
	public function __construct(array $properties = [], array $options = []) {
		$properties = $this->mapFields($properties, true);

		//Remember the original field names here.
		$this->setOriginalField(array_keys($properties));

		parent::__construct($properties, $options);

		if (!array_key_exists('_translations', $this->_accessible)) {
			$this->setAccess('_translations', true);
		}

		if (!array_key_exists('_publicationData', $this->_accessible)) {
			$this->setAccess('_publicationData', true);
		}

		if (!array_key_exists('mediaAssignments', $this->_accessible)) {
			$this->setAccess('mediaAssignments', true);
		}

		if (!array_key_exists('mediaElementAssignments', $this->_accessible)) {
			$this->setAccess('mediaElementAssignments', true);
		}
	}


	/**
	 * @inheritDoc
	 */
	public function &get(string $field): mixed {
		$field = static::mapField($field);

		/** @noinspection PhpUnnecessaryLocalVariableInspection ... stupid PhpStorm */
		$value = &$this->getOrGetFromAttribute($field);


		return $value;
	}


	/**
	 * @inheritDoc
	 * @param array|string $field
	 * @param mixed|null $value
	 * @param array $options
	 */
	public function set(array|string $field, mixed $value = null, array $options = []): EntityInterface {
		if (is_array($field)) {
			/**
			 * Let the parent method handle an array of fields.
			 * Since CakePHP 5.2.0, setting an array of fields
			 * is deprecated and will throw an exception in the future.
			 */
			return parent::set($field, $value, $options);
		}

		return $this->setOrSetAttribute(static ::mapField($field), $value, $options);
	}


	/**
	 * @inheritDoc
	 */
	public function patch(array $values, array $options = []): EntityInterface {
		$values = static::mapFields($values, true);


		return $this->patchOrPatchAttribute($values, $options);
	}


	/**
	 * Return the default values set for this entity
	 *
	 * @return array
	 */
	public function defaultValues(): array {
		return $this->defaultValues;
	}


	/**
	 * Returns whether the entity allows tracking changes by AuditBehavior
	 *
	 * @return bool
	 */
	public function allowsAudit(): bool {
		return $this->_audit;
	}


	/**
	 * Enables the audit flag that allows the AuditBehavior to track changes to this entity
	 *
	 * @param bool $audit
	 * @return $this
	 * @noinspection PhpUnused
	 */
	public function enableAudit(bool $audit = true): static {
		$this->_audit = $audit;


		return $this;
	}


	/**
	 * Disables the audit flag to prevent the AuditBehavior from tracking changes to this entity
	 *
	 * @return $this
	 */
	public function disableAudit(): static {
		$this->_audit = false;


		return $this;
	}


	/**
	 * @return bool|null
	 */
	public function isPublished(): ?bool {
		/**
		 * No publication dara or empty publication data means either
		 * - the table has no publication data behavior
		 * - the entity has no publication data
		 *
		 * In both cases the entity should be considered published
		 */
		if (empty($this->_publicationData)) {
			return null;
		}

		$now = new DateTime();

		if ($this->publicationStart && $this->publicationStart > $now) {
			return false;
		}

		if ($this->publicationEnd && $this->publicationEnd < $now) {
			return false;
		}

		return true;
	}


	/**
	 * Creates and returns a specific text, used for list items and so on
	 * It uses the first of following db columns identifier, filename, title if present and
	 * prepends a translatable text in case the entity is inactive (active = 0)
	 *
	 * The label can be translated as well
	 *
	 * @noinspection PhpUnused
	 */
	protected function _getLabel(): string {
		$scope = Inflector::underscore($this->getSource()) ?: 'system';

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$title = $this->title ?? $this->name;

		if (empty($title)) {
			if (!empty($this->identifier)) {
				$identifier = $this->identifier;
				$title = __d($scope, 'title_' . Inflector::underscore($identifier));
			}
			else {
				$title = $this->fileName ?? null ?? Inflector::singularize($this->getSource()) . $this->id;
			}
		}

		$inactive = '';
		if (key_exists('active', $this->_fields) && empty($this->active)) {
			$inactive = __d($scope, 'inactive') . ' ';
		}


		return $inactive . $title;
	}
}
