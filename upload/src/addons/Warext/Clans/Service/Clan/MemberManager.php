<?php

namespace Warext\Clans\Service\Clan;

use XF\Service\AbstractService;

class MemberManager extends AbstractService
{
    protected \Warext\Clans\Entity\Clan $clan;

    public function __construct(\XF\App $app, \Warext\Clans\Entity\Clan $clan)
    {
        parent::__construct($app);
        $this->clan = $clan;
    }

    public function assertMembershipLimit(\XF\Entity\User $user): void
    {
        $existing = $this->em()->find('Warext\\Clans:ClanMember', [$this->clan->clan_id, $user->user_id]);
        if ($existing && $existing->member_state === 'active')
        {
            return;
        }

        $max = (int)($this->app->options()->wxClansMaxMemberships ?? 0);
        if ($max > 0)
        {
            $count = $this->repository('Warext\\Clans:Clan')->countActiveMemberships($user->user_id);
            if ($count >= $max)
            {
                throw new \XF\PrintableException('This user has reached the maximum number of clan memberships.');
            }
        }

        $maxClanMembers = (int)($this->app->options()->wxClansMaxMembersPerClan ?? 0);
        if ($maxClanMembers > 0)
        {
            $activeMembers = $this->finder('Warext\\Clans:ClanMember')
                ->where('clan_id', $this->clan->clan_id)
                ->where('member_state', 'active')
                ->total();
            if ($activeMembers >= $maxClanMembers)
            {
                throw new \XF\PrintableException('This clan has reached its member limit.');
            }
        }
    }

    public function addMember(\XF\Entity\User $user, int $actorUserId = 0, int $roleId = 0): \Warext\Clans\Entity\ClanMember
    {
        if ($this->clan->isManagementLocked())
        {
            throw new \XF\PrintableException('This clan is currently locked by forum management.');
        }

        if ($user->user_state !== 'valid' || $user->is_banned)
        {
            throw new \XF\PrintableException('This forum account is not currently eligible to join clans.');
        }

        $this->assertMembershipLimit($user);

        if ($this->repository('Warext\\Clans:Clan')->isUserBlacklisted($this->clan->clan_id, $user->user_id))
        {
            throw new \XF\PrintableException('This user is currently blocked from joining the clan.');
        }

        $role = $roleId ? $this->em()->find('Warext\\Clans:ClanRole', $roleId) : null;
        if (!$role || $role->clan_id !== $this->clan->clan_id || $role->role_type !== 'custom')
        {
            $role = $this->finder('Warext\\Clans:ClanRole')
                ->where('clan_id', $this->clan->clan_id)
                ->where('role_type', 'member')
                ->fetchOne();
        }

        $existing = $this->em()->find('Warext\\Clans:ClanMember', [$this->clan->clan_id, $user->user_id]);
        if ($existing)
        {
            if ($existing->member_state !== 'active')
            {
                $existing->member_state = 'active';
                $existing->last_activity = \XF::$time;
                $existing->is_owner = false;
                $existing->is_manager = false;
                $existing->role_id = $role ? $role->role_id : 0;
                $existing->save();
                $this->recountMembers();
            }
            return $existing;
        }

        $member = $this->em()->create('Warext\\Clans:ClanMember');
        $member->bulkSet([
            'clan_id' => $this->clan->clan_id,
            'user_id' => $user->user_id,
            'role_id' => $role ? $role->role_id : 0,
            'member_state' => 'active',
            'join_date' => \XF::$time,
            'last_activity' => \XF::$time
        ]);
        $member->save();

        $this->recountMembers();
        $this->service('Warext\\Clans:Audit\\Logger')->log(
            $this->clan->clan_id,
            $actorUserId,
            'member_added',
            ['user_id' => $user->user_id],
            'user',
            $user->user_id
        );

        return $member;
    }

    public function removeMember(\Warext\Clans\Entity\ClanMember $member, int $actorUserId = 0, string $reason = ''): void
    {
        $this->assertMemberBelongsToClan($member);
        if ($member->is_owner)
        {
            throw new \XF\PrintableException('The clan owner cannot be removed. Transfer ownership first.');
        }

        $userId = $member->user_id;
        $member->delete();
        $this->recountMembers();
        $this->clearActivePreferenceIfNeeded($userId);

        $this->service('Warext\\Clans:Audit\\Logger')->log(
            $this->clan->clan_id,
            $actorUserId,
            'member_removed',
            ['user_id' => $userId, 'reason' => trim($reason)],
            'user',
            $userId
        );
    }

    public function setManager(\Warext\Clans\Entity\ClanMember $member, bool $isManager, int $actorUserId): void
    {
        $this->assertMemberBelongsToClan($member);
        if ($member->is_owner)
        {
            throw new \XF\PrintableException('The clan owner role cannot be changed.');
        }

        if ((bool)$member->is_manager === $isManager)
        {
            return;
        }

        if ($isManager)
        {
            $maxManagers = (int)($this->app->options()->wxClansMaxManagersPerClan ?? 0);
            if ($maxManagers > 0)
            {
                $managerCount = $this->finder('Warext\\Clans:ClanMember')
                    ->where('clan_id', $this->clan->clan_id)
                    ->where('member_state', 'active')
                    ->where('is_manager', 1)
                    ->total();
                if ($managerCount >= $maxManagers)
                {
                    throw new \XF\PrintableException('This clan has reached its manager limit.');
                }
            }

            $role = $this->service('Warext\\Clans:Clan\\RoleManager', $this->clan)->getOrCreateManagerRole();
            $member->is_manager = true;
            $member->role_id = $role->role_id;
        }
        else
        {
            $role = $this->finder('Warext\\Clans:ClanRole')
                ->where('clan_id', $this->clan->clan_id)
                ->where('role_type', 'member')
                ->fetchOne();
            $member->is_manager = false;
            $member->role_id = $role ? $role->role_id : 0;
        }

        $member->last_activity = \XF::$time;
        $member->save();

        $this->service('Warext\\Clans:Audit\\Logger')->log(
            $this->clan->clan_id,
            $actorUserId,
            $isManager ? 'manager_promoted' : 'manager_demoted',
            ['user_id' => $member->user_id],
            'user',
            $member->user_id
        );
    }

    public function assignCustomRole(\Warext\Clans\Entity\ClanMember $member, ?\Warext\Clans\Entity\ClanRole $role, int $actorUserId): void
    {
        $this->assertMemberBelongsToClan($member);
        if ($member->is_owner || $member->is_manager)
        {
            throw new \XF\PrintableException('Owner and manager roles are managed separately.');
        }

        if ($role && ($role->clan_id !== $this->clan->clan_id || $role->role_type !== 'custom'))
        {
            throw new \XF\PrintableException('Invalid clan role.');
        }

        if (!$role)
        {
            $role = $this->finder('Warext\\Clans:ClanRole')
                ->where('clan_id', $this->clan->clan_id)
                ->where('role_type', 'member')
                ->fetchOne();
        }

        $member->role_id = $role ? $role->role_id : 0;
        $member->last_activity = \XF::$time;
        $member->save();

        $this->service('Warext\\Clans:Audit\\Logger')->log(
            $this->clan->clan_id,
            $actorUserId,
            'member_role_changed',
            ['user_id' => $member->user_id, 'role_id' => $member->role_id],
            'user',
            $member->user_id
        );
    }

    public function recountMembers(): void
    {
        $count = $this->finder('Warext\\Clans:ClanMember')
            ->where('clan_id', $this->clan->clan_id)
            ->where('member_state', 'active')
            ->total();

        $this->clan->member_count = $count;
        $this->clan->saveIfChanged();
    }

    protected function clearActivePreferenceIfNeeded(int $userId): void
    {
        $pref = $this->em()->find('Warext\\Clans:ClanUserPreference', $userId);
        if ($pref && $pref->active_clan_id === $this->clan->clan_id)
        {
            $pref->active_clan_id = 0;
            $pref->save();
        }
    }

    protected function assertMemberBelongsToClan(\Warext\Clans\Entity\ClanMember $member): void
    {
        if ($member->clan_id !== $this->clan->clan_id)
        {
            throw new \LogicException('Member does not belong to this clan.');
        }
    }
}
