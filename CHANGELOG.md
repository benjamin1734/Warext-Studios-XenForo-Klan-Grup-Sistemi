# Changelog / Değişiklik Günlüğü

## 0.7.0 Alpha - 2026-09-25

### English

- Added controlled clan close and reopen lifecycle requests.
- Added an Admin CP lifecycle-review queue.
- Added lifecycle approval/rejection alerts, clan audit events and moderator-log integration.
- Allowed owners of closed clans to access the clan context needed to request reopening while preserving suspension restrictions.
- Improved clan list filtering, category/status search and pagination.
- Improved clan management/history surfaces.
- Added dedicated `wxClansManage` Admin CP permission and protected all clan-management controllers.
- Added default XenForo permission bootstrap for Registered, Administrative and Moderating groups without overwriting explicitly configured values.
- Added direct Admin CP settings navigation and option explanations.
- Standardized add-on metadata to XenForo 2.3.0+ / PHP 8.1+ and Warext Studios support metadata.
- Synchronized installable `_data` definitions for lifecycle routes, navigation, phrases and templates.
- Added repository validation and release automation structure.

### Türkçe

- Kontrollü klan kapatma ve yeniden açma talep sistemi eklendi.
- ACP yaşam döngüsü inceleme kuyruğu eklendi.
- Yaşam döngüsü onay/red bildirimleri, klan denetim kayıtları ve moderatör log entegrasyonu eklendi.
- Kapanmış klan Owner'ının yeniden açma talebi için gerekli klan bağlamına erişmesi sağlandı; `suspended` moderasyon durumu korunmaya devam eder.
- Klan listesi arama, kategori/durum filtreleri ve sayfalama geliştirildi.
- Klan yönetim/geçmiş ekranları geliştirildi.
- Ayrı `wxClansManage` ACP izni eklendi ve tüm klan yönetim controller'ları bu izinle korundu.
- Registered, Administrative ve Moderating grupları için mevcut açık izinleri ezmeden varsayılan XenForo izin kurulumu eklendi.
- ACP Ayarlar bağlantısı ve option açıklamaları eklendi.
- Eklenti metadata'sı XenForo 2.3.0+ / PHP 8.1+ ve Warext Studios destek standardına getirildi.
- Yaşam döngüsü route/navigation/phrase/template kayıtları kurulabilir `_data` dosyalarına senkronlandı.
- Repo doğrulama ve release otomasyonu altyapısı eklendi.

## 0.6.0 Alpha - 2026-09-25

- Membership/application/invitation workflows completed.
- Custom clan application fields added.
- Clan owner/manager/member hierarchy and clan-scoped permissions expanded.
- Custom clan roles, blacklist and announcement management added.
- Ownership transfer with candidate acceptance and forum approval added.
- Official clan identity-change approval workflow added.
- Active clan/tag preference and postbit/profile/tooltip display integration added.
- XenForo alerts, Report Center integration for clans and announcements, Moderator Log and clan audit records added.
- Admin CP application, identity and ownership queues added.

## 0.2.0 Alpha - 2026-09-25

- Added Owner / Manager / Member management hierarchy.
- Added manager-specific clan permissions and clan management panel.
- Added role/member management and clan audit visibility controls.
- Added suspended/closed clan management locking.

## 0.1.0 Alpha - 2026-09-25

- Initial add-on architecture.
- Initial clan/application entities and database schema.
- Initial forum-approval flow for clan creation.
- Initial public clan list/profile and Admin CP application views.
