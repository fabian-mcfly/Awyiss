<?php declare(strict_types=1);


namespace Awyiss\Annotation;


use Attribute;


#[Attribute(Attribute::TARGET_CLASS)]
class MediaElementAssignable {
	public const ENTITY_LEVEL = 1;
	public const MODEL_LEVEL = 2;


	public int $level;


	/**
	 * @param int $level
	 */
	public function __construct(int $level) {
		$this->level = $level;
	}
}
