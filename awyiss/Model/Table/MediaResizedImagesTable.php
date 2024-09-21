<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\Media;
use Awyiss\Model\Entity\MediaResizedImage;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Enum\ResizeStrategy;
use Awyiss\Model\Table;
use Awyiss\Utility\Inflector;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use InvalidArgumentException;


/**
 * MediaResizedImages Model
 *
 * @method \Awyiss\Model\Entity\MediaResizedImage newDefaultEntity(array $additionalData = [], array $options = [])
 */
class MediaResizedImagesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'media_resized_images';


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
	public function newEntityFromMedia(Media $media, ?int $width, ?int $height = null, ResizeStrategy $strategy = ResizeStrategy::Contain, string $format = 'webp'): MediaResizedImage {
		// Check if the format is supported
		if (!in_array($format, ['webp', 'jpg'])) {
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
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Cake\Validation\Validator
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
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->enum('strategy', ResizeStrategy::class);


		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 */
	public function buildRules(RulesChecker $rules): RulesChecker {
		$rules->add($rules->existsIn(['mediaId'], 'Media'), 'validMediaId', ['errorField' => 'mediaId']);

		return $rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		$schema->setColumnType('strategy', EnumType::from(ResizeStrategy::class));
		$schema->setColumnType('status', EnumType::from(ProcessStatus::class));
	}
}
