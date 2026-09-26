<?php

namespace Warext\Clans\Util;

final class Presentation
{
    public const ICONS = [
        'fa-users',
        'fa-shield',
        'fa-crown',
        'fa-star',
        'fa-bolt',
        'fa-fire',
        'fa-gamepad',
        'fa-trophy',
        'fa-flag',
        'fa-gem',
        'fa-heart',
        'fa-circle'
    ];

    public static function normalizeIcon(?string $icon): string
    {
        $icon = strtolower(trim((string)$icon));
        return in_array($icon, self::ICONS, true) ? $icon : '';
    }
}
