<?php

declare(strict_types=1);

namespace Connected\Conexteo\DTO\Rcs\Choice;

/**
 * Réponse rapide : le texte du bouton revient comme une réponse du destinataire.
 */
final readonly class ReplyChoiceDTO implements ChoiceInterface
{
    public function __construct(public string $text)
    {
    }

    public function toArray(): array
    {
        return ['type' => 'text', 'text' => $this->text];
    }
}
