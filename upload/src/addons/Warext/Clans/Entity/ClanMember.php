<?php

namespace Warext\Clans\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class ClanMember extends Entity
{
    public function hasClanPermission(string $permissionId): bool
    {
        if ($this->member_state !== 'active')
        {
            return false;
        }

        if ($this->is_owner)
        {
            return true;
        }

        if (!$this->is_manager)
        {
            return false;
        }

        $role = $this->Role;
        if (!$role || $role->clan_id !== $this->clan_id)
        {
            return false;
        }

        $permissions = is_array($role->permissions) ? $role->permissions : [];
        return !empty($permissions[$permissionId]);
    }

    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_wx_clan_member';
        $structure->shortName = 'Warext\\Clans:ClanMember';
        $structure->primaryKey = ['clan_id', 'user_id'];
        $structure->columns = [
            'clan_id' => ['type' => self::UINT, 'required' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'role_id' => ['type' => self::UINT, 'default' => 0],
            'member_state' => ['type' => self::STR, 'default' => 'active', 'allowedValues' => ['active', 'inactive']],
            'is_owner' => ['type' => self::BOOL, 'default' => false],
            'is_manager' => ['type' => self::BOOL, 'default' => false],
            'join_date' => ['type' => self::UINT, 'default' => \XF::$time],
            'last_activity' => ['type' => self::UINT, 'default' => \XF::$time]
        ];
        $structure->relations = [
            'Clan' => ['entity' => 'Warext\\Clans:Clan', 'type' => self::TO_ONE, 'conditions' => 'clan_id', 'primary' => true],
            'User' => ['entity' => 'XF:User', 'type' => self::TO_ONE, 'conditions' => 'user_id', 'primary' => true],
            'Role' => ['entity' => 'Warext\\Clans:ClanRole', 'type' => self::TO_ONE, 'conditions' => 'role_id']
        ];
        return $structure;
    }
}
