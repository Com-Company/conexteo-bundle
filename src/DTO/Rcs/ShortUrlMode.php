<?php

declare(strict_types=1);

namespace Connected\Conexteo\DTO\Rcs;

enum ShortUrlMode: string
{
    case Unique = 'unique';
    case Multiple = 'multiple';
}
