<?php

namespace Warext\Clans\Service\Admin;

use Warext\Clans\Util\Presentation;
use XF\Service\AbstractService;

class ClanManager extends AbstractService
{
    protected \Warext\Clans\Entity\Clan $clan;

    public function __construct(\XF\App $app, \Warext\Clans\Entity\Clan $clan)
    {
        parent::__construct($app);
        $this->clan = $clan;
    }

    public function update(array $input, \XF\Entity\User $actor): void
    {
        $title = trim((string)($input['title'] ?? ''));
        $tag = strtoupper(trim((string)($input['tag'] ?? '')));
        $description = trim((string)($input['description'] ?? ''));
        $rules = trim((string)($input['rules'] ?? ''));
        $category = trim((string)($input['category'] ?? ''));
        $joinMode = trim((string)($input['join_mode'] ?? 'application'));
        $memberVisibility = trim((string)($input['member_list_visibility'] ?? 'public'));
        $announcementVisibility = trim((string)($input['announcement_visibility'] ?? 'public'));
        $managerBanner = trim((string)($input['manager_banner'] ?? ''));
        $tagColor = $this->normalizeColor((string)($input['tag_color'] ?? '#4f46e5'));
        $managerBannerColor = $this->normalizeColor((string)($input['manager_banner_color'] ?? '#805ad5'));
        $tagIconInput = trim((string)($input['tag_icon'] ?? ''));
        $managerIconInput = trim((string)($input['manager_banner_icon'] ?? ''));
        $tagIcon = Presentation::normalizeIcon($tagIconInput);
        $managerIcon = Presentation::normalizeIcon($managerIconInput);
        $logoUrl = trim((string)($input['logo_url'] ?? ''));
        $coverUrl = trim((string)($input['cover_url'] ?? ''));

        if (mb_strlen($title) < 3 || mb_strlen($title) > 100)
        {
            throw new \XF\PrintableException('Clan name must be between 3 and 100 characters.');
        }
        if (!preg_match('/^[A-Z0-9_-]{2,24}$/', $tag))
        {
            throw new \XF\PrintableException('Clan tag must be 2-24 characters and contain only letters, numbers, _ or -.');
        }
        if (mb_strlen($description) > 20000 || mb_strlen($rules) > 20000)
        {
            throw new \XF\PrintableException('Clan description and rules may not exceed 20,000 characters.');
        }
        if (mb_strlen($category) > 50 || mb_strlen($managerBanner) > 100)
        {
            throw new \XF\PrintableException('Clan category or manager banner is too long.');
        }
        if (!in_array($joinMode, ['open', 'application', 'invite', 'closed'], true))
        {
            throw new \XF\PrintableException('Invalid clan join mode.');
        }
        if (!in_array($memberVisibility, ['public', 'members', 'staff'], true))
        {
            throw new \XF\PrintableException('Invalid member list visibility.');
        }
        if (!in_array($announcementVisibility, ['public', 'members'], true))
        {
            throw new \XF\PrintableException('Invalid announcement visibility.');
        }
        if (!$tagColor || !$managerBannerColor)
        {
            throw new \XF\PrintableException('Tag and manager banner colors must be valid hexadecimal colors.');
        }
        if ($tagIconInput !== '' && !$tagIcon)
        {
            throw new \XF\PrintableException('Invalid clan tag icon.');
        }
        if ($managerIconInput !== '' && !$managerIcon)
        {
            throw new \XF\PrintableException('Invalid manager banner icon.');
        }
        foreach ([$logoUrl, $coverUrl] as $url)
        {
            if ($url !== '' && (!$this->isValidHttpUrl($url) || mb_strlen($url) > 255))
            {
                throw new \XF\PrintableException('Logo and cover URLs must use http or https and be 255 characters or less.');
            }
        }

        $clanRepo = $this->repository('Warext\\Clans:Clan');
        if ($tag !== strtoupper((string)$this->clan->tag) && !$clanRepo->isTagAvailable($tag, $this->clan->clan_id))
        {
            throw new \XF\PrintableException('The requested clan tag is already in use or awaiting approval.');
        }
        if ($title !== (string)$this->clan->title && !$clanRepo->isTitleAvailable($title, $this->clan->clan_id))
        {
            throw new \XF\PrintableException('The requested clan name is already in use or awaiting approval.');
        }

        $before = [
            'title' => (string)$this->clan->title,
            'tag' => (string)$this->clan->tag,
            'description' => (string)$this->clan->description,
            'rules' => (string)$this->clan->rules,
            'category' => (string)$this->clan->category,
            'join_mode' => (string)$this->clan->join_mode,
            'member_list_visibility' => (string)$this->clan->member_list_visibility,
            'announcement_visibility' => (string)$this->clan->announcement_visibility,
            'manager_banner' => (string)$this->clan->manager_banner,
            'tag_icon' => (string)$this->clan->tag_icon,
            'manager_banner_icon' => (string)$this->clan->manager_banner_icon,
            'tag_color' => (string)$this->clan->tag_color,
            'manager_banner_color' => (string)$this->clan->manager_banner_color,
            'logo_url' => (string)$this->clan->logo_url,
            'cover_url' => (string)$this->clan->cover_url
        ];

        $this->clan->bulkSet([
            'title' => $title,
            'tag' => $tag,
            'slug' => \XF::app()->router()->prepareStringForUrl($title),
            'description' => $description,
            'rules' => $rules,
            'category' => $category,
            'join_mode' => $joinMode,
            'member_list_visibility' => $memberVisibility,
            'announcement_visibility' => $announcementVisibility,
            'manager_banner' => mb_substr($managerBanner, 0, 100),
            'tag_icon' => $tagIcon,
            'manager_banner_icon' => $managerIcon,
            'tag_color' => $tagColor,
            'manager_banner_color' => $managerBannerColor,
            'logo_url' => $logoUrl,
            'cover_url' => $coverUrl
        ]);
        $this->clan->save();

        $changes = [];
        foreach ($before as $key => $oldValue)
        {
            $newValue = (string)$this->clan->$key;
            if ($oldValue !== $newValue)
            {
                $changes[$key] = ['old' => $oldValue, 'new' => $newValue];
            }
        }

        if ($changes)
        {
            $this->service('Warext\\Clans:Audit\\Logger')->log(
                $this->clan->clan_id,
                $actor->user_id,
                'forum_admin_details_updated',
                ['changes' => $changes],
                'clan',
                $this->clan->clan_id
            );

            if ($actor->user_id && ($actor->is_moderator || $actor->is_admin))
            {
                \XF::app()->logger()->moderatorLogger()->log(
                    'wx_clan',
                    $this->clan,
                    'admin_edit',
                    ['changes' => $changes],
                    false,
                    $actor
                );
            }
        }
    }

    public function delete(\XF\Entity\User $actor, string $reason): void
    {
        $reason = mb_substr(trim($reason), 0, 5000);
        if ($reason === '')
        {
            throw new \XF\PrintableException('A reason is required to permanently delete a clan.');
        }

        $clanId = (int)$this->clan->clan_id;
        $snapshot = [
            'clan_id' => $clanId,
            'title' => (string)$this->clan->title,
            'tag' => (string)$this->clan->tag,
            'owner_user_id' => (int)$this->clan->owner_user_id,
            'reason' => $reason
        ];

        $db = $this->db();
        $db->beginTransaction();
        try
        {
            $applicationIds = $db->fetchAllColumn(
                'SELECT application_id FROM xf_wx_clan_application WHERE clan_id = ? OR created_clan_id = ?',
                [$clanId, $clanId]
            );
            if ($applicationIds)
            {
                $ids = implode(',', array_map('intval', $applicationIds));
                $db->query('DELETE FROM xf_wx_clan_application_answer WHERE application_id IN (' . $ids . ')');
            }

            $db->delete('xf_wx_clan_user_pref', 'active_clan_id = ?', $clanId);
            $db->delete('xf_wx_clan_announcement', 'clan_id = ?', $clanId);
            $db->delete('xf_wx_clan_blacklist', 'clan_id = ?', $clanId);
            $db->delete('xf_wx_clan_ownership_transfer', 'clan_id = ?', $clanId);
            $db->delete('xf_wx_clan_invitation', 'clan_id = ?', $clanId);
            $db->delete('xf_wx_clan_application_field', 'clan_id = ?', $clanId);
            $db->query('DELETE FROM xf_wx_clan_application WHERE clan_id = ? OR created_clan_id = ?', [$clanId, $clanId]);
            $db->delete('xf_wx_clan_member', 'clan_id = ?', $clanId);
            $db->delete('xf_wx_clan_role', 'clan_id = ?', $clanId);
            $db->delete('xf_wx_clan_audit_log', 'clan_id = ?', $clanId);
            $this->clan->delete();

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
                'delete',
                $snapshot,
                false,
                $actor
            );
        }
    }

    protected function normalizeColor(string $color): string
    {
        $color = strtolower(trim($color));
        return preg_match('/^#[0-9a-f]{6}$/', $color) ? $color : '';
    }

    protected function isValidHttpUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL))
        {
            return false;
        }

        return in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}
