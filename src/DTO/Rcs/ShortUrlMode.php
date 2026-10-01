<?php

declare(strict_types=1);

namespace Connected\Conexteo\DTO\Rcs;

/**
 * Façon dont Conexteo raccourcit le lien de `ShortUrlDTO` (champ `shorturl.mode`).
 *
 * À confirmer avec la documentation Conexteo :
 * - UNIQUE   : un lien court différent pour chaque destinataire, ce qui permet
 *              de savoir précisément qui a cliqué ;
 * - MULTIPLE : un seul lien court commun à tous les destinataires, qui donne
 *              seulement le nombre total de clics.
 */
enum ShortUrlMode: string
{
    case UNIQUE = 'unique';
    case MULTIPLE = 'multiple';
}