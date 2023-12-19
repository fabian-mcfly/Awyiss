<?php declare(strict_types=1);

/**
 * @noinspection HtmlUnknownAttribute
 * @noinspection HtmlWrongAttributeValue
 */
return [
	// Wrapper container for checkboxes.
	'checkboxWrapper' => '<div class="Input InputType-Checkbox">{{label}}</div>',
	// Error message wrapper elements.
	'error' => '{{content}}',
	// Container for error items.
	'errorList' => '<ul class="ErrorMessages">{{content}}</ul>',
	// Error item wrapper.
	'errorItem' => '<li class="ErrorMessage">{{text}}</li>',
	// Fieldset element used by allControls().
	'fieldset' => '<fieldset{{attrs}}>{{content}}</fieldset>',
	// Wrapper content used to hide other content.
	'hiddenBlock' => '<div class="Hidden">{{content}}</div>',
	// Generic input element.
	'input' => '<input type="{{type}}" name="{{name}}" {{attrs}}>',
	// Container element used by control().
	'inputContainer' => '<div class="Input InputType-{{type}}{{required}}">{{content}}</div>',
	// Container element used by control() when a field has an error.
	'inputContainerError' => '<div class="Input InputType-{{type}}{{required}} Error">{{content}}{{error}}</div>',
	// Legends created by allControls()
	'legend' => '<legend class="Legend">{{text}}</legend>',
	// Multi-Checkbox input set title element.
	'multicheckboxTitle' => '<legend class="Legend">{{text}}</legend>',
	// Wrapping container for radio input/label,
	'radioWrapper' => '{{label}}',
	// Textarea input element,
	'textarea' => '<textarea name="{{name}}" {{attrs}}>{{value}}</textarea>',
	// Container for submit buttons.
	'submitContainer' => '<div class="Submit">{{content}}</div>',
	// selected class
	'selectedClass' => 'Selected',
];
