# Warext Studios XenForo Clan & Group System - Technical Architecture

## Version

`0.9.0 Alpha`

## Add-on ID

`Warext/Clans`

Source directory:

`upload/src/addons/Warext/Clans`

## Core design rule

The add-on treats **forum authority** and **clan authority** as different security domains.

XenForo user groups / moderator permissions control the forum. Clan membership records and clan roles control only the clan.

A `Manager` row inside a clan is not a XenForo moderator role. No code path should convert clan rank into thread/post/user moderation rights.

## Authority hierarchy

Forum side:

`XenForo administrator / authorized clan moderator`

Clan side:

`Owner → Manager → custom roles / Member`

Forum authority may approve, reject, suspend or otherwise moderate official clans according to XenForo permissions. Clan authority operates only inside the selected clan.

## Main entities and tables

### `xf_wx_clan`

Official clan record.

Stores the current owner, public identity, category, join mode, member/announcement visibility, manager banner/tag colors, media URLs, member count and moderation/lifecycle status.

Important statuses include active, restricted/suspended and closed states used by service-level checks.

### `xf_wx_clan_role`

Clan-local roles.

Base roles are Owner, Manager and Member. Additional custom roles can be created without creating XenForo user groups.

The role permission payload contains only clan-scoped permissions.

### `xf_wx_clan_member`

Membership relation between a XenForo user and a clan.

Stores role assignment, member state, owner/manager flags, join date and activity metadata.

The composite `(clan_id, user_id)` primary key prevents duplicate membership rows.

### `xf_wx_clan_application`

Unified workflow table for official requests and clan membership applications.

Application types include clan creation, join/identity-related flows and lifecycle requests such as close/reopen.

Decision actor/time/reason fields preserve review history.

### `xf_wx_clan_application_field`

Per-clan custom membership form definition.

Supports independent field keys, types, options, required state and display ordering.

### `xf_wx_clan_application_answer`

Answers submitted for a membership application.

Answers remain linked to the application and field identifiers.

### `xf_wx_clan_invitation`

Clan invitation records with sender, receiver, state and expiry time.

Invitation acceptance validates current eligibility again rather than trusting the original send-time state.

### `xf_wx_clan_ownership_transfer`

Controlled Owner-transfer workflow.

Tracks old owner, proposed new owner, candidate response and forum review state.

### `xf_wx_clan_blacklist`

Clan-local blocklist used to prevent a specific user from joining/applying while the entry is active.

This is not a XenForo ban and cannot restrict the user's forum access.

### `xf_wx_clan_announcement`

Clan announcement content with author, pin state and timestamps.

Announcements are independently reportable through XenForo Report Center.

### `xf_wx_clan_user_pref`

Stores the user's preferred active clan for tag/banner display.

The preference is validated against current active membership when consumed.

### `xf_wx_clan_audit_log`

Clan-internal audit trail.

Stores actor, target, action, optional metadata and timestamp for important clan operations.

## Service boundaries

### Application services

Creation applications are submitted through dedicated application services and reviewed through a decision service. Approval creates the official clan and base membership/roles.

### `Service/Clan/MemberManager`

Responsible for membership state changes and hierarchy-aware member operations.

It must prevent managers from managing users at or above protected hierarchy levels.

### `Service/Clan/RoleManager`

Creates/edits clan-local custom roles and assigns eligible roles while preserving Owner/Manager invariants.

### `Service/Join/Manager`

Processes open joins and application-based joins. Limits, blacklist state and duplicate records are checked server-side.

### `Service/Invitation/Manager`

Creates and resolves invitations, including expiry and eligibility checks.

### `Service/Ownership/Manager`

Handles ownership transfer request, target acceptance/rejection, forum review and final atomic role/ownership mutation.

### `Service/Identity/Manager`

Handles owner requests for protected public clan identity changes and applies them only after the required forum decision.

### `Service/Clan/LifecycleManager`

Handles close/reopen requests.

A clan Owner cannot use reopen to bypass a forum suspension. Close/reopen decisions remain forum-reviewed lifecycle operations.

### `Service/Maintenance`

Runs periodic integrity maintenance outside public GET requests. It expires overdue invitations, cancels stale ownership/Owner-bound requests, repairs invalid display preferences and reconciles cached clan member counts. The same service is used by cron and the Admin CP manual-maintenance action.

### `Service/Moderation/StatusManager`

Centralizes forum-side clan status changes, optional moderation reasons, clan audit events, XenForo Moderator Log entries and Owner alerts.

### `Service/Ownership/AdminTransfer`

Provides the forum-authority recovery path when the normal Owner transfer flow cannot be completed. The target must already be an eligible active clan member. The operation atomically rewrites owner/member flags and cancels stale owner-bound requests.

### `Service/Clan/BlacklistManager`

Manages clan-local blacklist records without touching XenForo user-ban state.

### `Service/Clan/AnnouncementManager`

Creates/edits/deletes announcements and records the action in clan audit history.

### `Service/Audit/Logger`

Creates immutable-style append-only clan audit events for important management operations.

## Permission layers

### XenForo public permissions

Permission group: `wxClans`

- `view`
- `apply`
- `moderate`

`moderate` is forum authority over clan content. It is not granted by clan Manager status.

### Admin CP permission

`wxClansManage`

All add-on-specific Admin CP controllers assert this permission. Navigation visibility is also wired to the permission.

### Clan permissions

Clan permissions are stored in clan role data and evaluated through the add-on's `ClanPermission` layer.

They cover clan-only operations such as members, applications, invitations, announcements, settings and audit access.

## XenForo integration

### Alerts

`wx_clan` has an alert handler. Application decisions, invitations, membership/manager events, ownership transfer and lifecycle decisions use XenForo native alerts.

### Report Center

Content types:

- `wx_clan`
- `wx_clan_announcement`

Both have entities and report handlers registered through XenForo content-type fields.

### Moderator Log

`wx_clan` registers a moderator-log handler for forum-side clan moderation actions.

Clan-internal manager actions do not use this log; they go to `xf_wx_clan_audit_log`.

### User display integration

The `XF\Entity\User` class is extended by `Warext\Clans\XF\Entity\User` to expose clan-display data.

Template modifications integrate approved clan tag/manager banner display into XenForo member/message surfaces without modifying core templates. Member profiles may enumerate all active memberships, while message/tooltip identity remains tied to the user-selected active clan.

## Reserved identity rules

Reserved tag and clan-name lists are stored as XenForo options. They are normalized before comparison and enforced in clan-creation and identity-change services.

Availability is checked both when a request is submitted and again when staff approves it. This prevents an old pending request from bypassing a newly reserved value or an identity claimed while the request was waiting.

## Capacity and anti-spam enforcement

Global XenForo options can limit active members per clan and Manager-role members per clan. These checks live in service classes rather than templates, so crafted requests cannot bypass them.

Join applications and repeated invitations can also use configurable cooldown windows. Cooldown checks are scoped to the same clan/user pair and can be disabled with a zero value.

## Clan privacy model

Member-list visibility is evaluated by the Clan entity and may be public, active-members only or Owner/forum-staff only. Announcement visibility may be public or active-members only. Controllers avoid loading protected collections when the current visitor cannot view them.

## Maintenance model

Public GET endpoints do not change invitation expiry state. Scheduled maintenance performs expiry and other integrity repairs. This keeps read requests read-only and makes cleanup independent from traffic to a particular clan page.

The maintenance service is intentionally idempotent: running it repeatedly should converge to the same consistent state.

## Lifecycle state rules

`active` is the normal usable state.

A closed clan preserves historical data and can be reopened only through the lifecycle workflow.

A forum-suspended clan remains under forum authority; Owner actions cannot silently turn it active.

Management services check clan operability before modifying protected state.

## Transaction boundaries

Critical multi-record operations should run inside database transactions, especially:

- Membership acceptance
- Invitation acceptance
- Ownership transfer finalization
- Identity decision application
- Blacklist/member state changes where multiple records must remain consistent
- Lifecycle decisions that change official state and request state together

Notifications should be emitted after the committed state exists whenever practical.

## Upgrade strategy

`Setup.php` owns schema installation and versioned upgrades.

Existing installations are upgraded in place. Upgrade packages must not require resetting the XenForo database or reinstalling the add-on.

Version `0.2.0` introduced base role normalization. Version `0.5.0` introduced later tables/fields needed by ownership, blacklist, announcements and active preferences. Version `0.7.0` added lifecycle behavior without requiring destructive schema reset. Version `0.8.0` adds option/cron/service behavior and does not require a destructive schema reset. Version `0.9.0` adds two clan privacy columns through an in-place schema upgrade plus service-level capacity/cooldown controls.

## Repository layout

The repository stores installable source under `upload/` and XenForo exported install data under `_data`.

Development-only `_output` is not committed to keep the public repository and release package focused on source required for installation.

GitHub Actions validates source/metadata and creates the direct-install release ZIP from `upload/`.
