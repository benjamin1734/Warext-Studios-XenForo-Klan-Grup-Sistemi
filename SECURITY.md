# Security Policy / Güvenlik Politikası

## Supported branch

Security fixes are applied to the current `main` branch and then included in the next release package.

## Reporting a vulnerability

Please do not publish exploitable security details in a public issue before the maintainers have had a reasonable opportunity to investigate.

Use the Warext Studios support channel for responsible disclosure:

**Discord:** https://discord.gg/tgsV5XMcFS

Useful reports should include the affected version, XenForo/PHP versions, reproduction steps, expected behavior, actual behavior and the security impact.

## Security boundaries

The add-on is designed around the following authority boundaries:

- XenForo forum moderation permissions and clan-management permissions are separate.
- A clan owner/manager must never gain XenForo moderator capabilities simply because of a clan role.
- Owner-only clan actions must not be available to ordinary managers.
- Admin CP management routes require `wxClansManage`.
- Forum-level clan moderation uses the XenForo `wxClans / moderate` permission or administrator authority.
- Clan status restrictions such as `suspended` and `closed` must be enforced server-side.
- Critical ownership, membership and approval transitions should remain transaction-safe.
- Report Center integration must preserve XenForo report visibility/action rules.

## Sensitive issue classes

Please report immediately if you find any issue involving:

- Clan role escalation into forum moderation
- Unauthorized Owner or Manager assignment
- Ownership transfer bypass
- Unauthorized acceptance/rejection of applications or lifecycle requests
- Admin CP authorization bypass
- Ability to reopen a suspended clan without forum authority
- Cross-clan member/role modification
- SQL injection or unsafe query construction
- Stored or reflected XSS in clan names, tags, descriptions, rules, announcements or application answers
- CSRF/state-changing GET behavior
- Report Center or Moderator Log privilege bypass
- Exposure of private application answers to unauthorized users

## Dependency and runtime note

This repository can statically validate source and XenForo add-on metadata, but a licensed XenForo runtime is not included in CI. Security-sensitive changes should additionally be tested on a real supported XenForo installation before being treated as production-ready.

---

## Türkçe

Güvenlik düzeltmeleri güncel `main` branch üzerinde uygulanır ve sonraki release paketine dahil edilir.

İstismar edilebilir güvenlik detaylarını, inceleme fırsatı verilmeden herkese açık issue olarak paylaşmayın. Sorumlu bildirim için Warext Studios destek Discord sunucusunu kullanabilirsiniz:

**Discord:** https://discord.gg/tgsV5XMcFS

Özellikle klan rolünden forum moderasyonuna yetki sızması, izinsiz Owner/Manager ataması, sahiplik devri veya ACP izin bypass'ı, askıya alınmış klanı yetkisiz açma, başka klanın üyelerini yönetme, XSS/SQL injection/CSRF ve gizli başvuru cevaplarının açığa çıkması kritik güvenlik konusu kabul edilir.
