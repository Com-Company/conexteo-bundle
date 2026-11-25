<?php

declare(strict_types=1);

namespace Connected\Conexteo\Exception;

use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Levée quand l'API Conexteo est injoignable, renvoie une erreur HTTP
 * ou un corps qui n'est pas exploitable en JSON.
 */
final class ConexteoApiException extends \RuntimeException
{
    public function __construct(string $message, public readonly ResponseInterface $response, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
