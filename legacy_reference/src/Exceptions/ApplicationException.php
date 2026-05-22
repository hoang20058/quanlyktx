<?php

declare(strict_types=1);

/**
 * Base Exception Class for the Application
 *
 * All custom exceptions should extend this class for consistent
 * error handling and logging across the application.
 *
 * @package App\Exceptions
 */
class ApplicationException extends Exception
{
    /**
     * HTTP status code for this exception
     *
     * @var int
     */
    protected int $statusCode = 500;

    /**
     * User-friendly error message
     *
     * @var string
     */
    protected string $userMessage;

    /**
     * Constructor
     *
     * @param string $message Technical error message for logs
     * @param int $statusCode HTTP status code
     * @param string|null $userMessage User-friendly message (defaults to message if not provided)
     * @param int $code Exception code
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = "",
        int $statusCode = 500,
        ?string $userMessage = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->statusCode = $statusCode;
        $this->userMessage = $userMessage ?? $message;
    }

    /**
     * Get HTTP status code
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get user-friendly message
     *
     * @return string
     */
    public function getUserMessage(): string
    {
        return $this->userMessage;
    }
}
