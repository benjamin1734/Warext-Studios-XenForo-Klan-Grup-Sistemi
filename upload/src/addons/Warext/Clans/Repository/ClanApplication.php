<?php

namespace Warext\Clans\Repository;

use XF\Mvc\Entity\Repository;

class ClanApplication extends Repository
{
    public function findPendingCreateApplications()
    {
        return $this->finder('Warext\\Clans:ClanApplication')
            ->where('application_type', 'create')
            ->where('status', 'pending')
            ->with('User')
            ->order('create_date', 'ASC');
    }

    public function findPendingChangeApplications()
    {
        return $this->finder('Warext\\Clans:ClanApplication')
            ->where('application_type', 'change')
            ->where('status', 'pending')
            ->with(['User', 'Clan'])
            ->order('create_date', 'ASC');
    }


    public function findPendingLifecycleRequests()
    {
        return $this->finder('Warext\Clans:ClanApplication')
            ->where('application_type', ['close', 'reopen'])
            ->where('status', 'pending')
            ->with(['User', 'Clan.Owner'])
            ->order('create_date', 'ASC');
    }

    public function findPendingJoinApplicationsForClan(int $clanId)
    {
        return $this->finder('Warext\\Clans:ClanApplication')
            ->where('application_type', 'join')
            ->where('clan_id', $clanId)
            ->where('status', 'pending')
            ->with('User')
            ->order('create_date', 'ASC');
    }
}
