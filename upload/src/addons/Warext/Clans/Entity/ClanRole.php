<?php
namespace Warext\Clans\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class ClanRole extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_wx_clan_role';
        $structure->shortName = 'Warext\\Clans:ClanRole';
        $structure->primaryKey = 'role_id';
        $structure->columns = [
            'role_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'clan_id' => ['type' => self::UINT, 'required' => true],
            'title' => ['type' => self::STR, 'maxLength' => 75, 'required' => true],
            'role_type' => ['type' => self::STR, 'default' => 'custom'],
            'display_order' => ['type' => self::UINT, 'default' => 100],
            'permissions' => ['type' => self::JSON_ARRAY, 'default' => []],
            'created_date' => ['type' => self::UINT, 'default' => \XF::$time]
        ];
        $structure->relations = [
            'Clan' => ['entity' => 'Warext\\Clans:Clan', 'type' => self::TO_ONE, 'conditions' => 'clan_id', 'primary' => true]
        ];
        return $structure;
    }
}
