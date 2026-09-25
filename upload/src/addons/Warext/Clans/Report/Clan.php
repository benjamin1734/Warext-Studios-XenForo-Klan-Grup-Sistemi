<?php

namespace Warext\Clans\Report;

use XF\Entity\Report;
use XF\Mvc\Entity\Entity;
use XF\Report\AbstractHandler;

class Clan extends AbstractHandler
{
    protected function canViewContent(Report $report)
    {
        return true;
    }

    protected function canActionContent(Report $report)
    {
        $visitor = \XF::visitor();
        return (bool)($visitor->is_admin || $visitor->hasPermission('wxClans', 'moderate'));
    }

    public function setupReportEntityContent(Report $report, Entity $content)
    {
        $report->content_user_id = $content->owner_user_id;
        $report->content_info = [
            'title' => $content->title,
            'tag' => $content->tag,
            'description' => $content->description,
            'owner_user_id' => $content->owner_user_id,
            'owner_username' => $content->Owner ? $content->Owner->username : '',
            'clan_id' => $content->clan_id
        ];
    }

    public function getContentTitle(Report $report)
    {
        return '[' . ($report->content_info['tag'] ?? '') . '] ' . ($report->content_info['title'] ?? 'Clan');
    }

    public function getContentMessage(Report $report)
    {
        return (string)($report->content_info['description'] ?? '');
    }

    public function getContentLink(Report $report)
    {
        $clan = $this->getContent($report->content_id);
        return $clan ? \XF::app()->router('public')->buildLink('canonical:clans', $clan) : '';
    }

    public function getEntityWith()
    {
        return ['Owner'];
    }
}
