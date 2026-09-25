<?php

namespace Warext\Clans\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class ClanAnnouncement extends Entity
{

    public function canView(?string &$error = null): bool
    {
        return (bool)(
            $this->Clan
            && $this->Clan->canView($error)
            && $this->Clan->canViewAnnouncements($error)
        );
    }

    public function canReport(?string &$error = null): bool
    {
        return (bool)($this->Clan && $this->Clan->canReport($error));
    }

    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_wx_clan_announcement';
        $structure->shortName = 'Warext\\Clans:ClanAnnouncement';
        $structure->primaryKey = 'announcement_id';
        $structure->columns = [
            'announcement_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'clan_id' => ['type' => self::UINT, 'required' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'title' => ['type' => self::STR, 'maxLength' => 120, 'required' => true],
            'message' => ['type' => self::STR, 'required' => true],
            'is_pinned' => ['type' => self::BOOL, 'default' => false],
            'create_date' => ['type' => self::UINT, 'default' => \XF::$time],
            'update_date' => ['type' => self::UINT, 'default' => \XF::$time]
        ];
        $structure->relations = [
            'Clan' => ['entity' => 'Warext\\Clans:Clan', 'type' => self::TO_ONE, 'conditions' => 'clan_id', 'primary' => true],
            'User' => ['entity' => 'XF:User', 'type' => self::TO_ONE, 'conditions' => 'user_id', 'primary' => true]
        ];
        return $structure;
    }
}
