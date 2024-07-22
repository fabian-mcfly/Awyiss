<?php declare(strict_types=1);


namespace Awyiss\Annotation;


use Attribute;


/**
 * Annotation to mark a class as assignable to a media element.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class MediaElementAssignable {
	/**
	 * Media element can be assigned to a single record
	 */
	public const ENTITY_LEVEL = 1;
	/**
	 * Media element can be assigned to the model,
	 * which means it's available for all records.
	 */
	public const MODEL_LEVEL = 2;


	public int $level;


	/**
	 * @param int $level
	 */
	public function __construct(int $level) {
		$this->level = $level;
	}
}
