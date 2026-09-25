<?php

namespace Warext\Clans\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class ClanOwnershipTransfer extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table='xf_wx_clan_ownership_transfer';
        $structure->shortName='Warext\\Clans:ClanOwnershipTransfer';
        $structure->primaryKey='transfer_id';
        $structure->columns=[
            'transfer_id'=>['type'=>self::UINT,'autoIncrement'=>true],
            'clan_id'=>['type'=>self::UINT,'required'=>true],
            'from_user_id'=>['type'=>self::UINT,'required'=>true],
            'to_user_id'=>['type'=>self::UINT,'required'=>true],
            'status'=>['type'=>self::STR,'default'=>'pending','allowedValues'=>['pending','accepted','approved','rejected','cancelled']],
            'create_date'=>['type'=>self::UINT,'default'=>\XF::$time],
            'accepted_date'=>['type'=>self::UINT,'default'=>0],
            'approved_by'=>['type'=>self::UINT,'default'=>0],
            'approved_date'=>['type'=>self::UINT,'default'=>0],
            'decision_reason'=>['type'=>self::STR,'default'=>'']
        ];
        $structure->relations = [
            'Clan' => ['entity' => 'Warext\\Clans:Clan', 'type' => self::TO_ONE, 'conditions' => 'clan_id', 'primary' => true],
            'FromUser' => ['entity' => 'XF:User', 'type' => self::TO_ONE, 'conditions' => [['user_id', '=', '$from_user_id']]],
            'ToUser' => ['entity' => 'XF:User', 'type' => self::TO_ONE, 'conditions' => [['user_id', '=', '$to_user_id']]],
            'ApprovedBy' => ['entity' => 'XF:User', 'type' => self::TO_ONE, 'conditions' => [['user_id', '=', '$approved_by']]]
        ];
        return $structure;
    }
}
