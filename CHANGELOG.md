# Changelog / Değişiklik Günlüğü

## 0.13.0 RC - 2026-09-26

### English

- Replaced plain clan identity HEX fields with an interactive circular HSV color picker that keeps HEX and RGB values synchronized.
- Added separate XenForo/Font Awesome icon selection for clan tags and Manager banners.
- Added approved tag/Manager icon persistence to clans and pending creation/identity-change applications.
- Render clan identity icons using XenForo's native `<xf:fa>` template tag in postbit, profile and management-related output.
- Added a safe server-side whitelist for selectable icon names.
- Added an in-place 0.13 schema upgrade for existing installations; existing clans remain iconless until configured.
- Removed separate UPDATE ZIP generation from GitHub Releases to match the other Warext XenForo add-ons. XenForo's normal full add-on ZIP remains the single install/upgrade package.

### Türkçe

- Klan kimliğindeki düz HEX kutuları, HEX ve RGB değerlerini canlı senkronlayan tıklanabilir/sürüklenebilir dairesel HSV renk seçiciyle değiştirildi.
- Klan tagı ve Manager bannerı için ayrı XenForo/Font Awesome ikon seçimi eklendi.
- Tag/Manager ikonları hem klan kayıtlarında hem de oluşturma/kimlik değişikliği onay taleplerinde saklanır hale getirildi.
- Klan ikonları postbit, profil ve ilgili görünümlerde XenForo'nun yerel `<xf:fa>` template etiketiyle render ediliyor.
- Seçilebilir ikon isimleri için güvenli sunucu tarafı whitelist eklendi.
- Mevcut kurulumlar için yerinde 0.13 schema upgrade eklendi; eski klanlar ikon seçilene kadar ikonsuz kalır.
- Diğer Warext XenForo eklentileriyle aynı standarda dönmek için ayrı UPDATE ZIP üretimi GitHub Release workflow'undan kaldırıldı. Kurulum/güncelleme için tek normal XenForo ZIP'i kullanılır.

## 0.12.2 RC - 2026-09-26

### English

- Fixed a real XenForo runtime fatal error caused by calling the non-existent `$this->db()` helper from public/Admin controllers.
- Replaced controller database access with XenForo's supported `$this->app()->db()` access.
- Fixed the public clan index category query and all Admin CP clan dashboard/category/statistic queries.
- Added a permanent CI guard that rejects `$this->db()` inside controller classes.

### Türkçe

- Public/Admin controller sınıflarında bulunmayan `$this->db()` metodunun çağrılması nedeniyle oluşan gerçek XenForo fatal runtime hatası düzeltildi.
- Controller veritabanı erişimi XenForo'nun desteklenen `$this->app()->db()` kullanımına geçirildi.
- Public klan liste kategori sorgusu ile ACP klan dashboard/kategori/istatistik sorgularının tamamı düzeltildi.
- Controller sınıflarında tekrar `$this->db()` kullanılmasını engelleyen kalıcı CI kontrolü eklendi.

## 0.12.1 RC - 2026-09-25

### English

- Prevented members-only clan announcements from being reported through a direct report URL by requiring announcement visibility before reporting.
- Added explicit per-clan application field-key uniqueness validation before persistence.
- Made custom-role deletion atomic with reassignment of affected members and the audit event.
- Made application-field deletion atomic with deletion of historical answers and the audit event.
- Added permanent CI verification that every Entity column matches the fresh-install schema in Setup.php.

### Türkçe

- Yalnız üyelere açık klan duyurularının doğrudan rapor URL'si üzerinden raporlanabilmesi engellendi; raporlamadan önce duyuru görünürlüğü zorunlu hale getirildi.
- Başvuru alanı anahtarları için veritabanına yazmadan önce klan içi benzersizlik kontrolü eklendi.
- Özel rol silme işlemi, etkilenen üyelerin yeniden atanması ve audit kaydıyla birlikte atomik hale getirildi.
- Başvuru alanı silme işlemi, geçmiş cevapların silinmesi ve audit kaydıyla birlikte atomik hale getirildi.
- Tüm Entity kolonlarının Setup.php sıfır kurulum şemasıyla birebir eşleştiğini doğrulayan kalıcı CI testi eklendi.

## 0.12.0 RC - 2026-09-25

### English

- Added service-level per-clan limits for custom roles, custom application fields and stored announcements.
- Added a maximum of 50 unique options for select/checkbox application fields.
- Added explicit 20,000-character limits for clan creation descriptions and announcement messages.
- Added explicit clan category, manager-banner and media-URL length validation.
- Kept logo/cover media URLs restricted to HTTP/HTTPS and capped them at the database-safe 255-character limit.
- Updated release automation so Alpha/Beta/RC versions are prereleases while future stable versions publish as normal releases.
- Expanded CI validation for resource limits, content validation and release-candidate invariants.

### Türkçe

- Klan başına özel rol, özel başvuru sorusu ve saklanan duyuru sayısı için servis katmanı limitleri eklendi.
- Seçim/çoklu seçim başvuru alanlarında en fazla 50 benzersiz seçenek sınırı eklendi.
- Klan oluşturma açıklaması ve duyuru mesajlarında açık 20.000 karakter sınırı eklendi.
- Klan kategorisi, yönetici bannerı ve medya URL'leri için açık uzunluk doğrulamaları eklendi.
- Logo/kapak URL'leri yalnız HTTP/HTTPS olacak şekilde korunup veritabanıyla uyumlu 255 karakterle sınırlandırıldı.
- Release otomasyonu Alpha/Beta/RC sürümlerini prerelease, gelecekteki stabil sürümleri normal release olarak yayınlayacak şekilde hazırlandı.
- CI; kaynak limitleri, içerik doğrulama ve release-candidate bütünlüğünü kapsayacak şekilde genişletildi.

## 0.11.0 Beta - 2026-09-25

### English

- Enforced the XenForo `wxClans/view` permission across public clan controller actions instead of relying only on navigation visibility.
- Enforced `wxClans/apply` for clan-creation applications.
- Added entity-level clan view permission checks so alerts, reports and other XenForo content consumers share the same access rule.
- Updated clan-announcement visibility to honor both base clan visibility and the clan announcement privacy setting.
- Hid public clan navigation entries when the visitor lacks the required clan-view/moderation permission.
- Expanded CI checks for public permission enforcement and navigation/entity consistency.

### Türkçe

- XenForo `wxClans/view` izni yalnız navigasyon görünürlüğüne bırakılmayıp tüm public klan controller işlemlerinde zorunlu hale getirildi.
- Klan oluşturma başvuruları için `wxClans/apply` izni zorunlu hale getirildi.
- Alert, report ve diğer XenForo içerik tüketicilerinin de aynı erişim kuralını kullanması için Entity seviyesinde klan görüntüleme izni eklendi.
- Klan duyurusu görünürlüğü hem temel klan erişimini hem de klanın duyuru gizlilik ayarını dikkate alacak şekilde güçlendirildi.
- Klan görüntüleme/moderasyon izni olmayan ziyaretçiler için public klan navigasyonları gizlendi.
- CI kontrolleri public izin zorlaması ve navigation/entity tutarlılığını kapsayacak şekilde genişletildi.

## 0.10.0 Alpha - 2026-09-25

### English

- Corrected Manager-limit accounting so the clan Owner is not counted against the configurable Manager cap.
- Hardened both normal and forum-forced ownership transfers so demoting the previous Owner to Manager cannot exceed the Manager limit.
- Added configurable member pagination for public clan profiles and clan management screens.
- Reduced large-clan page load cost by limiting active member entity hydration to the current page.
- Expanded CI validation for pagination and ownership/Manager-limit invariants.

### Türkçe

- Klan Owner'ının ayarlanabilir Manager limitine dahil edilmesine neden olan hesaplama hatası düzeltildi.
- Normal ve forum tarafından zorlanan sahiplik devirlerinde eski Owner'ın Manager yapılmasının Manager limitini aşması engellendi.
- Public klan profili ve klan yönetim ekranına ayarlanabilir üye sayfalaması eklendi.
- Büyük klanlarda yalnız mevcut sayfadaki aktif üyeler yüklenerek sayfa yükü azaltıldı.
- CI kontrolleri sayfalama ve sahiplik/Manager-limit bütünlüğünü kapsayacak şekilde genişletildi.

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
