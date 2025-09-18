<?php declare(strict_types=1);


namespace Awyiss\Attribute;


use Awyiss\Model\Entity;
use Cake\View\Form\ContextInterface;


/**
 * Signature of all necessary methods to connect `AttributeOptionsCollection` with `AttributeOptionsProvider`
 *
 * @see \Awyiss\Attribute\AttributeOption
 * @see \Awyiss\Attribute\AttributeOptionsProvider
 */
interface AttributeOptionsCollectionInterface {
	/**
	 * Initializes the attribute options and adds them to the current object (`AttributeOptionsCollection`)
	 *
	 * @return void
	 */
	public function initializeAttributeOptions(): void;


	/**
	 * Returns the options for the given identifier
	 *
	 * @param string $identifier
	 * @return \Awyiss\Attribute\AttributeOption|null
	 */
	public function getAttributeOption(string $identifier): ?AttributeOption;


	/**
	 * Return the options found under the path provided.
	 *
	 * @param string $identifier
	 * @param array $currentOptions
	 * @param ContextInterface|null $context
	 * @return array
	 * @see AttributeOption
	 * @see \Cake\Utility\Hash::get()
	 */
	public function getAttributeOptionsAttributes(string $identifier, array $currentOptions = [], ?ContextInterface $context = null): array;


	/**
	 * Retrieves an options class and validates the provided value for the given attributeOptionIdentifier
	 *
	 * Returns a string with an error message if the value is not valid.
	 *
	 * @param string $identifier
	 * @param mixed $value
	 * @param Entity|null $entity
	 * @return string|bool
	 */
	public function validateValue(string $identifier, mixed $value, ?Entity $entity = null): bool|string;
}
