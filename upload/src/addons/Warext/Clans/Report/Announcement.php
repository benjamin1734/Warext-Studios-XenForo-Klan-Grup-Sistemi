<?php

namespace Warext\Clans\Report;

use XF\Entity\Report;
use XF\Mvc\Entity\Entity;
use XF\Report\AbstractHandler;

class Announcement extends AbstractHandler
{
    protected function canViewContent(Report $report)
    {
        $content = $report->Content;
        return $content instanceof \Warext\Clans\Entity\ClanAnnouncement
            ? $content->canView()
            : true;
    }

    protected function canActionContent(Report $report)
    {
        $visitor = \XF::visitor();
        return (bool)($visitor->is_admin || $visitor->hasPermission('wxClans', 'moderate'));
    }

    public function setupReportEntityContent(Report $report, Entity $content)
    {
        $report->content_user_id = $content->user_id;
        $report->content_info = [
            'announcement_id' => $content->announcement_id,
            'clan_id' => $content->clan_id,
            'clan_title' => $content->Clan ? $content->Clan->title : '',
            'clan_tag' => $content->Clan ? $content->Clan->tag : '',
            'title' => $content->title,
            'message' => $content->message,
            'user_id' => $content->user_id,
            'username' => $content->User ? $content->User->username : ''
        ];
    }

    public function getContentTitle(Report $report)
    {
        $clanTag = (string)($report->content_info['clan_tag'] ?? '');
        $title = (string)($report->content_info['title'] ?? 'Clan announcement');
        return ($clanTag !== '' ? '[' . $clanTag . '] ' : '') . $title;
    }

    public function getContentMessage(Report $report)
    {
        return (string)($report->content_info['message'] ?? '');
    }

    public function getContentLink(Report $report)
    {
        $announcement = $this->getContent($report->content_id);
        if (!$announcement || !$announcement->Clan)
        {
            return '';
        }

        return \XF::app()->router('public')->buildLink('canonical:clans', $announcement->Clan)
            . '#announcement-' . $announcement->announcement_id;
    }

    public function getEntityWith()
    {
        return ['Clan', 'User'];
    }
}
