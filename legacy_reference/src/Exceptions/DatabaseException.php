<?php

declare(strict_types=1);

/**
 * Database Exception
 *
 * Thrown when database operations fail. Helps distinguish between
 * database-related errors and other application errors.
 *
 * @package App\Exceptions
 */
class DatabaseException extends ApplicationException
{
    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int $code Exception code
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = "Lỗi cơ sở dữ liệu",
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            500, // Internal Server Error
            "Có lỗi xảy ra với cơ sở dữ liệu. Vui lòng thử lại sau.",
            $code,
            $previous
        );
    }
}
