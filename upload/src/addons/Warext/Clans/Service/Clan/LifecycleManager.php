<?php

namespace Warext\Clans\Service\Clan;

use XF\Service\AbstractService;

class LifecycleManager extends AbstractService
{
    protected \Warext\Clans\Entity\Clan $clan;

    public function __construct(\XF\App $app, \Warext\Clans\Entity\Clan $clan)
    {
        parent::__construct($app);
        $this->clan = $clan;
    }

    public function request(string $type, \XF\Entity\User $actor, string $reason = ''): \Warext\Clans\Entity\ClanApplication
    {
        if (!$actor->user_id || $actor->user_id !== $this->clan->owner_user_id || $actor->user_state !== 'valid' || $actor->is_banned)
        {
            throw new \XF\PrintableException('Only the current clan owner can submit this request.');
        }

        if (!in_array($type, ['close', 'reopen'], true))
        {
            throw new \InvalidArgumentException('Invalid clan lifecycle request type.');
        }

        if ($type === 'close' && !in_array($this->clan->status, ['active', 'restricted'], true))
        {
            throw new \XF\PrintableException('This clan cannot be closed from its current state.');
        }
        if ($type === 'reopen' && $this->clan->status !== 'closed')
        {
            throw new \XF\PrintableException('Only a closed clan can be reopened through this request.');
        }

        $existing = $this->finder('Warext\\Clans:ClanApplication')
            ->where('clan_id', $this->clan->clan_id)
            ->where('application_type', ['close', 'reopen'])
            ->where('status', 'pending')
            ->fetchOne();
        if ($existing)
        {
            throw new \XF\PrintableException('A clan lifecycle request is already pending.');
        }

        $request = $this->em()->create('Warext\\Clans:ClanApplication');
        $request->bulkSet([
            'application_type' => $type,
            'clan_id' => $this->clan->clan_id,
            'user_id' => $actor->user_id,
            'title' => $this->clan->title,
            'tag' => $this->clan->tag,
            'description' => mb_substr(trim($reason), 0, 5000),
            'status' => 'pending',
            'create_date' => \XF::$time
        ]);
        $request->save();

        $this->service('Warext\\Clans:Audit\\Logger')->log(
            $this->clan->clan_id,
            $actor->user_id,
            'lifecycle_request_submitted',
            ['type' => $type, 'reason' => trim($reason)],
            'clan_application',
            $request->application_id
        );

        return $request;
    }

    public function cancel(\Warext\Clans\Entity\ClanApplication $request, \XF\Entity\User $actor): void
    {
        if ($request->clan_id !== $this->clan->clan_id || !$request->isLifecycleRequest() || $request->status !== 'pending' || $actor->user_id !== $this->clan->owner_user_id)
        {
            throw new \XF\PrintableException('This request cannot be cancelled.');
        }

        $request->status = 'cancelled';
        $request->decision_date = \XF::$time;
        $request->save();
        $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $actor->user_id, 'lifecycle_request_cancelled', ['type' => $request->application_type], 'clan_application', $request->application_id);
    }

    public function decide(\Warext\Clans\Entity\ClanApplication $request, bool $approve, \XF\Entity\User $actor, string $reason = ''): void
    {
        if (!$request->isLifecycleRequest() || $request->clan_id !== $this->clan->clan_id || $request->status !== 'pending')
        {
            throw new \XF\PrintableException('This lifecycle request is no longer pending.');
        }

        if ($request->user_id !== $this->clan->owner_user_id)
        {
            throw new \XF\PrintableException('The clan owner changed after this request was submitted.');
        }

        if ($approve)
        {
            if ($request->application_type === 'close' && !in_array($this->clan->status, ['active', 'restricted'], true))
            {
                throw new \XF\PrintableException('The clan is no longer in a state that can be closed.');
            }
            if ($request->application_type === 'reopen' && $this->clan->status !== 'closed')
            {
                throw new \XF\PrintableException('The clan is no longer closed.');
            }
        }

        $oldStatus = $this->clan->status;
        $db = $this->db();
        $db->beginTransaction();
        try
        {
            if ($approve)
            {
                $this->clan->status = $request->application_type === 'close' ? 'closed' : 'active';
                $this->clan->save();
            }

            $request->status = $approve ? 'approved' : 'rejected';
            $request->decision_date = \XF::$time;
            $request->decision_user_id = $actor->user_id;
            $request->decision_reason = mb_substr(trim($reason), 0, 5000);
            $request->save();

            $this->service('Warext\\Clans:Audit\\Logger')->log(
                $this->clan->clan_id,
                $actor->user_id,
                $approve ? 'lifecycle_request_approved' : 'lifecycle_request_rejected',
                ['type' => $request->application_type, 'reason' => trim($reason), 'old_status' => $oldStatus, 'new_status' => $this->clan->status],
                'clan_application',
                $request->application_id
            );
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
                $approve ? 'lifecycle_approved' : 'lifecycle_rejected',
                ['type' => $request->application_type, 'reason' => trim($reason)],
                false,
                $actor
            );
        }

        if ($this->clan->Owner)
        {
            $this->service('Warext\\Clans:Notification')->alertUser(
                $this->clan->Owner,
                $this->clan,
                $approve ? 'lifecycle_approved' : 'lifecycle_rejected',
                $actor,
                ['type' => $request->application_type, 'reason' => trim($reason)]
            );
        }
    }
}
