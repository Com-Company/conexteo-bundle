<?php

declare(strict_types=1);

namespace Connected\Conexteo\Rcs\Choice;

use Connected\Conexteo\Rcs\TextLengthGuard;

/**
 * Réponse rapide : le texte du bouton revient comme une réponse du destinataire.
 */
final readonly class ReplyChoice implements ChoiceInterface
{
    public function __construct(public string $text)
    {
        TextLengthGuard::assertNotLongerThan($text, self::MAX_TITLE_LENGTH, 'button text');
    }

    public function toArray(): array
    {
        return ['type' => 'text', 'text' => $this->text];
    }
}
