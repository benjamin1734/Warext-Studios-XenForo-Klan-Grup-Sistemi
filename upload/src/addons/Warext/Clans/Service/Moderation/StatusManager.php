<?php

namespace Warext\Clans\Service\Moderation;

use XF\Service\AbstractService;

class StatusManager extends AbstractService
{
    protected \Warext\Clans\Entity\Clan $clan;

    public function __construct(\XF\App $app, \Warext\Clans\Entity\Clan $clan)
    {
        parent::__construct($app);
        $this->clan = $clan;
    }

    public function change(string $status, \XF\Entity\User $actor, string $reason = ''): void
    {
        if (!in_array($status, ['active', 'restricted', 'suspended', 'closed'], true))
        {
            throw new \XF\PrintableException('Invalid clan status.');
        }

        $reason = mb_substr(trim($reason), 0, 5000);
        $oldStatus = $this->clan->status;
        if ($oldStatus === $status && $reason === '')
        {
            return;
        }

        $this->clan->status = $status;
        $this->clan->save();

        $this->service('Warext\\Clans:Audit\\Logger')->log(
            $this->clan->clan_id,
            $actor->user_id,
            'forum_status_changed',
            ['old' => $oldStatus, 'new' => $status, 'reason' => $reason],
            'clan',
            $this->clan->clan_id
        );

        if ($actor->user_id && ($actor->is_moderator || $actor->is_admin))
        {
            \XF::app()->logger()->moderatorLogger()->log(
                'wx_clan',
                $this->clan,
                'status_update',
                ['old' => $oldStatus, 'new' => $status, 'reason' => $reason],
                false,
                $actor
            );
        }

        if ($this->clan->Owner)
        {
            $this->service('Warext\\Clans:Notification')->alertUser(
                $this->clan->Owner,
                $this->clan,
                'moderation_status_changed',
                $actor,
                ['old' => $oldStatus, 'new' => $status, 'reason' => $reason]
            );
        }
    }
}
