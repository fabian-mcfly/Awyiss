<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\Media;
use Awyiss\Model\Entity\MediaResizedImage;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Enum\ResizeStrategy;
use Awyiss\Model\Table;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use InvalidArgumentException;


/**
 * MediaResizedImages Model
 *
 * @method \Awyiss\Model\Entity\MediaResizedImage newDefaultEntity(array $aa_additionalData = [], array $aa_options = [])
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
	public function initialize(array $aa_config): void {
		parent::initialize($aa_config);

		$this->belongsTo('Media', [
			'joinType' => 'INNER',
		]);
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
	 * @param \Cake\Validation\Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Cake\Validation\Validator
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault(Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);


		$ao_validator->requirePresence([
			'mediaId',
			'name',
			'strategy',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('mediaId');
		$ao_validator->add('mediaId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('name');
		$ao_validator->add('name', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->enum('strategy', ResizeStrategy::class);


		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Cake\ORM\RulesChecker $ao_rules The rules object to be modified.
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $ao_rules The rules object to be modified.
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules(RulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->existsIn(['mediaId'], 'Media'), 'validMediaId', ['errorField' => 'mediaId']);

		return $ao_rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $ao_schema): void {
		parent::initializeSchema($ao_schema);

		$this->getSchema()->setColumnType('strategy', EnumType::from(ResizeStrategy::class));
		$this->getSchema()->setColumnType('status', EnumType::from(ProcessStatus::class));
	}
}
