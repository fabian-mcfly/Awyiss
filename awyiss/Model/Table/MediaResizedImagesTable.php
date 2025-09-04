<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Core\App;
use Awyiss\Model\Entity\Media;
use Awyiss\Model\Entity\MediaResizedImage;
use Awyiss\Model\Enum\ResizeStrategy;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Inflector;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;
use InvalidArgumentException;


/**
 * MediaResizedImages Model
 *
 * @method \Awyiss\Model\Entity\MediaResizedImage newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class MediaResizedImagesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'media_resized_images';


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->belongsTo('Media');
	}


	/**
	 * Create a new entity from the given media entity.
	 *
	 * @param \Awyiss\Model\Entity\Media $media
	 * @param int|null $width
	 * @param int|null $height
	 * @param \Awyiss\Model\Enum\ResizeStrategy $strategy
	 * @param string $format
	 * @return \Awyiss\Model\Entity\MediaResizedImage
	 */
	public function newEntityFromMedia(Media $media, ?int $width, ?int $height = null, ResizeStrategy $strategy = ResizeStrategy::Contain, string $format = 'avif'): MediaResizedImage {
		// Check if the format is supported
		if (!in_array($format, ['avif', 'jpg', 'png', 'webp'])) {
			throw new InvalidArgumentException('The format is not supported.');
		}

		$ls_baseName = $media->isImage() ? $media->cleanName : $media->name;

		$ls_appendix = $width ? 'w' . $width : '';
		$ls_appendix .= $height ? 'h' . $height : '';
		$ls_appendix .= $strategy !== ResizeStrategy::Contain ? Inflector::underscore($strategy->name) : '';

		$ls_name = $ls_baseName . '-[' . $ls_appendix . '].' . $format;

		$ls_path = $media->path;
		$ls_path = substr($ls_path, 0, strrpos($ls_path, DS)) . DS . '_resized' . DS . $ls_name;

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$lo_entity = $this->newDefaultEntity([
			'mediaId' => $media->id,
			'name' => $ls_name,
			'path' => $ls_path,
			'width' => $width,
			'height' => $height,
			'media' => $media,
			'strategy' => $strategy,
		]);

		return $lo_entity;
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'mediaId',
			'name',
			'strategy',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('mediaId');
		$validator->add('mediaId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('name');
		$validator->add('name', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		/** @var class-string<\Awyiss\Model\Enum\ProcessStatus> $ls_processStatusEnumClass */
		$ls_processStatusEnumClass = App::className('ProcessStatus', 'Model/Enum');
		$validator->add('status', [
			'enum' => ['rule' => ['enum', $ls_processStatusEnumClass]],
		]);


		$validator->notEmptyString('strategy');
		/** @var class-string<\Awyiss\Model\Enum\ResizeStrategy> $ls_resizeStrategyEnumClass */
		$ls_resizeStrategyEnumClass = App::className('ResizeStrategy', 'Model/Enum');
		$validator->add('strategy', [
			'enum' => ['rule' => ['enum', $ls_resizeStrategyEnumClass]],
		]);


		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add($rules->existsIn(['mediaId'], 'Media'), 'validMediaId', ['errorField' => 'mediaId']);

		$rules->add(
			function (MediaResizedImage $entity): bool {
				if ($entity->status === null) {
					return true;
				}

				/** @var class-string<\Awyiss\Model\Enum\ProcessStatus> $ls_processStatusEnumClass */
				$ls_processStatusEnumClass = App::className('ProcessStatus', 'Model/Enum');

				if (is_int($entity->status)) {
					return $ls_processStatusEnumClass::tryFrom($entity->status) !== null;
				}

				return in_array($entity->status, $ls_processStatusEnumClass::cases());
			},
			'validStatus',
			[
				'errorField' => 'status',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_status'),
			]
		);


		$rules->add(
			function (MediaResizedImage $entity): bool {
				/** @var class-string<\Awyiss\Model\Enum\ResizeStrategy> $ls_resizeStrategyEnumClass */
				$ls_resizeStrategyEnumClass = App::className('ResizeStrategy', 'Model/Enum');

				if (is_int($entity->strategy)) {
					return $ls_resizeStrategyEnumClass::tryFrom($entity->strategy) !== null;
				}

				return in_array($entity->strategy, $ls_resizeStrategyEnumClass::cases());
			},
			'validStrategy',
			[
				'errorField' => 'strategy',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_strategy'),
			]
		);

		return $rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		/** @var class-string<\Awyiss\Model\Enum\ProcessStatus> $ls_processStatusEnumClass */
		$ls_processStatusEnumClass = App::className('ProcessStatus', 'Model/Enum');

		$schema->setColumnType('status', EnumType::from($ls_processStatusEnumClass));

		/** @var class-string<\Awyiss\Model\Enum\ResizeStrategy> $ls_resizeStrategyEnumClass */
		$ls_resizeStrategyEnumClass = App::className('ResizeStrategy', 'Model/Enum');

		$schema->setColumnType('strategy', EnumType::from($ls_resizeStrategyEnumClass));
	}
}
