<?php

namespace Warext\Clans\Service\Clan;

use XF\Service\AbstractService;

class AnnouncementManager extends AbstractService
{
    protected \Warext\Clans\Entity\Clan $clan;

    public function __construct(\XF\App $app, \Warext\Clans\Entity\Clan $clan)
    {
        parent::__construct($app);
        $this->clan = $clan;
    }

    public function save(?\Warext\Clans\Entity\ClanAnnouncement $announcement, array $input, \XF\Entity\User $actor): \Warext\Clans\Entity\ClanAnnouncement
    {
        if (!$announcement)
        {
            $maxAnnouncements = (int)($this->app->options()->wxClansMaxAnnouncements ?? 100);
            if ($maxAnnouncements > 0)
            {
                $announcementCount = $this->finder('Warext\\Clans:ClanAnnouncement')
                    ->where('clan_id', $this->clan->clan_id)
                    ->total();
                if ($announcementCount >= $maxAnnouncements)
                {
                    throw new \XF\PrintableException('This clan has reached the maximum number of announcements.');
                }
            }

            $announcement = $this->em()->create('Warext\\Clans:ClanAnnouncement');
            $announcement->clan_id = $this->clan->clan_id;
            $announcement->user_id = $actor->user_id;
            $announcement->create_date = \XF::$time;
        }
        elseif ($announcement->clan_id !== $this->clan->clan_id)
        {
            throw new \LogicException('Announcement does not belong to this clan.');
        }

        $title = trim((string)($input['title'] ?? ''));
        $message = trim((string)($input['message'] ?? ''));
        if ($title === '' || $message === '')
        {
            throw new \XF\PrintableException('Announcement title and message are required.');
        }
        if (mb_strlen($title) > 120)
        {
            throw new \XF\PrintableException('Announcement title may not exceed 120 characters.');
        }
        if (mb_strlen($message) > 20000)
        {
            throw new \XF\PrintableException('Announcement message may not exceed 20,000 characters.');
        }

        $announcement->title = $title;
        $announcement->message = $message;
        $announcement->is_pinned = !empty($input['is_pinned']);
        $announcement->update_date = \XF::$time;
        $announcement->save();

        $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $actor->user_id, 'announcement_saved', ['announcement_id'=>$announcement->announcement_id], 'clan_announcement', $announcement->announcement_id);
        return $announcement;
    }

    public function delete(\Warext\Clans\Entity\ClanAnnouncement $announcement, int $actorUserId): void
    {
        if ($announcement->clan_id !== $this->clan->clan_id)
        {
            throw new \LogicException('Announcement does not belong to this clan.');
        }
        $id = $announcement->announcement_id;
        $announcement->delete();
        $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $actorUserId, 'announcement_deleted', ['announcement_id'=>$id], 'clan_announcement', $id);
    }
}
