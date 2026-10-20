<?php

/*
| Service catalog: form-schema validation messages (FormSchemaValidator) and service-details answers
| (ServiceFormValidator).
*/
return [
    'schema' => [
        'too_many_fields' => 'A form can have at most :max fields.',
        'invalid_field' => 'This field definition is not valid.',
        'key_invalid' => 'The key must start with a lowercase letter and contain only lowercase letters, digits and underscores (max 40).',
        'key_duplicate' => 'Another field already uses this key.',
        'type_invalid' => 'Choose a valid field type.',
        'label_ar_required' => 'The Arabic label is required.',
        'text_too_long' => 'The text is too long (max :max characters).',
        'required_invalid' => 'Required must be yes or no.',
        'range_not_supported' => 'Minimum and maximum are not supported by this field type.',
        'range_numeric' => 'Enter a number.',
        'range_negative' => 'The value cannot be negative.',
        'range_integer' => 'Enter a whole number.',
        'range_order' => 'The maximum must be greater than or equal to the minimum.',
        'options_invalid' => 'The options are not valid.',
        'options_not_allowed' => 'This field type does not use options.',
        'options_required' => 'Add at least one option.',
        'options_too_many' => 'At most :max options are allowed.',
        'option_value_invalid' => 'Option :line: the value may only contain letters, digits, underscores and hyphens (max 40).',
        'option_duplicate' => 'Option :line: the value is used twice.',
        'option_label_required' => 'Option :line: the Arabic label is required.',
        'visible_invalid' => 'The visibility condition is not valid.',
        'visible_field_unknown' => 'The controlling field must be an earlier field of the form.',
        'visible_operator_invalid' => 'Choose a valid condition.',
        'visible_list_required' => 'Enter one or more values separated by commas.',
        'visible_value_required' => 'Enter the value to compare with.',
        'visible_number_required' => 'Enter a number to compare with.',
    ],

    'details' => [
        'unknown_key' => 'The field ":key" is not part of this service form.',
        'phone_invalid' => 'Enter a valid phone number in international format.',
    ],
];
