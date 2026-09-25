<?php

namespace Warext\Clans\Service;

use XF\Service\AbstractService;

class Notification extends AbstractService
{
    public function alertUser(\XF\Entity\User $receiver, \Warext\Clans\Entity\Clan $clan, string $action, ?\XF\Entity\User $sender = null, array $extra = []): void
    {
        if (!$receiver->user_id || ($sender && $receiver->user_id === $sender->user_id))
        {
            return;
        }

        $extra['depends_on_addon_id'] = 'Warext/Clans';
        $this->repository('XF:UserAlert')->alertFromUser($receiver, $sender, 'wx_clan', $clan->clan_id, $action, $extra);
    }

    public function alertClanManagers(\Warext\Clans\Entity\Clan $clan, string $action, ?\XF\Entity\User $sender = null, array $extra = []): void
    {
        $members = $this->finder('Warext\\Clans:ClanMember')
            ->where('clan_id', $clan->clan_id)
            ->where('member_state', 'active')
            ->whereOr(['is_owner', 1], ['is_manager', 1])
            ->with('User')
            ->fetch();

        foreach ($members as $member)
        {
            if ($member->User)
            {
                $this->alertUser($member->User, $clan, $action, $sender, $extra);
            }
        }
    }
}
