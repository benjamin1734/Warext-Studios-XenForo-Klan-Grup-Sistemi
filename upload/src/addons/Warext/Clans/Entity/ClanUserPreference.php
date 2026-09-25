<?php

namespace Warext\Clans\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class ClanUserPreference extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_wx_clan_user_pref';
        $structure->shortName = 'Warext\\Clans:ClanUserPreference';
        $structure->primaryKey = 'user_id';
        $structure->columns = [
            'user_id' => ['type' => self::UINT, 'required' => true],
            'active_clan_id' => ['type' => self::UINT, 'default' => 0]
        ];
        $structure->relations = [
            'User' => ['entity' => 'XF:User', 'type' => self::TO_ONE, 'conditions' => 'user_id', 'primary' => true],
            'Clan' => ['entity' => 'Warext\\Clans:Clan', 'type' => self::TO_ONE, 'conditions' => [['clan_id', '=', '$active_clan_id']]]
        ];
        return $structure;
    }
}
