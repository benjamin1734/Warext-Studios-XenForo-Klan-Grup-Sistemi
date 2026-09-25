<?php

namespace Warext\Clans\Service;

use XF\Service\AbstractService;

class Maintenance extends AbstractService
{
    public function run(): array
    {
        $time = \XF::$time;
        $expiredInvitations = $this->db()->update(
            'xf_wx_clan_invitation',
            ['status' => 'expired'],
            "status = 'pending' AND expiry_date > 0 AND expiry_date <= ?",
            $time
        );

        $cancelledTransfers = $this->db()->query(
            "UPDATE xf_wx_clan_ownership_transfer t
                INNER JOIN xf_wx_clan c ON c.clan_id = t.clan_id
             SET t.status = 'cancelled',
                 t.approved_date = ?,
                 t.decision_reason = 'Cancelled automatically because clan ownership changed.'
             WHERE t.status IN ('pending', 'accepted')
               AND t.from_user_id <> c.owner_user_id",
            [$time]
        )->rowsAffected();

        $cancelledRequests = $this->db()->query(
            "UPDATE xf_wx_clan_application a
                INNER JOIN xf_wx_clan c ON c.clan_id = a.clan_id
             SET a.status = 'cancelled',
                 a.decision_date = ?,
                 a.decision_reason = 'Cancelled automatically because clan ownership changed.'
             WHERE a.application_type IN ('change', 'close', 'reopen')
               AND a.status = 'pending'
               AND a.user_id <> c.owner_user_id",
            [$time]
        )->rowsAffected();

        $clearedPreferences = $this->db()->query(
            "UPDATE xf_wx_clan_user_pref p
                LEFT JOIN xf_wx_clan_member m
                    ON m.clan_id = p.active_clan_id
                   AND m.user_id = p.user_id
                   AND m.member_state = 'active'
                LEFT JOIN xf_wx_clan c
                    ON c.clan_id = p.active_clan_id
             SET p.active_clan_id = 0
             WHERE p.active_clan_id <> 0
               AND (m.user_id IS NULL OR c.clan_id IS NULL OR c.status NOT IN ('active', 'restricted'))"
        )->rowsAffected();

        $recountedClans = $this->db()->query(
            "UPDATE xf_wx_clan c
                LEFT JOIN (
                    SELECT clan_id, COUNT(*) AS member_count
                    FROM xf_wx_clan_member
                    WHERE member_state = 'active'
                    GROUP BY clan_id
                ) m ON m.clan_id = c.clan_id
             SET c.member_count = COALESCE(m.member_count, 0)
             WHERE c.member_count <> COALESCE(m.member_count, 0)"
        )->rowsAffected();

        return [
            'expired_invitations' => $expiredInvitations,
            'cancelled_transfers' => $cancelledTransfers,
            'cancelled_requests' => $cancelledRequests,
            'cleared_preferences' => $clearedPreferences,
            'recounted_clans' => $recountedClans
        ];
    }

    public function getStats(): array
    {
        $time = \XF::$time;

        return [
            'pending_expired_invitations' => (int)$this->db()->fetchOne(
                "SELECT COUNT(*) FROM xf_wx_clan_invitation WHERE status = 'pending' AND expiry_date > 0 AND expiry_date <= ?",
                $time
            ),
            'stale_transfers' => (int)$this->db()->fetchOne(
                "SELECT COUNT(*)
                 FROM xf_wx_clan_ownership_transfer t
                 INNER JOIN xf_wx_clan c ON c.clan_id = t.clan_id
                 WHERE t.status IN ('pending', 'accepted') AND t.from_user_id <> c.owner_user_id"
            ),
            'stale_owner_requests' => (int)$this->db()->fetchOne(
                "SELECT COUNT(*)
                 FROM xf_wx_clan_application a
                 INNER JOIN xf_wx_clan c ON c.clan_id = a.clan_id
                 WHERE a.application_type IN ('change', 'close', 'reopen')
                   AND a.status = 'pending'
                   AND a.user_id <> c.owner_user_id"
            ),
            'invalid_preferences' => (int)$this->db()->fetchOne(
                "SELECT COUNT(*)
                 FROM xf_wx_clan_user_pref p
                 LEFT JOIN xf_wx_clan_member m
                    ON m.clan_id = p.active_clan_id
                   AND m.user_id = p.user_id
                   AND m.member_state = 'active'
                 LEFT JOIN xf_wx_clan c ON c.clan_id = p.active_clan_id
                 WHERE p.active_clan_id <> 0
                   AND (m.user_id IS NULL OR c.clan_id IS NULL OR c.status NOT IN ('active', 'restricted'))"
            ),
            'active_preferences' => (int)$this->db()->fetchOne(
                "SELECT COUNT(*) FROM xf_wx_clan_user_pref WHERE active_clan_id <> 0"
            ),
            'clans' => (int)$this->db()->fetchOne("SELECT COUNT(*) FROM xf_wx_clan")
        ];
    }
}
