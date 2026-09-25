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


    public function isTitleAvailable(string $title, int $ignoreClanId = 0, int $ignoreApplicationId = 0): bool
    {
        $title = trim($title);
        if ($title === '')
        {
            return false;
        }

        $clanFinder = $this->finder('Warext\\Clans:Clan')->where('title', $title);
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
            ->where('title', $title);
        if ($ignoreApplicationId)
        {
            $applicationFinder->where('application_id', '<>', $ignoreApplicationId);
        }

        return !$applicationFinder->fetchOne();
    }

    public function isTagReserved(string $tag): bool
    {
        $tag = strtoupper(trim($tag));
        return in_array($tag, $this->getReservedValues('wxClansReservedTags', true), true);
    }

    public function isTitleReserved(string $title): bool
    {
        $title = mb_strtolower(trim($title));
        return in_array($title, $this->getReservedValues('wxClansReservedNames', false), true);
    }

    protected function getReservedValues(string $optionId, bool $uppercase): array
    {
        $raw = (string)($this->app()->options()->$optionId ?? '');
        if ($raw === '')
        {
            return [];
        }

        $values = preg_split('/[\r\n,;]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $normalized = [];
        foreach ($values as $value)
        {
            $value = trim($value);
            if ($value === '')
            {
                continue;
            }
            $normalized[] = $uppercase ? strtoupper($value) : mb_strtolower($value);
        }

        return array_values(array_unique($normalized));
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
