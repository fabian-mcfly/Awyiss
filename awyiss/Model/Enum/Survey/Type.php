<?php declare(strict_types=1);


namespace Awyiss\Model\Enum\Survey;


use Cake\Database\Type\EnumLabelInterface;


/**
 * Survey Type Enum
 */
enum Type: string implements EnumLabelInterface {
	case Linear = 'linear';
	case Configurator = 'configurator';


	/**
	 * @return string
	 */
	public function label(): string {
		return __d('surveys', 'survey_type_' . $this->value);
	}
}
