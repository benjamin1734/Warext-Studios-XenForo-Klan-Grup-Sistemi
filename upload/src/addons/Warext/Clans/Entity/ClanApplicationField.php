<?php

namespace Warext\Clans\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class ClanApplicationField extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_wx_clan_application_field';
        $structure->shortName = 'Warext\\Clans:ClanApplicationField';
        $structure->primaryKey = 'field_id';
        $structure->columns = [
            'field_id' => ['type'=>self::UINT,'autoIncrement'=>true],
            'clan_id' => ['type'=>self::UINT,'required'=>true],
            'field_key' => ['type'=>self::STR,'maxLength'=>50,'required'=>true],
            'title' => ['type'=>self::STR,'maxLength'=>100,'required'=>true],
            'field_type' => ['type'=>self::STR,'default'=>'text','allowedValues'=>['text','textarea','select','checkbox','number','date']],
            'field_options' => ['type'=>self::JSON_ARRAY,'default'=>[]],
            'required' => ['type'=>self::BOOL,'default'=>false],
            'display_order' => ['type'=>self::UINT,'default'=>100],
            'active' => ['type'=>self::BOOL,'default'=>true]
        ];
        $structure->relations = [
            'Clan' => ['entity' => 'Warext\\Clans:Clan', 'type' => self::TO_ONE, 'conditions' => 'clan_id', 'primary' => true]
        ];
        return $structure;
    }
}
