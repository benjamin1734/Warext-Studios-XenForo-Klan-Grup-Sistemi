<?php

namespace Warext\Clans\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Clan extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission('wxClansManage');
    }

    public function actionIndex()
    {
        $page = $this->filterPage();
        $perPage = 50;
        $status = trim($this->filter('status','str'));
        $query = trim($this->filter('q','str'));
        $category = trim($this->filter('category','str'));

        $finder = $this->finder('Warext\\Clans:Clan')->with('Owner')->order('created_date','DESC');
        if ($status !== '')
        {
            $finder->where('status', $status);
        }
        if ($category !== '')
        {
            $finder->where('category', $category);
        }
        if ($query !== '')
        {
            $like = '%' . $finder->escapeLike($query) . '%';
            $finder->whereOr(['title','LIKE',$like], ['tag','LIKE',$like]);
        }

        $total = $finder->total();
        $finder->limitByPage($page, $perPage);
        $categories = $this->db()->fetchAllColumn("SELECT DISTINCT category FROM xf_wx_clan WHERE category <> '' ORDER BY category");
        $stats = [
            'total_clans' => (int)$this->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan"),
            'active_clans' => (int)$this->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan WHERE status = 'active'"),
            'restricted_clans' => (int)$this->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan WHERE status = 'restricted'"),
            'suspended_clans' => (int)$this->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan WHERE status = 'suspended'"),
            'closed_clans' => (int)$this->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan WHERE status = 'closed'"),
            'active_memberships' => (int)$this->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan_member WHERE member_state = 'active'"),
            'pending_create' => (int)$this->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan_application WHERE application_type = 'create' AND status = 'pending'"),
            'pending_identity' => (int)$this->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan_application WHERE application_type = 'change' AND status = 'pending'"),
            'pending_lifecycle' => (int)$this->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan_application WHERE application_type IN ('close','reopen') AND status = 'pending'"),
            'pending_ownership' => (int)$this->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan_ownership_transfer WHERE status = 'accepted'")
        ];

        return $this->view('Warext\\Clans:ClanList', 'wx_clans_admin_list', [
            'clans'=>$finder->fetch(), 'status'=>$status, 'query'=>$query, 'category'=>$category,
            'categories'=>$categories, 'page'=>$page, 'perPage'=>$perPage, 'total'=>$total, 'stats'=>$stats
        ]);
    }

    public function actionStatus(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        $status = $this->filter('status','str');
        $reason = $this->filter('reason','str');
        $this->service('Warext\\Clans:Moderation\\StatusManager', $clan)->change($status, \XF::visitor(), $reason);
        return $this->redirect($this->buildLink('warext-clans'));
    }

    public function actionForceOwner(ParameterBag $params)
    {
        $clan = $this->assertClanExists($params->clan_id);

        if ($this->isPost())
        {
            $userId = $this->filter('user_id', 'uint');
            $member = $this->em()->find('Warext\\Clans:ClanMember', [$clan->clan_id, $userId], ['User']);
            if (!$member)
            {
                return $this->error('The selected user is not an active member of this clan.');
            }

            $keepAsManager = $this->filter('keep_old_owner_manager', 'bool');
            $reason = $this->filter('reason', 'str');
            $this->service('Warext\\Clans:Ownership\\AdminTransfer', $clan)->transfer($member, \XF::visitor(), $keepAsManager, $reason);
            return $this->redirect($this->buildLink('warext-clans'), 'Clan ownership updated.');
        }

        $members = $this->finder('Warext\\Clans:ClanMember')
            ->where('clan_id', $clan->clan_id)
            ->where('member_state', 'active')
            ->where('user_id', '<>', $clan->owner_user_id)
            ->with('User')
            ->order('join_date', 'ASC')
            ->fetch();

        return $this->view('Warext\\Clans:ForceOwner', 'wx_clans_admin_force_owner', [
            'clan' => $clan,
            'members' => $members
        ]);
    }

    public function actionMaintenance()
    {
        $service = $this->service('Warext\\Clans:Maintenance');
        if ($this->isPost())
        {
            $result = $service->run();
            $message = sprintf(
                'Maintenance completed: %d invitations expired, %d stale transfers cancelled, %d stale owner requests cancelled, %d display preferences repaired, %d clan member counts reconciled.',
                $result['expired_invitations'],
                $result['cancelled_transfers'],
                $result['cancelled_requests'],
                $result['cleared_preferences'],
                $result['recounted_clans']
            );
            return $this->redirect($this->buildLink('warext-clans/maintenance'), $message);
        }

        return $this->view('Warext\\Clans:Maintenance', 'wx_clans_admin_maintenance', [
            'stats' => $service->getStats()
        ]);
    }

    protected function assertClanExists(int $id): \Warext\Clans\Entity\Clan
    {
        return $this->assertRecordExists('Warext\\Clans:Clan', $id, ['Owner']);
    }
}
