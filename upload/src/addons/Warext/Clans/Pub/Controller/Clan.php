<?php

namespace Warext\Clans\Pub\Controller;

use Warext\Clans\Permission\ClanPermission;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Clan extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        if ($this->request->getRequestMethod() !== 'GET')
        {
            $visitor = \XF::visitor();
            if ($visitor->user_id && ($visitor->user_state !== 'valid' || $visitor->is_banned))
            {
                throw $this->exception($this->noPermission('Your forum account is not currently eligible to perform clan actions.'));
            }
        }
    }

    public function actionIndex(ParameterBag $params)
    {
        if ($params->clan_id)
        {
            return $this->rerouteController(__CLASS__, 'view', $params);
        }

        $page = $this->filterPage();
        $perPage = 20;
        $query = trim($this->filter('q', 'str'));
        $category = trim($this->filter('category', 'str'));
        $joinMode = trim($this->filter('join_mode', 'str'));
        $sort = trim($this->filter('sort', 'str')) ?: 'newest';

        $finder = $this->repository('Warext\\Clans:Clan')->findClansForList();
        $this->repository('Warext\\Clans:Clan')->applyListFilters($finder, $query, $category, $joinMode, $sort);
        $total = $finder->total();
        $finder->limitByPage($page, $perPage);

        $categories = $this->db()->fetchAllColumn("SELECT DISTINCT category FROM xf_wx_clan WHERE status IN ('active', 'restricted') AND category <> '' ORDER BY category");

        return $this->view('Warext\\Clans:ClanList', 'wx_clans_list', [
            'clans' => $finder->fetch(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'query' => $query,
            'category' => $category,
            'joinMode' => $joinMode,
            'sort' => $sort,
            'categories' => $categories
        ]);
    }

    public function actionView(ParameterBag $params)
    {
        $clan = $this->assertClanExists($params->clan_id, ['Owner']);
        if (!$clan->canView($error))
        {
            throw $this->exception($this->noPermission($error));
        }

        $canViewMembers = $clan->canViewMemberList($memberListError);
        $memberPage = $this->filterPage();
        $memberPerPage = max(10, min(100, (int)($this->options()->wxClansMembersPerPage ?? 50)));
        $memberTotal = 0;
        if ($canViewMembers)
        {
            $memberFinder = $this->finder('Warext\\Clans:ClanMember')
                ->where('clan_id', $clan->clan_id)
                ->where('member_state', 'active')
                ->with(['User', 'Role'])
                ->order('is_owner', 'DESC')
                ->order('is_manager', 'DESC')
                ->order('join_date', 'ASC');
            $memberTotal = $memberFinder->total();
            $memberFinder->limitByPage($memberPage, $memberPerPage);
            $members = $memberFinder->fetch();
        }
        else
        {
            $members = [];
        }

        $canViewAnnouncements = $clan->canViewAnnouncements($announcementError);
        $announcements = $canViewAnnouncements
            ? $this->finder('Warext\\Clans:ClanAnnouncement')
                ->where('clan_id', $clan->clan_id)
                ->with('User')
                ->order('is_pinned', 'DESC')
                ->order('create_date', 'DESC')
                ->limit(10)
                ->fetch()
            : [];

        $pendingJoin = null;
        if (\XF::visitor()->user_id)
        {
            $pendingJoin = $this->finder('Warext\\Clans:ClanApplication')
                ->where('application_type', 'join')
                ->where('clan_id', $clan->clan_id)
                ->where('user_id', \XF::visitor()->user_id)
                ->where('status', 'pending')
                ->fetchOne();
        }

        return $this->view('Warext\\Clans:ClanView', 'wx_clans_view', [
            'clan' => $clan,
            'members' => $members,
            'announcements' => $announcements,
            'visitorMembership' => $clan->getVisitorMembership(),
            'pendingJoin' => $pendingJoin,
            'canViewMembers' => $canViewMembers,
            'memberListError' => $memberListError ?? '',
            'memberPage' => $memberPage,
            'memberPerPage' => $memberPerPage,
            'memberTotal' => $memberTotal,
            'canViewAnnouncements' => $canViewAnnouncements,
            'announcementError' => $announcementError ?? '',
            'pendingLifecycle' => $clan->isVisitorOwner() ? $this->finder('Warext\\Clans:ClanApplication')->where('clan_id', $clan->clan_id)->where('application_type', ['close','reopen'])->where('status', 'pending')->order('create_date', 'DESC')->fetchOne() : null
        ]);
    }

    public function actionMy()
    {
        $this->assertRegistrationRequired();
        $visitor = \XF::visitor();

        $memberships = $this->finder('Warext\\Clans:ClanMember')
            ->where('user_id', $visitor->user_id)
            ->where('member_state', 'active')
            ->with(['Clan.Owner', 'Role'])
            ->order('is_owner', 'DESC')
            ->order('is_manager', 'DESC')
            ->order('join_date', 'ASC')
            ->fetch();

        $invitations = $this->finder('Warext\\Clans:ClanInvitation')
            ->where('user_id', $visitor->user_id)
            ->where('status', 'pending')
            ->where('expiry_date', '>', \XF::$time)
            ->with(['Clan', 'Inviter'])
            ->order('create_date', 'DESC')
            ->fetch();

        $applications = $this->finder('Warext\\Clans:ClanApplication')
            ->where('user_id', $visitor->user_id)
            ->with('Clan')
            ->order('create_date', 'DESC')
            ->limit(50)
            ->fetch();

        $transfers = $this->finder('Warext\\Clans:ClanOwnershipTransfer')
            ->where('to_user_id', $visitor->user_id)
            ->where('status', ['pending','accepted'])
            ->with(['Clan','FromUser'])
            ->order('create_date', 'DESC')
            ->fetch();


        $invitationHistory = $this->finder('Warext\Clans:ClanInvitation')
            ->where('user_id', $visitor->user_id)
            ->with(['Clan', 'Inviter'])
            ->order('create_date', 'DESC')
            ->limit(50)
            ->fetch();

        $transferHistory = $this->finder('Warext\Clans:ClanOwnershipTransfer')
            ->whereOr(['to_user_id', $visitor->user_id], ['from_user_id', $visitor->user_id])
            ->with(['Clan','FromUser','ToUser'])
            ->order('create_date', 'DESC')
            ->limit(50)
            ->fetch();

        $pref = $this->em()->find('Warext\\Clans:ClanUserPreference', $visitor->user_id);

        return $this->view('Warext\\Clans:MyClans', 'wx_clans_my', [
            'memberships' => $memberships,
            'invitations' => $invitations,
            'applications' => $applications,
            'transfers' => $transfers,
            'invitationHistory' => $invitationHistory,
            'transferHistory' => $transferHistory,
            'activeClanId' => $pref ? $pref->active_clan_id : 0
        ]);
    }

    public function actionApply()
    {
        $this->assertRegistrationRequired();
        $visitor = \XF::visitor();
        $existing = $this->finder('Warext\\Clans:ClanApplication')
            ->where('application_type', 'create')
            ->where('user_id', $visitor->user_id)
            ->where('status', 'changes_requested')
            ->order('create_date', 'DESC')
            ->fetchOne();

        if ($this->isPost())
        {
            $applicationId = $this->filter('application_id', 'uint');
            if ($applicationId)
            {
                $existing = $this->em()->find('Warext\\Clans:ClanApplication', $applicationId);
            }

            $input = $this->filter([
                'title' => 'str',
                'tag' => 'str',
                'description' => 'str',
                'category' => 'str',
                'requested_manager_banner' => 'str',
                'requested_tag_color' => 'str',
                'requested_manager_banner_color' => 'str'
            ]);
            $service = $this->service('Warext\\Clans:Application\\SubmitCreate', $visitor);
            $service->setExisting($existing);
            $service->setInput($input);
            $service->save();
            return $this->redirect($this->buildLink('clans/my'), 'Clan application submitted for approval.');
        }

        return $this->view('Warext\\Clans:ClanApply', 'wx_clans_apply', ['application' => $existing]);
    }

    public function actionApplicationCancel()
    {
        $this->assertPostOnly();
        $this->assertRegistrationRequired();
        $application = $this->assertApplicationExists($this->filter('application_id', 'uint'));
        if ($application->user_id !== \XF::visitor()->user_id || !in_array($application->status, ['pending','changes_requested'], true))
        {
            throw $this->exception($this->noPermission());
        }
        $application->status = 'cancelled';
        $application->decision_date = \XF::$time;
        $application->save();
        return $this->redirect($this->buildLink('clans/my'));
    }

    public function actionJoin(ParameterBag $params)
    {
        $this->assertPostOnly();
        $this->assertRegistrationRequired();
        $clan = $this->assertClanExists($params->clan_id);
        $this->service('Warext\\Clans:Join\\Manager', $clan)->joinOpen(\XF::visitor());
        return $this->redirect($this->buildLink('clans', $clan), 'You joined the clan.');
    }

    public function actionJoinApply(ParameterBag $params)
    {
        $this->assertRegistrationRequired();
        $clan = $this->assertClanExists($params->clan_id);
        if ($clan->join_mode !== 'application' || !$clan->canJoin($error))
        {
            throw $this->exception($this->noPermission($error));
        }

        $fields = $this->finder('Warext\\Clans:ClanApplicationField')
            ->where('clan_id', $clan->clan_id)
            ->where('active', 1)
            ->order('display_order')
            ->fetch();

        if ($this->isPost())
        {
            $answers = $this->filter('answers', 'array');
            $this->service('Warext\\Clans:Join\\Manager', $clan)->submitApplication(\XF::visitor(), $answers);
            return $this->redirect($this->buildLink('clans', $clan), 'Your clan application was submitted.');
        }

        return $this->view('Warext\\Clans:JoinApply', 'wx_clans_join_apply', ['clan'=>$clan,'fields'=>$fields]);
    }

    public function actionLeave(ParameterBag $params)
    {
        $this->assertPostOnly();
        $this->assertRegistrationRequired();
        $clan = $this->assertClanExists($params->clan_id);
        if (!$clan->canLeave($error))
        {
            throw $this->exception($this->noPermission($error));
        }
        $member = $clan->getVisitorMembership();
        $this->service('Warext\\Clans:Clan\\MemberManager', $clan)->removeMember($member, \XF::visitor()->user_id, 'Voluntary leave');
        return $this->redirect($this->buildLink('clans', $clan), 'You left the clan.');
    }

    public function actionInvitationRespond(ParameterBag $params)
    {
        $this->assertPostOnly();
        $this->assertRegistrationRequired();
        $clan = $this->assertClanExists($params->clan_id);
        $invitation = $this->assertInvitationExists($this->filter('invitation_id', 'uint'));
        $accept = $this->filter('accept', 'bool');
        $this->service('Warext\\Clans:Invitation\\Manager', $clan)->respond($invitation, \XF::visitor(), $accept);
        return $this->redirect($this->buildLink('clans/my'));
    }

    public function actionInvitationCancel(ParameterBag $params)
    {
        $this->assertPostOnly();
        $this->assertRegistrationRequired();
        $clan = $this->assertClanExists($params->clan_id);
        $this->assertClanPermission($clan, ClanPermission::MANAGE_MEMBERS);
        $invitation = $this->assertInvitationExists($this->filter('invitation_id', 'uint'));
        $this->service('Warext\Clans:Invitation\Manager', $clan)->cancel($invitation, \XF::visitor());
        return $this->redirect($this->buildLink('clans/manage', $clan), 'Invitation cancelled.');
    }

    public function actionOwnershipAccept(ParameterBag $params)
    {
        $this->assertPostOnly();
        $this->assertRegistrationRequired();
        $clan = $this->assertClanExists($params->clan_id);
        $transfer = $this->assertTransferExists($this->filter('transfer_id', 'uint'));
        $this->service('Warext\\Clans:Ownership\\Manager', $clan)->accept($transfer, \XF::visitor());
        return $this->redirect($this->buildLink('clans/my'), 'Ownership transfer accepted and sent for forum approval.');
    }

    public function actionActive()
    {
        $this->assertPostOnly();
        $this->assertRegistrationRequired();
        $clanId = $this->filter('clan_id', 'uint');
        $this->service('Warext\\Clans:Clan\\PreferenceManager')->setActiveClan(\XF::visitor(), $clanId);
        return $this->redirect($this->buildLink('clans/my'), 'Displayed clan updated.');
    }

    public function actionModeratorStatus(ParameterBag $params)
    {
        $this->assertPostOnly();
        if (!\XF::visitor()->hasPermission('wxClans', 'moderate'))
        {
            throw $this->exception($this->noPermission());
        }
        $clan = $this->assertClanExists($params->clan_id, ['Owner']);
        $status = $this->filter('status', 'str');
        $reason = $this->filter('reason', 'str');
        $this->service('Warext\\Clans:Moderation\\StatusManager', $clan)->change($status, \XF::visitor(), $reason);
        return $this->redirect($this->buildLink('clans', $clan), 'Clan moderation status updated.');
    }

    public function actionReport(ParameterBag $params)
    {
        $clan = $this->assertClanExists($params->clan_id, ['Owner']);
        if (!$clan->canReport($error))
        {
            return $this->noPermission($error);
        }
        $reportPlugin = $this->plugin('XF:Report');
        return $reportPlugin->actionReport(
            'wx_clan', $clan,
            $this->buildLink('clans/report', $clan),
            $this->buildLink('clans', $clan)
        );
    }

    public function actionManage(ParameterBag $params)
    {
        $this->assertRegistrationRequired();
        $clan = $this->assertClanExists($params->clan_id, ['Owner']);
        $this->assertCanManageClan($clan);

        $memberPage = $this->filterPage();
        $memberPerPage = max(10, min(100, (int)($this->options()->wxClansMembersPerPage ?? 50)));
        $memberFinder = $this->finder('Warext\\Clans:ClanMember')
            ->where('clan_id', $clan->clan_id)
            ->where('member_state', 'active')
            ->with(['User', 'Role'])
            ->order('is_owner', 'DESC')
            ->order('is_manager', 'DESC')
            ->order('join_date', 'ASC');
        $memberTotal = $memberFinder->total();
        $memberFinder->limitByPage($memberPage, $memberPerPage);
        $members = $memberFinder->fetch();

        $roleManager = $this->service('Warext\\Clans:Clan\\RoleManager', $clan);
        $managerRole = $roleManager->getOrCreateManagerRole();
        $customRoles = $this->finder('Warext\\Clans:ClanRole')
            ->where('clan_id', $clan->clan_id)
            ->where('role_type', 'custom')
            ->order('display_order')
            ->fetch();

        $joinApplications = $clan->canManagePermission(ClanPermission::MANAGE_APPLICATIONS)
            ? $this->repository('Warext\\Clans:ClanApplication')->findPendingJoinApplicationsForClan($clan->clan_id)->fetch()
            : [];

        $applicationFields = $clan->canManagePermission(ClanPermission::MANAGE_APPLICATIONS)
            ? $this->finder('Warext\\Clans:ClanApplicationField')->where('clan_id',$clan->clan_id)->order('display_order')->fetch()
            : [];

        $blacklist = $clan->canManagePermission(ClanPermission::MANAGE_MEMBERS)
            ? $this->finder('Warext\\Clans:ClanBlacklist')->where('clan_id',$clan->clan_id)->with(['User','AddedBy'])->order('create_date','DESC')->fetch()
            : [];

        $pendingInvitations = $clan->canManagePermission(ClanPermission::MANAGE_MEMBERS)
            ? $this->finder('Warext\\Clans:ClanInvitation')->where('clan_id',$clan->clan_id)->where('status','pending')->where('expiry_date','>', \XF::$time)->with(['User','Inviter'])->order('create_date','DESC')->fetch()
            : [];

        $announcements = $this->finder('Warext\\Clans:ClanAnnouncement')->where('clan_id',$clan->clan_id)->with('User')->order('is_pinned','DESC')->order('create_date','DESC')->fetch();

        $ownershipTransfer = $clan->isVisitorOwner()
            ? $this->finder('Warext\\Clans:ClanOwnershipTransfer')->where('clan_id',$clan->clan_id)->where('status',['pending','accepted'])->with(['ToUser','FromUser'])->order('create_date','DESC')->fetchOne()
            : null;

        $identityRequest = $clan->isVisitorOwner()
            ? $this->finder('Warext\\Clans:ClanApplication')->where('application_type','change')->where('clan_id',$clan->clan_id)->where('status','pending')->fetchOne()
            : null;

        $lifecycleRequest = $clan->isVisitorOwner()
            ? $this->finder('Warext\Clans:ClanApplication')->where('application_type',['close','reopen'])->where('clan_id',$clan->clan_id)->where('status','pending')->order('create_date','DESC')->fetchOne()
            : null;

        $joinApplicationHistory = $clan->canManagePermission(ClanPermission::MANAGE_APPLICATIONS)
            ? $this->finder('Warext\Clans:ClanApplication')->where('application_type','join')->where('clan_id',$clan->clan_id)->with(['User','DecisionUser'])->order('create_date','DESC')->limit(50)->fetch()
            : [];

        $invitationHistory = $clan->canManagePermission(ClanPermission::MANAGE_MEMBERS)
            ? $this->finder('Warext\Clans:ClanInvitation')->where('clan_id',$clan->clan_id)->with(['User','Inviter'])->order('create_date','DESC')->limit(50)->fetch()
            : [];

        $ownershipHistory = $clan->isVisitorOwner()
            ? $this->finder('Warext\Clans:ClanOwnershipTransfer')->where('clan_id',$clan->clan_id)->with(['FromUser','ToUser'])->order('create_date','DESC')->limit(25)->fetch()
            : [];

        $identityHistory = $clan->isVisitorOwner()
            ? $this->finder('Warext\Clans:ClanApplication')->where('application_type','change')->where('clan_id',$clan->clan_id)->with('DecisionUser')->order('create_date','DESC')->limit(25)->fetch()
            : [];

        $lifecycleHistory = $clan->isVisitorOwner()
            ? $this->finder('Warext\Clans:ClanApplication')->where('application_type',['close','reopen'])->where('clan_id',$clan->clan_id)->with('DecisionUser')->order('create_date','DESC')->limit(25)->fetch()
            : [];


        $logs = null;
        if ($clan->canManagePermission(ClanPermission::VIEW_AUDIT_LOG))
        {
            $logs = $this->finder('Warext\\Clans:ClanAuditLog')
                ->where('clan_id', $clan->clan_id)
                ->with('User')
                ->order('log_date', 'DESC')
                ->limit(100)
                ->fetch();
        }

        $managerPermissionRows = [];
        foreach (ClanPermission::managerDefinitions() as $permissionId => $title)
        {
            $managerPermissionRows[] = ['id'=>$permissionId,'title'=>$title,'enabled'=>!empty($managerRole->permissions[$permissionId])];
        }

        return $this->view('Warext\\Clans:ClanManage', 'wx_clans_manage', [
            'clan' => $clan,
            'members' => $members,
            'memberPage' => $memberPage,
            'memberPerPage' => $memberPerPage,
            'memberTotal' => $memberTotal,
            'managerRole' => $managerRole,
            'managerPermissionRows' => $managerPermissionRows,
            'customRoles' => $customRoles,
            'joinApplications' => $joinApplications,
            'applicationFields' => $applicationFields,
            'blacklist' => $blacklist,
            'pendingInvitations' => $pendingInvitations,
            'announcements' => $announcements,
            'ownershipTransfer' => $ownershipTransfer,
            'identityRequest' => $identityRequest,
            'lifecycleRequest' => $lifecycleRequest,
            'joinApplicationHistory' => $joinApplicationHistory,
            'invitationHistory' => $invitationHistory,
            'ownershipHistory' => $ownershipHistory,
            'identityHistory' => $identityHistory,
            'lifecycleHistory' => $lifecycleHistory,
            'logs' => $logs
        ]);
    }

    public function actionMemberAdd(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        $this->assertClanPermission($clan, ClanPermission::MANAGE_MEMBERS);
        $user = $this->findValidUserByName($this->filter('username', 'str'));
        $this->service('Warext\\Clans:Clan\\MemberManager', $clan)->addMember($user, \XF::visitor()->user_id);
        $this->service('Warext\\Clans:Notification')->alertUser($user, $clan, 'member_added', \XF::visitor());
        return $this->redirect($this->buildLink('clans/manage', $clan), 'Member added.');
    }

    public function actionInvite(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        $this->assertClanPermission($clan, ClanPermission::MANAGE_MEMBERS);
        $user = $this->findValidUserByName($this->filter('username', 'str'));
        $this->service('Warext\\Clans:Invitation\\Manager', $clan)->create($user, \XF::visitor());
        return $this->redirect($this->buildLink('clans/manage', $clan), 'Clan invitation sent.');
    }

    public function actionMemberRemove(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        $this->assertClanPermission($clan, ClanPermission::MANAGE_MEMBERS);
        $member = $this->assertClanMemberExists($clan->clan_id, $this->filter('user_id', 'uint'));
        if ($member->is_manager && !$clan->isVisitorOwner())
        {
            throw $this->exception($this->noPermission());
        }
        $this->service('Warext\\Clans:Clan\\MemberManager', $clan)->removeMember($member, \XF::visitor()->user_id, $this->filter('reason','str'));
        return $this->redirect($this->buildLink('clans/manage', $clan), 'Member removed.');
    }

    public function actionManagerToggle(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        if (!$clan->canManageManagers())
        {
            throw $this->exception($this->noPermission());
        }
        $member = $this->assertClanMemberExists($clan->clan_id, $this->filter('user_id', 'uint'));
        $makeManager = $this->filter('make_manager', 'bool');
        $this->service('Warext\\Clans:Clan\\MemberManager', $clan)->setManager($member, $makeManager, \XF::visitor()->user_id);
        if ($member->User && $makeManager)
        {
            $this->service('Warext\\Clans:Notification')->alertUser($member->User, $clan, 'manager_assigned', \XF::visitor());
        }
        return $this->redirect($this->buildLink('clans/manage', $clan));
    }

    public function actionManagerPermissions(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        if (!$clan->canManageRoles())
        {
            throw $this->exception($this->noPermission());
        }
        $this->service('Warext\\Clans:Clan\\RoleManager', $clan)->saveManagerPermissions($this->filter('manager_permissions', 'array'), \XF::visitor()->user_id);
        return $this->redirect($this->buildLink('clans/manage', $clan), 'Manager permissions updated.');
    }

    public function actionRoleEdit(ParameterBag $params)
    {
        $this->assertRegistrationRequired();
        $clan = $this->assertClanExists($params->clan_id);
        if (!$clan->canManageRoles())
        {
            throw $this->exception($this->noPermission());
        }
        $role = $this->assertRoleExists($this->filter('role_id', 'uint'));
        if ($role->clan_id !== $clan->clan_id || $role->role_type !== 'custom')
        {
            throw $this->exception($this->notFound());
        }

        return $this->view('Warext\\Clans:RoleEdit', 'wx_clans_role_edit', [
            'clan' => $clan,
            'role' => $role
        ]);
    }

    public function actionRoleSave(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        if (!$clan->canManageRoles())
        {
            throw $this->exception($this->noPermission());
        }
        $roleId = $this->filter('role_id', 'uint');
        $role = $roleId ? $this->em()->find('Warext\\Clans:ClanRole', $roleId) : null;
        $service = $this->service('Warext\\Clans:Clan\\RoleManager', $clan);
        if ($role)
        {
            $service->updateCustomRole($role, $this->filter('title','str'), $this->filter('display_order','uint'), \XF::visitor()->user_id);
        }
        else
        {
            $service->createCustomRole($this->filter('title','str'), $this->filter('display_order','uint'), \XF::visitor()->user_id);
        }
        return $this->redirect($this->buildLink('clans/manage', $clan));
    }

    public function actionRoleDelete(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        if (!$clan->canManageRoles())
        {
            throw $this->exception($this->noPermission());
        }
        $role = $this->assertRoleExists($this->filter('role_id','uint'));
        $this->service('Warext\\Clans:Clan\\RoleManager', $clan)->deleteCustomRole($role, \XF::visitor()->user_id);
        return $this->redirect($this->buildLink('clans/manage', $clan));
    }

    public function actionRoleAssign(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        if (!$clan->canManageRoles())
        {
            throw $this->exception($this->noPermission());
        }
        $member = $this->assertClanMemberExists($clan->clan_id, $this->filter('user_id','uint'));
        $roleId = $this->filter('role_id','uint');
        $role = $roleId ? $this->em()->find('Warext\\Clans:ClanRole', $roleId) : null;
        $this->service('Warext\\Clans:Clan\\MemberManager', $clan)->assignCustomRole($member, $role, \XF::visitor()->user_id);
        return $this->redirect($this->buildLink('clans/manage', $clan));
    }

    public function actionJoinApplicationDecision(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        $this->assertClanPermission($clan, ClanPermission::MANAGE_APPLICATIONS);
        $application = $this->assertApplicationExists($this->filter('application_id','uint'));
        $approve = $this->filter('approve','bool');
        $this->service('Warext\\Clans:Join\\Manager', $clan)->decideApplication($application, $approve, \XF::visitor(), $this->filter('reason','str'));
        return $this->redirect($this->buildLink('clans/manage', $clan));
    }

    public function actionApplicationFieldEdit(ParameterBag $params)
    {
        $this->assertRegistrationRequired();
        $clan = $this->assertClanExists($params->clan_id);
        $this->assertClanPermission($clan, ClanPermission::MANAGE_APPLICATIONS);
        $field = $this->assertApplicationFieldExists($this->filter('field_id', 'uint'));
        if ($field->clan_id !== $clan->clan_id)
        {
            throw $this->exception($this->notFound());
        }

        return $this->view('Warext\\Clans:ApplicationFieldEdit', 'wx_clans_application_field_edit', [
            'clan' => $clan,
            'field' => $field,
            'optionsText' => implode("\n", $field->field_options ?: [])
        ]);
    }

    public function actionApplicationFieldSave(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        $this->assertClanPermission($clan, ClanPermission::MANAGE_APPLICATIONS);
        $fieldId = $this->filter('field_id','uint');
        $field = $fieldId ? $this->em()->find('Warext\\Clans:ClanApplicationField', $fieldId) : null;
        $input = $this->filter(['field_key'=>'str','title'=>'str','field_type'=>'str','options_text'=>'str','required'=>'bool','display_order'=>'uint','active'=>'bool']);
        $this->service('Warext\\Clans:ApplicationForm\\Manager', $clan)->save($field, $input, \XF::visitor()->user_id);
        return $this->redirect($this->buildLink('clans/manage', $clan));
    }

    public function actionApplicationFieldDelete(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        $this->assertClanPermission($clan, ClanPermission::MANAGE_APPLICATIONS);
        $field = $this->assertApplicationFieldExists($this->filter('field_id','uint'));
        $this->service('Warext\\Clans:ApplicationForm\\Manager', $clan)->delete($field, \XF::visitor()->user_id);
        return $this->redirect($this->buildLink('clans/manage', $clan));
    }

    public function actionBlacklistAdd(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        $this->assertClanPermission($clan, ClanPermission::MANAGE_MEMBERS);
        $user = $this->findValidUserByName($this->filter('username','str'));
        $this->service('Warext\\Clans:Clan\\BlacklistManager', $clan)->add($user, \XF::visitor(), $this->filter('reason','str'), $this->filter('days','uint'));
        return $this->redirect($this->buildLink('clans/manage', $clan));
    }

    public function actionBlacklistRemove(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        $this->assertClanPermission($clan, ClanPermission::MANAGE_MEMBERS);
        $entry = $this->assertBlacklistExists($this->filter('blacklist_id','uint'));
        $this->service('Warext\\Clans:Clan\\BlacklistManager', $clan)->remove($entry, \XF::visitor()->user_id);
        return $this->redirect($this->buildLink('clans/manage', $clan));
    }

    public function actionAnnouncementEdit(ParameterBag $params)
    {
        $this->assertRegistrationRequired();
        $clan = $this->assertClanExists($params->clan_id);
        $this->assertClanPermission($clan, ClanPermission::MANAGE_ANNOUNCEMENTS);
        $announcement = $this->assertAnnouncementExists($this->filter('announcement_id', 'uint'));
        if ($announcement->clan_id !== $clan->clan_id)
        {
            throw $this->exception($this->notFound());
        }

        return $this->view('Warext\\Clans:AnnouncementEdit', 'wx_clans_announcement_edit', [
            'clan' => $clan,
            'announcement' => $announcement
        ]);
    }

    public function actionAnnouncementSave(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        $this->assertClanPermission($clan, ClanPermission::MANAGE_ANNOUNCEMENTS);
        $announcementId = $this->filter('announcement_id','uint');
        $announcement = $announcementId ? $this->em()->find('Warext\\Clans:ClanAnnouncement', $announcementId) : null;
        $input = $this->filter(['title'=>'str','message'=>'str','is_pinned'=>'bool']);
        $this->service('Warext\\Clans:Clan\\AnnouncementManager', $clan)->save($announcement, $input, \XF::visitor());
        return $this->redirect($this->buildLink('clans/manage', $clan));
    }

    public function actionAnnouncementDelete(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        $this->assertClanPermission($clan, ClanPermission::MANAGE_ANNOUNCEMENTS);
        $announcement = $this->assertAnnouncementExists($this->filter('announcement_id','uint'));
        $this->service('Warext\\Clans:Clan\\AnnouncementManager', $clan)->delete($announcement, \XF::visitor()->user_id);
        return $this->redirect($this->buildLink('clans/manage', $clan));
    }

    public function actionAnnouncementReport(ParameterBag $params)
    {
        $announcement = $this->assertAnnouncementExists($this->filter('announcement_id', 'uint'));
        if ($announcement->clan_id !== $params->clan_id || !$announcement->canReport($error))
        {
            return $this->noPermission($error);
        }
        $reportPlugin = $this->plugin('XF:Report');
        return $reportPlugin->actionReport(
            'wx_clan_announcement', $announcement,
            $this->buildLink('clans/announcement-report', $announcement->Clan, ['announcement_id' => $announcement->announcement_id]),
            $this->buildLink('clans', $announcement->Clan)
        );
    }

    public function actionModeratorAnnouncementDelete(ParameterBag $params)
    {
        $this->assertPostOnly();
        if (!\XF::visitor()->hasPermission('wxClans', 'moderate'))
        {
            throw $this->exception($this->noPermission());
        }
        $clan = $this->assertClanExists($params->clan_id, ['Owner']);
        $announcement = $this->assertAnnouncementExists($this->filter('announcement_id', 'uint'));
        if ($announcement->clan_id !== $clan->clan_id)
        {
            throw $this->exception($this->notFound());
        }
        $announcementId = $announcement->announcement_id;
        $title = $announcement->title;
        $announcement->delete();
        $this->service('Warext\Clans:Audit\Logger')->log($clan->clan_id, \XF::visitor()->user_id, 'forum_announcement_removed', ['announcement_id'=>$announcementId,'title'=>$title], 'clan_announcement', $announcementId);
        \XF::app()->logger()->moderatorLogger()->log('wx_clan', $clan, 'announcement_removed', ['announcement_id'=>$announcementId,'title'=>$title], false, \XF::visitor());
        return $this->redirect($this->buildLink('clans', $clan), 'Clan announcement removed.');
    }

    public function actionOwnershipTransfer(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        if (!$clan->canTransferOwnership())
        {
            throw $this->exception($this->noPermission());
        }
        $user = $this->findValidUserByName($this->filter('username','str'));
        $this->service('Warext\\Clans:Ownership\\Manager', $clan)->create($user, \XF::visitor());
        return $this->redirect($this->buildLink('clans/manage', $clan), 'Ownership transfer sent to the selected user.');
    }

    public function actionOwnershipCancel(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        $transfer = $this->assertTransferExists($this->filter('transfer_id','uint'));
        $this->service('Warext\\Clans:Ownership\\Manager', $clan)->cancel($transfer, \XF::visitor());
        return $this->redirect($this->buildLink('clans/manage', $clan));
    }

    public function actionIdentityRequest(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        $input = $this->filter(['title'=>'str','tag'=>'str','manager_banner'=>'str','tag_color'=>'str','manager_banner_color'=>'str']);
        $this->service('Warext\\Clans:Identity\\Manager', $clan)->submit($input, \XF::visitor());
        return $this->redirect($this->buildLink('clans/manage', $clan), 'Clan identity change request submitted for forum approval.');
    }

    public function actionLifecycleRequest(ParameterBag $params)
    {
        $this->assertPostOnly();
        $this->assertRegistrationRequired();
        $clan = $this->assertClanExists($params->clan_id, ['Owner']);
        $type = $this->filter('type', 'str');
        $this->service('Warext\\Clans:Clan\\LifecycleManager', $clan)->request($type, \XF::visitor(), $this->filter('reason', 'str'));
        return $this->redirect($this->buildLink('clans', $clan), 'Request submitted for forum approval.');
    }

    public function actionLifecycleCancel(ParameterBag $params)
    {
        $this->assertPostOnly();
        $this->assertRegistrationRequired();
        $clan = $this->assertClanExists($params->clan_id, ['Owner']);
        $request = $this->assertApplicationExists($this->filter('application_id', 'uint'));
        $this->service('Warext\\Clans:Clan\\LifecycleManager', $clan)->cancel($request, \XF::visitor());
        return $this->redirect($this->buildLink('clans', $clan), 'Request cancelled.');
    }

    public function actionSettings(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        $this->assertClanPermission($clan, ClanPermission::MANAGE_SETTINGS);

        $input = $this->filter(['description'=>'str','rules'=>'str','category'=>'str','join_mode'=>'str','member_list_visibility'=>'str','announcement_visibility'=>'str','logo_url'=>'str','cover_url'=>'str']);
        if (!in_array($input['join_mode'], ['open', 'application', 'invite', 'closed'], true))
        {
            return $this->error('Invalid join mode.');
        }
        if (!in_array($input['member_list_visibility'], ['public','members','staff'], true))
        {
            return $this->error('Invalid member list visibility.');
        }
        if (!in_array($input['announcement_visibility'], ['public','members'], true))
        {
            return $this->error('Invalid announcement visibility.');
        }

        foreach (['logo_url','cover_url'] as $field)
        {
            $value = trim($input[$field]);
            if ($value !== '' && !$this->isValidHttpUrl($value))
            {
                return $this->error('Logo and cover URLs must use http or https.');
            }
        }

        $clan->description = mb_substr(trim($input['description']), 0, 20000);
        $clan->rules = mb_substr(trim($input['rules']), 0, 20000);
        $clan->category = mb_substr(trim($input['category']), 0, 50);
        $clan->join_mode = $input['join_mode'];
        $clan->member_list_visibility = $input['member_list_visibility'];
        $clan->announcement_visibility = $input['announcement_visibility'];
        $clan->logo_url = trim($input['logo_url']);
        $clan->cover_url = trim($input['cover_url']);
        $clan->save();

        $this->service('Warext\\Clans:Audit\\Logger')->log($clan->clan_id, \XF::visitor()->user_id, 'settings_updated', [
            'category'=>$clan->category,
            'join_mode'=>$clan->join_mode,
            'member_list_visibility'=>$clan->member_list_visibility,
            'announcement_visibility'=>$clan->announcement_visibility
        ], 'clan', $clan->clan_id);
        return $this->redirect($this->buildLink('clans/manage', $clan), 'Clan settings updated.');
    }

    protected function findValidUserByName(string $username): \XF\Entity\User
    {
        $user = $this->finder('XF:User')->where('username', trim($username))->where('user_state', 'valid')->fetchOne();
        if (!$user)
        {
            throw new \XF\PrintableException('User not found.');
        }
        return $user;
    }

    protected function isValidHttpUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL))
        {
            return false;
        }
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http','https'], true);
    }

    protected function assertCanManageClan(\Warext\Clans\Entity\Clan $clan): void
    {
        if (!$clan->canManage($error))
        {
            throw $this->exception($this->noPermission($error));
        }
    }

    protected function assertClanPermission(\Warext\Clans\Entity\Clan $clan, string $permissionId): void
    {
        if (!$clan->canManagePermission($permissionId, $error))
        {
            throw $this->exception($this->noPermission($error));
        }
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

    protected function assertClanExists(int $id, $with = null): \Warext\Clans\Entity\Clan
    {
        return $this->assertRecordExists('Warext\\Clans:Clan', $id, $with, 'requested_page_not_found');
    }

    protected function assertApplicationExists(int $id): \Warext\Clans\Entity\ClanApplication
    {
        return $this->assertRecordExists('Warext\\Clans:ClanApplication', $id, ['User', 'Clan']);
    }

    protected function assertInvitationExists(int $id): \Warext\Clans\Entity\ClanInvitation
    {
        return $this->assertRecordExists('Warext\\Clans:ClanInvitation', $id, ['Clan','User','Inviter']);
    }

    protected function assertTransferExists(int $id): \Warext\Clans\Entity\ClanOwnershipTransfer
    {
        return $this->assertRecordExists('Warext\\Clans:ClanOwnershipTransfer', $id, ['Clan','FromUser','ToUser']);
    }

    protected function assertRoleExists(int $id): \Warext\Clans\Entity\ClanRole
    {
        return $this->assertRecordExists('Warext\\Clans:ClanRole', $id);
    }

    protected function assertApplicationFieldExists(int $id): \Warext\Clans\Entity\ClanApplicationField
    {
        return $this->assertRecordExists('Warext\\Clans:ClanApplicationField', $id);
    }

    protected function assertBlacklistExists(int $id): \Warext\Clans\Entity\ClanBlacklist
    {
        return $this->assertRecordExists('Warext\\Clans:ClanBlacklist', $id, ['User']);
    }

    protected function assertAnnouncementExists(int $id): \Warext\Clans\Entity\ClanAnnouncement
    {
        return $this->assertRecordExists('Warext\\Clans:ClanAnnouncement', $id, ['User']);
    }
}
