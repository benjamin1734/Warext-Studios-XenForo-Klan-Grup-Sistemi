<?php

namespace Warext\Clans\Cron;

class Maintenance
{
    public static function run(): void
    {
        \XF::service('Warext\\Clans:Maintenance')->run();
    }
}
