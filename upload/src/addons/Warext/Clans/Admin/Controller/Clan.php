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
        $categories = $this->app()->db()->fetchAllColumn("SELECT DISTINCT category FROM xf_wx_clan WHERE category <> '' ORDER BY category");
        $stats = [
            'total_clans' => (int)$this->app()->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan"),
            'active_clans' => (int)$this->app()->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan WHERE status = 'active'"),
            'restricted_clans' => (int)$this->app()->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan WHERE status = 'restricted'"),
            'suspended_clans' => (int)$this->app()->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan WHERE status = 'suspended'"),
            'closed_clans' => (int)$this->app()->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan WHERE status = 'closed'"),
            'active_memberships' => (int)$this->app()->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan_member WHERE member_state = 'active'"),
            'pending_create' => (int)$this->app()->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan_application WHERE application_type = 'create' AND status = 'pending'"),
            'pending_identity' => (int)$this->app()->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan_application WHERE application_type = 'change' AND status = 'pending'"),
            'pending_lifecycle' => (int)$this->app()->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan_application WHERE application_type IN ('close','reopen') AND status = 'pending'"),
            'pending_ownership' => (int)$this->app()->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan_ownership_transfer WHERE status = 'accepted'")
        ];

        return $this->view('Warext\\Clans:ClanList', 'wx_clans_admin_list', [
            'clans'=>$finder->fetch(), 'status'=>$status, 'query'=>$query, 'category'=>$category,
            'categories'=>$categories, 'page'=>$page, 'perPage'=>$perPage, 'total'=>$total, 'stats'=>$stats
        ]);
    }

    public function actionManage(ParameterBag $params)
    {
        $clan = $this->assertClanExists($params->clan_id);

        $members = $this->finder('Warext\\Clans:ClanMember')
            ->where('clan_id', $clan->clan_id)
            ->where('member_state', 'active')
            ->with(['User', 'Role'])
            ->order('is_owner', 'DESC')
            ->order('is_manager', 'DESC')
            ->order('join_date', 'ASC')
            ->limit(100)
            ->fetch();

        $applications = $this->finder('Warext\\Clans:ClanApplication')
            ->whereOr(['clan_id', $clan->clan_id], ['created_clan_id', $clan->clan_id])
            ->with(['User', 'DecisionUser'])
            ->order('create_date', 'DESC')
            ->limit(30)
            ->fetch();

        $auditLogs = $this->finder('Warext\\Clans:ClanAuditLog')
            ->where('clan_id', $clan->clan_id)
            ->with('User')
            ->order('log_date', 'DESC')
            ->limit(50)
            ->fetch();

        $db = $this->app()->db();
        $stats = [
            'active_members' => (int)$db->fetchOne("SELECT COUNT(*) FROM xf_wx_clan_member WHERE clan_id = ? AND member_state = 'active'", $clan->clan_id),
            'managers' => (int)$db->fetchOne("SELECT COUNT(*) FROM xf_wx_clan_member WHERE clan_id = ? AND member_state = 'active' AND is_manager = 1 AND is_owner = 0", $clan->clan_id),
            'roles' => (int)$db->fetchOne('SELECT COUNT(*) FROM xf_wx_clan_role WHERE clan_id = ?', $clan->clan_id),
            'announcements' => (int)$db->fetchOne('SELECT COUNT(*) FROM xf_wx_clan_announcement WHERE clan_id = ?', $clan->clan_id),
            'pending_join' => (int)$db->fetchOne("SELECT COUNT(*) FROM xf_wx_clan_application WHERE clan_id = ? AND application_type = 'join' AND status = 'pending'", $clan->clan_id),
            'pending_identity' => (int)$db->fetchOne("SELECT COUNT(*) FROM xf_wx_clan_application WHERE clan_id = ? AND application_type = 'change' AND status = 'pending'", $clan->clan_id),
            'pending_lifecycle' => (int)$db->fetchOne("SELECT COUNT(*) FROM xf_wx_clan_application WHERE clan_id = ? AND application_type IN ('close','reopen') AND status = 'pending'", $clan->clan_id),
            'pending_invitations' => (int)$db->fetchOne("SELECT COUNT(*) FROM xf_wx_clan_invitation WHERE clan_id = ? AND status = 'pending'", $clan->clan_id),
            'pending_ownership' => (int)$db->fetchOne("SELECT COUNT(*) FROM xf_wx_clan_ownership_transfer WHERE clan_id = ? AND status IN ('pending','accepted')", $clan->clan_id)
        ];

        return $this->view('Warext\\Clans:ClanManage', 'wx_clans_admin_manage', [
            'clan' => $clan,
            'members' => $members,
            'applications' => $applications,
            'auditLogs' => $auditLogs,
            'stats' => $stats
        ]);
    }

    public function actionSave(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        $input = $this->filter([
            'title' => 'str',
            'tag' => 'str',
            'description' => 'str',
            'rules' => 'str',
            'category' => 'str',
            'join_mode' => 'str',
            'member_list_visibility' => 'str',
            'announcement_visibility' => 'str',
            'manager_banner' => 'str',
            'tag_icon' => 'str',
            'manager_banner_icon' => 'str',
            'tag_color' => 'str',
            'manager_banner_color' => 'str',
            'logo_url' => 'str',
            'cover_url' => 'str'
        ]);

        $this->service('Warext\\Clans:Admin\\ClanManager', $clan)->update($input, \XF::visitor());

        return $this->redirect(
            $this->buildLink('warext-clans/clans/manage', $clan),
            'Clan details updated.'
        );
    }

    public function actionMemberManager(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        $member = $this->assertClanMemberExists($clan->clan_id, $this->filter('user_id', 'uint'));
        $mode = $this->filter('mode', 'str');
        $this->service('Warext\\Clans:Clan\\MemberManager', $clan)
            ->setManager($member, $mode === 'promote', \XF::visitor()->user_id);

        return $this->redirect($this->buildLink('warext-clans/clans/manage', $clan));
    }

    public function actionMemberRemove(ParameterBag $params)
    {
        $clan = $this->assertClanExists($params->clan_id);
        $member = $this->assertClanMemberExists($clan->clan_id, $this->filter('user_id', 'uint'));

        if ($this->isPost())
        {
            $reason = trim($this->filter('reason', 'str'));
            if ($reason === '')
            {
                return $this->error('A reason is required to remove a clan member from Admin CP.');
            }

            $this->service('Warext\\Clans:Clan\\MemberManager', $clan)
                ->removeMember($member, \XF::visitor()->user_id, $reason);

            return $this->redirect(
                $this->buildLink('warext-clans/clans/manage', $clan),
                'Clan member removed.'
            );
        }

        return $this->view('Warext\\Clans:ClanMemberRemove', 'wx_clans_admin_member_remove', [
            'clan' => $clan,
            'member' => $member
        ]);
    }

    public function actionDelete(ParameterBag $params)
    {
        $clan = $this->assertClanExists($params->clan_id);

        if ($this->isPost())
        {
            $reason = $this->filter('reason', 'str');
            $this->service('Warext\\Clans:Admin\\ClanManager', $clan)->delete(\XF::visitor(), $reason);
            return $this->redirect($this->buildLink('warext-clans'), 'Clan permanently deleted.');
        }

        return $this->view('Warext\\Clans:ClanDelete', 'wx_clans_admin_delete', [
            'clan' => $clan
        ]);
    }

    public function actionStatus(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        $status = $this->filter('status','str');
        $reason = $this->filter('reason','str');
        $this->service('Warext\\Clans:Moderation\\StatusManager', $clan)->change($status, \XF::visitor(), $reason);
        if ($this->filter('from_manage', 'bool'))
        {
            return $this->redirect($this->buildLink('warext-clans/clans/manage', $clan));
        }
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

    protected function assertClanMemberExists(int $clanId, int $userId): \Warext\Clans\Entity\ClanMember
    {
        $member = $this->em()->find('Warext\\Clans:ClanMember', [$clanId, $userId], ['User', 'Role']);
        if (!$member)
        {
            throw $this->exception($this->notFound('Clan member not found.'));
        }

        return $member;
    }

    protected function assertClanExists(int $id): \Warext\Clans\Entity\Clan
    {
        return $this->assertRecordExists('Warext\\Clans:Clan', $id, ['Owner']);
    }
}
