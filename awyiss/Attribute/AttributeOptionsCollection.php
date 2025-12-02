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
abstract class AttributeOptionsCollection extends ArrayIterator implements AttributeOptionsCollectionInterface {
	/**
	 * Construct a new AttributeOptionsCollection
	 *
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct() {
		$this->initializeAttributeOptions();
	}


	/**
	 * Adds a AttributeOptionsCollection or a set of elements, containing nested AttributeOptionsCollection or
	 * AttributeOptionsCollection to this collection
	 *
	 * @param \Awyiss\Attribute\AttributeOption|array<int|string, \Awyiss\Attribute\AttributeOption> $attributeOption
	 * @return $this
	 */
	public function add(array|AttributeOption $attributeOption): static {
		/*
		 * If the provided value for `$attributeOption` is an instance of `AttributeOptionsCollection`,
		 * add it to the current collection
		 */
		if ($attributeOption instanceof AttributeOption) {
			$identifier = $attributeOption->getIdentifier();

			//We cannot have the same identifier more than once inside this collection
			if ($this->offsetExists($identifier)) {
				throw new RuntimeException(sprintf('The identifier `%s` is already in use.', $identifier));
			}

			$this->offsetSet($identifier, $attributeOption);


			return $this;
		}

		//Traverse the provided array
		$attributeOptions = $attributeOption;
		foreach ($attributeOptions as $key => $attributeOption) {
			//If the current value is an instance of AttributeOptionsCollection, add it as is
			if ($attributeOption instanceof AttributeOption) {
				$this->add($attributeOption);
			}
			/*
			 * Otherwise the current value is most likely an array. So we try creating an instance of AttributeOptionsCollection with it
			 * and then add that instance to the collection.
			 * */
			else {
				if (is_string($key)) {
					//No need to sanitize the key here, since `AttributeOptions::setIdentifier()` will do this
					$attributeOption += ['identifier' => $key];
				}

				$this->add(new AttributeOption(...$attributeOption));
			}
		}


		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getAttributeOption(string $identifier): ?AttributeOption {
		$identifier = AttributeOptionsProvider::sanitizeIdentifier($identifier);

		/** @var AttributeOption $lo_attributeOptions */
		return Hash::get($this, $identifier);
	}


	/**
	 * @inheritDoc
	 */
	public function getAttributeOptionsAttributes(string $identifier, array $currentOptions = [], ?ContextInterface $context = null): array {
		$identifier = AttributeOptionsProvider::sanitizeIdentifier($identifier);

		/** @var \Awyiss\Attribute\AttributeOption $attributeOptions */
		$attributeOptions = Hash::get($this, $identifier);

		if (!$attributeOptions) {
			return $currentOptions;
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		return $attributeOptions->buildOptions($currentOptions, $context?->entity());
	}


	/**
	 * @inheritDoc
	 */
	public function validateValue(string $identifier, mixed $value, ?Entity $entity = null): bool|string {
		$identifier = AttributeOptionsProvider::sanitizeIdentifier($identifier);

		/** @var \Awyiss\Attribute\AttributeOption $attributeOptions */
		$attributeOptions = Hash::get($this, $identifier);
		if (!$attributeOptions) {
			return true;
		}


		return $attributeOptions->validateValue($value, $entity);
	}
}
