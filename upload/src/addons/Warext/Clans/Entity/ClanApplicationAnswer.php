<?php

namespace Warext\Clans\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class ClanApplicationAnswer extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_wx_clan_application_answer';
        $structure->shortName = 'Warext\\Clans:ClanApplicationAnswer';
        $structure->primaryKey = ['application_id','field_id'];
        $structure->columns = [
            'application_id' => ['type'=>self::UINT,'required'=>true],
            'field_id' => ['type'=>self::UINT,'required'=>true],
            'answer' => ['type'=>self::STR,'default'=>'']
        ];
        $structure->relations = [
            'Application' => ['entity' => 'Warext\\Clans:ClanApplication', 'type' => self::TO_ONE, 'conditions' => 'application_id', 'primary' => true],
            'Field' => ['entity' => 'Warext\\Clans:ClanApplicationField', 'type' => self::TO_ONE, 'conditions' => 'field_id', 'primary' => true]
        ];
        return $structure;
    }
}
