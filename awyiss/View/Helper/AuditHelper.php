<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Entity\User;
use Awyiss\Utility\Arrays;
use Awyiss\Utility\Inflector;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Hash;
use Cake\View\Helper;
use UnitEnum;


/**
 * AuditHelper
 *
 * @property \Awyiss\View\Helper\HtmlHelper $Html
 * @property \Awyiss\View\Helper\MediaHelper $Media
 * @property \Cake\View\Helper\TimeHelper $Time
 * @method \Awyiss\View\BackendView|\Awyiss\View\FrontendView getView()
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class AuditHelper extends Helper {
	use LocatorAwareTrait;


	/**
	 * User cache
	 *
	 * @var array<int, \Awyiss\Model\Entity\User>|null
	 */
	protected static ?array $userCache = null;


	/**
	 * @inheritDoc
	 */
	protected array $helpers = ['Html', 'Media', 'Time'];


	/**
	 * @param int $userId
	 * @return \Awyiss\Model\Entity\User|null
	 */
	public function getUser(int $userId): ?User {
		if (!isset(static::$userCache)) {
			$this->loadUsers();
		}

		return static::$userCache[ $userId ] ?? null;
	}


	/**
	 * @param int $userId
	 * @return string
	 */
	public function getUsername(int $userId): string {
		return $this->getUser($userId)?->username ?? __('user_unknown');
	}


	/**
	 * @return void
	 */
	protected function loadUsers(): void {
		$usersTable = $this->fetchTable('Users');
		$users = $usersTable->find()->all();

		static::$userCache = $users->indexBy('id')->toArray();
	}


	/**
	 * Format a field value for display in audit history
	 *
	 * @param string $field The field name
	 * @param array $data The data array (oldData or newData)
	 * @param \Cake\Datasource\EntityInterface $entity The current entity
	 * @param array<string, array{name: string, property: string, type: string}> $associations The associations
	 * @param bool $differsFromCurrent Whether the value differs from current
	 * @param \Awyiss\Model\Entity\Attribute|null $attribute The attribute entity if this is an attribute
	 * @param array|null $attributeOptions The attribute options if applicable
	 * @param array $settings Additional settings (media, languages, etc.)
	 * @return string The formatted value
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function formatOldValue(
		string $field,
		array $data,
		EntityInterface $entity,
		array $associations,
		bool $differsFromCurrent,
		?Attribute $attribute = null,
		?array $attributeOptions = null,
		array $settings = []
	): string {
		// Handle associations
		if (isset($associations[ $field ])) {
			return $this->formatAssociationValue($field, $data, $entity, $associations, $differsFromCurrent);
		}

		$value = $data[ $field ] ?? null;

		return $this->formatFieldValue($field, $value, $entity, $attribute, $attributeOptions, $settings);
	}


	/**
	 * Format association value for display
	 *
	 * @param string $field The field name
	 * @param array $data The data array
	 * @param \Cake\Datasource\EntityInterface $entity The current entity
	 * @param array<string, array{name: string, property: string, type: string}> $associations The associations
	 * @param bool $differsFromCurrent Whether the value differs from current
	 * @return string The formatted value
	 */
	protected function formatAssociationValue(
		string $field,
		array $data,
		EntityInterface $entity,
		array $associations,
		bool $differsFromCurrent
	): string {
		$association = $associations[ $field ];
		$associationField = $association['property'];
		$value = $data[ $field ] ?? null;

		// Handle many-to-many and one-to-many associations
		if (empty($value)) {
			return '';
		}

		if (in_array($association['type'], ['oneToMany', 'manyToMany'])) {
			// Special handling for UsergroupPermissions
			if ($association['name'] === 'UsergroupPermissions') {
				return $this->formatUsergroupPermissions($value);
			}

			// Default: list of entities
			return $this->formatListItems($value);
		}

		// If value didn't change, use current entity
		if (!$differsFromCurrent && $entity->has($associationField)) {
			$associatedEntity = $entity->get($associationField);
			$label = $associatedEntity->label ?? '';
			$id = $associatedEntity->id ?? null;
		}
		// Try to get from included association data
		elseif (isset($data[ $associationField ])) {
			$associatedEntity = $data[ $associationField ];
			$label = $associatedEntity->label ?? __('unknown_entity');
			$id = $associatedEntity->id ?? null;
		}
		// Fallback: just show ID
		else {
			return __('unknown_entity') . ' (ID: ' . $value . ')';
		}

		$result = $label;
		if ($id) {
			$result .= ' (ID: ' . $id . ')';
		}

		return $result;
	}


	/**
	 * Format a field value (common logic for both audit data and current values)
	 *
	 * @param string $field The field name
	 * @param mixed $value The value to format
	 * @param \Cake\Datasource\EntityInterface $entity The entity (for schema lookup)
	 * @param \Awyiss\Model\Entity\Attribute|null $attribute The attribute entity if this is an attribute
	 * @param array|null $attributeOptions The attribute options if applicable
	 * @param array $settings Additional settings (media, languages, etc.)
	 * @return string The formatted value
	 * @throws \Exception
	 */
	protected function formatFieldValue(
		string $field,
		mixed $value,
		EntityInterface $entity,
		?Attribute $attribute = null,
		?array $attributeOptions = null,
		array $settings = []
	): string {
		$isAttribute = $attribute !== null;

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$columnType = $entity->getColumnType($field);

		if ($field === 'columnWidth') {
			return $entity->column->width->label ?? '';
		}

		if ($field === 'columnIndent') {
			return $entity->column->indent->label ?? '';
		}

		// Handle HTML/text fields (audit-specific: wrapped in <code>)
		if (in_array($field, ['text', 'textHtml', 'successMessage']) || ($isAttribute && $attribute->inputType === 'texteditor')) {
			$media = $settings['media'] ?? [];

			return '<code>' . h($this->Media->rebuildSimpleImageTagsInText($value, $media)) . '</code>';
		}

		// Handle CSS fields (audit-specific: readonly textarea)
		if ($field === 'css') {
			return '<textarea data-readonly="1" data-css-editor="1">' . h($value) . '</textarea>';
		}

		// Handle password fields
		if ($field === 'password' || ($isAttribute && $attribute->inputType === 'password')) {
			return $value ? '********' : '';
		}

		if (is_string($value)) {
			// Try to translate value based on the scope and field name
			$translation = __d($entity->getSource(), Inflector::underscore($field) . '_' . Inflector::underscore($value));

			if (!str_starts_with($translation, $entity->getSource() . '::')) {
				return $translation;
			}
		}

		// Handle attribute options
		if (
			$attributeOptions
			&& (
				is_string($value)
				|| is_int($value)
			)
		) {
			return $attributeOptions[ $value ] ?? '';
		}

		// Use HtmlHelper for all standard value formatting (boolean, date/time, json, enum, numbers, etc.)
		return $this->Html->formatValueByType($value, $columnType);
	}


	/**
	 * Format usergroup permissions grouped by scope
	 *
	 * @param array<\Cake\Datasource\EntityInterface> $entities The permission entities
	 * @return string The formatted HTML
	 */
	public function formatUsergroupPermissions(array $entities): string {
		$entitiesGroupedByScope = [];

		foreach ($entities as $entity) {
			$labelData = $entity->labelData ?? null;
			if (!$labelData) {
				continue;
			}

			$entityScope = $labelData['scope'] ?? 'unknown';
			if (!isset($entitiesGroupedByScope[ $entityScope ])) {
				$entitiesGroupedByScope[ $entityScope ] = [];
			}
			$entitiesGroupedByScope[ $entityScope ][] = $entity;
		}

		Arrays::naturalSort($entitiesGroupedByScope, null, true);

		$html = '';
		$first = true;
		foreach ($entitiesGroupedByScope as $scopeTitle => $groupEntities) {
			Arrays::naturalSort($groupEntities, 'label');

			if (!$first) {
				$html .= '<br><hr><br>';
			}
			$first = false;

			$html .= '<strong>' . h($scopeTitle) . ':</strong>';
			$html .= '<ul>';
			foreach ($groupEntities as $entity) {
				$labelData = $entity->labelData ?? null;
				$identifier = $labelData['identifier'] ?? '';
				$accessClass = 'PermissionAccess-' . ($entity->access->name ?? '');
				$html .= '<li class="' . $accessClass . '">' . h($identifier) . ' (ID: ' . $entity->id . ')</li>';
			}
			$html .= '</ul>';
		}

		return $html;
	}


	/**
	 * Format media entities for display
	 *
	 * @param mixed $value The media ID(s)
	 * @param array $settings Settings containing media data
	 * @return string The formatted HTML
	 * @noinspection PhpUnused
	 */
	public function formatMediaEntities(mixed $value, array $settings): string {
		$media = $settings['media'] ?? [];
		$baseUrl = $settings['baseUrl'] ?? $this->getView()->get('baseUrl') ?? $this
			->getView()
			->getTwig()
			->getGlobals()['baseUrl'] ?? '';

		if (is_array($value)) {
			$html = '';
			foreach ($value as $mediaId) {
				if (isset($media[ $mediaId ])) {
					$path = $media[ $mediaId ]->path ?? '';
					$html .= '<a href="' . h($baseUrl . $path) . '" target="_blank">' . h($path) . '</a> (ID: ' . $mediaId . ')<br>';
				}
				else {
					$html .= __('unknown_file') . ' (ID: ' . $mediaId . ')<br>';
				}
			}

			return $html;
		}

		if ($value !== null) {
			if (isset($media[ $value ])) {
				$path = $media[ $value ]->path ?? '';

				return '<a href="' . h($baseUrl . $path) . '" target="_blank">' . h($path) . '</a> (ID: ' . $value . ')';
			}

			return __('unknown_file') . ' (ID: ' . $value . ')';
		}

		return '';
	}


	/**
	 * Format current field value for display in audit history
	 *
	 * @param string $field The field name
	 * @param \Cake\Datasource\EntityInterface $entity The current entity
	 * @param array<string, array{name: string, property: string, type: string}> $associations The associations
	 * @param \Awyiss\Model\Entity\Attribute|null $attribute The attribute entity if this is an attribute
	 * @param array|null $attributeOptions The attribute options if applicable
	 * @param array $settings Additional settings (media, languages, etc.)
	 * @return string The formatted value
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function formatCurrentValue(
		string $field,
		EntityInterface $entity,
		array $associations,
		?Attribute $attribute = null,
		?array $attributeOptions = null,
		array $settings = []
	): string {
		// Handle associations (audit-specific with natural sorting)
		if (isset($associations[ $field ])) {
			$associationField = $associations[ $field ]['property'];
			$associatedValue = $entity->get($associationField);

			if (in_array($associations[ $field ]['type'], ['oneToMany', 'manyToMany'])) {
				if (empty($associatedValue)) {
					return '';
				}

				// Special handling for UsergroupPermissions
				if ($associations[ $field ]['name'] === 'UsergroupPermissions') {
					return $this->formatUsergroupPermissions($associatedValue);
				}

				Arrays::naturalSort($associatedValue, 'label');

				// Default: list of entities
				return $this->formatListItems($associatedValue);
			}

			// belongsTo
			$label = $associatedValue->label ?? '';
			$id = $associatedValue->id ?? null;
			$result = $label;
			if ($id) {
				$result .= ' (ID: ' . $id . ')';
			}

			return $result;
		}

		$value = $entity->get($field);

		return $this->formatFieldValue($field, $value, $entity, $attribute, $attributeOptions, $settings);
	}


	/**
	 * @param mixed $auditValue Value from audit data
	 * @param mixed $entityValue Value from entity
	 * @return bool True if values differ
	 * @noinspection PhpUnused
	 */
	public function attributeValuesDiffer(mixed $auditValue, mixed $entityValue): bool {
		if (empty($auditValue) && empty($entityValue)) {
			return false;
		}

		return $auditValue != $entityValue;
	}


	/**
	 * Compare two values to determine if they differ
	 *
	 * @param mixed $value1 First value
	 * @param mixed $value2 Second value
	 * @param string $field The field name
	 * @param \Cake\Datasource\EntityInterface $entity The entity (to determine column type)
	 * @param array<string, array{name: string, property: string, type: string}> $associations The associations
	 * @return bool True if values differ
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function valuesDiffer(mixed $value1, mixed $value2, string $field, EntityInterface $entity, array $associations): bool {
		static $dateFormat = $this->getView()->get('dateFormat') ?? $this
			->getView()
			->getTwig()
			->getGlobals()['dateFormat'] ?? 'Y-m-d';
		static $timeFormat = $this->getView()->get('timeFormat') ?? $this
			->getView()
			->getTwig()
			->getGlobals()['timeFormat'] ?? 'H:i:s';

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$columnType = $entity->getColumnType($field);

		// Handle many-to-many associations
		if (isset($associations[ $field ]) && $associations[ $field ]['type'] === 'manyToMany') {
			$ids1 = !empty($value1) ? Hash::extract($value1, '{n}.id') : [];
			$ids2 = !empty($value2) ? Hash::extract($value2, '{n}.id') : [];
			sort($ids1);
			sort($ids2);

			return $ids1 !== $ids2;
		}

		// Handle enum values - compare by value
		if ($value1 instanceof UnitEnum && $value2 instanceof UnitEnum) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			return $value1->value !== $value2->value;
		}
		if ($value1 instanceof UnitEnum) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			return $value1->value != $value2;
		}
		if ($value2 instanceof UnitEnum) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			return $value1 != $value2->value;
		}

		// For date/time values, format both and compare formatted strings
		// This ensures consistent comparison based on how they're displayed
		if (in_array($columnType, ['date', 'datetime', 'time'])) {
			if ($columnType === 'date') {
				$formatted1 = $value1 ? $this->Time->i18nFormat($value1, $dateFormat) : '';
				$formatted2 = $value2 ? $this->Time->i18nFormat($value2, $dateFormat) : '';

				return $formatted1 !== $formatted2;
			}

			if ($columnType === 'datetime') {
				$formatted1 = $value1 ? $this->Time->nice($value1) : '';
				$formatted2 = $value2 ? $this->Time->nice($value2) : '';

				return $formatted1 !== $formatted2;
			}

			if ($columnType === 'time') {
				$formatted1 = $value1 ? $this->Time->i18nFormat($value1, $timeFormat) : '';
				$formatted2 = $value2 ? $this->Time->i18nFormat($value2, $timeFormat) : '';

				return $formatted1 !== $formatted2;
			}
		}

		// Default comparison
		return $value1 != $value2;
	}


	/**
	 * @param iterable $associatedValue
	 * @return string
	 */
	protected function formatListItems(iterable $associatedValue): string {
		$html = '<ul>';

		foreach ($associatedValue as $associatedEntity) {
			$label = $associatedEntity ? ($associatedEntity->label ?? __('unknown_entity')) : __('unknown_entity');
			$id = $associatedEntity->id ?? null;
			$html .= '<li>' . $label;
			if ($id) {
				$html .= ' (ID: ' . $id . ')';
			}
			$html .= '</li>';
		}
		$html .= '</ul>';

		return $html;
	}
}
