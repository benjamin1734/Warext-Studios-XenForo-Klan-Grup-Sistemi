<?php

namespace Warext\Clans\Service\Identity;

use XF\Service\AbstractService;

class Manager extends AbstractService
{
    protected \Warext\Clans\Entity\Clan $clan;

    public function __construct(\XF\App $app, \Warext\Clans\Entity\Clan $clan)
    {
        parent::__construct($app);
        $this->clan = $clan;
    }

    public function submit(array $input, \XF\Entity\User $owner): \Warext\Clans\Entity\ClanApplication
    {
        if ($this->clan->owner_user_id !== $owner->user_id || $this->clan->isManagementLocked())
        {
            throw new \XF\PrintableException('Only the active clan owner may request identity changes.');
        }
        if ($owner->user_state !== 'valid' || $owner->is_banned)
        {
            throw new \XF\PrintableException('Your account is not eligible to manage clan identity changes.');
        }

        $pending = $this->finder('Warext\\Clans:ClanApplication')
            ->where('application_type', 'change')
            ->where('clan_id', $this->clan->clan_id)
            ->where('status', 'pending')
            ->fetchOne();
        if ($pending)
        {
            throw new \XF\PrintableException('A clan identity change request is already pending.');
        }

        $title = trim((string)($input['title'] ?? $this->clan->title));
        $tag = strtoupper(trim((string)($input['tag'] ?? $this->clan->tag)));
        $banner = mb_substr(trim((string)($input['manager_banner'] ?? $this->clan->manager_banner)), 0, 100);
        $tagColor = $this->normalizeColor((string)($input['tag_color'] ?? $this->clan->tag_color));
        $bannerColor = $this->normalizeColor((string)($input['manager_banner_color'] ?? $this->clan->manager_banner_color));

        if (mb_strlen($title) < 3 || mb_strlen($title) > 100)
        {
            throw new \XF\PrintableException('Clan name must be between 3 and 100 characters.');
        }
        if (!preg_match('/^[A-Z0-9_-]{2,24}$/', $tag))
        {
            throw new \XF\PrintableException('Clan tag must be 2-24 characters and contain only letters, numbers, _ or -.');
        }
        if (!$this->repository('Warext\\Clans:Clan')->isTagAvailable($tag, $this->clan->clan_id))
        {
            throw new \XF\PrintableException('The requested clan tag is not available.');
        }
        if (!$tagColor || !$bannerColor)
        {
            throw new \XF\PrintableException('Tag and banner colors must be valid hexadecimal colors.');
        }

        $application = $this->em()->create('Warext\\Clans:ClanApplication');
        $application->bulkSet([
            'application_type' => 'change',
            'clan_id' => $this->clan->clan_id,
            'user_id' => $owner->user_id,
            'title' => $title,
            'tag' => $tag,
            'requested_manager_banner' => $banner,
            'requested_tag_color' => $tagColor,
            'requested_manager_banner_color' => $bannerColor,
            'status' => 'pending',
            'create_date' => \XF::$time
        ]);
        $application->save();

        $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $owner->user_id, 'identity_change_requested', ['application_id'=>$application->application_id], 'clan_application', $application->application_id);
        return $application;
    }

    public function approve(\Warext\Clans\Entity\ClanApplication $application, \XF\Entity\User $actor): void
    {
        if ($application->application_type !== 'change' || $application->clan_id !== $this->clan->clan_id || $application->status !== 'pending')
        {
            throw new \XF\PrintableException('This identity change request is no longer pending.');
        }
        if ($application->user_id !== $this->clan->owner_user_id)
        {
            throw new \XF\PrintableException('Clan ownership changed after this request was submitted. A new request is required.');
        }
        $currentOwner = $this->clan->Owner ?: $this->em()->find('XF:User', $this->clan->owner_user_id);
        if (!$currentOwner || $currentOwner->user_state !== 'valid' || $currentOwner->is_banned)
        {
            throw new \XF\PrintableException('The current clan owner account is not eligible for this identity change.');
        }
        if (!$this->repository('Warext\\Clans:Clan')->isTagAvailable($application->tag, $this->clan->clan_id, $application->application_id))
        {
            throw new \XF\PrintableException('The requested clan tag is no longer available.');
        }

        $db = $this->db();
        $db->beginTransaction();
        try
        {
            $this->clan->title = $application->title;
            $this->clan->tag = $application->tag;
            $this->clan->slug = \XF::app()->router()->prepareStringForUrl($application->title);
            $this->clan->manager_banner = $application->requested_manager_banner;
            $this->clan->tag_color = $application->requested_tag_color;
            $this->clan->manager_banner_color = $application->requested_manager_banner_color;
            $this->clan->save();

            $application->status = 'approved';
            $application->decision_user_id = $actor->user_id;
            $application->decision_date = \XF::$time;
            $application->decision_reason = '';
            $application->save();

            $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $actor->user_id, 'identity_change_approved', ['application_id'=>$application->application_id], 'clan_application', $application->application_id);
            $db->commit();
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }

        if ($application->User)
        {
            $this->service('Warext\\Clans:Notification')->alertUser($application->User, $this->clan, 'identity_change_approved', $actor);
        }
    }

    public function reject(\Warext\Clans\Entity\ClanApplication $application, \XF\Entity\User $actor, string $reason = ''): void
    {
        if ($application->application_type !== 'change' || $application->clan_id !== $this->clan->clan_id || $application->status !== 'pending')
        {
            throw new \XF\PrintableException('This identity change request is no longer pending.');
        }
        $application->status = 'rejected';
        $application->decision_user_id = $actor->user_id;
        $application->decision_date = \XF::$time;
        $application->decision_reason = trim($reason);
        $application->save();
        $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $actor->user_id, 'identity_change_rejected', ['application_id'=>$application->application_id,'reason'=>trim($reason)], 'clan_application', $application->application_id);
        if ($application->User)
        {
            $this->service('Warext\\Clans:Notification')->alertUser($application->User, $this->clan, 'identity_change_rejected', $actor, ['reason'=>trim($reason)]);
        }
    }

    protected function normalizeColor(string $color): string
    {
        $color = strtolower(trim($color));
        return preg_match('/^#[0-9a-f]{6}$/', $color) ? $color : '';
    }
}
