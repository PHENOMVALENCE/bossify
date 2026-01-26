<?php
/**
 * Input Validation Class
 */

class Validator {
    private $errors = [];
    
    public function validate($data, $rules) {
        $this->errors = [];
        
        foreach ($rules as $field => $ruleSet) {
            $rulesArray = explode('|', $ruleSet);
            $value = isset($data[$field]) ? trim($data[$field]) : '';
            $isRequired = in_array('required', $rulesArray);
            
            // Check required first
            if ($isRequired && empty($value)) {
                $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                continue; // Skip other validations if required field is empty
            }
            
            // Skip other validations if field is empty and not required
            if (empty($value) && !$isRequired) {
                continue;
            }
            
            foreach ($rulesArray as $rule) {
                if ($rule === 'required') {
                    // Already checked above
                    continue;
                }
                
                if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field] = 'Invalid email format';
                    break;
                }
                
                if (strpos($rule, 'min:') === 0) {
                    $min = (int) substr($rule, 4);
                    if (strlen($value) < $min) {
                        $fieldName = ucfirst(str_replace('_', ' ', $field));
                        $this->errors[$field] = "{$fieldName} must be at least {$min} characters long";
                        break;
                    }
                }
                
                if (strpos($rule, 'max:') === 0) {
                    $max = (int) substr($rule, 4);
                    if (strlen($value) > $max) {
                        $fieldName = ucfirst(str_replace('_', ' ', $field));
                        $this->errors[$field] = "{$fieldName} must not exceed {$max} characters";
                        break;
                    }
                }
            }
        }
        
        return empty($this->errors);
    }
    
    public function getErrors() {
        return $this->errors;
    }
}
