<?php

namespace Warext\Clans\Permission;

final class ClanPermission
{
    public const MANAGE_MEMBERS = 'manage_members';
    public const MANAGE_APPLICATIONS = 'manage_applications';
    public const MANAGE_SETTINGS = 'manage_settings';
    public const MANAGE_ANNOUNCEMENTS = 'manage_announcements';
    public const VIEW_AUDIT_LOG = 'view_audit_log';

    public const OWNER_ONLY = [
        'manage_managers',
        'manage_roles',
        'transfer_ownership',
        'request_identity_change'
    ];

    public static function managerDefinitions(): array
    {
        return [
            self::MANAGE_MEMBERS => 'Manage clan members and invitations',
            self::MANAGE_APPLICATIONS => 'Manage join applications and application form',
            self::MANAGE_SETTINGS => 'Manage clan profile settings',
            self::MANAGE_ANNOUNCEMENTS => 'Manage clan announcements',
            self::VIEW_AUDIT_LOG => 'View clan audit log'
        ];
    }

    public static function defaultManagerPermissions(): array
    {
        return [
            self::MANAGE_MEMBERS => true,
            self::MANAGE_APPLICATIONS => true,
            self::MANAGE_SETTINGS => false,
            self::MANAGE_ANNOUNCEMENTS => true,
            self::VIEW_AUDIT_LOG => false
        ];
    }

    public static function sanitizeManagerPermissions(array $permissions): array
    {
        $clean = [];
        foreach (array_keys(self::managerDefinitions()) as $permissionId)
        {
            $clean[$permissionId] = !empty($permissions[$permissionId]);
        }
        return $clean;
    }
}
