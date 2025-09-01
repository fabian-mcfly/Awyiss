<?php declare(strict_types=1);


namespace Awyiss\Utility\Content;


use Awyiss\Model\Entity;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use DOMDocument;
use DOMXPath;
use InvalidArgumentException;


/**
 * Cleans HTML content of an entity
 */
class HtmlCleaner {
	/**
	 * No cleaning at all
	 */
	public const CLEAN_NONE = 'none';
	/**
	 * Moderate cleaning.
	 *
	 * - Converts all leading and trailing `<br>`-tags to `<p>`-tags
	 * - Removes leading and trailing `<br>`-tags inside any tag
	 * - Replaces `&nbsp;` after a dot, comma, question mark, or exclamation mark with a regular space
	 * - Removes leading and trailing whitespaces (including non-breaking spaces) from each tag
	 * - Combines consecutive whitespaces (including non-breaking spaces)
	 */
	public const CLEAN_MODERATE = 'moderate';
	/**
	 * Strict cleaning.
	 *
	 * Does everything the moderate cleaning does and additionally
	 * - Cleans up all tags that have nothing inside but whitespaces, `<br>`-tags` or non-breaking spaces
	 * - Converts multiple consecutive `<br>`-tags to a single `<br>`-tag
	 * - Removes leading and trailing empty tags
	 */
	public const CLEAN_STRICT = 'strict';

	/**
	 * The default fields to clean
	 *
	 * @var array<int, string>
	 */
	protected static array $defaultFields = ['text', 'textHtml', 'successMessage'];


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param string $method
	 * @param array $fields
	 * @return void
	 * @throws \DOMException
	 */
	public static function clean(EntityInterface $entity, string $method = self::CLEAN_STRICT, array $fields = []): void {
		$la_fields = $fields ?: static::getDefaultFields($entity);

		if ($method === 'none') {
			return;
		}

		foreach ($la_fields as $ls_field) {
			if (!static::fieldIsValid($entity, $ls_field)) {
				continue;
			}

			if ($method === self::CLEAN_MODERATE) {
				static::cleanModerate($entity, $ls_field);
				continue;
			}

			if ($method === self::CLEAN_STRICT) {
				static::cleanStrict($entity, $ls_field);
				continue;
			}

			throw new InvalidArgumentException(sprintf('Invalid clean method. Expected one of `none`, `moderate`, `strict`. `%s` given.', $method));
		}

		if ($entity->has('_translations')) {
			foreach ($entity->get('_translations') as $lo_translation) {
				static::clean($lo_translation, $method, $fields);
			}
		}
	}


	/**
	 * Returns the default fields to clean,
	 * including the attributes with input type `texteditor`
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return array
	 */
	public static function getDefaultFields(EntityInterface $entity): array {
		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($entity->getSource());

		if (!$lo_table->hasBehavior('Attributes') || !$lo_table->hasAttributes()) {
			return static::$defaultFields;
		}

		$la_fields = static::$defaultFields;

		foreach ($lo_table->getAttributes() as $lo_attribute) {
			if ($lo_attribute->inputType === 'texteditor') {
				$la_fields[] = $lo_attribute->identifier;
			}
		}

		return $la_fields;
	}


	/**
	 * Cleans the given field with the moderate method
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param string $field
	 * @return void
	 * @throws \DOMException
	 */
	protected static function cleanModerate(EntityInterface $entity, string $field): void {
		$ls_value = $entity->get($field);
		$lo_dom = static::getDomDocument($ls_value);

		// Convert all leading and trailing `<br>`-tags to `<p>`-tags
		static::convertLeadingAndTrailingBrTags($lo_dom);

		// Remove leading and trailing `<br>`-tags inside any tag
		static::removeLeadingAndTrailingBrTags($lo_dom);

		// Replace &nbsp; after a dot, comma, question mark, or exclamation mark with a regular space
		static::replaceNbspAfterPunctuation($lo_dom);

		// Remove leading and trailing whitespaces (including non-breaking spaces) from each tag
		static::removeLeadingAndTrailingWhitespace($lo_dom);

		// Combine consecutive whitespaces
		static::combineConsecutiveWhitespace($lo_dom);

		// Set the cleaned value back to the entity
		$entity->set($field, trim(static::getBody($lo_dom)) ?: null);
	}


	/**
	 * Cleans the given field with the moderate method
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param string $field
	 * @return void
	 * @throws \DOMException
	 */
	protected static function cleanStrict(EntityInterface $entity, string $field): void {
		$ls_value = $entity->get($field);
		$lo_dom = static::getDomDocument($ls_value);

		// Convert all leading and trailing `<br>`-tags to `<p>`-tags
		static::convertLeadingAndTrailingBrTags($lo_dom);

		// Remove leading and trailing `<br>`-tags inside any tag
		static::removeLeadingAndTrailingBrTags($lo_dom);

		// Replace &nbsp; after a dot, comma, question mark, or exclamation mark with a regular space
		static::replaceNbspAfterPunctuation($lo_dom);

		// Clean up all tags that have nothing inside but whitespaces, `<br>`-tags` or non-breaking spaces
		static::cleanEmptyTags($lo_dom);

		// Convert multiple consecutive `<br>`-tags to a single `<br>`-tag
		static::combineConsecutiveBrTags($lo_dom);

		// Remove leading and trailing whitespaces (including non-breaking spaces) from each tag
		static::removeLeadingAndTrailingWhitespace($lo_dom);

		// Combine consecutive whitespaces
		static::combineConsecutiveWhitespace($lo_dom);

		// Combine consecutive empty `<p>`-tags
		static::combineConsecutiveEmptyPTags($lo_dom);

		// Remove leading and trailing empty tags inside the `<body>`-tag
		static::removeLeadingAndTrailingEmptyTags($lo_dom);

		// Set the cleaned value back to the entity
		$entity->set($field, trim(static::getBody($lo_dom)) ?: null);
	}


	/**
	 * Creates a DOMDocument from the given HTML string
	 *
	 * @param string $value
	 * @return \DOMDocument
	 */
	protected static function getDomDocument(string $value): DOMDocument {
		$lo_dom = new DOMDocument('1.0', 'UTF-8');

		// Suppress errors due to malformed HTML
		libxml_use_internal_errors(true);

		// Load the HTML string into the DOMDocument
		$lo_dom->loadHTML('<!DOCTYPE html>' . mb_encode_numericentity($value, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));

		// Clear any errors collected during loadHTML
		libxml_clear_errors();

		return $lo_dom;
	}


	/**
	 * Cleans up all tags that have nothing inside but whitespaces, `<br>`-tags` or,
	 * non-breaking spaces as either `&nbsp;` or `\xC2\xA0`
	 *
	 * and replaces the content with a non-breaking space
	 *
	 * @param \DOMDocument $dom
	 * @return void
	 */
	protected static function cleanEmptyTags(DOMDocument $dom): void {
		// Get all tags inside the `<body>`-tag
		$lo_body = $dom->getElementsByTagName('body')->item(0);
		$lo_tags = $lo_body->getElementsByTagName('*');

		foreach ($lo_tags as $lo_tag) {
			if (in_array($lo_tag->nodeName, ['br', 'hr', 'img'])) {
				continue;
			}

			$ls_content = $lo_tag->textContent;

			// If the text content is a non-breaking space, do nothing
			if (in_array($ls_content, ['', '&nbsp;', "\u{A0}"], true)) {
				continue;
			}

			// Check if the content of the tag is empty or only contains whitespaces or non-breaking spaces
			if (preg_match('/^([\s\n\r\t]|\xC2\xA0)*$/', $ls_content)) {
				if (in_array($lo_tag->nodeName, ['ul', 'ol', 'dl'])) {
					if ($lo_tag->nextSibling && $lo_tag->nextSibling->nodeName === '#text') {
						$lo_tag->parentNode->removeChild($lo_tag->nextSibling);
					}

					// Empty lists? Remove them
					$lo_tag->parentNode->removeChild($lo_tag);
					continue;
				}

				$lo_tag->nodeValue = '&nbsp;';
			}
		}
	}


	/**
	 * Converts multiple consecutive `<br>`-tags to a single `<br>`-tag
	 * in all text nodes of the given DOMDocument
	 *
	 * @param \DOMDocument $dom
	 */
	protected static function combineConsecutiveBrTags(DOMDocument $dom): void {
		$lo_brTags = $dom->getElementsByTagName('br');

		$li_brTags = $lo_brTags->length;

		for ($li_i = 0; $li_i < $li_brTags; $li_i++) {
			$lo_brTag = $lo_brTags->item($li_i);

			if (!$lo_brTag->parentNode) {
				continue;
			}

			// Check if the next sibling is a <br>-tag
			if ($lo_brTag->nextSibling) {
				if ($lo_brTag->nextSibling->nodeName === 'br') {
					$lo_brTag->parentNode->removeChild($lo_brTag->nextSibling);
					$li_i--;
					$li_brTags--;
				}
				elseif ($lo_brTag->nextSibling->nodeName === '#text' && str_starts_with($lo_brTag->nextSibling->nodeValue, "\u{A0}")) {
					// Left trim the text node from non-breaking spaces
					$lo_brTag->nextSibling->nodeValue = ltrim($lo_brTag->nextSibling->nodeValue, "\u{A0}");
				}

				// If the next sibling is a text node and only consist of whitespaces,
				// remove it. No space should occur after a <br>-tag.
				if ($lo_brTag->nextSibling->nodeName === '#text' && preg_match('/^([\s\n\r\t]|\xC2\xA0)*$/', $lo_brTag->nextSibling->nodeValue)) {
					$lo_brTag->parentNode->removeChild($lo_brTag->nextSibling);
				}
			}

			if ($lo_brTag->previousSibling) {
				if ($lo_brTag->previousSibling->nodeName === '#text' && str_ends_with($lo_brTag->previousSibling->nodeValue, "\u{A0}")) {
					// Right trim the text node from non-breaking spaces
					$lo_brTag->previousSibling->nodeValue = rtrim($lo_brTag->previousSibling->nodeValue, "\u{A0}");
				}
			}

			// Remove the next sibling if it is a <br>-tag to avoid multiple consecutive <br>-tags
			if ($lo_brTag->nextSibling->nodeName === 'br') {
				$lo_brTag->parentNode->removeChild($lo_brTag->nextSibling);
				$li_i--;
				$li_brTags--;
			}
		}
	}


	/**
	 * Combines consecutive empty `<p>`-tags
	 *
	 * @param \DOMDocument $dom
	 * @return void
	 */
	protected static function combineConsecutiveEmptyPTags(DOMDocument $dom): void {
		$lo_pTags = $dom->getElementsByTagName('p');

		$li_pTags = $lo_pTags->length;

		for ($li_i = 0; $li_i < $li_pTags; $li_i++) {
			$lo_pTag = $lo_pTags->item($li_i);

			if (!$lo_pTag->parentNode) {
				continue;
			}

			if (!preg_match('/^([\s\n\r\t]|\xC2\xA0\x{A0})*$/u', $lo_pTag->textContent)) {
				continue;
			}

			// If the current node has any non-textnode children, skip it
			if ($lo_pTag->hasChildNodes()) {
				/** @var \DOMNode $lo_childNode */
				foreach ($lo_pTag->childNodes as $lo_childNode) {
					if ($lo_childNode->nodeName !== '#text' && $lo_childNode->nodeType !== XML_ENTITY_REF_NODE) {
						continue 2;
					}
				}
			}

			$lo_nextSibling = $lo_pTag->nextSibling;

			if (!$lo_nextSibling) {
				continue;
			}

			if ($lo_nextSibling->nodeName === '#text') {
				$lo_nextSibling = $lo_nextSibling->nextSibling;
			}


			if (!$lo_nextSibling || $lo_nextSibling->nodeName !== 'p') {
				continue;
			}

			// Check if the next sibling is a <p>-tag and empty
			if (
				preg_match('/^([\s\n\r\t]|\xC2\xA0)*$/', $lo_nextSibling->textContent)
			) {
				if ($lo_nextSibling->hasChildNodes()) {
					// If the current node has any non-textnode children, skip it
					foreach ($lo_nextSibling->childNodes as $lo_childNode) {
						if ($lo_childNode->nodeName !== '#text' && $lo_childNode->nodeType !== XML_ENTITY_REF_NODE) {
							continue 2;
						}
					}
				}

				if ($lo_pTag->nextSibling->nodeName === '#text') {
					$lo_pTag->parentNode->removeChild($lo_pTag->nextSibling);
				}

				$lo_pTag->parentNode->removeChild($lo_nextSibling);
				$li_i--;
				$li_pTags--;
			}
		}
	}


	/**
	 * Combines consecutive whitespaces (including non-breaking spaces)
	 * in all text nodes of the given DOMDocument
	 *
	 * @param \DOMDocument $dom
	 * @return void
	 */
	protected static function combineConsecutiveWhitespace(DOMDocument $dom): void {
		$lo_path = new DOMXPath($dom);
		$lo_textNodes = $lo_path->query('//text()');

		foreach ($lo_textNodes as $lo_textNode) {
			if (in_array($lo_textNode->parentNode->nodeName, ['pre', 'code', 'script', 'style', 'textarea', 'body', 'ul', 'ol', 'dl'])) {
				continue;
			}

			$ls_content = $lo_textNode->nodeValue;

			// If at least one non-breaking space is found, use the non-breaking space as the replacement
			$ls_replacement = ' ';
			if (str_contains($ls_content, "\u{A0}")) {
				$ls_replacement = "\u{A0}";
			}

			$ls_content = preg_replace('/([\s\n\r\t]|\xC2\xA0){2,}/', $ls_replacement, $ls_content);

			$lo_textNode->nodeValue = $ls_content;
		}
	}


	/**
	 * Converts all leading and trailing `<br>`-tags inside `<p>`-tags to `<p>`-tags
	 *
	 * @param \DOMDocument $dom
	 * @return void
	 * @throws \DOMException
	 */
	protected static function convertLeadingAndTrailingBrTags(DOMDocument $dom): void {
		$lo_pTags = $dom->getElementsByTagName('p');

		/** @var \DOMNode $lo_pTag */
		foreach ($lo_pTags as $lo_pTag) {
			// As long as the first child of the <p>-tag is a <br>-tag, remove it and prepend a <p>-tag
			while ($lo_pTag->firstChild && $lo_pTag->firstChild->nodeName === 'br') {
				$lo_brTag = $lo_pTag->firstChild;
				$lo_pTag->removeChild($lo_brTag);

				$lo_newPTag = $dom->createElement('p', '&nbsp;');
				$lo_pTag->parentNode->insertBefore($lo_newPTag, $lo_pTag);

				// Create a new newline text node
				$lo_newTextNode = $dom->createTextNode("\n");
				// and insert it between the new <p>-tag and the old <p>-tag
				$lo_pTag->parentNode->insertBefore($lo_newTextNode, $lo_pTag);
			}

			// As long as the last child of the <p>-tag is a <br>-tag, remove it and append a <p>-tag
			while ($lo_pTag->lastChild && $lo_pTag->lastChild->nodeName === 'br') {
				$lo_brTag = $lo_pTag->lastChild;
				$lo_pTag->removeChild($lo_brTag);

				$lo_newPTag = $dom->createElement('p', '&nbsp;');
				if ($lo_pTag->nextSibling === null) {
					// Create a new \n text node
					$lo_newTextNode = $dom->createTextNode("\n");
					// and append it to the parent node
					$lo_pTag->parentNode->appendChild($lo_newTextNode);

					// Now append the new <p>-tag
					$lo_pTag->parentNode->appendChild($lo_newPTag);
				}
				else {
					// Insert the new <p>-tag after the current <p>-tag
					$lo_pTag->parentNode->insertBefore($lo_newPTag, $lo_pTag->nextSibling);

					// Create a new \n text node
					$lo_newTextNode = $dom->createTextNode("\n");
					// and insert it before the new <p>-tag
					$lo_pTag->parentNode->insertBefore($lo_newTextNode, $lo_newPTag);
				}
			}
		}
	}


	/**
	 * Removes leading and trailing `<br>`-tags inside any tag
	 *
	 * @param \DOMDocument $dom
	 * @return void
	 */
	protected static function removeLeadingAndTrailingBrTags(DOMDocument $dom): void {
		$lo_brTags = $dom->getElementsByTagName('br');

		foreach ($lo_brTags as $lo_brTag) {
			// Check if the <br> tag is the first child of its parent
			if ($lo_brTag->isSameNode($lo_brTag->parentNode->firstChild)) {
				$lo_brTag->parentNode->removeChild($lo_brTag);
			}
		}

		/**
		 * Remove the last <br> tag, if it is the last child of the parent
		 * It's necessary to get the <br> tags again, because the NodeList is updated after removing a child,
		 * and it's necessary to remove the last <br> tag multiple times, if there are multiple <br> tags at the end
		 */
		$lo_brTags = $dom->getElementsByTagName('br');

		for ($li_i = $lo_brTags->length - 1; $li_i >= 0; $li_i--) {
			$lo_brTag = $lo_brTags->item($li_i);

			if ($lo_brTag->isSameNode($lo_brTag->parentNode->lastChild)) {
				$lo_brTag->parentNode->removeChild($lo_brTag);
			}
		}
	}


	/**
	 * Removes leading and trailing empty tags from the given DOMDocument
	 *
	 * @param \DOMDocument $dom
	 * @return void
	 */
	protected static function removeLeadingAndTrailingEmptyTags(DOMDocument $dom): void {
		$lo_body = $dom->getElementsByTagName('body')->item(0);

		while ($lo_body->firstChild) {
			if (!preg_match('/^([\s\n\r\t]|\xC2\xA0)*$/', $lo_body->firstChild->textContent)) {
				break;
			}

			// If the first tag is a hr, break
			if ($lo_body->firstChild->nodeName === 'hr') {
				break;
			}

			// If the first child has a link or an img tag inside
			if (in_array($lo_body->firstChild->firstChild?->nodeName, ['a', 'awyiss-responsive-image', 'img', 'module'])) {
				break;
			}

			$lo_body->removeChild($lo_body->firstChild);
		}

		while ($lo_body->lastChild) {
			if (!preg_match('/^([\s\n\r\t]|\xC2\xA0)*$/', $lo_body->lastChild->textContent)) {
				break;
			}

			// If the last tag is a hr, break
			if ($lo_body->lastChild->nodeName === 'hr') {
				break;
			}

			// If the last child has a link or an img tag inside
			if (in_array($lo_body->lastChild->lastChild?->nodeName, ['a', 'awyiss-responsive-image', 'img', 'module'])) {
				break;
			}

			$lo_body->removeChild($lo_body->lastChild);
		}
	}


	/**
	 * Removes leading and trailing whitespaces (including non-breaking spaces)
	 * from each `<p>`- and `<li>`-tag of the given DOMDocument
	 *
	 * @param \DOMDocument $dom
	 * @return void
	 */
	protected static function removeLeadingAndTrailingWhitespace(DOMDocument $dom): void {
		// Get all `<p>`- and `<li>`-tags
		$lo_pTags = $dom->getElementsByTagName('p');
		$lo_liTags = $dom->getElementsByTagName('li');

		$lo_tags = array_merge(iterator_to_array($lo_pTags), iterator_to_array($lo_liTags));

		foreach ($lo_tags as $lo_tag) {
			while ($lo_tag->firstChild) {
				if ($lo_tag->firstChild->nodeName === '#text') {
					$ls_content = $lo_tag->firstChild->nodeValue;
					$ls_content = ltrim($ls_content, "\t\n\r\xC2\xA0 ");

					$lo_tag->firstChild->nodeValue = $ls_content;

					if ($ls_content !== '') {
						break;
					}

					if ($lo_tag->lastChild->isSameNode($lo_tag->firstChild)) {
						// Create a new text node with a non-breaking space
						$lo_tag->appendChild($dom->createTextNode("\u{A0}"));
						break;
					}

					$lo_tag->removeChild($lo_tag->firstChild);
				}
				elseif ($lo_tag->firstChild->nodeName === 'br') {
					$lo_tag->removeChild($lo_tag->firstChild);
				}
				else {
					break;
				}
			}

			while ($lo_tag->lastChild) {
				if ($lo_tag->lastChild->nodeName === '#text') {
					$ls_content = $lo_tag->lastChild->nodeValue;
					$ls_content = rtrim($ls_content, "\t\n\r\xC2\xA0 ");

					$lo_tag->lastChild->nodeValue = $ls_content;

					if ($ls_content !== '') {
						break;
					}

					if ($lo_tag->firstChild->isSameNode($lo_tag->lastChild)) {
						// Create a new text node with a non-breaking space
						$lo_tag->appendChild($dom->createTextNode("\u{A0}"));
						break;
					}

					$lo_tag->removeChild($lo_tag->lastChild);
				}
				else {
					break;
				}
			}
		}
	}


	/**
	 * Replaces `&nbsp;` after a dot, comma, question mark, or exclamation mark with a regular space
	 * in all text nodes of the given DOMDocument
	 *
	 * @param \DOMDocument $dom
	 * @return void
	 */
	protected static function replaceNbspAfterPunctuation(DOMDocument $dom): void {
		$lo_path = new DOMXPath($dom);
		$lo_textNodes = $lo_path->query('//text()');

		foreach ($lo_textNodes as $lo_textNode) {
			$ls_content = $lo_textNode->nodeValue;
			$ls_content = preg_replace('/([.,?!])\s*(?:&nbsp;|\xC2\xA0)/', '$1 ', $ls_content);
			$lo_textNode->nodeValue = $ls_content;
		}
	}


	/**
	 * Returns the contents of `<body>`-tag of the given DOMDocument as a string
	 *
	 * @param \DOMDocument $dom
	 * @return string|false
	 * @noinspection DuplicatedCode
	 */
	protected static function getBody(DOMDocument $dom): string|false {
		// Remove the doctype
		$dom->removeChild($dom->doctype);

		// Remove the opening and closing `<html>`-tags
		$dom->replaceChild($dom->firstChild->firstChild, $dom->firstChild);

		// Remove the opening and closing `<body>`-tags
		$lo_body = $dom->getElementsByTagName('body')->item(0);

		if (!$lo_body) {
			// Return the cleaned HTML
			return $dom->saveHTML();
		}

		while ($lo_body->firstChild) {
			$dom->appendChild($lo_body->firstChild);
		}

		$dom->removeChild($lo_body);

		// Return the cleaned HTML
		return $dom->saveHTML();
	}


	/**
	 * Checks if the given field is valid for the entity
	 * or if it exists in the attributes of the entity.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param string $field
	 * @return bool
	 */
	protected static function fieldIsValid(EntityInterface $entity, string $field): bool {
		// Field is valid if it exists in the entity and is a string
		if ($entity->has($field)) {
			return is_string($entity->get($field)) && $entity->isDirty($field);
		}

		// If the entity has no attributes or the attributes are not an instance of Entity,
		// the field is not valid
		if (
			!$entity->has('attributes') || !($entity->get('attributes') instanceof Entity)
		) {
			return false;
		}


		/**
		 * Field is valid if it exists in the attributes and is a string
		 *
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		return $entity->attributes->has($field) && is_string($entity->attributes->get($field)) && $entity->attributes->isDirty($field);
	}
}
