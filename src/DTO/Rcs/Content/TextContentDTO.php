<?php

declare(strict_types=1);

namespace Connected\Conexteo\DTO\Rcs\Content;

final readonly class TextContentDTO implements RcsContentInterface
{
    public function __construct(public string $text)
    {
    }

    public function type(): string
    {
        return 'text';
    }

    public function toData(): array
    {
        return ['text' => $this->text];
    }
}
