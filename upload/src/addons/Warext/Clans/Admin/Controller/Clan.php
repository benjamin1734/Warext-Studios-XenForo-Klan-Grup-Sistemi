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

        return $this->view('Warext\\Clans:ClanList', 'wx_clans_admin_list', [
            'clans'=>$finder->fetch(), 'status'=>$status, 'query'=>$query, 'category'=>$category,
            'categories'=>$categories, 'page'=>$page, 'perPage'=>$perPage, 'total'=>$total
        ]);
    }

    public function actionStatus(ParameterBag $params)
    {
        $this->assertPostOnly();
        $clan = $this->assertClanExists($params->clan_id);
        $status = $this->filter('status','str');
        if (!in_array($status, ['active','restricted','suspended','closed'], true))
        {
            return $this->error('Invalid clan status.');
        }
        $old = $clan->status;
        $clan->status = $status;
        $clan->save();
        $this->service('Warext\\Clans:Audit\\Logger')->log($clan->clan_id, \XF::visitor()->user_id, 'forum_status_changed', ['old'=>$old,'new'=>$status], 'clan', $clan->clan_id);
        if (\XF::visitor()->user_id && (\XF::visitor()->is_moderator || \XF::visitor()->is_admin))
        {
            \XF::app()->logger()->moderatorLogger()->log('wx_clan', $clan, 'status_update', ['old'=>$old,'new'=>$status], false, \XF::visitor());
        }
        return $this->redirect($this->buildLink('warext-clans'));
    }

    protected function assertClanExists(int $id): \Warext\Clans\Entity\Clan
    {
        return $this->assertRecordExists('Warext\\Clans:Clan', $id, ['Owner']);
    }
}
