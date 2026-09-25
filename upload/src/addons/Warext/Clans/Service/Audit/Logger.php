<?php
namespace Warext\Clans\Service\Audit;

use XF\Service\AbstractService;

class Logger extends AbstractService
{
    public function log(int $clanId, int $userId, string $action, array $details = [], string $contentType = '', int $contentId = 0): void
    {
        $log = $this->em()->create('Warext\\Clans:ClanAuditLog');
        $log->bulkSet([
            'clan_id' => $clanId,
            'user_id' => $userId,
            'action' => $action,
            'content_type' => $contentType,
            'content_id' => $contentId,
            'details' => $details,
            'log_date' => \XF::$time
        ]);
        $log->save();
    }
}
