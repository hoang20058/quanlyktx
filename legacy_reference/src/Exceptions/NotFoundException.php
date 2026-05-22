<?php

declare(strict_types=1);

/**
 * Not Found Exception
 *
 * Thrown when a requested resource is not found in the database.
 *
 * @package App\Exceptions
 */
class NotFoundException extends ApplicationException
{
    /**
     * Constructor
     *
     * @param string $resource Name of the resource being looked up
     * @param mixed $id ID of the resource
     */
    public function __construct(string $resource = "Tài nguyên", mixed $id = null)
    {
        $message = "Không tìm thấy {$resource}";
        if ($id !== null) {
            $message .= " (ID: {$id})";
        }

        parent::__construct(
            $message,
            404, // Not Found
            $message
        );
    }
}
