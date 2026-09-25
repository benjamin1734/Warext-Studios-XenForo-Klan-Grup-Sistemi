<?php

namespace Warext\Clans\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class IdentityChange extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission('wxClansManage');
    }

    public function actionIndex()
    {
        $requests = $this->repository('Warext\\Clans:ClanApplication')->findPendingChangeApplications()->fetch();
        return $this->view('Warext\\Clans:IdentityChangeList', 'wx_clans_admin_identity_changes', ['requests'=>$requests]);
    }

    public function actionApprove(ParameterBag $params)
    {
        $this->assertPostOnly();
        $application = $this->assertApplicationExists($params->application_id);
        if (!$application->Clan)
        {
            throw new \XF\PrintableException('Clan not found.');
        }
        $this->service('Warext\\Clans:Identity\\Manager', $application->Clan)->approve($application, \XF::visitor());
        $this->logModeratorAction($application->Clan, 'identity_change_approved', ['application_id'=>$application->application_id]);
        return $this->redirect($this->buildLink('warext-clans/identity-changes'));
    }

    public function actionReject(ParameterBag $params)
    {
        $this->assertPostOnly();
        $application = $this->assertApplicationExists($params->application_id);
        if (!$application->Clan)
        {
            throw new \XF\PrintableException('Clan not found.');
        }
        $this->service('Warext\\Clans:Identity\\Manager', $application->Clan)->reject($application, \XF::visitor(), $this->filter('reason','str'));
        $this->logModeratorAction($application->Clan, 'identity_change_rejected', ['application_id'=>$application->application_id]);
        return $this->redirect($this->buildLink('warext-clans/identity-changes'));
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
