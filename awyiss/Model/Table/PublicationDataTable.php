<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Enum\PublicationDataType;
use Awyiss\Model\Table;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\Validation\Validator;


/**
 * PublicationDataTable class
 */
class PublicationDataTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'publication_data';


	/**
	 * @inheritDoc
	 */
	protected array $audit = [
		'enabled' => false,
	];


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator->requirePresence([
			'scope',
			'type',
		], 'create');


		$validator->add('scope', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('foreignKey');
		$validator->add('foreignKey', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('type');
		$validator->add('type', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 20]],
		]);

		$validator->allowEmptyDateTime('dateTime');
		$validator->add('dateTime', [
			'dateTime' => ['rule' => 'dateTime'],
		]);


		return $validator;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		$schema->setColumnType('type', EnumType::from(PublicationDataType::class));
	}
}
