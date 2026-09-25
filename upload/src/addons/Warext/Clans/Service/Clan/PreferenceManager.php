<?php

namespace Warext\Clans\Service\Clan;

use XF\Service\AbstractService;

class PreferenceManager extends AbstractService
{
    public function setActiveClan(\XF\Entity\User $user, int $clanId): void
    {
        if ($clanId)
        {
            $member = $this->em()->find('Warext\\Clans:ClanMember', [$clanId, $user->user_id]);
            if (!$member || $member->member_state !== 'active' || !$member->Clan || !in_array($member->Clan->status, ['active', 'restricted'], true))
            {
                throw new \XF\PrintableException('You can only display an active clan you currently belong to.');
            }
        }

        $pref = $this->em()->find('Warext\\Clans:ClanUserPreference', $user->user_id);
        if (!$pref)
        {
            $pref = $this->em()->create('Warext\\Clans:ClanUserPreference');
            $pref->user_id = $user->user_id;
        }
        $pref->active_clan_id = $clanId;
        $pref->save();
    }
}
