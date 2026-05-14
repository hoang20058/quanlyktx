<?php

declare(strict_types=1);

/**
 * Authentication Exception
 *
 * Thrown when authentication fails (login, permissions, etc.)
 *
 * @package App\Exceptions
 */
class AuthenticationException extends ApplicationException
{
    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int $code Exception code
     */
    public function __construct(
        string $message = "Xác thực thất bại",
        int $code = 0
    ) {
        parent::__construct(
            $message,
            401, // Unauthorized
            "Bạn không có quyền truy cập. Vui lòng đăng nhập.",
            $code
        );
    }
}
