<?php declare(strict_types=1);


namespace Awyiss\Attribute;


use ArrayIterator;
use Awyiss\Model\Entity;
use Cake\Utility\Hash;
use Cake\View\Form\ContextInterface;
use RuntimeException;


/**
 * A class to collect multiple AttributeOptions instances
 */
abstract class AttributeOptionsCollection extends ArrayIterator implements AttributeOptionsInterface {
	/**
	 * Construct a new AttributeOptionsCollection
	 *
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct() {
		$ls_scope = static::getScope();
		$ls_testScope = AttributeOptionsProvider::sanitizeScope($ls_scope);

		if ($ls_testScope !== $ls_scope) {
			throw new RuntimeException(sprintf('The provided scope should be written CamelCased (`%s`). `%s` given.', $ls_testScope, $ls_scope));
		}

		$this->initializeAttributeOptions();
	}


	/**
	 * Adds a AttributeOptionsCollection or a set of elements, containing nested AttributeOptionsCollection or
	 * AttributeOptionsCollection to this collection
	 *
	 * @param AttributeOptions|array<int|string, AttributeOptions> $ax_attributeOption
	 * @return $this
	 */
	public function add(array|AttributeOptions $ax_attributeOption): static {
		/*
		 * If the provided value for `$ax_attributeOption` is an instance of `AttributeOptionsCollection`,
		 * add it to the current collection
		 */
		if ($ax_attributeOption instanceof AttributeOptions) {
			$ls_identifier = $ax_attributeOption->getIdentifier();

			//We cannot have the same identifier more than once inside this collection
			if ($this->offsetExists($ls_identifier)) {
				throw new RuntimeException(sprintf('The identifier `%s` is already in use.', $ls_identifier));
			}

			$this->offsetSet($ls_identifier, $ax_attributeOption);


			return $this;
		}

		//Traverse the provided array
		foreach ($ax_attributeOption as $lx_key => $lx_attributeOption) {
			//If the current value is an instance of AttributeOptionsCollection, add it as is
			if ($lx_attributeOption instanceof AttributeOptions) {
				$this->add($lx_attributeOption);
			}
			/*
			 * Otherwise the current value is most likely an array. So we try creating an instance of AttributeOptionsCollection with it
			 * and then add that instance to the collection.
			 * */
			else {
				if (is_string($lx_key)) {
					//No need to sanitize the key here, since `AttributeOptions::setIdentifier()` will do this
					$lx_attributeOption += ['identifier' => $lx_key];
				}

				$this->add(new AttributeOptions($lx_attributeOption));
			}
		}


		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getAttributeOptions(string $as_identifier, array $aa_currentOptions = [], ?ContextInterface $ao_context = null): array {
		$ls_identifier = AttributeOptionsProvider::sanitizeIdentifier($as_identifier);

		/** @var AttributeOptions $lo_attributeOptions */
		$lo_attributeOptions = Hash::get($this, $ls_identifier);
		if (!$lo_attributeOptions) {
			return $aa_currentOptions;
		}


		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		return $lo_attributeOptions->buildOptions($aa_currentOptions, $ao_context->entity());
	}


	/**
	 * @inheritDoc
	 */
	public function validateValue(string $as_identifier, mixed $ax_value, ?Entity $ao_entity = null): bool|string {
		$ls_identifier = AttributeOptionsProvider::sanitizeIdentifier($as_identifier);

		/** @var AttributeOptions $lo_attributeOptions */
		$lo_attributeOptions = Hash::get($this, $ls_identifier);
		if (!$lo_attributeOptions) {
			return true;
		}


		return $lo_attributeOptions->validateValue($ax_value, $ao_entity);
	}


	/**
	 * @inheritDoc
	 */
	public static function getScope(): string {
		if (!isset(static::$scope)) {
			$la_parts = explode('\\', static::class);
			static::$scope = array_pop($la_parts);
			static::$scope = substr(static::$scope, 0, -16);
			static::$scope = AttributeOptionsProvider::sanitizeScope(static::$scope);
		}


		return static::$scope;
	}
}
