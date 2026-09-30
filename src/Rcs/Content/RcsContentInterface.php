<?php

declare(strict_types=1);

namespace Connected\Conexteo\Rcs\Content;

/**
 * Contenu d'un RCS : donne le `type` et le `data` du payload de `POST /messages/rcs`.
 */
interface RcsContentInterface
{
    public function type(): string;

    public function toData(): array;
}
