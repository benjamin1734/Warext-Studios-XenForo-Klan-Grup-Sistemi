<?php

namespace Warext\Clans\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class ClanAuditLog extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_wx_clan_audit_log';
        $structure->shortName = 'Warext\\Clans:ClanAuditLog';
        $structure->primaryKey = 'log_id';
        $structure->columns = [
            'log_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'clan_id' => ['type' => self::UINT, 'default' => 0],
            'user_id' => ['type' => self::UINT, 'default' => 0],
            'action' => ['type' => self::STR, 'maxLength' => 75, 'required' => true],
            'content_type' => ['type' => self::STR, 'maxLength' => 40, 'default' => ''],
            'content_id' => ['type' => self::UINT, 'default' => 0],
            'details' => ['type' => self::JSON_ARRAY, 'default' => []],
            'log_date' => ['type' => self::UINT, 'default' => \XF::$time]
        ];
        $structure->relations = [
            'Clan' => ['entity' => 'Warext\\Clans:Clan', 'type' => self::TO_ONE, 'conditions' => 'clan_id', 'primary' => true],
            'User' => ['entity' => 'XF:User', 'type' => self::TO_ONE, 'conditions' => 'user_id']
        ];
        return $structure;
    }
}
