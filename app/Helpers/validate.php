<?php
/**
 * Validation Helper
 * Provides server-side validation functions
 */

class Validate
{
    private $errors = [];
    private $data = [];

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    /**
     * Validate required field
     */
    public function required($field, $message = null)
    {
        $value = $this->getValue($field);
        
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, $message ?: "The {$field} field is required.");
        }
        
        return $this;
    }

    /**
     * Validate email format
     */
    public function email($field, $message = null)
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, $message ?: "The {$field} must be a valid email address.");
        }
        
        return $this;
    }

    /**
     * Validate numeric value
     */
    public function numeric($field, $message = null)
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->addError($field, $message ?: "The {$field} must be a number.");
        }
        
        return $this;
    }

    /**
     * Validate decimal value with specific precision
     */
    public function decimal($field, $precision = 2, $message = null)
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '') {
            if (!is_numeric($value)) {
                $this->addError($field, $message ?: "The {$field} must be a valid decimal number.");
            } else {
                $decimalPlaces = strlen(substr(strrchr($value, "."), 1));
                if ($decimalPlaces > $precision) {
                    $this->addError($field, $message ?: "The {$field} cannot have more than {$precision} decimal places.");
                }
            }
        }
        
        return $this;
    }

    /**
     * Validate minimum value
     */
    public function min($field, $min, $message = null)
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && is_numeric($value) && $value < $min) {
            $this->addError($field, $message ?: "The {$field} must be at least {$min}.");
        }
        
        return $this;
    }

    /**
     * Validate maximum value
     */
    public function max($field, $max, $message = null)
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && is_numeric($value) && $value > $max) {
            $this->addError($field, $message ?: "The {$field} cannot be greater than {$max}.");
        }
        
        return $this;
    }

    /**
     * Validate date format
     */
    public function date($field, $format = 'Y-m-d', $message = null)
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '') {
            $date = DateTime::createFromFormat($format, $value);
            if (!$date || $date->format($format) !== $value) {
                $this->addError($field, $message ?: "The {$field} must be a valid date in format {$format}.");
            }
        }
        
        return $this;
    }

    /**
     * Validate date is not in the past
     */
    public function notPast($field, $message = null)
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '') {
            $date = new DateTime($value);
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            
            if ($date < $today) {
                $this->addError($field, $message ?: "The {$field} cannot be in the past.");
            }
        }
        
        return $this;
    }

    /**
     * Validate unique value in database
     */
    public function unique($field, $table, $column = null, $except = null, $message = null)
    {
        $value = $this->getValue($field);
        $column = $column ?: $field;
        
        if ($value !== null && $value !== '') {
            $db = Database::getInstance();
            
            $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
            $params = [$value];
            
            if ($except !== null) {
                $sql .= " AND id != ?";
                $params[] = $except;
            }
            
            $result = $db->fetch($sql, $params);
            
            if ($result['count'] > 0) {
                $this->addError($field, $message ?: "The {$field} has already been taken.");
            }
        }
        
        return $this;
    }

    /**
     * Validate string length
     */
    public function length($field, $min = null, $max = null, $message = null)
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '') {
            $length = strlen($value);
            
            if ($min !== null && $length < $min) {
                $this->addError($field, $message ?: "The {$field} must be at least {$min} characters.");
            }
            
            if ($max !== null && $length > $max) {
                $this->addError($field, $message ?: "The {$field} cannot be longer than {$max} characters.");
            }
        }
        
        return $this;
    }

    /**
     * Validate value is in array
     */
    public function in($field, $values, $message = null)
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && !in_array($value, $values)) {
            $valuesList = implode(', ', $values);
            $this->addError($field, $message ?: "The {$field} must be one of: {$valuesList}.");
        }
        
        return $this;
    }

    /**
     * Custom validation rule
     */
    public function custom($field, $callback, $message = null)
    {
        $value = $this->getValue($field);
        
        if (!$callback($value, $this->data)) {
            $this->addError($field, $message ?: "The {$field} is invalid.");
        }
        
        return $this;
    }

    /**
     * Get field value from data
     */
    private function getValue($field)
    {
        return isset($this->data[$field]) ? $this->data[$field] : null;
    }

    /**
     * Add error message
     */
    private function addError($field, $message)
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }

    /**
     * Check if validation passed
     */
    public function passes()
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails()
    {
        return !$this->passes();
    }

    /**
     * Get all errors
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Get errors for specific field
     */
    public function getErrors($field)
    {
        return isset($this->errors[$field]) ? $this->errors[$field] : [];
    }

    /**
     * Get first error for field
     */
    public function getFirstError($field)
    {
        $errors = $this->getErrors($field);
        return !empty($errors) ? $errors[0] : null;
    }

    /**
     * Get all error messages as flat array
     */
    public function getAllMessages()
    {
        $messages = [];
        
        foreach ($this->errors as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $messages[] = $error;
            }
        }
        
        return $messages;
    }

    /**
     * Static validation method
     */
    public static function make($data, $rules)
    {
        $validator = new self($data);
        
        foreach ($rules as $field => $fieldRules) {
            $fieldRules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            
            foreach ($fieldRules as $rule) {
                $parts = explode(':', $rule);
                $ruleName = $parts[0];
                $ruleParams = isset($parts[1]) ? explode(',', $parts[1]) : [];
                
                switch ($ruleName) {
                    case 'required':
                        $validator->required($field);
                        break;
                    case 'email':
                        $validator->email($field);
                        break;
                    case 'numeric':
                        $validator->numeric($field);
                        break;
                    case 'decimal':
                        $precision = isset($ruleParams[0]) ? (int)$ruleParams[0] : 2;
                        $validator->decimal($field, $precision);
                        break;
                    case 'min':
                        if (isset($ruleParams[0])) {
                            $validator->min($field, $ruleParams[0]);
                        }
                        break;
                    case 'max':
                        if (isset($ruleParams[0])) {
                            $validator->max($field, $ruleParams[0]);
                        }
                        break;
                    case 'date':
                        $format = isset($ruleParams[0]) ? $ruleParams[0] : 'Y-m-d';
                        $validator->date($field, $format);
                        break;
                    case 'unique':
                        $table = $ruleParams[0] ?? null;
                        $column = $ruleParams[1] ?? null;
                        $except = $ruleParams[2] ?? null;
                        if ($table) {
                            $validator->unique($field, $table, $column, $except);
                        }
                        break;
                }
            }
        }
        
        return $validator;
    }
}
