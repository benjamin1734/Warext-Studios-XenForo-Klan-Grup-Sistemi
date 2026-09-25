<?php

namespace Warext\Clans\Service\Invitation;

use XF\Service\AbstractService;

class Manager extends AbstractService
{
    protected \Warext\Clans\Entity\Clan $clan;

    public function __construct(\XF\App $app, \Warext\Clans\Entity\Clan $clan)
    {
        parent::__construct($app);
        $this->clan = $clan;
    }

    public function create(\XF\Entity\User $user, \XF\Entity\User $actor): \Warext\Clans\Entity\ClanInvitation
    {
        if ($this->clan->isManagementLocked())
        {
            throw new \XF\PrintableException('This clan is currently locked.');
        }
        if ($user->user_state !== 'valid' || $user->is_banned)
        {
            throw new \XF\PrintableException('This forum account is not currently eligible to join clans.');
        }

        $membership = $this->em()->find('Warext\\Clans:ClanMember', [$this->clan->clan_id, $user->user_id]);
        if ($membership && $membership->member_state === 'active')
        {
            throw new \XF\PrintableException('This user is already a clan member.');
        }
        if ($this->repository('Warext\\Clans:Clan')->isUserBlacklisted($this->clan->clan_id, $user->user_id))
        {
            throw new \XF\PrintableException('This user is currently blocked from joining the clan.');
        }

        $existing = $this->finder('Warext\\Clans:ClanInvitation')
            ->where('clan_id', $this->clan->clan_id)
            ->where('user_id', $user->user_id)
            ->where('status', 'pending')
            ->fetchOne();
        if ($existing && !$existing->isExpired())
        {
            throw new \XF\PrintableException('This user already has a pending invitation.');
        }
        if ($existing && $existing->isExpired())
        {
            $existing->status = 'expired';
            $existing->save();
        }

        $days = max(1, (int)($this->app->options()->wxClansInviteDays ?? 7));
        $invitation = $this->em()->create('Warext\\Clans:ClanInvitation');
        $invitation->bulkSet([
            'clan_id' => $this->clan->clan_id,
            'user_id' => $user->user_id,
            'invited_by' => $actor->user_id,
            'status' => 'pending',
            'create_date' => \XF::$time,
            'expiry_date' => \XF::$time + ($days * 86400)
        ]);
        $invitation->save();

        $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $actor->user_id, 'invitation_created', ['user_id' => $user->user_id], 'clan_invitation', $invitation->invitation_id);
        $this->service('Warext\\Clans:Notification')->alertUser($user, $this->clan, 'invited', $actor, ['invitation_id' => $invitation->invitation_id]);
        return $invitation;
    }

    public function respond(\Warext\Clans\Entity\ClanInvitation $invitation, \XF\Entity\User $user, bool $accept): void
    {
        if ($invitation->clan_id !== $this->clan->clan_id || $invitation->user_id !== $user->user_id || $invitation->status !== 'pending')
        {
            throw new \XF\PrintableException('This invitation is no longer available.');
        }
        if ($invitation->isExpired())
        {
            $invitation->status = 'expired';
            $invitation->save();
            throw new \XF\PrintableException('This invitation has expired.');
        }

        $db = $this->db();
        $db->beginTransaction();
        try
        {
            if ($accept)
            {
                $this->service('Warext\\Clans:Clan\\MemberManager', $this->clan)->addMember($user, $user->user_id);
                $invitation->status = 'accepted';
            }
            else
            {
                $invitation->status = 'declined';
            }
            $invitation->save();

            $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $user->user_id, $accept ? 'invitation_accepted' : 'invitation_declined', ['user_id' => $user->user_id], 'clan_invitation', $invitation->invitation_id);
            $db->commit();
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }

        if ($accept)
        {
            $this->service('Warext\\Clans:Notification')->alertClanManagers($this->clan, 'member_joined', $user, ['user_id' => $user->user_id]);
        }
    }

    public function cancel(\Warext\Clans\Entity\ClanInvitation $invitation, \XF\Entity\User $actor): void
    {
        if ($invitation->clan_id !== $this->clan->clan_id || $invitation->status !== 'pending')
        {
            throw new \XF\PrintableException('This invitation is no longer pending.');
        }

        $invitation->status = 'cancelled';
        $invitation->save();
        $this->service('Warext\\Clans:Audit\\Logger')->log(
            $this->clan->clan_id,
            $actor->user_id,
            'invitation_cancelled',
            ['user_id' => $invitation->user_id],
            'clan_invitation',
            $invitation->invitation_id
        );
    }

}
