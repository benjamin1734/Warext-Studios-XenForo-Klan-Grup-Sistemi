<?php

namespace Warext\Clans\Service\Ownership;

use XF\Service\AbstractService;

class Manager extends AbstractService
{
    protected \Warext\Clans\Entity\Clan $clan;

    public function __construct(\XF\App $app, \Warext\Clans\Entity\Clan $clan)
    {
        parent::__construct($app);
        $this->clan = $clan;
    }

    public function create(\XF\Entity\User $toUser, \XF\Entity\User $owner): \Warext\Clans\Entity\ClanOwnershipTransfer
    {
        if ($this->clan->owner_user_id !== $owner->user_id || $this->clan->isManagementLocked())
        {
            throw new \XF\PrintableException('Only the active clan owner may transfer ownership.');
        }
        if ($toUser->user_id === $owner->user_id)
        {
            throw new \XF\PrintableException('You already own this clan.');
        }
        if ($toUser->user_state !== 'valid' || $toUser->is_banned)
        {
            throw new \XF\PrintableException('The selected user account is not eligible to receive clan ownership.');
        }

        $member = $this->em()->find('Warext\\Clans:ClanMember', [$this->clan->clan_id, $toUser->user_id]);
        if (!$member || $member->member_state !== 'active')
        {
            throw new \XF\PrintableException('The new owner must already be an active clan member.');
        }

        $pending = $this->finder('Warext\\Clans:ClanOwnershipTransfer')
            ->where('clan_id', $this->clan->clan_id)
            ->where('status', ['pending','accepted'])
            ->fetchOne();
        if ($pending)
        {
            throw new \XF\PrintableException('An ownership transfer is already pending.');
        }

        $maxOwned = (int)($this->app->options()->wxClansMaxOwned ?? 0);
        if ($maxOwned > 0 && $this->repository('Warext\\Clans:Clan')->countOwnedClans($toUser->user_id) >= $maxOwned)
        {
            throw new \XF\PrintableException('The selected user has reached the maximum number of clans they may own.');
        }

        $transfer = $this->em()->create('Warext\\Clans:ClanOwnershipTransfer');
        $transfer->bulkSet([
            'clan_id' => $this->clan->clan_id,
            'from_user_id' => $owner->user_id,
            'to_user_id' => $toUser->user_id,
            'status' => 'pending',
            'create_date' => \XF::$time
        ]);
        $transfer->save();

        $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $owner->user_id, 'ownership_transfer_requested', ['to_user_id'=>$toUser->user_id,'transfer_id'=>$transfer->transfer_id], 'clan_ownership_transfer', $transfer->transfer_id);
        $this->service('Warext\\Clans:Notification')->alertUser($toUser, $this->clan, 'ownership_transfer_requested', $owner, ['transfer_id'=>$transfer->transfer_id]);
        return $transfer;
    }

    public function accept(\Warext\Clans\Entity\ClanOwnershipTransfer $transfer, \XF\Entity\User $user): void
    {
        if ($this->clan->isManagementLocked())
        {
            throw new \XF\PrintableException('This clan is currently locked by forum management.');
        }

        if ($transfer->clan_id !== $this->clan->clan_id || $transfer->to_user_id !== $user->user_id || $transfer->status !== 'pending')
        {
            throw new \XF\PrintableException('This ownership transfer is no longer available.');
        }
        if ($user->user_state !== 'valid' || $user->is_banned)
        {
            throw new \XF\PrintableException('Your account is not eligible to receive clan ownership.');
        }
        if ($this->clan->owner_user_id !== $transfer->from_user_id)
        {
            throw new \XF\PrintableException('The clan ownership has changed since this transfer was created.');
        }
        $member = $this->em()->find('Warext\\Clans:ClanMember', [$this->clan->clan_id, $user->user_id]);
        if (!$member || $member->member_state !== 'active')
        {
            throw new \XF\PrintableException('You must still be an active clan member to accept ownership.');
        }

        $transfer->status = 'accepted';
        $transfer->accepted_date = \XF::$time;
        $transfer->save();

        $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $user->user_id, 'ownership_transfer_accepted', ['transfer_id'=>$transfer->transfer_id], 'clan_ownership_transfer', $transfer->transfer_id);
    }

    public function cancel(\Warext\Clans\Entity\ClanOwnershipTransfer $transfer, \XF\Entity\User $owner): void
    {
        if ($this->clan->owner_user_id !== $owner->user_id || $transfer->clan_id !== $this->clan->clan_id || !in_array($transfer->status, ['pending','accepted'], true))
        {
            throw new \XF\PrintableException('This ownership transfer cannot be cancelled.');
        }
        $transfer->status = 'cancelled';
        $transfer->save();
        $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $owner->user_id, 'ownership_transfer_cancelled', ['transfer_id'=>$transfer->transfer_id,'to_user_id'=>$transfer->to_user_id], 'clan_ownership_transfer', $transfer->transfer_id);
    }

    public function approve(\Warext\Clans\Entity\ClanOwnershipTransfer $transfer, \XF\Entity\User $actor): void
    {
        if ($transfer->clan_id !== $this->clan->clan_id || $transfer->status !== 'accepted')
        {
            throw new \XF\PrintableException('The new owner must accept the transfer before forum approval.');
        }
        if ($this->clan->owner_user_id !== $transfer->from_user_id)
        {
            throw new \XF\PrintableException('The clan ownership has changed since this transfer was created.');
        }
        $targetUser = $transfer->ToUser ?: $this->em()->find('XF:User', $transfer->to_user_id);
        if (!$targetUser || $targetUser->user_state !== 'valid' || $targetUser->is_banned)
        {
            throw new \XF\PrintableException('The proposed owner account is no longer eligible.');
        }
        $maxOwned = (int)($this->app->options()->wxClansMaxOwned ?? 0);
        if ($maxOwned > 0 && $this->repository('Warext\\Clans:Clan')->countOwnedClans($transfer->to_user_id) >= $maxOwned)
        {
            throw new \XF\PrintableException('The proposed owner has reached the maximum number of clans they may own.');
        }

        $newOwnerMember = $this->em()->find('Warext\\Clans:ClanMember', [$this->clan->clan_id, $transfer->to_user_id]);
        $oldOwnerMember = $this->em()->find('Warext\\Clans:ClanMember', [$this->clan->clan_id, $transfer->from_user_id]);
        if (!$newOwnerMember || $newOwnerMember->member_state !== 'active' || !$oldOwnerMember || $oldOwnerMember->member_state !== 'active')
        {
            throw new \XF\PrintableException('Ownership transfer members could not be resolved.');
        }

        $roleManager = $this->service('Warext\\Clans:Clan\\RoleManager', $this->clan);
        $ownerRole = $roleManager->getRoleByType('owner');
        $managerRole = $roleManager->getOrCreateManagerRole();

        $maxManagers = (int)($this->app->options()->wxClansMaxManagersPerClan ?? 0);
        if ($maxManagers > 0)
        {
            $managerCount = $this->finder('Warext\\Clans:ClanMember')
                ->where('clan_id', $this->clan->clan_id)
                ->where('member_state', 'active')
                ->where('is_manager', 1)
                ->where('is_owner', 0)
                ->total();

            $targetWasManager = (bool)($newOwnerMember->is_manager && !$newOwnerMember->is_owner);
            $managerCountAfter = $managerCount - ($targetWasManager ? 1 : 0) + 1;
            if ($managerCountAfter > $maxManagers)
            {
                throw new \XF\PrintableException('Ownership transfer would exceed the clan manager limit.');
            }
        }

        $db = $this->db();
        $db->beginTransaction();
        try
        {
            $oldOwnerMember->is_owner = false;
            $oldOwnerMember->is_manager = true;
            $oldOwnerMember->role_id = $managerRole->role_id;
            $oldOwnerMember->save();

            $newOwnerMember->is_owner = true;
            $newOwnerMember->is_manager = true;
            $newOwnerMember->role_id = $ownerRole ? $ownerRole->role_id : $managerRole->role_id;
            $newOwnerMember->save();

            $this->clan->owner_user_id = $transfer->to_user_id;
            $this->clan->save();

            $transfer->status = 'approved';
            $transfer->approved_by = $actor->user_id;
            $transfer->approved_date = \XF::$time;
            $transfer->decision_reason = '';
            $transfer->save();

            $db->query(
                "UPDATE xf_wx_clan_application
                 SET status = 'cancelled', decision_date = ?, decision_user_id = ?, decision_reason = ?
                 WHERE clan_id = ?
                   AND application_type IN ('change', 'close', 'reopen')
                   AND status = 'pending'
                   AND user_id <> ?",
                [\XF::$time, $actor->user_id, 'Cancelled because clan ownership changed.', $this->clan->clan_id, $transfer->to_user_id]
            );

            $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $actor->user_id, 'ownership_transfer_approved', ['from_user_id'=>$transfer->from_user_id,'to_user_id'=>$transfer->to_user_id], 'clan_ownership_transfer', $transfer->transfer_id);

            $db->commit();
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }

        if ($transfer->ToUser)
        {
            $this->service('Warext\\Clans:Notification')->alertUser($transfer->ToUser, $this->clan, 'ownership_transfer_approved', $actor);
        }
        if ($transfer->FromUser)
        {
            $this->service('Warext\\Clans:Notification')->alertUser($transfer->FromUser, $this->clan, 'ownership_transfer_approved', $actor);
        }
    }

    public function reject(\Warext\Clans\Entity\ClanOwnershipTransfer $transfer, \XF\Entity\User $actor, string $reason = ''): void
    {
        if ($transfer->clan_id !== $this->clan->clan_id || !in_array($transfer->status, ['pending','accepted'], true))
        {
            throw new \XF\PrintableException('This ownership transfer can no longer be rejected.');
        }
        $transfer->status = 'rejected';
        $transfer->approved_by = $actor->user_id;
        $transfer->approved_date = \XF::$time;
        $transfer->decision_reason = trim($reason);
        $transfer->save();

        $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $actor->user_id, 'ownership_transfer_rejected', ['transfer_id'=>$transfer->transfer_id,'reason'=>trim($reason)], 'clan_ownership_transfer', $transfer->transfer_id);
        if ($transfer->ToUser)
        {
            $this->service('Warext\\Clans:Notification')->alertUser($transfer->ToUser, $this->clan, 'ownership_transfer_rejected', $actor, ['reason'=>trim($reason)]);
        }
        if ($transfer->FromUser)
        {
            $this->service('Warext\\Clans:Notification')->alertUser($transfer->FromUser, $this->clan, 'ownership_transfer_rejected', $actor, ['reason'=>trim($reason)]);
        }
    }
}
