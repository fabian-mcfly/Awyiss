<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Attribute\AttributeOption;
use Awyiss\Model\Entity;
use Awyiss\Utility\Inflector;
use Cake\Datasource\EntityInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\I18n\Time;
use Cake\View\Helper\HtmlHelper as BaseHtmlHelper;
use UnitEnum;


/**
 * HtmlHelper
 * Extends CakePHP HtmlHelper with custom functionality
 *
 * @property \Awyiss\View\Helper\AttributesHelper $Attributes
 * @property \Awyiss\View\Helper\AuditHelper $Audit
 * @property \Cake\View\Helper\TimeHelper $Time
 * @property \Awyiss\View\Helper\UrlHelper $Url
 * @method \Awyiss\View\BackendView|\Awyiss\View\FrontendView getView()
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class HtmlHelper extends BaseHtmlHelper {
	/**
	 * @inheritDoc
	 */
	protected array $helpers = ['Attributes', 'Audit', 'Time', 'Url'];


	/**
	 * Display a field value based on the entity's table schema and field type
	 *
	 * @param mixed|null $value The value to display
	 * @param \Cake\Datasource\EntityInterface $entity The entity
	 * @param string $field The field name
	 * @param array<string, mixed> $options Additional options
	 * @return string The formatted value
	 * @throws \Exception
	 */
	public function formatValue(mixed $value, EntityInterface $entity, string $field, array $options = []): string {
		// Handle special fields first
		$specialFieldResult = null;
		if ($this->formatSpecialField($field, $entity, $value, $specialFieldResult)) {
			return $specialFieldResult;
		}

		// Handle null/missing values
		if ($value === null || $value === '') {
			return $options['emptyValue'] ?? '-';
		}

		// Handle password fields
		if ($field === 'password' || str_ends_with($field, 'Password')) {
			return $value ? '********' : '';
		}

		if (is_string($value)) {
			// Try to translate value based on the scope and field name
			$translation = __d($entity->getSource(), Inflector::underscore($field) . '_' . Inflector::underscore($value));

			if (!str_starts_with($translation, $entity->getSource() . '::')) {
				return $translation;
			}
		}

		// Get column type from entity schema
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$columnType = $entity->getColumnType($field);

		// Format based on type
		return $this->formatValueByType($value, $columnType, $options);
	}


	/**
	 * Display a field value based on the entity's table schema and field type
	 *
	 * @param \Cake\Datasource\EntityInterface $entity The entity
	 * @param string $field The field name
	 * @param array<string, mixed> $options Additional options
	 * @param bool $isAttribute Whether this is an attribute field
	 * @return string The formatted value
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function formatFieldValue(EntityInterface $entity, string $field, array $options = [], bool $isAttribute = false): string {
		$attributeOptions = null;
		if ($isAttribute) {
			$baseEntity = $entity;
			$entity = $entity->get('attributes');
			$attributeOptions = $this->Attributes->getAttributeOptions($baseEntity);
		}

		$value = $entity->has($field) ? $entity->get($field) : null;

		if (
			$attributeOptions
			&& $attributeOptions->getAttributeOption($field)
		) {
			/** @noinspection PhpParamsInspection */
			return $this->formatAttributeOptionValue($value, $attributeOptions->getAttributeOption($field), $baseEntity);
		}

		return $this->formatValue($value, $entity, $field, $options);
	}


	/**
	 * Check if this is a special field that needs custom handling
	 *
	 * @param string $field The field name
	 * @param \Cake\Datasource\EntityInterface $entity The entity
	 * @param mixed $value The value
	 * @param string|null &$result The result if special field handling applies
	 * @return bool True if this is a special field
	 */
	protected function formatSpecialField(string $field, EntityInterface $entity, mixed $value, ?string &$result): bool {
		static $languages = $this->getView()->get('languages') ?? $this
			->getView()
			->getTwig()
			->getGlobals()['languages'] ?? [];

		// Language shortcode
		if ($field === 'languageShortcode') {
			$result = $value && isset($languages['Frontend'][ $value ]) ? $languages['Frontend'][ $value ]['title'] : null;

			if (!$result) {
				$result = $value && isset($languages['Backend'][ $value ]) ? $languages['Backend'][ $value ]['title'] : '-';
			}

			return true;
		}

		// Template ID fields
		$templateFields = [
			'contentTemplateId' => 'contentTemplate',
			'emailTemplateId' => 'emailTemplate',
			'globalContentTemplateId' => 'globalContentTemplate',
			'pageTemplateId' => 'pageTemplate',
		];

		if (isset($templateFields[ $field ])) {
			$result = $this->getTemplateLabel($entity, $field, $templateFields[ $field ]);

			return true;
		}

		if (in_array($field, ['createdBy', 'changedBy'])) {
			$result = $entity->hasValue($field) ? $this->Audit->getUsername($entity->get($field)) : '-';

			return true;
		}

		// Categories
		$categoriesIdentifier = $this->getView()->get('_categoriesIdentifier');
		if ($categoriesIdentifier && Inflector::underscore($categoriesIdentifier) === Inflector::underscore($field)) {
			$categories = $this->getView()->get('categories');
			$result = $entity->hasValue($field) ? ($categories['simple'][ $entity->get($field) ] ?? '-') : '-';

			return true;
		}

		$categoriesConfig = $this->getView()->get('_categories') ?: [];
		$categoriesField = $categoriesConfig['categories']['config']['field'] ?? null;
		if ($categoriesField && $categoriesField === $field) {
			$categories = $categoriesConfig['categories']['simple'];
			$result = $entity->hasValue($field) ? ($categories[ $entity->get($field) ] ?? '-') : '-';

			return true;
		}

		return false;
	}


	/**
	 * Get template label for template ID fields
	 *
	 * @param \Cake\Datasource\EntityInterface $entity The entity
	 * @param string $idField The ID field name
	 * @param string $associationName The association property name
	 * @return string The template label
	 */
	protected function getTemplateLabel(EntityInterface $entity, string $idField, string $associationName): string {
		// Try to get from association first
		if ($entity->has($associationName)) {
			$template = $entity->get($associationName);
			if (is_object($template) && is_a($template, Entity::class)) {
				return $template->label;
			}
		}

		// Try to get from view variables
		$templatesVar = $idField;
		if (str_ends_with($templatesVar, 'Id')) {
			$templatesVar = substr($templatesVar, 0, -2);
			$templatesVar = Inflector::pluralize($templatesVar);
		}

		$templates = $this->getView()->get($templatesVar);
		$templateId = $entity->get($idField);

		if ($templates && $templateId && isset($templates[ $templateId ])) {
			return $templates[ $templateId ]->label ?? '-';
		}

		return '-';
	}


	/**
	 * Format value based on type
	 *
	 * @param mixed $value The value to format
	 * @param string $columnType The column type
	 * @param array<string, mixed> $options Additional options
	 * @return string The formatted value
	 * @throws \Exception
	 */
	public function formatValueByType(mixed $value, string $columnType, array $options = []): string {
		static $dateFormat = $this->getView()->get('dateFormat') ?? $this
			->getView()
			->getTwig()
			->getGlobals()['dateFormat'] ?? 'Y-m-d';
		static $timeFormat = $this->getView()->get('timeFormat') ?? $this
			->getView()
			->getTwig()
			->getGlobals()['timeFormat'] ?? 'H:i:s';

		// Handle boolean values
		if (
			$value === true
			|| $value === 'true'
			|| (
				$columnType === 'boolean'
				&& !empty($value)
			)
		) {
			return '<i class="las la-check">' . __('true') . '</i>';
		}

		if (
			$value === false
			|| $value === 'false'
			|| (
				$columnType === 'boolean'
				&& empty($value)
			)
		) {
			return '<i class="las la-times">' . __('false') . '</i>';
		}

		// Handle enum values
		if ($value instanceof UnitEnum) {
			return $value->name;
		}

		// Handle datetime/date/time values
		if ($columnType === 'datetime' || $columnType === 'timestamp') {
			return $this->Time->nice($value);
		}

		if ($columnType === 'date') {
			return $this->Time->i18nFormat($value, $dateFormat);
		}

		if ($columnType === 'time') {
			if ($value instanceof Time) {
				return $value->format('H:i');
			}

			return $this->Time->i18nFormat($value, $timeFormat);
		}

		if ($value instanceof Date || $value instanceof Time || $value instanceof DateTime) {
			// Fallback to nice() if column type is unknown
			return $this->Time->nice($value);
		}

		// Handle integer/float values
		if ($columnType === 'integer' || $columnType === 'biginteger' || $columnType === 'smallinteger') {
			return (string)$value;
		}

		if ($columnType === 'float' || $columnType === 'decimal') {
			$decimals = $options['decimals'] ?? 2;

			return number_format((float)$value, $decimals, '.', '');
		}

		// Handle JSON values
		if ($columnType === 'json') {
			return !empty($value) ? json_encode($value) : '';
		}

		// Handle arrays/objects
		if (is_array($value) || is_object($value)) {
			return json_encode($value);
		}

		// Default: cast to string
		return strip_tags((string)$value);
	}


	/**
	 * Format attribute option value based on its readable option
	 *
	 * @param mixed $value
	 * @param \Awyiss\Attribute\AttributeOption $attributeOption
	 * @param \Awyiss\Model\Entity $entity
	 * @return string
	 */
	protected function formatAttributeOptionValue(mixed $value, AttributeOption $attributeOption, Entity $entity): string {
		$options = $attributeOption->getOptions(true, $entity);

		if (!$value && array_key_exists('', $options)) {
			return $options[''];
		}

		return $options[ $value ] ?? '-';
	}
}
