<?php

namespace Warext\Clans\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Clan extends Entity
{
    public function getVisitorMembership(): ?ClanMember
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            return null;
        }

        return $this->em()->find('Warext\\Clans:ClanMember', [$this->clan_id, $visitor->user_id], ['Role']);
    }

    public function isVisitorOwner(): bool
    {
        $visitor = \XF::visitor();
        return $visitor->user_id && $visitor->user_id === $this->owner_user_id;
    }

    public function isManagementLocked(): bool
    {
        return in_array($this->status, ['suspended', 'closed'], true);
    }

    public function isVisitorAccountEligible(): bool
    {
        $visitor = \XF::visitor();
        return (bool)($visitor->user_id && $visitor->user_state === 'valid' && !$visitor->is_banned);
    }

    public function visitorCanModerateClans(): bool
    {
        $visitor = \XF::visitor();
        return (bool)($visitor->is_admin || $visitor->hasPermission('wxClans', 'moderate'));
    }

    public function canView(?string &$error = null): bool
    {
        return $this->status !== 'closed' || $this->isVisitorOwner() || $this->visitorCanModerateClans();
    }

    public function canManage(?string &$error = null): bool
    {
        if (!$this->isVisitorAccountEligible())
        {
            $error = 'Your forum account is not currently eligible to manage clans.';
            return false;
        }

        if ($this->isManagementLocked())
        {
            $error = 'This clan is currently locked by forum management.';
            return false;
        }

        if ($this->isVisitorOwner())
        {
            return true;
        }

        $member = $this->getVisitorMembership();
        return (bool)($member && $member->member_state === 'active' && $member->is_manager);
    }

    public function canManagePermission(string $permissionId, ?string &$error = null): bool
    {
        if (!$this->isVisitorAccountEligible())
        {
            $error = 'Your forum account is not currently eligible to manage clans.';
            return false;
        }

        if ($this->isManagementLocked())
        {
            $error = 'This clan is currently locked by forum management.';
            return false;
        }

        if ($this->isVisitorOwner())
        {
            return true;
        }

        $member = $this->getVisitorMembership();
        if (!$member || !$member->is_manager)
        {
            return false;
        }

        return $member->hasClanPermission($permissionId);
    }

    public function canManageManagers(?string &$error = null): bool
    {
        return $this->isVisitorAccountEligible() && !$this->isManagementLocked() && $this->isVisitorOwner();
    }

    public function canManageRoles(?string &$error = null): bool
    {
        return $this->isVisitorAccountEligible() && !$this->isManagementLocked() && $this->isVisitorOwner();
    }

    public function canTransferOwnership(?string &$error = null): bool
    {
        return $this->isVisitorAccountEligible() && !$this->isManagementLocked() && $this->isVisitorOwner();
    }

    public function canReport(?string &$error = null): bool
    {
        return $this->isVisitorAccountEligible() && $this->status !== 'closed';
    }

    public function canJoin(?string &$error = null): bool
    {
        $visitor = \XF::visitor();
        if (!$this->isVisitorAccountEligible() || $this->status !== 'active')
        {
            return false;
        }

        $membership = $this->em()->find('Warext\\Clans:ClanMember', [$this->clan_id, $visitor->user_id]);
        if ($membership && $membership->member_state === 'active')
        {
            return false;
        }

        $blacklist = $this->finder('Warext\\Clans:ClanBlacklist')
            ->where('clan_id', $this->clan_id)
            ->where('user_id', $visitor->user_id)
            ->fetchOne();

        if ($blacklist && $blacklist->isActive())
        {
            $error = 'You are not currently eligible to join this clan.';
            return false;
        }

        return $this->join_mode !== 'closed';
    }

    public function canLeave(?string &$error = null): bool
    {
        if (!$this->isVisitorAccountEligible())
        {
            $error = 'Your forum account is not currently eligible to perform clan actions.';
            return false;
        }

        if ($this->isVisitorOwner())
        {
            $error = 'The clan owner must transfer ownership before leaving.';
            return false;
        }

        $member = $this->getVisitorMembership();
        return (bool)($member && $member->member_state === 'active');
    }

    public function getDisplayTag(): string
    {
        return '[' . strtoupper($this->tag) . ']';
    }

    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_wx_clan';
        $structure->shortName = 'Warext\\Clans:Clan';
        $structure->primaryKey = 'clan_id';
        $structure->columns = [
            'clan_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'owner_user_id' => ['type' => self::UINT, 'required' => true],
            'title' => ['type' => self::STR, 'maxLength' => 100, 'required' => true],
            'tag' => ['type' => self::STR, 'maxLength' => 24, 'required' => true],
            'slug' => ['type' => self::STR, 'maxLength' => 100, 'default' => ''],
            'description' => ['type' => self::STR, 'default' => ''],
            'rules' => ['type' => self::STR, 'default' => ''],
            'category' => ['type' => self::STR, 'maxLength' => 50, 'default' => ''],
            'status' => ['type' => self::STR, 'default' => 'active', 'allowedValues' => ['active','restricted','suspended','closed']],
            'join_mode' => ['type' => self::STR, 'default' => 'application', 'allowedValues' => ['open','application','invite','closed']],
            'manager_banner' => ['type' => self::STR, 'maxLength' => 100, 'default' => ''],
            'tag_color' => ['type' => self::STR, 'maxLength' => 7, 'default' => '#4f46e5'],
            'manager_banner_color' => ['type' => self::STR, 'maxLength' => 7, 'default' => '#805ad5'],
            'logo_url' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'cover_url' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'member_count' => ['type' => self::UINT, 'default' => 1],
            'created_date' => ['type' => self::UINT, 'default' => \XF::$time],
            'approved_date' => ['type' => self::UINT, 'default' => 0],
            'approved_by' => ['type' => self::UINT, 'default' => 0]
        ];
        $structure->getters = [
            'display_tag' => true
        ];
        $structure->relations = [
            'Owner' => ['entity' => 'XF:User', 'type' => self::TO_ONE, 'conditions' => [['user_id', '=', '$owner_user_id']], 'primary' => true],
            'Members' => ['entity' => 'Warext\\Clans:ClanMember', 'type' => self::TO_MANY, 'conditions' => 'clan_id', 'key' => 'user_id'],
            'Roles' => ['entity' => 'Warext\\Clans:ClanRole', 'type' => self::TO_MANY, 'conditions' => 'clan_id', 'order' => 'display_order'],
            'Announcements' => ['entity' => 'Warext\\Clans:ClanAnnouncement', 'type' => self::TO_MANY, 'conditions' => 'clan_id', 'order' => 'create_date']
        ];
        return $structure;
    }
}
