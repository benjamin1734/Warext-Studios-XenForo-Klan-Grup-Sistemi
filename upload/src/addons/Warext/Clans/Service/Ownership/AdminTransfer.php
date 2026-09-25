<?php

namespace Warext\Clans\Service\Ownership;

use XF\Service\AbstractService;

class AdminTransfer extends AbstractService
{
    protected \Warext\Clans\Entity\Clan $clan;

    public function __construct(\XF\App $app, \Warext\Clans\Entity\Clan $clan)
    {
        parent::__construct($app);
        $this->clan = $clan;
    }

    public function transfer(\Warext\Clans\Entity\ClanMember $targetMember, \XF\Entity\User $actor, bool $keepOldOwnerAsManager, string $reason = ''): void
    {
        if ($targetMember->clan_id !== $this->clan->clan_id || $targetMember->member_state !== 'active')
        {
            throw new \XF\PrintableException('The selected new owner must be an active clan member.');
        }
        if ($targetMember->user_id === $this->clan->owner_user_id)
        {
            throw new \XF\PrintableException('The selected user already owns this clan.');
        }

        $targetUser = $targetMember->User ?: $this->em()->find('XF:User', $targetMember->user_id);
        if (!$targetUser || $targetUser->user_state !== 'valid' || $targetUser->is_banned)
        {
            throw new \XF\PrintableException('The selected user is not eligible to own a clan.');
        }

        $maxOwned = (int)($this->app->options()->wxClansMaxOwned ?? 0);
        if ($maxOwned > 0 && $this->repository('Warext\\Clans:Clan')->countOwnedClans($targetMember->user_id) >= $maxOwned)
        {
            throw new \XF\PrintableException('The selected user has reached the maximum number of clans they may own.');
        }

        $oldOwnerId = $this->clan->owner_user_id;
        $oldOwnerMember = $this->em()->find('Warext\\Clans:ClanMember', [$this->clan->clan_id, $oldOwnerId], ['User']);
        if (!$oldOwnerMember || $oldOwnerMember->member_state !== 'active')
        {
            throw new \XF\PrintableException('The current owner membership could not be resolved.');
        }

        $roleManager = $this->service('Warext\\Clans:Clan\\RoleManager', $this->clan);
        $ownerRole = $roleManager->getRoleByType('owner');
        $managerRole = $roleManager->getOrCreateManagerRole();
        $memberRole = $roleManager->getRoleByType('member');
        $reason = mb_substr(trim($reason), 0, 5000);
        if ($reason === '')
        {
            throw new \XF\PrintableException('A reason is required for a forum-forced ownership change.');
        }

        $maxManagers = (int)($this->app->options()->wxClansMaxManagersPerClan ?? 0);
        if ($maxManagers > 0 && $keepOldOwnerAsManager)
        {
            $managerCount = $this->finder('Warext\\Clans:ClanMember')
                ->where('clan_id', $this->clan->clan_id)
                ->where('member_state', 'active')
                ->where('is_manager', 1)
                ->where('is_owner', 0)
                ->total();

            $targetWasManager = (bool)($targetMember->is_manager && !$targetMember->is_owner);
            $managerCountAfter = $managerCount - ($targetWasManager ? 1 : 0) + 1;
            if ($managerCountAfter > $maxManagers)
            {
                throw new \XF\PrintableException('Keeping the previous owner as Manager would exceed the clan manager limit.');
            }
        }

        $db = $this->db();
        $db->beginTransaction();
        try
        {
            $oldOwnerMember->is_owner = false;
            $oldOwnerMember->is_manager = $keepOldOwnerAsManager;
            $oldOwnerMember->role_id = $keepOldOwnerAsManager
                ? $managerRole->role_id
                : ($memberRole ? $memberRole->role_id : 0);
            $oldOwnerMember->last_activity = \XF::$time;
            $oldOwnerMember->save();

            $targetMember->is_owner = true;
            $targetMember->is_manager = true;
            $targetMember->role_id = $ownerRole ? $ownerRole->role_id : $managerRole->role_id;
            $targetMember->last_activity = \XF::$time;
            $targetMember->save();

            $this->clan->owner_user_id = $targetMember->user_id;
            $this->clan->save();

            $db->update(
                'xf_wx_clan_ownership_transfer',
                [
                    'status' => 'cancelled',
                    'approved_by' => $actor->user_id,
                    'approved_date' => \XF::$time,
                    'decision_reason' => 'Cancelled because forum management changed clan ownership.'
                ],
                "clan_id = ? AND status IN ('pending', 'accepted')",
                $this->clan->clan_id
            );

            $db->query(
                "UPDATE xf_wx_clan_application
                 SET status = 'cancelled', decision_date = ?, decision_user_id = ?, decision_reason = ?
                 WHERE clan_id = ?
                   AND application_type IN ('change', 'close', 'reopen')
                   AND status = 'pending'",
                [\XF::$time, $actor->user_id, 'Cancelled because forum management changed clan ownership.', $this->clan->clan_id]
            );

            $this->service('Warext\\Clans:Audit\\Logger')->log(
                $this->clan->clan_id,
                $actor->user_id,
                'ownership_forced_by_forum',
                [
                    'from_user_id' => $oldOwnerId,
                    'to_user_id' => $targetMember->user_id,
                    'old_owner_kept_as_manager' => $keepOldOwnerAsManager,
                    'reason' => $reason
                ],
                'clan',
                $this->clan->clan_id
            );

            $db->commit();
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }

        if ($actor->user_id && ($actor->is_moderator || $actor->is_admin))
        {
            \XF::app()->logger()->moderatorLogger()->log(
                'wx_clan',
                $this->clan,
                'owner_forced_change',
                ['old_owner_user_id' => $oldOwnerId, 'new_owner_user_id' => $targetMember->user_id, 'reason' => $reason],
                false,
                $actor
            );
        }

        if ($targetUser)
        {
            $this->service('Warext\\Clans:Notification')->alertUser(
                $targetUser,
                $this->clan,
                'ownership_forced',
                $actor,
                ['role' => 'new_owner', 'reason' => $reason]
            );
        }
        if ($oldOwnerMember->User)
        {
            $this->service('Warext\\Clans:Notification')->alertUser(
                $oldOwnerMember->User,
                $this->clan,
                'ownership_forced',
                $actor,
                ['role' => 'old_owner', 'reason' => $reason]
            );
        }
    }
}
