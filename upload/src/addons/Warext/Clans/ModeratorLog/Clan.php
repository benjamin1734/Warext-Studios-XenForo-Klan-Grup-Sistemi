<?php

namespace Warext\Clans\ModeratorLog;

use XF\Entity\ModeratorLog;
use XF\Mvc\Entity\Entity;
use XF\ModeratorLog\AbstractHandler;

class Clan extends AbstractHandler
{
    public function isLoggableUser(\XF\Entity\User $actor)
    {
        return (bool)($actor->user_id && ($actor->is_moderator || $actor->is_admin));
    }

    protected function getLogActionForChange(Entity $content, $field, $newValue, $oldValue)
    {
        if (in_array($field, ['status', 'title', 'tag', 'owner_user_id', 'manager_banner'], true))
        {
            return ['change_' . $field, ['old' => $oldValue, 'new' => $newValue]];
        }
        return null;
    }

    protected function setupLogEntityContent(ModeratorLog $log, Entity $content)
    {
        $log->content_user_id = $content->owner_user_id;
        $log->content_username = $content->Owner ? $content->Owner->username : '';
        $log->content_title = '[' . $content->tag . '] ' . $content->title;
        $log->content_url = \XF::app()->router('public')->buildLink('canonical:clans', $content);
        $log->discussion_content_type = '';
        $log->discussion_content_id = 0;
    }

    public function getEntityWith()
    {
        return ['Owner'];
    }
}
