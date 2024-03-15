<?php declare(strict_types=1);


namespace Awyiss\Model;


use Awyiss\Model\Trait\EntityAttributesTrait;
use Awyiss\Model\Trait\EntityFieldMapTrait;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\Behavior\Translate\TranslateTrait;
use Cake\ORM\Entity as BaseEntity;
use Cake\Utility\Inflector;


/**
 * Page Entity
 *
 * @property string $label
 * @property ?\Cake\Datasource\EntityInterface $attributes
 */
class Entity extends BaseEntity {
	use EntityAttributesTrait {
		EntityAttributesTrait::get as getOrGetFromAttribute;
		EntityAttributesTrait::set as setOrSetAttribute;
	}
	use EntityFieldMapTrait;
	use TranslateTrait;


	protected bool $_audit = true;
	/**
	 * @var array Default values for the entity
	 */
	protected array $defaultValues = [];
	/**
	 * @inheritDoc
	 */
	protected array $_virtual = ['label'];


	/**
	 * @param array $aa_properties
	 * @param array $aa_options
	 */
	public function __construct(array $aa_properties = [], array $aa_options = []) {
		$la_properties = $this->mapFields($aa_properties, true);

		//Remember the original field names here.
		$this->setOriginalField(array_keys($la_properties));

		parent::__construct($la_properties, $aa_options);

		if (isset($this->_fields['attributes']) && $this->getSource()) {
			/** @var Table $lo_table */
			$lo_table = FactoryLocator::get('Table')->get($this->getSource());
			if ($lo_table->hasAttributes()) {
				/** @var \Awyiss\ORM\Association\HasOne $lo_association */
				$lo_association = $lo_table->getAssociation($lo_table->getAttributesTableName(true));

				/** @var static $ls_associationEntityClass */
				$ls_associationEntityClass = $lo_association->getEntityClass();

				$ls_foreignKey = $ls_associationEntityClass::mapField($lo_association->getForeignKey());

				$this->initAttributesField($lo_association, $ls_foreignKey);
			}
		}

		if (!array_key_exists('_translations', $this->_accessible)) {
			$this->setAccess('_translations', true);
		}

		if (!array_key_exists('_publicationData', $this->_accessible)) {
			$this->setAccess('_publicationData', true);
		}
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function &get(string $as_field): mixed {
		$ls_field = $as_field;
		$ls_field = static::mapField($ls_field);

		/** @noinspection PhpUnnecessaryLocalVariableInspection ... stupid PhpStorm */
		$lx_value = &$this->getOrGetFromAttribute($ls_field);


		return $lx_value;
	}


	/**
	 * @inheritDoc
	 * @param array|string $ax_field
	 * @param mixed|null $ax_value
	 * @param array $aa_options
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function set(array|string $ax_field, mixed $ax_value = null, array $aa_options = []): EntityInterface {
		$lx_field = $ax_field;
		if (is_string($lx_field)) {
			$lx_field = static::mapField($lx_field);
		}
		elseif (is_array($ax_field) && $ax_field) {
			$lx_field = static::mapFields($ax_field, true);
		}


		return $this->setOrSetAttribute($lx_field, $ax_value, $aa_options);
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
	 * @param bool $ab_audit
	 * @return $this
	 * @noinspection PhpUnused
	 */
	public function enableAudit(bool $ab_audit = true): static {
		$this->_audit = $ab_audit;


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
	 * Creates and returns a specific text, used for list items and so on
	 * It uses the first of following db colums identifier, filename, title if present and
	 * prepends a translatable text in case the entity is inactive (active = 0)
	 *
	 * The label can be translated as well
	 *
	 * @noinspection PhpUnused
	 */
	protected function _getLabel(): string {
		$ls_scope = Inflector::underscore($this->getSource()) ?: 'system';

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$ls_title = $this->title ?? $this->name;

		if (empty($ls_title)) {
			if (!empty($this->identifier)) {
				$ls_identifier = $this->identifier;
				$ls_title = __d($ls_scope, 'title_' . Inflector::underscore($ls_identifier));
			}
			else {
				$ls_title = $this->fileName ?? null ?? Inflector::singularize($this->getSource()) . $this->id;
			}
		}

		$ls_inactive = '';
		if (key_exists('active', $this->_fields) && empty($this->active)) {
			$ls_inactive = __d($ls_scope, 'inactive') . ' ';
		}


		return $ls_inactive . $ls_title;
	}
}
