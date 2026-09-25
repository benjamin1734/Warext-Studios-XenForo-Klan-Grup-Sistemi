<?php

namespace Warext\Clans\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class LifecycleRequest extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission('wxClansManage');
    }

    public function actionIndex()
    {
        $requests = $this->repository('Warext\\Clans:ClanApplication')->findPendingLifecycleRequests()->fetch();
        return $this->view('Warext\\Clans:LifecycleRequests', 'wx_clans_admin_lifecycle_requests', ['requests' => $requests]);
    }

    public function actionApprove(ParameterBag $params)
    {
        $this->assertPostOnly();
        $request = $this->assertRequestExists($params->application_id);
        $clan = $request->Clan;
        if (!$clan)
        {
            return $this->error('Clan not found.');
        }
        $this->service('Warext\\Clans:Clan\\LifecycleManager', $clan)->decide($request, true, \XF::visitor(), $this->filter('reason', 'str'));
        return $this->redirect($this->buildLink('warext-clans/lifecycle-requests'));
    }

    public function actionReject(ParameterBag $params)
    {
        $request = $this->assertRequestExists($params->application_id);
        if ($this->isPost())
        {
            $clan = $request->Clan;
            if (!$clan)
            {
                return $this->error('Clan not found.');
            }
            $this->service('Warext\\Clans:Clan\\LifecycleManager', $clan)->decide($request, false, \XF::visitor(), $this->filter('reason', 'str'));
            return $this->redirect($this->buildLink('warext-clans/lifecycle-requests'));
        }
        return $this->view('Warext\\Clans:RejectLifecycle', 'wx_clans_admin_reject', [
            'action' => $this->buildLink('warext-clans/lifecycle-requests/reject', $request),
            'title' => 'Reject clan lifecycle request'
        ]);
    }

    protected function assertRequestExists(int $id): \Warext\Clans\Entity\ClanApplication
    {
        $request = $this->assertRecordExists('Warext\\Clans:ClanApplication', $id, ['User', 'Clan.Owner']);
        if (!$request->isLifecycleRequest())
        {
            throw $this->exception($this->notFound());
        }
        return $request;
    }
}
