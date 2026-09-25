<?php

namespace Warext\Clans\Service\Application;

use Warext\Clans\Permission\ClanPermission;
use XF\Service\AbstractService;

class Decision extends AbstractService
{
    protected \Warext\Clans\Entity\ClanApplication $application;

    public function __construct(\XF\App $app, \Warext\Clans\Entity\ClanApplication $application)
    {
        parent::__construct($app);
        $this->application = $application;
    }

    public function approve(\XF\Entity\User $actor): \Warext\Clans\Entity\Clan
    {
        if ($this->application->status !== 'pending' || $this->application->application_type !== 'create')
        {
            throw new \LogicException('Application is not pending.');
        }
        $clanRepo = $this->repository('Warext\\Clans:Clan');
        if ($clanRepo->isTagReserved($this->application->tag) || $clanRepo->isTitleReserved($this->application->title))
        {
            throw new \XF\PrintableException('The requested clan identity is reserved by forum management.');
        }
        if (!$clanRepo->isTagAvailable($this->application->tag, 0, $this->application->application_id))
        {
            throw new \XF\PrintableException('The requested clan tag is no longer available.');
        }
        if (!$clanRepo->isTitleAvailable($this->application->title, 0, $this->application->application_id))
        {
            throw new \XF\PrintableException('The requested clan name is no longer available.');
        }

        $owner = $this->application->User ?: $this->em()->find('XF:User', $this->application->user_id);
        if (!$owner || $owner->user_state !== 'valid' || $owner->is_banned)
        {
            throw new \XF\PrintableException('The applicant account is no longer eligible to own a clan.');
        }
        $maxOwned = (int)($this->app->options()->wxClansMaxOwned ?? 0);
        if ($maxOwned > 0 && $this->repository('Warext\\Clans:Clan')->countOwnedClans($owner->user_id) >= $maxOwned)
        {
            throw new \XF\PrintableException('The applicant has reached the maximum number of clans they may own.');
        }

        $db = $this->db();
        $db->beginTransaction();
        try
        {
            $clan = $this->em()->create('Warext\\Clans:Clan');
            $clan->bulkSet([
                'owner_user_id' => $this->application->user_id,
                'title' => $this->application->title,
                'tag' => $this->application->tag,
                'slug' => \XF::app()->router()->prepareStringForUrl($this->application->title),
                'description' => $this->application->description,
                'category' => $this->application->category,
                'manager_banner' => $this->application->requested_manager_banner,
                'tag_color' => $this->application->requested_tag_color,
                'manager_banner_color' => $this->application->requested_manager_banner_color,
                'status' => 'active',
                'join_mode' => 'application',
                'member_count' => 1,
                'created_date' => \XF::$time,
                'approved_date' => \XF::$time,
                'approved_by' => $actor->user_id
            ]);
            $clan->save();

            $roles = [
                ['Owner', 'owner', 1, ['manage_members'=>true,'manage_applications'=>true,'manage_settings'=>true,'manage_announcements'=>true,'view_audit_log'=>true,'manage_roles'=>true,'manage_managers'=>true,'transfer_ownership'=>true]],
                ['Manager', 'manager', 10, ClanPermission::defaultManagerPermissions()],
                ['Member', 'member', 100, []]
            ];
            $ownerRole = null;
            foreach ($roles as [$title, $type, $order, $permissions])
            {
                $role = $this->em()->create('Warext\\Clans:ClanRole');
                $role->bulkSet(['clan_id'=>$clan->clan_id,'title'=>$title,'role_type'=>$type,'display_order'=>$order,'permissions'=>$permissions,'created_date'=>\XF::$time]);
                $role->save();
                if ($type === 'owner')
                {
                    $ownerRole = $role;
                }
            }

            $member = $this->em()->create('Warext\\Clans:ClanMember');
            $member->bulkSet([
                'clan_id' => $clan->clan_id,
                'user_id' => $this->application->user_id,
                'role_id' => $ownerRole ? $ownerRole->role_id : 0,
                'member_state' => 'active',
                'is_owner' => true,
                'is_manager' => true,
                'join_date' => \XF::$time,
                'last_activity' => \XF::$time
            ]);
            $member->save();

            $this->application->bulkSet(['status'=>'approved','decision_date'=>\XF::$time,'decision_user_id'=>$actor->user_id,'created_clan_id'=>$clan->clan_id,'decision_reason'=>'']);
            $this->application->save();

            $this->service('Warext\\Clans:Audit\\Logger')->log($clan->clan_id, $actor->user_id, 'clan_created_from_application', ['application_id'=>$this->application->application_id], 'clan_application', $this->application->application_id);

            $db->commit();
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }

        if ($owner)
        {
            $this->service('Warext\\Clans:Notification')->alertUser($owner, $clan, 'clan_approved', $actor);
        }

        return $clan;
    }

    public function reject(\XF\Entity\User $actor, string $reason = ''): void
    {
        $this->decideStatus('rejected', $actor, $reason);
    }

    public function requestChanges(\XF\Entity\User $actor, string $reason = ''): void
    {
        $this->decideStatus('changes_requested', $actor, $reason);
    }

    protected function decideStatus(string $status, \XF\Entity\User $actor, string $reason): void
    {
        if ($this->application->status !== 'pending')
        {
            throw new \LogicException('Application is not pending.');
        }
        $this->application->bulkSet(['status'=>$status,'decision_date'=>\XF::$time,'decision_user_id'=>$actor->user_id,'decision_reason'=>trim($reason)]);
        $this->application->save();
    }
}
