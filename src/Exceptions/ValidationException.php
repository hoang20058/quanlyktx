<?php

declare(strict_types=1);

/**
 * Validation Exception
 *
 * Thrown when input validation fails. Contains detailed error information
 * about which fields failed validation and why.
 *
 * @package App\Exceptions
 */
class ValidationException extends ApplicationException
{
    /**
     * Array of validation errors by field
     *
     * @var array<string, array<string>>
     */
    private array $errors = [];

    /**
     * Constructor
     *
     * @param array<string, array<string>> $errors Map of field names to error messages
     * @param string|null $message Overall error message
     */
    public function __construct(array $errors = [], ?string $message = null)
    {
        $this->errors = $errors;
        $statusCode = 422; // Unprocessable Entity
        $message = $message ?? 'Dữ liệu không hợp lệ';

        parent::__construct($message, $statusCode, $message);
    }

    /**
     * Get validation errors by field
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors for a specific field
     *
     * @param string $field Field name
     * @return array<string> Error messages for this field
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Check if a field has errors
     *
     * @param string $field Field name
     * @return bool
     */
    public function hasFieldError(string $field): bool
    {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }
}
