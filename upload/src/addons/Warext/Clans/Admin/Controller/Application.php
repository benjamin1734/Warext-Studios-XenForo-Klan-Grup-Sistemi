<?php

namespace Warext\Clans\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Application extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission('wxClansManage');
    }

    public function actionIndex()
    {
        $applications = $this->repository('Warext\\Clans:ClanApplication')->findPendingCreateApplications()->fetch();
        return $this->view('Warext\\Clans:ApplicationList', 'wx_clans_admin_applications', ['applications'=>$applications]);
    }

    public function actionApprove(ParameterBag $params)
    {
        $this->assertPostOnly();
        $application = $this->assertApplicationExists($params->application_id);
        $clan = $this->service('Warext\\Clans:Application\\Decision', $application)->approve(\XF::visitor());
        $this->logModeratorAction($clan, 'approve_creation', ['application_id'=>$application->application_id]);
        return $this->redirect($this->buildLink('warext-clans/applications'), 'Clan approved: ' . $clan->title);
    }

    public function actionReject(ParameterBag $params)
    {
        $application = $this->assertApplicationExists($params->application_id);
        if ($this->isPost())
        {
            $reason = $this->filter('reason','str');
            $mode = $this->filter('mode','str');
            $decision = $this->service('Warext\\Clans:Application\\Decision', $application);
            if ($mode === 'changes')
            {
                $decision->requestChanges(\XF::visitor(), $reason);
            }
            else
            {
                $decision->reject(\XF::visitor(), $reason);
            }
            return $this->redirect($this->buildLink('warext-clans/applications'));
        }
        return $this->view('Warext\\Clans:ApplicationReject', 'wx_clans_admin_reject', ['application'=>$application]);
    }

    protected function logModeratorAction(\Warext\Clans\Entity\Clan $clan, string $action, array $params = []): void
    {
        $visitor = \XF::visitor();
        if ($visitor->user_id && ($visitor->is_moderator || $visitor->is_admin))
        {
            \XF::app()->logger()->moderatorLogger()->log('wx_clan', $clan, $action, $params, false, $visitor);
        }
    }

    protected function assertApplicationExists(int $id): \Warext\Clans\Entity\ClanApplication
    {
        return $this->assertRecordExists('Warext\\Clans:ClanApplication', $id, ['User','Clan']);
    }
}
