<?php

namespace Warext\Clans\XF\Entity;

use XF\Mvc\Entity\Structure;

class User extends XFCP_User
{
    protected static array $wxClanMembershipCache = [];
    public function getWxActiveClanMembership(): ?\Warext\Clans\Entity\ClanMember
    {
        if (!$this->user_id)
        {
            return null;
        }
        if (array_key_exists($this->user_id, self::$wxClanMembershipCache))
        {
            return self::$wxClanMembershipCache[$this->user_id];
        }

        $pref = \XF::em()->find('Warext\\Clans:ClanUserPreference', $this->user_id);
        if ($pref && $pref->active_clan_id)
        {
            $membership = \XF::em()->find('Warext\\Clans:ClanMember', [$pref->active_clan_id, $this->user_id], ['Clan', 'Role']);
            if ($membership && $membership->member_state === 'active' && $membership->Clan && in_array($membership->Clan->status, ['active', 'restricted'], true))
            {
                return self::$wxClanMembershipCache[$this->user_id] = $membership;
            }
        }

        $membership = \XF::finder('Warext\\Clans:ClanMember')
            ->where('user_id', $this->user_id)
            ->where('member_state', 'active')
            ->with(['Clan', 'Role'])
            ->where('Clan.status', ['active', 'restricted'])
            ->order('is_owner', 'DESC')
            ->order('is_manager', 'DESC')
            ->order('join_date', 'ASC')
            ->fetchOne();

        self::$wxClanMembershipCache[$this->user_id] = $membership ?: null;
        return $membership ?: null;
    }

    public function getWxActiveClan(): ?\Warext\Clans\Entity\Clan
    {
        $membership = $this->getWxActiveClanMembership();
        return $membership ? $membership->Clan : null;
    }

    public function getWxClanMemberships()
    {
        if (!$this->user_id)
        {
            return [];
        }

        return \XF::finder('Warext\\Clans:ClanMember')
            ->where('user_id', $this->user_id)
            ->where('member_state', 'active')
            ->with(['Clan', 'Role'])
            ->where('Clan.status', ['active', 'restricted'])
            ->order('is_owner', 'DESC')
            ->order('is_manager', 'DESC')
            ->order('join_date', 'ASC')
            ->fetch();
    }

    public function getWxClanMembershipCount(): int
    {
        if (!$this->user_id)
        {
            return 0;
        }
        return \XF::finder('Warext\\Clans:ClanMember')
            ->where('user_id', $this->user_id)
            ->where('member_state', 'active')
            ->total();
    }

    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);
        $structure->getters['wx_active_clan_membership'] = true;
        $structure->getters['wx_active_clan'] = true;
        $structure->getters['wx_clan_memberships'] = true;
        $structure->getters['wx_clan_membership_count'] = true;
        return $structure;
    }
}
