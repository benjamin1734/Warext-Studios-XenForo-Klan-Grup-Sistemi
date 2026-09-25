# Changelog / Değişiklik Günlüğü

## 0.9.0 Alpha - 2026-09-25

### English

- Added global per-clan active member and Manager limits enforced in the service layer.
- Added join-application and invitation resend cooldowns to reduce repeated application/invite spam.
- Added clan privacy controls for member lists and announcements.
- Added an Admin CP system overview with clan states, active memberships and pending review queues.
- Added XenForo schema upgrade support for the new privacy fields.
- Expanded CI checks for 0.9 privacy, limits, cooldowns and option metadata.

### Türkçe

- Servis katmanında zorlanan global klan başına aktif üye ve Manager limitleri eklendi.
- Tekrarlanan başvuru/davet spamini azaltmak için üyelik başvurusu ve davet yeniden gönderme cooldown ayarları eklendi.
- Üye listesi ve duyurular için klan gizlilik seçenekleri eklendi.
- ACP ana klan ekranına durumlar, aktif üyelikler ve bekleyen inceleme kuyruklarını gösteren sistem özeti eklendi.
- Yeni gizlilik alanları için XenForo schema upgrade adımı eklendi.
- CI kontrolleri 0.9 gizlilik, limit, cooldown ve option metadata alanlarını kapsayacak şekilde genişletildi.

## 0.8.0 Alpha - 2026-09-25

### English

- Added forum-managed reserved clan tags and clan names to prevent misleading or protected identities from being requested or approved.
- Revalidated reserved/duplicate clan identities at both submission and approval time to prevent stale approval races.
- Added daily clan maintenance cron and a manual Admin CP maintenance screen.
- Maintenance now expires stale invitations, cancels ownership transfers and owner-bound requests invalidated by ownership changes, repairs invalid active-clan preferences and reconciles cached member counts.
- Removed invitation-expiry database writes from public GET pages; expiry state is now maintained by the maintenance service.
- Added forced ownership transfer for authorized forum administrators, including old-owner Manager/member handling and automatic cancellation of stale ownership/identity/lifecycle requests.
- Added forum moderation reasons to clan status changes and sends the reason/state transition to the clan Owner through XenForo alerts.
- Added all active clan memberships to member profiles while keeping the selected active clan as the postbit/tooltip identity.
- Added missing alert-handler actions for clan approval, manager assignment and direct member addition.
- Expanded CI validation for cron callbacks, reserved identity controls, maintenance, forced ownership, alerts and 0.8 Admin CP surfaces.

### Türkçe

- Yanıltıcı veya korunan kimliklerin talep edilmesini engellemek için forum yönetimli rezerve klan tagı ve klan adı sistemi eklendi.
- Rezerve/çakışan klan kimliği kontrolleri hem başvuru hem onay anında tekrar doğrulanarak eski bekleyen talepler üzerinden çakışma oluşması engellendi.
- Günlük klan bakım cron'u ve manuel ACP bakım ekranı eklendi.
- Bakım sistemi süresi dolan davetleri kapatır, sahiplik değişimiyle geçersiz kalan sahiplik transferi ve Owner'a bağlı talepleri iptal eder, geçersiz aktif-klan tercihlerini düzeltir ve üye sayaçlarını uzlaştırır.
- Public GET sayfalarında davet süresi dolumu için veritabanına yazma kaldırıldı; bu işlem bakım servisine taşındı.
- Yetkili forum yöneticisi için zorunlu Owner değiştirme aracı eklendi; eski Owner'ın Manager/üye olarak bırakılması seçilebilir ve eski sahipliğe bağlı bekleyen talepler otomatik iptal edilir.
- Klan durum moderasyonuna işlem nedeni eklendi; durum değişimi ve nedeni XenForo bildirimiyle Owner'a iletilir.
- Kullanıcı profilinde tüm aktif klan üyelikleri gösterilir; postbit/tooltip üzerinde yalnızca seçili aktif klan kimliği gösterilmeye devam eder.
- Klan onayı, Manager ataması ve doğrudan üye ekleme için eksik Alert handler action kayıtları tamamlandı.
- CI; cron, rezerve kimlik, bakım, zorunlu sahiplik, bildirim ve 0.8 ACP ekranlarını kapsayacak şekilde genişletildi.

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
