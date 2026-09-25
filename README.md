# Warext Studios | XenForo Clan & Group System

## English

Warext Studios XenForo Clan & Group System is an open-source, general-purpose clan, team, guild and community-management add-on for XenForo 2.3.x.

The system is intentionally independent from XenForo forum moderation. Clan owners and clan managers can manage only their own clan responsibilities; they do not gain forum moderation powers. Forum staff remains the authority for clan approval, official clan identity changes, ownership transfers requiring staff review, lifecycle requests and reported clan content.

## Current version

**0.9.0 Alpha**

0.8.0 adds reserved clan identities, automatic maintenance, forum-forced ownership recovery, moderation reasons/Owner alerts and full membership display on member profiles, while preserving the existing lifecycle, membership, invitation, role and moderation workflows.

## Main features

### Forum-approved clan creation

- Users submit a clan creation application instead of creating an official clan directly.
- Applications include clan name, tag, category, description and requested manager banner/visual identity.
- Forum staff can approve, reject or request changes.
- Approved applications create the clan, owner membership and base Owner / Manager / Member roles automatically.
- Clan names and tags are validated before approval.

### Clan ownership and hierarchy

- Every clan has a single highest-level owner.
- Clan managers are separate from the owner and cannot elevate themselves to owner.
- Managers receive only clan-scoped permissions.
- Normal clan members do not gain management permissions.
- Ownership transfer uses a controlled request flow.
- The proposed new owner must accept before the forum review stage.
- Ownership history remains auditable.

### Clan-scoped role and permission model

Clan roles are independent from XenForo user groups.

A clan manager can be granted selected clan permissions such as:

- Managing normal clan members
- Reviewing membership applications
- Sending clan invitations
- Managing announcements
- Managing clan settings
- Viewing clan audit records

Clan roles never grant forum-level capabilities such as deleting forum threads, warning users, banning users, viewing moderator-only reports or editing unrelated forum content.

### Membership, applications and invitations

Supported join modes:

- Open membership
- Application required
- Invitation required
- Membership closed

The add-on includes:

- Membership applications
- Approve / reject workflow
- Clan invitations with expiry
- Invite acceptance / rejection
- Leave-clan flow
- Member removal
- Membership limits configured by administrators
- Per-clan blacklist support
- Duplicate membership/application protection

### Custom clan application forms

Clan management can create custom membership questions with multiple field types, including:

- Single-line text
- Long text
- Select fields
- Multiple-choice fields
- Numbers
- Dates

Fields can be required or optional and are stored independently per clan.

### Clan profile and management pages

Clan pages can display:

- Clan name and approved tag
- Description and rules
- Category
- Member count
- Owner and managers
- Member roles
- Announcements
- Join status
- Management controls for authorized clan staff

A separate **My Clans** area provides the user's memberships, pending applications, pending invitations and active display-clan preference.

### Clan tags and manager banners

- Approved clan members can display the clan tag.
- Clan owner/managers can additionally display the approved manager banner.
- A user with multiple clan memberships can select an active clan for display.
- Invalid/expired active selections fall back safely to an eligible active membership.
- Tag/banner output is integrated into XenForo postbit, member profile and member tooltip through XenForo class/template extension mechanisms.
- Member profiles additionally list all active clan memberships; postbit and tooltip continue to use only the selected active clan.
- Leaving a clan or losing manager status automatically removes the corresponding display privileges.

### Reserved clan identities

- Administrators can reserve clan tags such as `ADMIN`, `MOD`, `STAFF` or any site-specific value.
- Administrators can also reserve full clan names.
- Reserved values are checked when a clan is requested, when an identity change is requested, and again at staff approval time.
- Clan names are also checked for duplicate active/pending identities instead of protecting only the short tag.

### Automatic maintenance

- A daily XenForo cron expires overdue invitations without requiring a user to open a clan page.
- Stale ownership transfers and owner-bound identity/lifecycle requests are cancelled after ownership changes.
- Invalid active-clan display preferences are repaired automatically.
- Cached clan member counts are reconciled against active membership rows.
- Administrators can inspect maintenance state and run the same maintenance manually from Admin CP.

### Forum ownership recovery and moderation reasons

- Authorized Admin CP users can transfer a clan to another active member when the normal Owner workflow cannot be used.
- The old Owner can optionally remain a clan Manager or be demoted to a normal member.
- Forced ownership changes are written to clan audit and XenForo Moderator Log and notify affected users.
- Forum status changes support a moderation reason; the Owner receives the old state, new state and reason through XenForo alerts.

### Announcements

- Authorized clan management can create, edit and remove clan announcements.
- Announcements can be pinned.
- Announcement actions are recorded in the clan audit trail.
- Announcements are reportable through XenForo's Report Center.

### Official clan identity changes

Critical public clan identity values are not changed silently by clan managers.

The owner can submit a forum-review request for changes such as:

- Clan name
- Clan tag
- Manager banner text
- Tag color
- Manager banner color

Approved changes are applied by the system and retained in the audit history.

### Ownership transfer

- Only the current owner can start a transfer.
- The target account must be eligible.
- The target user accepts or rejects the transfer.
- Accepted transfers enter the forum review queue.
- Forum staff can approve or reject the transfer.
- Owner/member role flags and active membership data are updated transactionally.

### Clan lifecycle

0.7.0 adds controlled clan closing and reopening.

- Owners request closure instead of deleting a clan directly.
- Closure requests enter the Admin CP review queue.
- Closed clans preserve historical membership and audit data.
- The owner can later request reopening.
- Forum-suspended clans cannot be reopened by bypassing staff moderation.
- Lifecycle approvals/rejections are logged and generate XenForo alerts.

### Clan audit trail

Important clan operations are recorded, including membership, role, manager, blacklist, settings, application, announcement, identity, ownership and lifecycle actions.

The clan audit system is separate from forum moderation permissions, while forum-side moderation actions can additionally integrate with XenForo's moderator logging system.

### XenForo Report Center integration

The following content types can be reported through XenForo's native report system:

- Clan
- Clan announcement

This keeps clan-related reports inside the forum's existing moderation workflow instead of creating an isolated report center.

### XenForo moderator-log integration

Forum moderation actions concerning clans can be represented in XenForo's Moderator Log through the add-on's clan moderator-log handler.

Clan-internal management actions remain in the clan audit trail and do not impersonate forum moderation actions.

### Admin CP

The add-on provides a dedicated **Clans & Groups** section containing:

- Clan list and filtering
- Clan creation applications
- Official identity-change requests
- Ownership-transfer requests
- Clan close/reopen lifecycle requests
- Global clan settings
- Reserved clan tag/name settings
- Maintenance status and manual maintenance
- Forced Owner recovery for eligible active members

A dedicated `wxClansManage` Admin CP permission protects clan administration routes.

### Capacity, anti-spam and privacy

- Administrators can cap active members per clan and Manager-role members per clan.
- Rejected/cancelled join applications can be rate-limited before the same user reapplies.
- Resolved invitations can be rate-limited before management sends another invitation to the same user.
- Clan management can make the member list public, members-only or Owner/forum-staff only.
- Clan announcements can be public or members-only.

### Global settings

Administrators can configure:

- Maximum active clan memberships per user
- Maximum clans owned per user
- Clan invitation lifetime
- Reserved clan tags
- Reserved clan names

A value of `0` can be used for unlimited limits where supported.

## Permission model

XenForo permissions:

- `wxClans / view` — view clan pages
- `wxClans / apply` — submit clan creation applications
- `wxClans / moderate` — forum-level clan moderation permission
- `wxClansManage` — dedicated Admin CP clan management permission

Default installation behavior grants normal view/application access to registered users and clan moderation permission to the standard Administrative and Moderating groups. Existing explicit permission values are not overwritten.

## Database

The add-on creates and upgrades its own tables automatically. No manual SQL import is required.

Main tables:

- `xf_wx_clan`
- `xf_wx_clan_role`
- `xf_wx_clan_member`
- `xf_wx_clan_application`
- `xf_wx_clan_application_field`
- `xf_wx_clan_application_answer`
- `xf_wx_clan_invitation`
- `xf_wx_clan_ownership_transfer`
- `xf_wx_clan_blacklist`
- `xf_wx_clan_announcement`
- `xf_wx_clan_user_pref`
- `xf_wx_clan_audit_log`

## Security and integrity

- Clan permissions are checked server-side and are not derived from XenForo moderator status.
- Owner-only actions remain owner-only even when another member is a clan manager.
- Critical multi-record operations use database transactions.
- Duplicate invitations/applications and invalid ownership transitions are rejected.
- Suspended/closed clan state is enforced by management services.
- Admin CP clan routes require the dedicated admin permission.
- Clan and announcement reports use XenForo's native Report Center.
- The add-on does not modify XenForo core files.

## Compatibility

- XenForo 2.3.0+
- PHP 8.1+

The repository CI validates PHP syntax on PHP 8.1, 8.2, 8.3 and 8.4, validates XML/JSON add-on data and checks important XenForo route, template, permission and handler relationships.

A licensed live XenForo installation is still required for final runtime/theme verification.

## Installation

Use the ZIP attached to the latest GitHub Release:

1. Open XenForo Admin CP.
2. Go to **Add-ons → Install/upgrade from archive**.
3. Upload the release ZIP.
4. Install **Warext Studios | XenForo Klan & Grup Sistemi**.
5. Review global options and user-group permissions.

For manual installation, upload the repository's `upload/` directory into the XenForo installation root and install the add-on from Admin CP.

## Upgrade

Replace the add-on files with the newer release package and run XenForo's normal add-on upgrade process from Admin CP. Database and permission changes are handled by the add-on setup/upgrade routines; no manual SQL reset is required.

## Source-code rules

- XenForo core files are never modified.
- Clan roles are never treated as XenForo moderator/user-group roles.
- Critical authorization is enforced server-side.
- PHP source files are kept free of explanatory inline/PHPDoc comments in line with the existing Warext Studios add-on repository convention.
- PHP, JSON, XML and package structure are validated through GitHub Actions.
- Release packages contain the `upload/` tree rather than repository-development files.

## Project documentation

- `CHANGELOG.md` — version history
- `SECURITY.md` — security policy
- `CONTRIBUTING.md` — contribution rules
- `docs/ARCHITECTURE.md` — technical architecture and authority boundaries

## Add-on ID

`Warext/Clans`

## License

MIT License

## Support

For questions, bug reports, installation support and other Warext Studios XenForo add-ons:

**Discord:** https://discord.gg/tgsV5XMcFS

---

## Türkçe

Warext Studios XenForo Klan & Grup Sistemi; XenForo 2.3.x üzerinde genel amaçlı klan, ekip, guild ve topluluk yönetimi sağlayan açık kaynak bir eklentidir.

Sistemin temel güvenlik kuralı şudur: **klan yöneticiliği forum moderatörlüğü değildir.** Klan sahibi ve klan yöneticileri yalnızca kendi klanlarına ait üyelik, başvuru, davet, rol ve benzeri işlemleri yönetebilir. Forum genelindeki konu/mesaj silme, kullanıcı uyarma, yasaklama veya moderatör raporlarına erişim gibi yetkiler kazanmazlar.

## Güncel sürüm

**0.9.0 Alpha**

0.8.0 ile rezerve klan kimlikleri, otomatik bakım, forum yönetimi tarafından zorunlu sahiplik kurtarma, moderasyon nedeni/Owner bildirimi ve kullanıcı profilinde tüm klan üyeliklerinin gösterimi eklendi. Mevcut yaşam döngüsü, üyelik, davet, rol ve moderasyon akışları korunur.

## Temel özellikler

### Forum onaylı klan oluşturma

- Kullanıcı doğrudan resmi klan açmak yerine başvuru gönderir.
- Başvuruda klan adı, tag, kategori, açıklama ve yönetici bannerı/görsel kimlik bilgileri bulunur.
- Forum yönetimi başvuruyu onaylayabilir, reddedebilir veya düzenleme isteyebilir.
- Onay sonrası klan, Owner üyeliği ve temel Owner / Manager / Member rolleri otomatik oluşturulur.
- Klan adı ve tag değerleri onay öncesinde doğrulanır.

### Klan sahibi / yönetici / üye hiyerarşisi

- Her klanın tek bir en üst sahibi vardır.
- Manager, Owner'dan ayrıdır ve kendisini Owner seviyesine çıkaramaz.
- Manager yalnızca kendisine verilen klan içi yetkileri kullanabilir.
- Normal üye klan yönetim yetkisi kazanmaz.
- Klan sahipliği kontrollü aktarım akışıyla değiştirilebilir.
- Yeni sahip adayı forum incelemesinden önce aktarımı kabul etmek zorundadır.

### Klan içi rol ve izin sistemi

Klan rolleri XenForo kullanıcı gruplarından tamamen bağımsızdır.

Manager'a klan bazında şu tip yetkiler verilebilir:

- Normal üyeleri yönetme
- Üyelik başvurularını değerlendirme
- Klan daveti gönderme
- Duyuruları yönetme
- Klan ayarlarını yönetme
- Klan işlem kayıtlarını görüntüleme

Bu izinler konu silme, forum kullanıcısını uyarma/yasaklama veya moderatör raporlarını görme gibi forum yetkilerine dönüşmez.

### Üyelik / başvuru / davet sistemi

Desteklenen katılım biçimleri:

- Açık üyelik
- Başvuru gerekli
- Davet gerekli
- Üye alımı kapalı

Sistemde üyelik başvurusu, kabul/red, süreli davet, davet kabul/red, klandan ayrılma, üyeyi çıkarma, üyelik limiti, klan blacklist'i ve çift başvuru/davet koruması bulunur.

### Özelleştirilebilir klan başvuru formu

Klan yönetimi kendi üyelik sorularını oluşturabilir.

Desteklenen alanlar arasında:

- Tek satır metin
- Uzun metin
- Seçim alanı
- Çoklu seçim
- Sayı
- Tarih

bulunur. Alanlar zorunlu veya opsiyonel olabilir ve her klan için bağımsız saklanır.

### Klan profili ve yönetim paneli

Klan sayfalarında klan adı/tagı, açıklama, kurallar, kategori, üye sayısı, Owner/Manager listesi, roller, duyurular ve katılım durumu gösterilebilir.

**Klanlarım** alanı kullanıcının üyeliklerini, bekleyen başvurularını, davetlerini ve aktif gösterim klanı tercihini tek noktada toplar.

### Klan tagı ve yönetici bannerı

- Onaylı klan üyelerine klan tagı gösterilebilir.
- Owner ve Manager için ayrıca onaylı klan yönetim bannerı gösterilebilir.
- Birden fazla klana üye kullanıcı hangi klanı aktif göstereceğini seçebilir.
- Geçersiz hale gelen seçim güvenli biçimde uygun başka aktif üyeliğe düşer.
- Tag/banner postbit, kullanıcı profili ve kullanıcı tooltip alanına XenForo'nun class/template extension sistemiyle entegredir.
- Kullanıcı profilinde ayrıca tüm aktif klan üyelikleri listelenir; postbit ve tooltip yalnızca seçili aktif klanı kullanır.
- Klandan ayrılma veya Manager yetkisinin kaldırılması ilgili görünür yetkileri otomatik düşürür.

### Rezerve klan kimlikleri

- Yönetici; `ADMIN`, `MOD`, `STAFF` gibi veya siteye özel klan taglarını rezerve edebilir.
- Tam klan adları da rezerve edilebilir.
- Rezerve değerler klan başvurusunda, kimlik değişikliği talebinde ve forum yönetimi onayı sırasında tekrar kontrol edilir.
- Yalnızca kısa tag değil, klan adı için de mevcut/bekleyen kayıt çakışması kontrol edilir.

### Otomatik bakım sistemi

- Günlük XenForo cron'u süresi dolmuş davetleri kullanıcı sayfa açmadan otomatik kapatır.
- Sahiplik değişimiyle geçersiz kalan sahiplik transferleri ve Owner'a bağlı kimlik/yaşam döngüsü talepleri iptal edilir.
- Geçersiz aktif-klan gösterim tercihleri otomatik düzeltilir.
- Klan üye sayaçları gerçek aktif üyelik kayıtlarıyla uzlaştırılır.
- ACP'den bakım durumu görülebilir ve aynı bakım manuel çalıştırılabilir.

### Forum sahiplik kurtarma ve moderasyon nedeni

- Normal Owner aktarım akışı kullanılamadığında yetkili ACP kullanıcısı sahipliği başka bir aktif klan üyesine aktarabilir.
- Eski Owner isteğe göre Manager olarak bırakılabilir veya normal üyeye düşürülebilir.
- Zorunlu sahiplik değişikliği klan denetim kaydına ve XenForo Moderator Log'a yazılır; ilgili kullanıcılara bildirim gönderilir.
- Forum durum değişikliklerinde moderasyon nedeni girilebilir; eski/yeni durum ve neden XenForo bildirimiyle Owner'a iletilir.

### Klan duyuruları

Yetkili klan yönetimi duyuru oluşturabilir, düzenleyebilir, silebilir ve sabitleyebilir. Duyuru işlemleri klan denetim kaydına yazılır ve duyurular XenForo Report Center üzerinden raporlanabilir.

### Resmi klan kimliği değişikliği

Klan adı/tagı gibi kritik bilgiler Owner tarafından doğrudan sessizce değiştirilemez. Owner forum onayına şu tip değişiklik talepleri gönderebilir:

- Klan adı
- Klan tagı
- Yönetici banner metni
- Tag rengi
- Yönetici banner rengi

Onaylanan değişiklikler sistem tarafından uygulanır ve denetim geçmişinde tutulur.

### Klan sahipliği devri

- Devri yalnızca mevcut Owner başlatabilir.
- Yeni Owner adayı uygun hesap olmalıdır.
- Aday kullanıcı devri kabul veya reddeder.
- Kabul edilen devir forum yönetimi kuyruğuna gider.
- Forum yönetimi onay/red verebilir.
- Owner/member işaretleri ve üyelik bilgileri transaction içinde güncellenir.

### Klan yaşam döngüsü

0.7.0 ile kontrollü klan kapatma ve yeniden açma sistemi eklendi.

- Owner klanı doğrudan silmek yerine kapatma talebi gönderir.
- Kapatma talebi ACP inceleme kuyruğuna gider.
- Kapanan klanın üyelik ve denetim geçmişi korunur.
- Owner daha sonra yeniden açma talebi gönderebilir.
- Forum tarafından `suspended` durumuna alınmış klan, Owner işlemiyle moderasyonu aşarak tekrar açılamaz.
- Yaşam döngüsü onay/red işlemleri loglanır ve XenForo bildirimi üretir.

### Klan denetim kaydı

Üyelik, rol, Manager, blacklist, ayar, başvuru, duyuru, kimlik, sahiplik ve yaşam döngüsü gibi önemli işlemler klan denetim geçmişinde saklanır.

Klan içi yönetim logları forum moderatör işlemi gibi gösterilmez. Forum yönetiminin klanla ilgili gerçek moderasyon işlemleri ise XenForo Moderator Log entegrasyonundan yararlanabilir.

### XenForo Report Center entegrasyonu

Şu içerikler XenForo'nun kendi rapor sistemi üzerinden raporlanabilir:

- Klan
- Klan duyurusu

Böylece klan ihlalleri ayrı ve kopuk bir rapor paneli yerine forumun mevcut moderasyon akışına girer.

### XenForo Moderator Log entegrasyonu

Forum yönetiminin klan üzerinde yaptığı moderasyon işlemleri eklentinin clan moderator-log handler'ı üzerinden XenForo Moderator Log ile ilişkilendirilebilir.

Klan yöneticilerinin klan içi işlemleri ise klan audit kaydında tutulur ve forum moderasyonu gibi davranmaz.

### Admin CP

ACP içinde bağımsız **Klanlar & Gruplar** bölümü bulunur:

- Klan listesi ve filtreleme
- Klan oluşturma başvuruları
- Resmi isim/tag/banner değişiklik talepleri
- Sahiplik devir talepleri
- Klan kapatma/yeniden açma talepleri
- Global klan ayarları
- Rezerve klan tag/ad ayarları
- Bakım durumu ve manuel bakım
- Uygun aktif üyeye zorunlu Owner devri

Klan yönetim controller'ları ayrı `wxClansManage` ACP izniyle korunur.

### Kapasite, spam koruması ve gizlilik

- Yöneticiler klan başına aktif üye ve Manager sayısını sınırlandırabilir.
- Reddedilen/iptal edilen üyelik başvurularından sonra aynı kullanıcının yeniden başvurması cooldown ile sınırlandırılabilir.
- Sonuçlanmış davetten sonra aynı kullanıcıya tekrar davet göndermek cooldown ile sınırlandırılabilir.
- Klan yönetimi üye listesini herkese açık, yalnız üyelere açık veya yalnız Owner/forum ekibine açık yapabilir.
- Klan duyuruları herkese açık veya yalnız üyelere açık olabilir.

### Global ayarlar

Yönetici şu limitleri ayarlayabilir:

- Kullanıcı başına maksimum aktif klan üyeliği
- Kullanıcı başına maksimum sahip olunan klan
- Klan daveti geçerlilik süresi

Desteklenen limitlerde `0` sınırsız olarak kullanılabilir.

## Yetki modeli

XenForo izinleri:

- `wxClans / view` — klan sayfalarını görüntüleme
- `wxClans / apply` — klan oluşturma başvurusu gönderme
- `wxClans / moderate` — forum seviyesinde klan moderasyon izni
- `wxClansManage` — ACP klan yönetim izni

Kurulumda Registered gruba varsayılan görüntüleme/başvuru erişimi verilir. Standart Administrative ve Moderating gruplarına klan moderasyon izni de eklenir. Daha önce açıkça yapılandırılmış izin değerleri ezilmez.

## Veritabanı

Gerekli tablolar kurulum/yükseltme sırasında otomatik oluşturulur veya güncellenir; manuel SQL import gerekmez.

Ana tablolar:

- `xf_wx_clan`
- `xf_wx_clan_role`
- `xf_wx_clan_member`
- `xf_wx_clan_application`
- `xf_wx_clan_application_field`
- `xf_wx_clan_application_answer`
- `xf_wx_clan_invitation`
- `xf_wx_clan_ownership_transfer`
- `xf_wx_clan_blacklist`
- `xf_wx_clan_announcement`
- `xf_wx_clan_user_pref`
- `xf_wx_clan_audit_log`

## Güvenlik ve bütünlük

- Klan yetkileri sunucu tarafında kontrol edilir ve XenForo moderatör statüsünden türetilmez.
- Owner'a özel işlemler Manager tarafından kullanılamaz.
- Kritik çoklu kayıt işlemlerinde veritabanı transaction'ları kullanılır.
- Çift davet/başvuru ve geçersiz sahiplik geçişleri engellenir.
- Askıya alınmış/kapatılmış klan durumları servis katmanında uygulanır.
- ACP klan yönetim URL'leri ayrı admin izniyle korunur.
- Klan ve duyuru raporları XenForo Report Center ile çalışır.
- XenForo çekirdek dosyaları değiştirilmez.

## Uyumluluk

- XenForo 2.3.0+
- PHP 8.1+

Repo CI sistemi PHP 8.1, 8.2, 8.3 ve 8.4 üzerinde syntax kontrolü; XML/JSON doğrulaması ve önemli route/template/izin/handler çapraz kontrollerini çalıştırır.

Lisanslı gerçek XenForo kurulumu CI ortamında bulunmadığı için final runtime ve tema testi ayrıca gerçek XenForo 2.3 kurulumunda yapılmalıdır.

## Kurulum

En son GitHub Release altındaki ZIP dosyasını kullanın:

1. XenForo Admin CP'yi açın.
2. **Add-ons → Install/upgrade from archive** bölümüne girin.
3. Release ZIP'ini yükleyin.
4. **Warext Studios | XenForo Klan & Grup Sistemi** eklentisini kurun.
5. Global ayarları ve kullanıcı grubu izinlerini kontrol edin.

Manuel kurulumda repodaki `upload/` klasörünün içeriğini XenForo kurulum köküne yükleyip ACP üzerinden eklentiyi kurabilirsiniz.

## Güncelleme

Yeni sürüm dosyalarını mevcut dosyaların üzerine yükleyin ve XenForo ACP üzerinden normal add-on upgrade işlemini çalıştırın. Veritabanı ve izin değişiklikleri Setup/upgrade adımlarıyla uygulanır; veritabanını sıfırlamak veya manuel SQL çalıştırmak gerekmez.

## Kaynak kod kuralları

- XenForo çekirdek dosyaları değiştirilmez.
- Klan rolleri XenForo kullanıcı grubu/moderatör rolü olarak kullanılmaz.
- Kritik yetkilendirme sunucu tarafında uygulanır.
- Warext Studios'un diğer güncel eklenti repolarındaki düzene uyum için PHP kaynaklarında açıklama amaçlı inline/PHPDoc yorumları tutulmaz.
- PHP/JSON/XML ve paket yapısı GitHub Actions ile doğrulanır.
- Release ZIP'leri repo geliştirme dosyaları yerine `upload/` ağacını içerir.

## Proje belgeleri

- `CHANGELOG.md` — sürüm değişiklikleri
- `SECURITY.md` — güvenlik politikası
- `CONTRIBUTING.md` — katkı kuralları
- `docs/ARCHITECTURE.md` — teknik mimari ve yetki sınırları

## Eklenti kimliği

`Warext/Clans`

## Lisans

MIT License

## Destek

Sorular, hata bildirimleri, kurulum desteği ve diğer Warext Studios XenForo eklentileri için:

**Discord:** https://discord.gg/tgsV5XMcFS
