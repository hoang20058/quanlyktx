<?php

declare(strict_types=1);

/**
 * Input Validator
 *
 * Provides methods for validating various input types.
 * Collects all errors and throws ValidationException at the end.
 *
 * Usage:
 *   $validator = new Validator($_POST);
 *   $validator->required('name')
 *             ->string('email')
 *             ->email('email')
 *             ->numeric('age')
 *             ->validate(); // throws ValidationException if errors
 *
 * @package App\Validation
 */
class Validator
{
    /**
     * Input data to validate
     *
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * Collected validation errors
     *
     * @var array<string, array<string>>
     */
    private array $errors = [];

    /**
     * Constructor
     *
     * @param array<string, mixed> $data Input data to validate
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Validate that a field exists and is not empty
     *
     * @param string $field Field name
     * @param string|null $message Custom error message
     * @return self For method chaining
     */
    public function required(string $field, ?string $message = null): self
    {
        if (!isset($this->data[$field]) || trim((string)$this->data[$field]) === '') {
            $this->addError($field, $message ?? "Trường '{$field}' là bắt buộc.");
        }
        return $this;
    }

    /**
     * Validate that a field is a string
     *
     * @param string $field Field name
     * @param string|null $message Custom error message
     * @return self For method chaining
     */
    public function string(string $field, ?string $message = null): self
    {
        if (isset($this->data[$field]) && !is_string($this->data[$field])) {
            $this->addError($field, $message ?? "Trường '{$field}' phải là chuỗi ký tự.");
        }
        return $this;
    }

    /**
     * Validate minimum string length
     *
     * @param string $field Field name
     * @param int $min Minimum length
     * @param string|null $message Custom error message
     * @return self For method chaining
     */
    public function minLength(string $field, int $min, ?string $message = null): self
    {
        if (isset($this->data[$field]) && strlen((string)$this->data[$field]) < $min) {
            $this->addError($field, $message ?? "Trường '{$field}' phải có ít nhất {$min} ký tự.");
        }
        return $this;
    }

    /**
     * Validate maximum string length
     *
     * @param string $field Field name
     * @param int $max Maximum length
     * @param string|null $message Custom error message
     * @return self For method chaining
     */
    public function maxLength(string $field, int $max, ?string $message = null): self
    {
        if (isset($this->data[$field]) && strlen((string)$this->data[$field]) > $max) {
            $this->addError($field, $message ?? "Trường '{$field}' không được vượt quá {$max} ký tự.");
        }
        return $this;
    }

    /**
     * Validate that a field is numeric
     *
     * @param string $field Field name
     * @param string|null $message Custom error message
     * @return self For method chaining
     */
    public function numeric(string $field, ?string $message = null): self
    {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->addError($field, $message ?? "Trường '{$field}' phải là số.");
        }
        return $this;
    }

    /**
     * Validate that a field is an integer
     *
     * @param string $field Field name
     * @param string|null $message Custom error message
     * @return self For method chaining
     */
    public function integer(string $field, ?string $message = null): self
    {
        if (isset($this->data[$field]) && !is_int($this->data[$field]) && !ctype_digit((string)$this->data[$field])) {
            $this->addError($field, $message ?? "Trường '{$field}' phải là số nguyên.");
        }
        return $this;
    }

    /**
     * Validate that a field is a valid email
     *
     * @param string $field Field name
     * @param string|null $message Custom error message
     * @return self For method chaining
     */
    public function email(string $field, ?string $message = null): self
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, $message ?? "Trường '{$field}' phải là email hợp lệ.");
        }
        return $this;
    }

    /**
     * Validate that a field matches a regex pattern
     *
     * @param string $field Field name
     * @param string $pattern Regex pattern
     * @param string|null $message Custom error message
     * @return self For method chaining
     */
    public function regex(string $field, string $pattern, ?string $message = null): self
    {
        if (isset($this->data[$field]) && !preg_match($pattern, (string)$this->data[$field])) {
            $this->addError($field, $message ?? "Trường '{$field}' có định dạng không hợp lệ.");
        }
        return $this;
    }

    /**
     * Validate that a field value is in a list of allowed values
     *
     * @param string $field Field name
     * @param array<string> $values Allowed values
     * @param string|null $message Custom error message
     * @return self For method chaining
     */
    public function in(string $field, array $values, ?string $message = null): self
    {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values, true)) {
            $this->addError($field, $message ?? "Giá trị của '{$field}' không hợp lệ.");
        }
        return $this;
    }

    /**
     * Validate that a field is a valid date in format YYYY-MM-DD
     *
     * @param string $field Field name
     * @param string|null $message Custom error message
     * @return self For method chaining
     */
    public function date(string $field, ?string $message = null): self
    {
        if (isset($this->data[$field])) {
            $date = $this->data[$field];
            $regex = '/^\d{4}-\d{2}-\d{2}$/';
            if (!preg_match($regex, (string)$date)) {
                $this->addError($field, $message ?? "Trường '{$field}' phải là ngày hợp lệ (YYYY-MM-DD).");
                return $this;
            }
            // Validate it's an actual date
            $parts = explode('-', (string)$date);
            if (!checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
                $this->addError($field, $message ?? "Trường '{$field}' phải là ngày hợp lệ.");
            }
        }
        return $this;
    }

    /**
     * Validate that two fields match
     *
     * @param string $field1 First field name
     * @param string $field2 Second field name
     * @param string|null $message Custom error message
     * @return self For method chaining
     */
    public function matches(string $field1, string $field2, ?string $message = null): self
    {
        if (isset($this->data[$field1]) && isset($this->data[$field2])) {
            if ($this->data[$field1] !== $this->data[$field2]) {
                $this->addError($field1, $message ?? "Các trường '{$field1}' và '{$field2}' phải khớp với nhau.");
            }
        }
        return $this;
    }

    /**
     * Add a custom error for a field
     *
     * @param string $field Field name
     * @param string $message Error message
     * @return self For method chaining
     */
    public function addError(string $field, string $message): self
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
        return $this;
    }

    /**
     * Check if there are any validation errors
     *
     * @return bool True if errors exist, false otherwise
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get all validation errors
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Validate and throw exception if errors exist
     *
     * @throws ValidationException If validation fails
     * @return true If validation passes
     */
    public function validate(): bool
    {
        if ($this->hasErrors()) {
            throw new ValidationException($this->errors);
        }
        return true;
    }

    /**
     * Get validated data (returns only fields that were checked)
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
