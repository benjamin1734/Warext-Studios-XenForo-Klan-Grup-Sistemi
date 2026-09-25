<?php

namespace Warext\Clans\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class ClanApplication extends Entity
{
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isJoinApplication(): bool
    {
        return $this->application_type === 'join';
    }

    public function isCreateApplication(): bool
    {
        return $this->application_type === 'create';
    }

    public function isChangeRequest(): bool
    {
        return $this->application_type === 'change';
    }

    public function isLifecycleRequest(): bool
    {
        return in_array($this->application_type, ['close', 'reopen'], true);
    }

    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_wx_clan_application';
        $structure->shortName = 'Warext\\Clans:ClanApplication';
        $structure->primaryKey = 'application_id';
        $structure->columns = [
            'application_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'application_type' => ['type' => self::STR, 'default' => 'create', 'allowedValues' => ['create','join','change','close','reopen']],
            'clan_id' => ['type' => self::UINT, 'default' => 0],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'title' => ['type' => self::STR, 'maxLength' => 100, 'default' => ''],
            'tag' => ['type' => self::STR, 'maxLength' => 24, 'default' => ''],
            'description' => ['type' => self::STR, 'default' => ''],
            'category' => ['type' => self::STR, 'maxLength' => 50, 'default' => ''],
            'requested_manager_banner' => ['type' => self::STR, 'maxLength' => 100, 'default' => ''],
            'requested_tag_color' => ['type' => self::STR, 'maxLength' => 7, 'default' => '#4f46e5'],
            'requested_manager_banner_color' => ['type' => self::STR, 'maxLength' => 7, 'default' => '#805ad5'],
            'status' => ['type' => self::STR, 'default' => 'pending', 'allowedValues' => ['pending','approved','rejected','changes_requested','cancelled']],
            'create_date' => ['type' => self::UINT, 'default' => \XF::$time],
            'decision_date' => ['type' => self::UINT, 'default' => 0],
            'decision_user_id' => ['type' => self::UINT, 'default' => 0],
            'decision_reason' => ['type' => self::STR, 'default' => ''],
            'created_clan_id' => ['type' => self::UINT, 'default' => 0]
        ];
        $structure->relations = [
            'User' => ['entity' => 'XF:User', 'type' => self::TO_ONE, 'conditions' => 'user_id', 'primary' => true],
            'Clan' => ['entity' => 'Warext\\Clans:Clan', 'type' => self::TO_ONE, 'conditions' => [['clan_id', '=', '$clan_id']]],
            'CreatedClan' => ['entity' => 'Warext\\Clans:Clan', 'type' => self::TO_ONE, 'conditions' => [['clan_id', '=', '$created_clan_id']]],
            'DecisionUser' => ['entity' => 'XF:User', 'type' => self::TO_ONE, 'conditions' => [['user_id', '=', '$decision_user_id']]],
            'Answers' => ['entity' => 'Warext\\Clans:ClanApplicationAnswer', 'type' => self::TO_MANY, 'conditions' => 'application_id', 'key' => 'field_id']
        ];
        return $structure;
    }
}
