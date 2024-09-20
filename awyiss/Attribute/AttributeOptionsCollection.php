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

		if (empty($ls_testScope)) {
			throw new RuntimeException('The scope cannot be empty.');
		}

		if ($ls_testScope !== $ls_scope) {
			static::$scope = $ls_testScope;
		}

		$this->initializeAttributeOptions();
	}


	/**
	 * Adds a AttributeOptionsCollection or a set of elements, containing nested AttributeOptionsCollection or
	 * AttributeOptionsCollection to this collection
	 *
	 * @param AttributeOptions|array<int|string, AttributeOptions> $attributeOption
	 * @return $this
	 */
	public function add(array|AttributeOptions $attributeOption): static {
		/*
		 * If the provided value for `$attributeOption` is an instance of `AttributeOptionsCollection`,
		 * add it to the current collection
		 */
		if ($attributeOption instanceof AttributeOptions) {
			$ls_identifier = $attributeOption->getIdentifier();

			//We cannot have the same identifier more than once inside this collection
			if ($this->offsetExists($ls_identifier)) {
				throw new RuntimeException(sprintf('The identifier `%s` is already in use.', $ls_identifier));
			}

			$this->offsetSet($ls_identifier, $attributeOption);


			return $this;
		}

		//Traverse the provided array
		foreach ($attributeOption as $lx_key => $lx_attributeOption) {
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

				$this->add(new AttributeOptions(...$lx_attributeOption));
			}
		}


		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getAttributeOption(string $identifier): ?AttributeOptions {
		$ls_identifier = AttributeOptionsProvider::sanitizeIdentifier($identifier);

		/** @var AttributeOptions $lo_attributeOptions */
		return Hash::get($this, $ls_identifier);
	}


	/**
	 * @inheritDoc
	 */
	public function getAttributeOptionsAttributes(string $identifier, array $currentOptions = [], ?ContextInterface $context = null): array {
		$ls_identifier = AttributeOptionsProvider::sanitizeIdentifier($identifier);

		/** @var AttributeOptions $lo_attributeOptions */
		$lo_attributeOptions = Hash::get($this, $ls_identifier);

		if (!$lo_attributeOptions) {
			return $currentOptions;
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		return $lo_attributeOptions->buildOptions($currentOptions, $context?->entity());
	}


	/**
	 * @inheritDoc
	 */
	public function validateValue(string $identifier, mixed $value, ?Entity $entity = null): bool|string {
		$ls_identifier = AttributeOptionsProvider::sanitizeIdentifier($identifier);

		/** @var AttributeOptions $lo_attributeOptions */
		$lo_attributeOptions = Hash::get($this, $ls_identifier);
		if (!$lo_attributeOptions) {
			return true;
		}


		return $lo_attributeOptions->validateValue($value, $entity);
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
