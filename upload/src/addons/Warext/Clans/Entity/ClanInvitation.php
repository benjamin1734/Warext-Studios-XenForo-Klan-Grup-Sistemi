<?php

namespace Warext\Clans\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class ClanInvitation extends Entity
{
    public function isExpired(): bool
    {
        return $this->expiry_date > 0 && $this->expiry_date <= \XF::$time;
    }

    public static function getStructure(Structure $structure)
    {
        $structure->table='xf_wx_clan_invitation';
        $structure->shortName='Warext\\Clans:ClanInvitation';
        $structure->primaryKey='invitation_id';
        $structure->columns=[
            'invitation_id'=>['type'=>self::UINT,'autoIncrement'=>true],
            'clan_id'=>['type'=>self::UINT,'required'=>true],
            'user_id'=>['type'=>self::UINT,'required'=>true],
            'invited_by'=>['type'=>self::UINT,'required'=>true],
            'status'=>['type'=>self::STR,'default'=>'pending','allowedValues'=>['pending','accepted','declined','cancelled','expired']],
            'create_date'=>['type'=>self::UINT,'default'=>\XF::$time],
            'expiry_date'=>['type'=>self::UINT,'default'=>0]
        ];
        $structure->relations = [
            'Clan' => ['entity' => 'Warext\\Clans:Clan', 'type' => self::TO_ONE, 'conditions' => 'clan_id', 'primary' => true],
            'User' => ['entity' => 'XF:User', 'type' => self::TO_ONE, 'conditions' => 'user_id', 'primary' => true],
            'Inviter' => ['entity' => 'XF:User', 'type' => self::TO_ONE, 'conditions' => [['user_id', '=', '$invited_by']]]
        ];
        return $structure;
    }
}
