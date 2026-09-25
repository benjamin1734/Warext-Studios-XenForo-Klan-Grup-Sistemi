<?php

namespace Warext\Clans\Service\Clan;

use Warext\Clans\Permission\ClanPermission;
use XF\Service\AbstractService;

class RoleManager extends AbstractService
{
    protected \Warext\Clans\Entity\Clan $clan;

    public function __construct(\XF\App $app, \Warext\Clans\Entity\Clan $clan)
    {
        parent::__construct($app);
        $this->clan = $clan;
    }

    public function getRoleByType(string $type): ?\Warext\Clans\Entity\ClanRole
    {
        return $this->finder('Warext\\Clans:ClanRole')
            ->where('clan_id', $this->clan->clan_id)
            ->where('role_type', $type)
            ->fetchOne();
    }

    public function getManagerRole(): ?\Warext\Clans\Entity\ClanRole
    {
        return $this->getRoleByType('manager');
    }

    public function getOrCreateManagerRole(): \Warext\Clans\Entity\ClanRole
    {
        $role = $this->getManagerRole();
        if ($role)
        {
            return $role;
        }

        $role = $this->em()->create('Warext\\Clans:ClanRole');
        $role->bulkSet([
            'clan_id' => $this->clan->clan_id,
            'title' => 'Manager',
            'role_type' => 'manager',
            'display_order' => 10,
            'permissions' => ClanPermission::defaultManagerPermissions(),
            'created_date' => \XF::$time
        ]);
        $role->save();
        return $role;
    }

    public function saveManagerPermissions(array $permissions, int $actorUserId): \Warext\Clans\Entity\ClanRole
    {
        $role = $this->getOrCreateManagerRole();
        $role->permissions = ClanPermission::sanitizeManagerPermissions($permissions);
        $role->save();

        $this->service('Warext\\Clans:Audit\\Logger')->log(
            $this->clan->clan_id,
            $actorUserId,
            'manager_permissions_updated',
            ['permissions' => $role->permissions],
            'clan_role',
            $role->role_id
        );

        return $role;
    }

    public function createCustomRole(string $title, int $displayOrder, int $actorUserId): \Warext\Clans\Entity\ClanRole
    {
        $title = trim($title);
        if ($title === '' || mb_strlen($title) > 75)
        {
            throw new \XF\PrintableException('Role title must be between 1 and 75 characters.');
        }

        $role = $this->em()->create('Warext\\Clans:ClanRole');
        $role->bulkSet([
            'clan_id' => $this->clan->clan_id,
            'title' => $title,
            'role_type' => 'custom',
            'display_order' => max(20, $displayOrder),
            'permissions' => [],
            'created_date' => \XF::$time
        ]);
        $role->save();

        $this->service('Warext\\Clans:Audit\\Logger')->log(
            $this->clan->clan_id,
            $actorUserId,
            'custom_role_created',
            ['title' => $role->title],
            'clan_role',
            $role->role_id
        );

        return $role;
    }

    public function updateCustomRole(\Warext\Clans\Entity\ClanRole $role, string $title, int $displayOrder, int $actorUserId): void
    {
        $this->assertCustomRole($role);
        $title = trim($title);
        if ($title === '' || mb_strlen($title) > 75)
        {
            throw new \XF\PrintableException('Role title must be between 1 and 75 characters.');
        }

        $role->title = $title;
        $role->display_order = max(20, $displayOrder);
        $role->save();

        $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $actorUserId, 'custom_role_updated', ['title' => $role->title], 'clan_role', $role->role_id);
    }

    public function deleteCustomRole(\Warext\Clans\Entity\ClanRole $role, int $actorUserId): void
    {
        $this->assertCustomRole($role);
        $memberRole = $this->getRoleByType('member');

        $members = $this->finder('Warext\\Clans:ClanMember')
            ->where('clan_id', $this->clan->clan_id)
            ->where('role_id', $role->role_id)
            ->fetch();

        foreach ($members as $member)
        {
            $member->role_id = $memberRole ? $memberRole->role_id : 0;
            $member->save();
        }

        $roleId = $role->role_id;
        $role->delete();
        $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $actorUserId, 'custom_role_deleted', ['role_id' => $roleId], 'clan_role', $roleId);
    }

    protected function assertCustomRole(\Warext\Clans\Entity\ClanRole $role): void
    {
        if ($role->clan_id !== $this->clan->clan_id || $role->role_type !== 'custom')
        {
            throw new \XF\PrintableException('Only custom roles may be changed here.');
        }
    }
}
