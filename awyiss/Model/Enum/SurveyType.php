<?php declare(strict_types=1);


namespace Awyiss\Model\Enum;


use Cake\Database\Type\EnumLabelInterface;


/**
 * SurveyType Enum
 */
enum SurveyType: string implements EnumLabelInterface {
	case Linear = 'linear';
	case Configurator = 'configurator';


	/**
	 * @return string
	 */
	public function label(): string {
		return __d('surveys', 'survey_type_' . $this->value);
	}
}
