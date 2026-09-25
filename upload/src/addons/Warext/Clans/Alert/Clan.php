<?php

namespace Warext\Clans\Alert;

use XF\Alert\AbstractHandler;
use XF\Entity\UserAlert;
use XF\Mvc\Entity\Entity;

class Clan extends AbstractHandler
{
    public function canViewContent(Entity $entity, &$error = null)
    {
        return $entity instanceof \Warext\Clans\Entity\Clan && $entity->canView($error);
    }

    public function getEntityWith()
    {
        return ['Owner'];
    }

    public function getOptOutActions()
    {
        return [
            'invited',
            'member_joined',
            'join_application',
            'application_approved',
            'application_rejected',
            'identity_change_approved',
            'identity_change_rejected',
            'ownership_transfer_requested',
            'ownership_transfer_approved',
            'ownership_transfer_rejected',
            'lifecycle_approved',
            'lifecycle_rejected'
        ];
    }

    public function getOptOutDisplayOrder()
    {
        return 900;
    }

    public function canViewAlert(UserAlert $alert, &$error = null)
    {
        return true;
    }
}
