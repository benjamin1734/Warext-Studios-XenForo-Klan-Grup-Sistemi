<?php

namespace Warext\Clans\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class ClanBlacklist extends Entity
{
    public function isActive(): bool
    {
        return !$this->expiry_date || $this->expiry_date > \XF::$time;
    }

    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_wx_clan_blacklist';
        $structure->shortName = 'Warext\\Clans:ClanBlacklist';
        $structure->primaryKey = 'blacklist_id';
        $structure->columns = [
            'blacklist_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'clan_id' => ['type' => self::UINT, 'required' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'added_by' => ['type' => self::UINT, 'default' => 0],
            'reason' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'expiry_date' => ['type' => self::UINT, 'default' => 0],
            'create_date' => ['type' => self::UINT, 'default' => \XF::$time]
        ];
        $structure->relations = [
            'Clan' => ['entity' => 'Warext\\Clans:Clan', 'type' => self::TO_ONE, 'conditions' => 'clan_id', 'primary' => true],
            'User' => ['entity' => 'XF:User', 'type' => self::TO_ONE, 'conditions' => 'user_id', 'primary' => true],
            'AddedBy' => ['entity' => 'XF:User', 'type' => self::TO_ONE, 'conditions' => [['user_id', '=', '$added_by']]]
        ];
        return $structure;
    }
}
