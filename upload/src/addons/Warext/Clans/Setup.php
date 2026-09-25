<?php

namespace Warext\Clans;

use Warext\Clans\Permission\ClanPermission;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1(): void
    {
        $sm = $this->schemaManager();

        $sm->createTable('xf_wx_clan', function (Create $table)
        {
            $table->addColumn('clan_id', 'int')->autoIncrement();
            $table->addColumn('owner_user_id', 'int')->setDefault(0);
            $table->addColumn('title', 'varchar', 100)->setDefault('');
            $table->addColumn('tag', 'varchar', 24)->setDefault('');
            $table->addColumn('slug', 'varchar', 100)->setDefault('');
            $table->addColumn('description', 'mediumtext')->nullable(true);
            $table->addColumn('rules', 'mediumtext')->nullable(true);
            $table->addColumn('category', 'varchar', 50)->setDefault('');
            $table->addColumn('status', 'varchar', 25)->setDefault('active');
            $table->addColumn('join_mode', 'varchar', 25)->setDefault('application');
            $table->addColumn('member_list_visibility', 'varchar', 20)->setDefault('public');
            $table->addColumn('announcement_visibility', 'varchar', 20)->setDefault('public');
            $table->addColumn('manager_banner', 'varchar', 100)->setDefault('');
            $table->addColumn('tag_color', 'varchar', 7)->setDefault('#4f46e5');
            $table->addColumn('manager_banner_color', 'varchar', 7)->setDefault('#805ad5');
            $table->addColumn('logo_url', 'varchar', 255)->setDefault('');
            $table->addColumn('cover_url', 'varchar', 255)->setDefault('');
            $table->addColumn('member_count', 'int')->setDefault(1);
            $table->addColumn('created_date', 'int')->setDefault(0);
            $table->addColumn('approved_date', 'int')->setDefault(0);
            $table->addColumn('approved_by', 'int')->setDefault(0);
            $table->addUniqueKey('tag', 'tag');
            $table->addKey('owner_user_id', 'owner_user_id');
            $table->addKey(['status', 'created_date'], 'status_created');
        });

        $sm->createTable('xf_wx_clan_role', function (Create $table)
        {
            $table->addColumn('role_id', 'int')->autoIncrement();
            $table->addColumn('clan_id', 'int')->setDefault(0);
            $table->addColumn('title', 'varchar', 75)->setDefault('');
            $table->addColumn('role_type', 'varchar', 20)->setDefault('custom');
            $table->addColumn('display_order', 'int')->setDefault(100);
            $table->addColumn('permissions', 'mediumblob')->nullable(true);
            $table->addColumn('created_date', 'int')->setDefault(0);
            $table->addKey(['clan_id', 'display_order'], 'clan_order');
        });

        $sm->createTable('xf_wx_clan_member', function (Create $table)
        {
            $table->addColumn('clan_id', 'int')->setDefault(0);
            $table->addColumn('user_id', 'int')->setDefault(0);
            $table->addColumn('role_id', 'int')->setDefault(0);
            $table->addColumn('member_state', 'varchar', 20)->setDefault('active');
            $table->addColumn('is_owner', 'tinyint')->setDefault(0);
            $table->addColumn('is_manager', 'tinyint')->setDefault(0);
            $table->addColumn('join_date', 'int')->setDefault(0);
            $table->addColumn('last_activity', 'int')->setDefault(0);
            $table->addPrimaryKey(['clan_id', 'user_id']);
            $table->addKey('user_id', 'user_id');
            $table->addKey('role_id', 'role_id');
        });

        $sm->createTable('xf_wx_clan_application', function (Create $table)
        {
            $table->addColumn('application_id', 'int')->autoIncrement();
            $table->addColumn('application_type', 'varchar', 20)->setDefault('create');
            $table->addColumn('clan_id', 'int')->setDefault(0);
            $table->addColumn('user_id', 'int')->setDefault(0);
            $table->addColumn('title', 'varchar', 100)->setDefault('');
            $table->addColumn('tag', 'varchar', 24)->setDefault('');
            $table->addColumn('description', 'mediumtext')->nullable(true);
            $table->addColumn('category', 'varchar', 50)->setDefault('');
            $table->addColumn('requested_manager_banner', 'varchar', 100)->setDefault('');
            $table->addColumn('requested_tag_color', 'varchar', 7)->setDefault('#4f46e5');
            $table->addColumn('requested_manager_banner_color', 'varchar', 7)->setDefault('#805ad5');
            $table->addColumn('status', 'varchar', 20)->setDefault('pending');
            $table->addColumn('create_date', 'int')->setDefault(0);
            $table->addColumn('decision_date', 'int')->setDefault(0);
            $table->addColumn('decision_user_id', 'int')->setDefault(0);
            $table->addColumn('decision_reason', 'text')->nullable(true);
            $table->addColumn('created_clan_id', 'int')->setDefault(0);
            $table->addKey(['status', 'create_date'], 'status_date');
            $table->addKey(['application_type', 'status'], 'type_status');
            $table->addKey('user_id', 'user_id');
            $table->addKey('clan_id', 'clan_id');
        });

        $sm->createTable('xf_wx_clan_application_field', function (Create $table)
        {
            $table->addColumn('field_id', 'int')->autoIncrement();
            $table->addColumn('clan_id', 'int')->setDefault(0);
            $table->addColumn('field_key', 'varchar', 50)->setDefault('');
            $table->addColumn('title', 'varchar', 100)->setDefault('');
            $table->addColumn('field_type', 'varchar', 25)->setDefault('text');
            $table->addColumn('field_options', 'mediumblob')->nullable(true);
            $table->addColumn('required', 'tinyint')->setDefault(0);
            $table->addColumn('display_order', 'int')->setDefault(100);
            $table->addColumn('active', 'tinyint')->setDefault(1);
            $table->addUniqueKey(['clan_id', 'field_key'], 'clan_field');
        });

        $sm->createTable('xf_wx_clan_application_answer', function (Create $table)
        {
            $table->addColumn('application_id', 'int')->setDefault(0);
            $table->addColumn('field_id', 'int')->setDefault(0);
            $table->addColumn('answer', 'mediumtext')->nullable(true);
            $table->addPrimaryKey(['application_id', 'field_id']);
        });

        $sm->createTable('xf_wx_clan_invitation', function (Create $table)
        {
            $table->addColumn('invitation_id', 'int')->autoIncrement();
            $table->addColumn('clan_id', 'int')->setDefault(0);
            $table->addColumn('user_id', 'int')->setDefault(0);
            $table->addColumn('invited_by', 'int')->setDefault(0);
            $table->addColumn('status', 'varchar', 20)->setDefault('pending');
            $table->addColumn('create_date', 'int')->setDefault(0);
            $table->addColumn('expiry_date', 'int')->setDefault(0);
            $table->addKey(['clan_id', 'user_id', 'status'], 'clan_user_status');
            $table->addKey(['user_id', 'status'], 'user_status');
        });

        $sm->createTable('xf_wx_clan_ownership_transfer', function (Create $table)
        {
            $table->addColumn('transfer_id', 'int')->autoIncrement();
            $table->addColumn('clan_id', 'int')->setDefault(0);
            $table->addColumn('from_user_id', 'int')->setDefault(0);
            $table->addColumn('to_user_id', 'int')->setDefault(0);
            $table->addColumn('status', 'varchar', 20)->setDefault('pending');
            $table->addColumn('create_date', 'int')->setDefault(0);
            $table->addColumn('accepted_date', 'int')->setDefault(0);
            $table->addColumn('approved_by', 'int')->setDefault(0);
            $table->addColumn('approved_date', 'int')->setDefault(0);
            $table->addColumn('decision_reason', 'text')->nullable(true);
            $table->addKey(['clan_id', 'status'], 'clan_status');
            $table->addKey(['to_user_id', 'status'], 'recipient_status');
        });

        $sm->createTable('xf_wx_clan_blacklist', function (Create $table)
        {
            $table->addColumn('blacklist_id', 'int')->autoIncrement();
            $table->addColumn('clan_id', 'int')->setDefault(0);
            $table->addColumn('user_id', 'int')->setDefault(0);
            $table->addColumn('added_by', 'int')->setDefault(0);
            $table->addColumn('reason', 'varchar', 255)->setDefault('');
            $table->addColumn('expiry_date', 'int')->setDefault(0);
            $table->addColumn('create_date', 'int')->setDefault(0);
            $table->addUniqueKey(['clan_id', 'user_id'], 'clan_user');
            $table->addKey(['user_id', 'expiry_date'], 'user_expiry');
        });

        $sm->createTable('xf_wx_clan_announcement', function (Create $table)
        {
            $table->addColumn('announcement_id', 'int')->autoIncrement();
            $table->addColumn('clan_id', 'int')->setDefault(0);
            $table->addColumn('user_id', 'int')->setDefault(0);
            $table->addColumn('title', 'varchar', 120)->setDefault('');
            $table->addColumn('message', 'mediumtext')->nullable(true);
            $table->addColumn('is_pinned', 'tinyint')->setDefault(0);
            $table->addColumn('create_date', 'int')->setDefault(0);
            $table->addColumn('update_date', 'int')->setDefault(0);
            $table->addKey(['clan_id', 'is_pinned', 'create_date'], 'clan_order');
        });

        $sm->createTable('xf_wx_clan_user_pref', function (Create $table)
        {
            $table->addColumn('user_id', 'int')->setDefault(0);
            $table->addColumn('active_clan_id', 'int')->setDefault(0);
            $table->addPrimaryKey('user_id');
            $table->addKey('active_clan_id', 'active_clan_id');
        });

        $sm->createTable('xf_wx_clan_audit_log', function (Create $table)
        {
            $table->addColumn('log_id', 'bigint')->autoIncrement();
            $table->addColumn('clan_id', 'int')->setDefault(0);
            $table->addColumn('user_id', 'int')->setDefault(0);
            $table->addColumn('action', 'varchar', 75)->setDefault('');
            $table->addColumn('content_type', 'varchar', 40)->setDefault('');
            $table->addColumn('content_id', 'int')->setDefault(0);
            $table->addColumn('details', 'mediumblob')->nullable(true);
            $table->addColumn('log_date', 'int')->setDefault(0);
            $table->addKey(['clan_id', 'log_date'], 'clan_date');
            $table->addKey(['user_id', 'log_date'], 'user_date');
        });
    }

    public function installStep2(): void
    {
        $this->ensureBaseRoles();
    }

    public function upgrade1000020Step1(): void
    {
        $this->ensureBaseRoles();
    }

    public function upgrade1000050Step1(): void
    {
        $sm = $this->schemaManager();

        $sm->alterTable('xf_wx_clan', function (Alter $table)
        {
            $table->addColumn('tag_color', 'varchar', 7)->setDefault('#4f46e5');
            $table->addColumn('manager_banner_color', 'varchar', 7)->setDefault('#805ad5');
            $table->addColumn('logo_url', 'varchar', 255)->setDefault('');
            $table->addColumn('cover_url', 'varchar', 255)->setDefault('');
            $table->addColumn('rules', 'mediumtext')->nullable(true);
        });

        $sm->alterTable('xf_wx_clan_application', function (Alter $table)
        {
            $table->addColumn('requested_tag_color', 'varchar', 7)->setDefault('#4f46e5');
            $table->addColumn('requested_manager_banner_color', 'varchar', 7)->setDefault('#805ad5');
            $table->addKey(['application_type', 'status'], 'type_status');
        });

        $sm->alterTable('xf_wx_clan_invitation', function (Alter $table)
        {
            $table->addKey(['user_id', 'status'], 'user_status');
        });

        $sm->alterTable('xf_wx_clan_ownership_transfer', function (Alter $table)
        {
            $table->addColumn('decision_reason', 'text')->nullable(true);
            $table->addKey(['to_user_id', 'status'], 'recipient_status');
        });

        $sm->createTable('xf_wx_clan_blacklist', function (Create $table)
        {
            $table->addColumn('blacklist_id', 'int')->autoIncrement();
            $table->addColumn('clan_id', 'int')->setDefault(0);
            $table->addColumn('user_id', 'int')->setDefault(0);
            $table->addColumn('added_by', 'int')->setDefault(0);
            $table->addColumn('reason', 'varchar', 255)->setDefault('');
            $table->addColumn('expiry_date', 'int')->setDefault(0);
            $table->addColumn('create_date', 'int')->setDefault(0);
            $table->addUniqueKey(['clan_id', 'user_id'], 'clan_user');
            $table->addKey(['user_id', 'expiry_date'], 'user_expiry');
        });

        $sm->createTable('xf_wx_clan_announcement', function (Create $table)
        {
            $table->addColumn('announcement_id', 'int')->autoIncrement();
            $table->addColumn('clan_id', 'int')->setDefault(0);
            $table->addColumn('user_id', 'int')->setDefault(0);
            $table->addColumn('title', 'varchar', 120)->setDefault('');
            $table->addColumn('message', 'mediumtext')->nullable(true);
            $table->addColumn('is_pinned', 'tinyint')->setDefault(0);
            $table->addColumn('create_date', 'int')->setDefault(0);
            $table->addColumn('update_date', 'int')->setDefault(0);
            $table->addKey(['clan_id', 'is_pinned', 'create_date'], 'clan_order');
        });

        $sm->createTable('xf_wx_clan_user_pref', function (Create $table)
        {
            $table->addColumn('user_id', 'int')->setDefault(0);
            $table->addColumn('active_clan_id', 'int')->setDefault(0);
            $table->addPrimaryKey('user_id');
            $table->addKey('active_clan_id', 'active_clan_id');
        });
    }

    public function upgrade1000050Step2(): void
    {
        $this->ensureBaseRoles();
    }

    public function upgrade1000090Step1(): void
    {
        $sm = $this->schemaManager();

        $sm->alterTable('xf_wx_clan', function (Alter $table)
        {
            $table->addColumn('member_list_visibility', 'varchar', 20)->setDefault('public');
            $table->addColumn('announcement_visibility', 'varchar', 20)->setDefault('public');
        });
    }

    protected function ensureBaseRoles(): void
    {
        $db = $this->db();
        $clans = $db->fetchAll('SELECT clan_id FROM xf_wx_clan');

        foreach ($clans as $clanRow)
        {
            $clanId = (int)$clanRow['clan_id'];
            $roleDefinitions = [
                'owner' => ['Owner', 1, []],
                'manager' => ['Manager', 10, ClanPermission::defaultManagerPermissions()],
                'member' => ['Member', 100, []]
            ];

            foreach ($roleDefinitions as $roleType => [$title, $displayOrder, $permissions])
            {
                $exists = $db->fetchOne(
                    'SELECT role_id FROM xf_wx_clan_role WHERE clan_id = ? AND role_type = ? LIMIT 1',
                    [$clanId, $roleType]
                );

                if (!$exists)
                {
                    $db->insert('xf_wx_clan_role', [
                        'clan_id' => $clanId,
                        'title' => $title,
                        'role_type' => $roleType,
                        'display_order' => $displayOrder,
                        'permissions' => json_encode($permissions),
                        'created_date' => \XF::$time
                    ]);
                }
            }
        }
    }


    public function postInstall(array &$stateChanges): void
    {
        $this->applyDefaultPermissions();
    }

    public function postUpgrade($previousVersion, array &$stateChanges): void
    {
        $this->applyDefaultPermissions();
    }

    protected function applyDefaultPermissions(): void
    {
        $this->applyMissingGroupPermissions(2, ['view', 'apply']);
        $this->applyMissingGroupPermissions(3, ['view', 'apply', 'moderate']);
        $this->applyMissingGroupPermissions(4, ['view', 'apply', 'moderate']);

        $db = $this->app->db();
        $db->query("INSERT IGNORE INTO xf_admin_permission_entry (user_id, admin_permission_id)
            SELECT user_id, 'wxClansManage' FROM xf_admin");
        \XF::repository('XF:AdminPermission')->rebuildAdminPermissionCache();
    }

    protected function applyMissingGroupPermissions(int $userGroupId, array $permissionIds): void
    {
        $userGroup = \XF::em()->find('XF:UserGroup', $userGroupId);
        if (!$userGroup)
        {
            return;
        }

        $permissionRepo = \XF::repository('XF:PermissionEntry');
        $existing = $permissionRepo->getGlobalUserGroupPermissionEntries($userGroupId);
        $configured = $existing['wxClans'] ?? [];
        $values = [];

        foreach ($permissionIds as $permissionId)
        {
            if (!array_key_exists($permissionId, $configured))
            {
                $values[$permissionId] = 'allow';
            }
        }

        if (!$values)
        {
            return;
        }

        $service = \XF::service('XF:UpdatePermissions');
        $service->setUserGroup($userGroup);
        $service->setGlobal();
        $service->updatePermissions(['wxClans' => $values]);
    }

    protected function removeInstalledPermissions(): void
    {
        $db = $this->app->db();
        $db->delete('xf_permission_entry', 'permission_group_id = ?', 'wxClans');
        $db->delete('xf_admin_permission_entry', 'admin_permission_id = ?', 'wxClansManage');

        if ($this->app->container()->isCached('permission.builder'))
        {
            $this->app->permissionBuilder()->refreshData();
        }

        \XF::repository('XF:AdminPermission')->rebuildAdminPermissionCache();
    }

    public function uninstallStep1(): void
    {
        $sm = $this->schemaManager();
        foreach ([
            'xf_wx_clan_audit_log',
            'xf_wx_clan_user_pref',
            'xf_wx_clan_announcement',
            'xf_wx_clan_blacklist',
            'xf_wx_clan_ownership_transfer',
            'xf_wx_clan_invitation',
            'xf_wx_clan_application_answer',
            'xf_wx_clan_application_field',
            'xf_wx_clan_application',
            'xf_wx_clan_member',
            'xf_wx_clan_role',
            'xf_wx_clan'
        ] as $table)
        {
            $sm->dropTable($table);
        }
    }

    public function uninstallStep2(): void
    {
        $this->removeInstalledPermissions();
    }

}
