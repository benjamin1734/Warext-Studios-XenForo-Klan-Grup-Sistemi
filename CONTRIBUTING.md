# Contributing / Katkı Rehberi

Contributions should preserve the add-on's core authority model and the repository conventions used by Warext Studios XenForo projects.

## Development rules

- Target XenForo 2.3.0+ and PHP 8.1+ unless the project requirements are intentionally changed.
- Do not modify XenForo core files.
- Keep clan roles independent from XenForo user groups and moderator permissions.
- Enforce authorization in controllers/services, not only by hiding buttons in templates.
- Use database transactions for multi-record operations that must remain atomic.
- Keep upgrade paths compatible with existing installations; do not require database resets.
- Add schema changes through `Setup.php` upgrade steps.
- Keep installable XenForo metadata in `_data` synchronized with code changes.
- Keep PHP source free of explanatory inline/PHPDoc comments in line with current Warext Studios repository convention.
- Update `CHANGELOG.md` for user-visible changes.
- Update `docs/ARCHITECTURE.md` when authority boundaries, tables or core flows change.

## Before submitting

Run or verify:

- PHP syntax validation
- XML parsing for `_data`
- JSON parsing for `addon.json`
- Route → controller mappings
- Controller → template references
- Permission/admin-permission wiring
- Content-type handler classes
- Installation ZIP layout

GitHub Actions runs the same core validation automatically.

## Pull requests

Keep each pull request focused. Describe what changed, why it changed, any migration behavior and the security/permission implications.

---

## Türkçe

Katkılar eklentinin temel yetki modelini ve Warext Studios XenForo repo düzenini korumalıdır.

- XenForo çekirdek dosyalarını değiştirmeyin.
- Klan rolleri ile XenForo kullanıcı grubu/moderatör yetkilerini birleştirmeyin.
- Yetki kontrolünü yalnızca buton gizleyerek yapmayın; controller/servis katmanında uygulayın.
- Veritabanı sıfırlama gerektiren güncellemeler hazırlamayın; `Setup.php` upgrade adımlarını kullanın.
- Kod değişikliğine bağlı XenForo `_data` kayıtlarını güncel tutun.
- Kullanıcıya yansıyan değişiklikleri `CHANGELOG.md` içine ekleyin.
- Yetki sınırı, tablo veya temel iş akışı değişiyorsa `docs/ARCHITECTURE.md` belgesini de güncelleyin.
