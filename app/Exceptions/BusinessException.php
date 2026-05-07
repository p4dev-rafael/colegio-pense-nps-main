<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception for business-rule violations across the domain.
 *
 * Subclasses should provide static factory methods for each scenario and may
 * expose a translation key via {@see userMessage()} for user-facing display.
 */
abstract class BusinessException extends Exception
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message = '',
        protected string $userMessageKey = 'errors.business.generic',
        /** @var array<string, mixed> */
        protected array $context = [],
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function userMessage(): string
    {
        return __($this->userMessageKey, $this->translationParameters());
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    /**
     * @return array<string, scalar>
     */
    protected function translationParameters(): array
    {
        $params = [];
        foreach ($this->context as $key => $value) {
            if (is_scalar($value)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
