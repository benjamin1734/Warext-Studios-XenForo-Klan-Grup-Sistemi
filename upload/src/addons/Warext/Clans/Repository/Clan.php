<?php

namespace Warext\Clans\Repository;

use XF\Mvc\Entity\Repository;

class Clan extends Repository
{
    public function findClansForList()
    {
        return $this->finder('Warext\\Clans:Clan')
            ->where('status', ['active', 'restricted'])
            ->with('Owner');
    }

    public function applyListFilters($finder, string $query = '', string $category = '', string $joinMode = '', string $sort = 'newest')
    {
        $query = trim($query);
        $category = trim($category);
        $joinMode = trim($joinMode);

        if ($query !== '')
        {
            $like = '%' . $finder->escapeLike($query) . '%';
            $finder->whereOr(
                ['title', 'LIKE', $like],
                ['tag', 'LIKE', $like]
            );
        }

        if ($category !== '')
        {
            $finder->where('category', $category);
        }

        if ($joinMode !== '' && in_array($joinMode, ['open', 'application', 'invite', 'closed'], true))
        {
            $finder->where('join_mode', $joinMode);
        }

        switch ($sort)
        {
            case 'members':
                $finder->order('member_count', 'DESC')->order('created_date', 'DESC');
                break;
            case 'name':
                $finder->order('title', 'ASC');
                break;
            default:
                $finder->order('created_date', 'DESC');
        }

        return $finder;
    }

    public function findByTag(string $tag)
    {
        return $this->finder('Warext\\Clans:Clan')
            ->where('tag', strtoupper(trim($tag)))
            ->fetchOne();
    }

    public function isTagAvailable(string $tag, int $ignoreClanId = 0, int $ignoreApplicationId = 0): bool
    {
        $tag = strtoupper(trim($tag));
        if ($tag === '')
        {
            return false;
        }

        $clanFinder = $this->finder('Warext\\Clans:Clan')->where('tag', $tag);
        if ($ignoreClanId)
        {
            $clanFinder->where('clan_id', '<>', $ignoreClanId);
        }
        if ($clanFinder->fetchOne())
        {
            return false;
        }

        $applicationFinder = $this->finder('Warext\\Clans:ClanApplication')
            ->where('application_type', ['create', 'change'])
            ->where('status', ['pending', 'changes_requested'])
            ->where('tag', $tag);
        if ($ignoreApplicationId)
        {
            $applicationFinder->where('application_id', '<>', $ignoreApplicationId);
        }

        return !$applicationFinder->fetchOne();
    }

    public function countActiveMemberships(int $userId): int
    {
        return $this->finder('Warext\\Clans:ClanMember')
            ->where('user_id', $userId)
            ->where('member_state', 'active')
            ->total();
    }

    public function countOwnedClans(int $userId): int
    {
        return $this->finder('Warext\\Clans:Clan')
            ->where('owner_user_id', $userId)
            ->where('status', '<>', 'closed')
            ->total();
    }

    public function isUserBlacklisted(int $clanId, int $userId): bool
    {
        $entry = $this->finder('Warext\\Clans:ClanBlacklist')
            ->where('clan_id', $clanId)
            ->where('user_id', $userId)
            ->fetchOne();

        return (bool)($entry && $entry->isActive());
    }
}
