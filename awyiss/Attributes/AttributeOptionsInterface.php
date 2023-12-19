<?php declare(strict_types=1);


namespace Awyiss\Attributes;


use Awyiss\Model\Entity;
use Cake\View\Form\ContextInterface;


/**
 * Signature of all neccessary methods to connect `AttributeOptionsCollection` with `AttributeOptionsProvider`
 *
 * @see AttributeOptions
 * @see AttributeOptionsProvider
 */
interface AttributeOptionsInterface {
	/**
	 * Initializes the attribute options and adds them to the current object (`AttributeOptionsCollection`)
	 *
	 * @return void
	 */
	public function initializeAttributeOptions (): void;

	/**
	 * Return the options found under the path provided.
	 *
	 * @param string                $as_identifier
	 * @param array                 $aa_currentOptions
	 * @param NULL|ContextInterface $ao_context
	 *
	 * @return array
	 * @see AttributeOptions
	 * @see \Cake\Utility\Hash::get()
	 */
	public function getAttributeOptions (string $as_identifier, array $aa_currentOptions = [], ContextInterface $ao_context = NULL): array;

	/**
	 * Retreives an options class and validates the provided value for the given attributeOptionIdentifier
	 *
	 * Returns a string with an error message if the value is not valid.
	 *
	 * @param string      $as_identifier
	 * @param mixed       $ax_value
	 * @param NULL|Entity $ao_entity
	 *
	 * @return bool|string
	 */
	public function validateValue (string $as_identifier, mixed $ax_value, Entity $ao_entity = NULL): bool|string;

	/**
	 * Return the scope of the options-collection.
	 * If none is set, use the name of the class that extends this one
	 *
	 * @return string
	 */
	public static function getScope (): string;
}
