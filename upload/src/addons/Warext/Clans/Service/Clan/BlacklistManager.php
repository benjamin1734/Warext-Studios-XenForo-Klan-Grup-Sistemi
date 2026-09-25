<?php

namespace Warext\Clans\Service\Clan;

use XF\Service\AbstractService;

class BlacklistManager extends AbstractService
{
    protected \Warext\Clans\Entity\Clan $clan;

    public function __construct(\XF\App $app, \Warext\Clans\Entity\Clan $clan)
    {
        parent::__construct($app);
        $this->clan = $clan;
    }

    public function add(\XF\Entity\User $user, \XF\Entity\User $actor, string $reason = '', int $days = 0): \Warext\Clans\Entity\ClanBlacklist
    {
        if ($user->user_id === $actor->user_id)
        {
            throw new \XF\PrintableException('You cannot blacklist yourself.');
        }
        if ($user->user_id === $this->clan->owner_user_id)
        {
            throw new \XF\PrintableException('The clan owner cannot be blacklisted.');
        }

        $targetMember = $this->em()->find('Warext\\Clans:ClanMember', [$this->clan->clan_id, $user->user_id]);
        if ($targetMember && $targetMember->is_manager && $actor->user_id !== $this->clan->owner_user_id)
        {
            throw new \XF\PrintableException('Only the clan owner may blacklist a clan manager.');
        }

        $entry = $this->finder('Warext\\Clans:ClanBlacklist')
            ->where('clan_id', $this->clan->clan_id)
            ->where('user_id', $user->user_id)
            ->fetchOne();
        if (!$entry)
        {
            $entry = $this->em()->create('Warext\\Clans:ClanBlacklist');
            $entry->clan_id = $this->clan->clan_id;
            $entry->user_id = $user->user_id;
        }

        $db = $this->db();
        $db->beginTransaction();
        try
        {
            $entry->added_by = $actor->user_id;
            $entry->reason = mb_substr(trim($reason), 0, 255);
            $entry->expiry_date = $days > 0 ? \XF::$time + ($days * 86400) : 0;
            $entry->create_date = \XF::$time;
            $entry->save();

            $member = $this->em()->find('Warext\\Clans:ClanMember', [$this->clan->clan_id, $user->user_id]);
            if ($member && !$member->is_owner)
            {
                $this->service('Warext\\Clans:Clan\\MemberManager', $this->clan)->removeMember($member, $actor->user_id, 'Clan blacklist');
            }

            $db->update('xf_wx_clan_application', ['status'=>'rejected','decision_date'=>\XF::$time,'decision_user_id'=>$actor->user_id,'decision_reason'=>'Clan blacklist'], 'application_type = ? AND clan_id = ? AND user_id = ? AND status = ?', ['join',$this->clan->clan_id,$user->user_id,'pending']);
            $db->update('xf_wx_clan_invitation', ['status'=>'cancelled'], 'clan_id = ? AND user_id = ? AND status = ?', [$this->clan->clan_id,$user->user_id,'pending']);
            $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $actor->user_id, 'blacklist_added', ['user_id'=>$user->user_id,'reason'=>$entry->reason,'expiry_date'=>$entry->expiry_date], 'user', $user->user_id);
            $db->commit();
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }

        return $entry;
    }

    public function remove(\Warext\Clans\Entity\ClanBlacklist $entry, int $actorUserId): void
    {
        if ($entry->clan_id !== $this->clan->clan_id)
        {
            throw new \LogicException('Blacklist entry does not belong to this clan.');
        }
        $userId = $entry->user_id;
        $entry->delete();
        $this->service('Warext\\Clans:Audit\\Logger')->log($this->clan->clan_id, $actorUserId, 'blacklist_removed', ['user_id'=>$userId], 'user', $userId);
    }
}
