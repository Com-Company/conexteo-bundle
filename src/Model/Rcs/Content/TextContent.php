<?php

declare(strict_types=1);

namespace Connected\Conexteo\Model\Rcs\Content;

use Connected\Conexteo\Model\Rcs\TextLengthGuard;

final readonly class TextContent implements RcsContentInterface
{
    public const int MAX_TEXT_LENGTH = 2000;

    public function __construct(public string $text)
    {
        TextLengthGuard::assertNotLongerThan($text, self::MAX_TEXT_LENGTH, 'text');
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
