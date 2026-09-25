<?php

namespace Warext\Clans\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class OwnershipTransfer extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission('wxClansManage');
    }

    public function actionIndex()
    {
        $transfers = $this->finder('Warext\\Clans:ClanOwnershipTransfer')
            ->where('status', 'accepted')
            ->with(['Clan','FromUser','ToUser'])
            ->order('accepted_date', 'ASC')
            ->fetch();
        return $this->view('Warext\\Clans:OwnershipTransferList', 'wx_clans_admin_ownership_transfers', ['transfers'=>$transfers]);
    }

    public function actionApprove(ParameterBag $params)
    {
        $this->assertPostOnly();
        $transfer = $this->assertTransferExists($params->transfer_id);
        if (!$transfer->Clan)
        {
            throw new \XF\PrintableException('Clan not found.');
        }
        $clan = $transfer->Clan;
        $this->service('Warext\\Clans:Ownership\\Manager', $clan)->approve($transfer, \XF::visitor());
        $this->logModeratorAction($clan, 'ownership_transfer_approved', ['transfer_id'=>$transfer->transfer_id]);
        return $this->redirect($this->buildLink('warext-clans/ownership-transfers'));
    }

    public function actionReject(ParameterBag $params)
    {
        $this->assertPostOnly();
        $transfer = $this->assertTransferExists($params->transfer_id);
        if (!$transfer->Clan)
        {
            throw new \XF\PrintableException('Clan not found.');
        }
        $this->service('Warext\\Clans:Ownership\\Manager', $transfer->Clan)->reject($transfer, \XF::visitor(), $this->filter('reason','str'));
        $this->logModeratorAction($transfer->Clan, 'ownership_transfer_rejected', ['transfer_id'=>$transfer->transfer_id]);
        return $this->redirect($this->buildLink('warext-clans/ownership-transfers'));
    }

    protected function logModeratorAction(\Warext\Clans\Entity\Clan $clan, string $action, array $params = []): void
    {
        $visitor = \XF::visitor();
        if ($visitor->user_id && ($visitor->is_moderator || $visitor->is_admin))
        {
            \XF::app()->logger()->moderatorLogger()->log('wx_clan', $clan, $action, $params, false, $visitor);
        }
    }

    protected function assertTransferExists(int $id): \Warext\Clans\Entity\ClanOwnershipTransfer
    {
        return $this->assertRecordExists('Warext\\Clans:ClanOwnershipTransfer', $id, ['Clan','FromUser','ToUser']);
    }
}
