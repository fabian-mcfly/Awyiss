<?php declare(strict_types=1);


namespace Awyiss\Utility\Content;


use Awyiss\Model\Entity;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use Dom\HTMLCollection;
use Dom\HTMLDocument;
use Dom\XPath;
use InvalidArgumentException;


/**
 * Cleans HTML content of an entity
 */
class HtmlCleaner {
	/**
	 * No cleaning at all
	 */
	public const string CLEAN_NONE = 'none';
	/**
	 * Moderate cleaning.
	 *
	 * - Converts all leading and trailing `<br>`-tags to `<p>`-tags
	 * - Removes leading and trailing `<br>`-tags inside any tag
	 * - Replaces `&nbsp;` after a dot, comma, question mark, or exclamation mark with a regular space
	 * - Removes leading and trailing whitespaces (including non-breaking spaces) from each tag
	 * - Combines consecutive whitespaces (including non-breaking spaces)
	 */
	public const string CLEAN_MODERATE = 'moderate';
	/**
	 * Strict cleaning.
	 *
	 * Does everything the moderate cleaning does and additionally
	 * - Cleans up all tags that have nothing inside but whitespaces, `<br>`-tags` or non-breaking spaces
	 * - Converts multiple consecutive `<br>`-tags to a single `<br>`-tag
	 * - Removes leading and trailing empty tags
	 */
	public const string CLEAN_STRICT = 'strict';


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
	 */
	public static function clean(EntityInterface $entity, string $method = self::CLEAN_STRICT, array $fields = []): void {
		$fields = $fields ?: static::getDefaultFields($entity);

		if ($method === 'none') {
			return;
		}

		foreach ($fields as $field) {
			if (!static::fieldIsValid($entity, $field)) {
				continue;
			}

			if ($method === self::CLEAN_MODERATE) {
				static::cleanModerate($entity, $field);
				continue;
			}

			if ($method === self::CLEAN_STRICT) {
				static::cleanStrict($entity, $field);
				continue;
			}

			throw new InvalidArgumentException(
				sprintf('Invalid clean method. Expected one of `none`, `moderate`, `strict`. `%s` given.', $method)
			);
		}

		if ($entity->has('_translations')) {
			foreach ($entity->get('_translations') as $translation) {
				static::clean($translation, $method, $fields);
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
		/** @var \Awyiss\Model\Table $table */
		$table = FactoryLocator::get('Table')->get($entity->getSource());

		if (!$table->hasBehavior('Attributes') || !$table->hasAttributes()) {
			return static::$defaultFields;
		}

		$fields = static::$defaultFields;

		foreach ($table->getAttributes() as $attribute) {
			if ($attribute->inputType === 'texteditor') {
				$fields[] = $attribute->identifier;
			}
		}

		return $fields;
	}


	/**
	 * Cleans the given field with the moderate method
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param string $field
	 * @return void
	 */
	protected static function cleanModerate(EntityInterface $entity, string $field): void {
		$value = $entity->get($field);
		$dom = static::getDom($value);

		// Remove leading and trailing whitespaces (including non-breaking spaces) from each tag
		static::removeLeadingAndTrailingWhitespaceFromTags($dom);

		// Convert all leading and trailing `<br>`-tags to `<p>`-tags
		static::removeLeadingAndTrailingBrTagsInParagraphs($dom);

		// Remove leading and trailing `<br>`-tags inside any tag
		static::removeLeadingAndTrailingBrTags($dom);

		// Replace &nbsp; after a dot, comma, question mark, or exclamation mark with a regular space
		static::replaceNbspAfterPunctuation($dom);

		// Combine consecutive whitespaces
		static::combineConsecutiveWhitespace($dom);

		// Set the cleaned value back to the entity
		$entity->set($field, trim(static::getBody($dom)) ?: null);
	}


	/**
	 * Cleans the given field with the moderate method
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param string $field
	 * @return void
	 */
	protected static function cleanStrict(EntityInterface $entity, string $field): void {
		$value = $entity->get($field);
		$dom = static::getDom($value);

		// Remove leading and trailing whitespaces (including non-breaking spaces) from each tag
		static::removeLeadingAndTrailingWhitespaceFromTags($dom);

		// Convert all leading and trailing `<br>`-tags to `<p>`-tags
		static::removeLeadingAndTrailingBrTagsInParagraphs($dom);

		// Remove leading and trailing `<br>`-tags inside any tag
		static::removeLeadingAndTrailingBrTags($dom);

		// Replace &nbsp; after a dot, comma, question mark, or exclamation mark with a regular space
		static::replaceNbspAfterPunctuation($dom);

		// Clean up all tags that have nothing inside but whitespaces, `<br>`-tags` or non-breaking spaces
		static::cleanEmptyTags($dom);

		// Convert multiple consecutive `<br>`-tags to a single `<br>`-tag
		static::combineConsecutiveBrTags($dom);

		// Combine consecutive whitespaces
		static::combineConsecutiveWhitespace($dom);

		// Combine consecutive empty `<p>`-tags
		static::combineConsecutiveEmptyPTags($dom);

		// Remove leading and trailing empty tags inside the `<body>`-tag
		static::removeLeadingAndTrailingEmptyTags($dom);

		// Set the cleaned value back to the entity
		$entity->set($field, trim(static::getBody($dom)) ?: null);
	}


	/**
	 * Creates a \Dom\HTMLDocument from the given HTML string
	 *
	 * @param string $value
	 * @return \Dom\HTMLDocument
	 */
	protected static function getDom(string $value): HTMLDocument {
		// Load the HTML string into the \Dom\HTMLDocument
		return HTMLDocument::createFromString($value, LIBXML_NOERROR, 'UTF-8');
	}


	/**
	 * Cleans up all tags that have nothing inside but whitespaces, `<br>`-tags` or,
	 * non-breaking spaces as either `&nbsp;` or `\xC2\xA0`
	 * and replaces the content with a non-breaking space
	 *
	 * @param \Dom\HTMLDocument $dom
	 * @return void
	 */
	protected static function cleanEmptyTags(HtmlDocument $dom): void {
		// Clean all list items, list tags, starting from the items, because if the list items are empty, the list tags will be empty too
		$tags = array_merge(
			iterator_to_array($dom->getElementsByTagName('li')),
			iterator_to_array($dom->getElementsByTagName('dd')),
			iterator_to_array($dom->getElementsByTagName('dt')),
			iterator_to_array($dom->getElementsByTagName('ul')),
			iterator_to_array($dom->getElementsByTagName('ol')),
			iterator_to_array($dom->getElementsByTagName('dl'))
		);
		static::doCleanEmptyTags($tags, [
			'LI' => false,
			'DD' => false,
			'DT' => false,
			'UL' => false,
			'OL' => false,
			'DL' => false,
		]);

		// Get all direct child tags inside the `<body>`-tag
		$body = $dom->getElementsByTagName('body')->item(0);
		$tags = $body->getElementsByTagName('*');

		static::doCleanEmptyTags($tags, [
			'UL' => false,
			'OL' => false,
			'DL' => false,
		]);
	}


	/**
	 * Converts multiple consecutive `<br>`-tags to a single `<br>`-tag
	 * in all text nodes of the given \Dom\HTMLDocument
	 *
	 * @param \Dom\HTMLDocument $dom
	 */
	protected static function combineConsecutiveBrTags(HtmlDocument $dom): void {
		$brTags = $dom->querySelectorAll('br');

		$brTagCounter = $brTags->length;

		for ($i = 0; $i < $brTagCounter; $i++) {
			$brTag = $brTags->item($i);

			if (!$brTag->parentNode) {
				continue;
			}

			// Check if the next sibling is a <br>-tag
			if ($brTag->nextSibling) {
				if ($brTag->nextSibling->nodeName === 'BR') {
					$brTag->parentNode->removeChild($brTag->nextSibling);
					$i--;
					$brTagCounter--;
				}
				elseif ($brTag->nextSibling->nodeName === '#text' && str_starts_with($brTag->nextSibling->nodeValue, "\u{A0}")) {
					// Left trim the text node from non-breaking spaces
					$brTag->nextSibling->nodeValue = ltrim($brTag->nextSibling->nodeValue, "\u{A0}");
				}

				// If the next sibling is a text node and only consist of whitespaces,
				// remove it. No space should occur after a <br>-tag.
				if (
					$brTag->nextSibling
					&& $brTag->nextSibling->nodeName === '#text'
					&& preg_match('/^([\s\n\r\t]|\xC2\xA0)*$/', $brTag->nextSibling->nodeValue)
				) {
					$brTag->parentNode->removeChild($brTag->nextSibling);
				}
			}

			if ($brTag->previousSibling) {
				if ($brTag->previousSibling->nodeName === '#text' && str_ends_with($brTag->previousSibling->nodeValue, "\u{A0}")) {
					// Right trim the text node from non-breaking spaces
					$brTag->previousSibling->nodeValue = rtrim($brTag->previousSibling->nodeValue, "\u{A0}");
				}
			}

			// Remove the next sibling if it is a <br>-tag to avoid multiple consecutive <br>-tags
			if ($brTag->nextSibling && $brTag->nextSibling->nodeName === 'BR') {
				$brTag->parentNode->removeChild($brTag->nextSibling);
				$i--;
				$brTagCounter--;
			}
		}
	}


	/**
	 * Combines consecutive empty `<p>`-tags
	 *
	 * @param \Dom\HTMLDocument $dom
	 * @return void
	 */
	protected static function combineConsecutiveEmptyPTags(HtmlDocument $dom): void {
		$pTags = $dom->querySelectorAll('p');

		$pTagCounter = $pTags->length;
		$removeIndices = [];

		for ($i = 0; $i < $pTagCounter; $i++) {
			$pTag = $pTags->item($i);

			if (!$pTag->parentNode) {
				continue;
			}

			if (!preg_match('/^([\s\n\r\t]|\xC2\xA0\x{A0})*$/u', $pTag->textContent)) {
				continue;
			}

			// If the current node has any non-text node children, skip it
			if ($pTag->hasChildNodes()) {
				/** @var \Dom\Node $childNode */
				foreach ($pTag->childNodes as $childNode) {
					if ($childNode->nodeName !== '#text' && $childNode->nodeType !== XML_ENTITY_REF_NODE) {
						continue 2;
					}
				}
			}

			$nextSibling = $pTag->nextSibling;

			if (!$nextSibling) {
				continue;
			}

			if ($nextSibling->nodeName === '#text') {
				$nextSibling = $nextSibling->nextSibling;
			}

			if (!$nextSibling || $nextSibling->nodeName !== 'P') {
				continue;
			}

			// Check if the next sibling is a <p>-tag and empty
			if (preg_match('/^([\s\n\r\t]|\xC2\xA0)*$/', $nextSibling->textContent)) {
				if ($nextSibling->hasChildNodes()) {
					// If the current node has any non-text node children, skip it
					foreach ($nextSibling->childNodes as $childNode) {
						if ($childNode->nodeName !== '#text' && $childNode->nodeType !== XML_ENTITY_REF_NODE) {
							continue 2;
						}
					}
				}

				if ($pTag->nextSibling && $pTag->nextSibling->nodeName === '#text') {
					$pTag->parentNode->removeChild($pTag->nextSibling);
				}

				$removeIndices[] = $i + 1;
			}
		}

		// Remove the empty <p>-tags in reverse order to avoid index issues
		rsort($removeIndices);
		foreach ($removeIndices as $index) {
			$pTag = $pTags->item($index);

			$pTag->parentNode?->removeChild($pTag);
		}
	}


	/**
	 * Combines consecutive whitespaces (including non-breaking spaces)
	 * in all text nodes of the given \Dom\HTMLDocument
	 *
	 * @param \Dom\HTMLDocument $dom
	 * @return void
	 */
	protected static function combineConsecutiveWhitespace(HtmlDocument $dom): void {
		$path = new XPath($dom);
		$textNodes = $path->query('//text()');

		foreach ($textNodes as $textNode) {
			if (in_array($textNode->parentNode->nodeName, ['PRE', 'CODE', 'SCRIPT', 'STYLE', 'TEXTAREA', 'BODY', 'UL', 'OL', 'DL'])) {
				continue;
			}

			$content = $textNode->nodeValue;

			// If at least one non-breaking space is found, use the non-breaking space as the replacement
			$replacement = ' ';
			if (str_contains($content, "\u{A0}")) {
				$replacement = "\u{A0}";
			}

			$content = preg_replace('/([\s\n\r\t]|\xC2\xA0){2,}/', $replacement, $content);

			$textNode->nodeValue = $content;
		}
	}


	/**
	 * Removes all leading and trailing `<br>`-tags inside `<p>`-tags
	 *
	 * @param \Dom\HTMLDocument $dom
	 * @return void
	 */
	protected static function removeLeadingAndTrailingBrTagsInParagraphs(HtmlDocument $dom): void {
		$pTags = $dom->querySelectorAll('p');

		/** @var \Dom\Node $pTag */
		foreach ($pTags as $pTag) {
			// As long as the first child of the <p>-tag is a <br>-tag, remove it
			while ($pTag->firstChild && $pTag->firstChild->nodeName === 'BR') {
				$brTag = $pTag->firstChild;
				$pTag->removeChild($brTag);
			}

			// As long as the last child of the <p>-tag is a <br>-tag, remove it
			while ($pTag->lastChild && $pTag->lastChild->nodeName === 'BR') {
				$brTag = $pTag->lastChild;
				$pTag->removeChild($brTag);
			}
		}
	}


	/**
	 * Removes leading and trailing `<br>`-tags inside any tag
	 *
	 * @param \Dom\HTMLDocument $dom
	 * @return void
	 */
	protected static function removeLeadingAndTrailingBrTags(HtmlDocument $dom): void {
		$brTags = $dom->querySelectorAll('br');

		foreach ($brTags as $brTag) {
			// Check if the <br> tag is the first child of its parent
			if ($brTag->isSameNode($brTag->parentNode->firstChild)) {
				$brTag->parentNode->removeChild($brTag);
			}
		}

		/**
		 * Remove the last <br> tag, if it is the last child of the parent
		 * It's necessary to get the <br> tags again, because the NodeList is updated after removing a child,
		 * and it's necessary to remove the last <br> tag multiple times, if there are multiple <br> tags at the end.
		 */
		$brTags = $dom->querySelectorAll('br');

		for ($i = $brTags->length - 1; $i >= 0; $i--) {
			$brTag = $brTags->item($i);

			if (!$brTag->isSameNode($brTag->parentNode->lastChild)) {
				continue;
			}

			// Check if the parent node ha a follow-up sibling of type text node and not empty
			if (
				$brTag->parentNode->nextSibling && $brTag->parentNode->nextSibling->nodeName === '#text'
				&& !preg_match(
					'/^([\s\n\r\t]|\xC2\xA0)*$/',
					$brTag->parentNode->nextSibling->nodeValue
				)
			) {
				// If a next sibling exists, move the br between the parent and the next sibling
				$brTag->parentNode->parentNode->insertBefore($brTag, $brTag->parentNode->nextSibling);
				continue;
			}

			// Remove the <br> tag
			$brTag->parentNode->removeChild($brTag);
		}
	}


	/**
	 * Removes leading and trailing empty tags from the given \Dom\HTMLDocument
	 *
	 * @param \Dom\HTMLDocument $dom
	 * @return void
	 */
	protected static function removeLeadingAndTrailingEmptyTags(HtmlDocument $dom): void {
		$body = $dom->querySelector('body');

		while ($body->firstChild) {
			if (!preg_match('/^([\s\n\r\t]|\xC2\xA0)*$/', $body->firstChild->textContent)) {
				break;
			}

			// If the first tag is a hr, break
			if ($body->firstChild->nodeName === 'HR') {
				break;
			}

			// If the first child has a link or an img tag inside
			if (in_array($body->firstChild->firstChild?->nodeName, ['A', 'AWYISS-RESPONSIVE-IMAGE', 'IMG', 'WIDGET'])) {
				break;
			}

			$body->removeChild($body->firstChild);
		}

		while ($body->lastChild) {
			if (!preg_match('/^([\s\n\r\t]|\xC2\xA0)*$/', $body->lastChild->textContent)) {
				break;
			}

			// If the last tag is a hr, break
			if ($body->lastChild->nodeName === 'HR') {
				break;
			}

			// If the last child has a link or an img tag inside
			if (in_array($body->lastChild->lastChild?->nodeName, ['A', 'AWYISS-RESPONSIVE-IMAGE', 'IMG', 'WIDGET'])) {
				break;
			}

			$body->removeChild($body->lastChild);
		}
	}


	/**
	 * Removes leading and trailing whitespaces (including non-breaking spaces)
	 * from each tag inside the `<body>`-tag of the given \Dom\HTMLDocument
	 *
	 * @param \Dom\HTMLDocument $dom
	 * @return void
	 */
	protected static function removeLeadingAndTrailingWhitespaceFromTags(HtmlDocument $dom): void {
		// Get all tags inside `<body>`
		$tags = $dom->querySelectorAll('body *');
		$skipTags = ['PRE', 'CODE', 'SCRIPT', 'STYLE', 'TEXTAREA'];

		foreach ($tags as $tag) {
			if (in_array($tag->nodeName, $skipTags, true)) {
				continue;
			}

			$childRemoved = false;

			while ($tag->firstChild) {
				if ($tag->firstChild->nodeName === '#text') {
					$content = $tag->firstChild->nodeValue;
					$content = mb_ltrim($content);

					$tag->firstChild->nodeValue = $content;

					if ($content !== '') {
						break;
					}

					if ($tag->lastChild->isSameNode($tag->firstChild)) {
						// Create a new text node with a non-breaking space
						$tag->textContent = "\xC2\xA0";
						break;
					}

					$tag->removeChild($tag->firstChild);
					$childRemoved = true;
				}
				elseif ($tag->firstChild->nodeName === 'BR') {
					$tag->removeChild($tag->firstChild);
					$childRemoved = true;
				}
				else {
					break;
				}
			}

			while ($tag->lastChild) {
				if ($tag->lastChild->nodeName === '#text') {
					$content = $tag->lastChild->nodeValue;
					$content = mb_rtrim($content);

					$tag->lastChild->nodeValue = $content;

					if ($content !== '') {
						break;
					}

					if ($tag->firstChild->isSameNode($tag->lastChild)) {
						// Create a new text node with a non-breaking space
						$tag->textContent = "\xC2\xA0";
						break;
					}

					$tag->removeChild($tag->lastChild);
					$childRemoved = true;
				}
				else {
					break;
				}
			}

			// If the tag is empty, add a non-breaking space
			if (!$tag->firstChild) {
				// But only if anything has been removed from the tag, otherwise it would add a non-breaking space to an
				// empty tag that was already empty
				if ($childRemoved) {
					$tag->textContent = "\xC2\xA0";
				}
			}
		}
	}


	/**
	 * Replaces `&nbsp;` after a dot, comma, question mark, or exclamation mark with a regular space
	 * in all text nodes of the given \Dom\HTMLDocument
	 *
	 * @param \Dom\HTMLDocument $dom
	 * @return void
	 */
	protected static function replaceNbspAfterPunctuation(HtmlDocument $dom): void {
		$path = new XPath($dom);
		$textNodes = $path->query('//text()');

		foreach ($textNodes as $textNode) {
			$content = $textNode->nodeValue;
			$content = preg_replace('/([.,?!])\s*(?:&nbsp;|\xC2\xA0)/', '$1 ', $content);
			$textNode->nodeValue = $content;
		}
	}


	/**
	 * Returns the contents of `<body>`-tag of the given \Dom\HTMLDocument as a string
	 *
	 * @param \Dom\HTMLDocument $dom
	 * @return string|false
	 */
	protected static function getBody(HtmlDocument $dom): string|false {
		$html = '';

		// Remove the opening and closing `<body>`-tags
		$body = $dom->querySelector('body');

		while ($body->firstChild) {
			$html .= $dom->saveHTML($body->firstChild);
			$body->removeChild($body->firstChild);
		}

		// Return the cleaned HTML
		return $html;
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
			!$entity->has('attributes')
			|| !($entity->get('attributes') instanceof Entity)
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


	/**
	 * @param \Dom\HTMLCollection|array $tags
	 * @param array<string, bool|string> $handleEmptyTags A list of tags to handle differently. The key is the tag name, the value is
	 *  either `false` to remove the tag or a string to replace the content with. Node names not in the list will be replaced with
	 *  a non-breaking space.
	 * @return void
	 */
	protected static function doCleanEmptyTags(HTMLCollection|array $tags, array $handleEmptyTags = []): void {
		/** @var \Dom\Node $tag */
		foreach ($tags as $tag) {
			if (in_array($tag->nodeName, ['BR', 'HR', 'IMG', 'AWYISS-RESPONSIVE-IMAGE'])) {
				continue;
			}

			$content = $tag->textContent;

			// If the text content is a non-breaking space, or if the text content is empty and the tag has child nodes, skip it,
			// but only if the tag is not in the list of tags to handle differently or not set to `false`.
			// This handles `<a>`-tags that have an `<img>`-tag inside, which is a valid case and should not be removed.
			// Empty list items will be removed if they are in the list of tags to handle differently and set to `false`.
			if (
				(
					in_array($content, ['&nbsp;', "\u{A0}"], true)
					|| (
						$content === ''
						&& $tag->hasChildNodes()
					)
				)
				&& (
					!isset($handleEmptyTags[ $tag->nodeName ])
					|| $handleEmptyTags[ $tag->nodeName ] !== false
				)
			) {
				continue;
			}

			// Check if the content of the tag is empty or only contains whitespaces or non-breaking spaces
			if (preg_match('/^([\s\n\r\t]|\xC2\xA0)*$/', $content)) {
				if (
					array_key_exists($tag->nodeName, $handleEmptyTags)
					&& $handleEmptyTags[$tag->nodeName] === false
				) {
					if ($tag->nextSibling && $tag->nextSibling->nodeName === '#text') {
						$tag->parentNode->removeChild($tag->nextSibling);
					}

					// Empty lists? Remove them
					$tag->parentNode->removeChild($tag);
					continue;
				}

				while ($tag->firstChild) {
					$tag->removeChild($tag->firstChild);
				}

				// Add the replacement content if specified, otherwise add a non-breaking space
				if (
					array_key_exists($tag->nodeName, $handleEmptyTags)
					&& is_string($handleEmptyTags[$tag->nodeName])
				) {
					$tag->textContent = $handleEmptyTags[$tag->nodeName];
					continue;
				}

				$tag->textContent = "\xC2\xA0";
			}
		}
	}
}
