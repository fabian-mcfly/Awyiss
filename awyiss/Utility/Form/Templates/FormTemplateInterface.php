<?php declare(strict_types=1);


namespace Awyiss\Utility\Form\Templates;


/**
 * FormTemplateInterface
 */
interface FormTemplateInterface {
	/**
	 * @return string
	 */
	public static function getTitle(): string;

	/**
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages A list of frontend languages
	 * 	If more than one language is available, the form element should include the `_translations`-key
	 * 	containing an array of translations for each language.
	 * @return array<string, mixed> The form elements in patchable format
	 */
	public static function getElements(array $languages): array;
}
