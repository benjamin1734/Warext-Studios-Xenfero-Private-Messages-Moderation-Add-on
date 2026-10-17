# Warext Studios | XenForo Private Messages Moderation System

## English

Controlled, report-scoped moderation access for XenForo private conversation messages. The add-on is designed around data minimisation: a moderator starts with the reported message, can open only the necessary nearby context, and must use a reason plus senior approval for wider access.

> **Current release:** [v1.2.3](https://github.com/benjamin1734/Warext-Studios-Xenfero-Private-Messages-Moderation-Add-on/releases/tag/v1.2.3)  
> **Install ZIP:** [Warext-PrivateModeration-1.2.3.zip](https://github.com/benjamin1734/Warext-Studios-Xenfero-Private-Messages-Moderation-Add-on/releases/download/v1.2.3/Warext-PrivateModeration-1.2.3.zip)

### Features

- Opens only the reported conversation message in the first view.
- Opens limited nearby context after a moderator writes a reason.
- Keeps extended context scoped to the same conversation and report.
- Requires separate senior approval for extended context.
- Does not expose a user's unrelated private conversations.
- Provides a moderator report page and dedicated Admin CP dashboard.
- Provides pending context requests and audit-log screens in Admin CP.
- Allows a permitted moderator to apply a native XenForo message deletion action.
- Closes active access and pending requests when a report is closed.
- Expires limited and extended access automatically.
- Logs access, requests, decisions and moderation actions without copying message bodies into add-on tables.

### Requirements

- XenForo 2.2.0 or newer
- PHP supported by the installed XenForo release
- XenForo native report, conversation and moderation permissions

### Installation

1. Download the [latest release ZIP](https://github.com/benjamin1734/Warext-Studios-Xenfero-Private-Messages-Moderation-Add-on/releases).
2. In XenForo Admin CP, open **Add-ons → Install/upgrade from archive**.
3. Upload Warext-PrivateModeration-1.2.3.zip as-is. Do not extract and re-zip it.
4. Configure the permissions described in [docs/ADMIN-SETUP.md](docs/ADMIN-SETUP.md).
5. Add the private-message moderation disclosure to the forum privacy/information notice.

The archive uses XenForo's expected upload/src/addons/Warext/PrivateModeration/ layout. The _no_upload/ directory contains documentation only and is not copied into the XenForo upload tree.

### Moderation flow

1. A user reports a private conversation message.
2. The first moderator view contains only the reported message.
3. Open limited context reveals the configured nearby message window after a reason is entered.
4. Request extended context creates a report-scoped request.
5. A senior moderator with the approval permission and XenForo privacy-bypass permission approves or rejects the request.
6. The moderator can take an allowed native moderation action or close the report.
7. Access and decisions are written to the audit trail; message bodies are not copied into audit tables.

### Permissions

| Permission | Purpose |
|---|---|
| warextPmViewReported | View the reported message through the report-scoped page |
| warextPmOpenContext | Open limited nearby context with a reason |
| warextPmRequestExtended | Request wider context |
| warextPmApproveExtended | Approve or reject wider context |
| warextPmViewAudit | View audit records in Admin CP |
| warextPmModerateMessage | Apply the add-on's native message moderation action |

Extended approval also checks XenForo's native bypassUserPrivacy permission. The add-on never provides unrestricted conversation browsing.

### Admin CP and moderator URLs

- Moderator report page: /warext-pm-moderation/reports/{report_id}/
- Admin CP dashboard: admin.php?warext-pm-moderation/
- Context requests: admin.php?warext-pm-moderation/requests/
- Audit logs: admin.php?warext-pm-moderation/logs/

### Privacy and security model

- Access is initiated by a report and remains tied to that report and conversation.
- The first view is limited to the reported message.
- Wider access needs a written reason and senior approval.
- Access, reason, decision, visible message IDs, operator and timestamps are auditable.
- Message contents are rendered from XenForo at request time and are not duplicated in add-on audit records.
- Closed reports invalidate active context access and pending requests.
- Audit records are retained for 180 days by default; expired access records are retained for 24 hours for operational cleanup.
- The add-on is not legal advice. The forum operator must define the lawful basis, information notice, retention schedule, staff confidentiality rules and data-subject process for its own deployment.

See [docs/SECURITY-MODEL.md](docs/SECURITY-MODEL.md) and [docs/KVKK-TURKISH-DRAFT.md](docs/KVKK-TURKISH-DRAFT.md).

### Repository structure

```
src/addons/Warext/PrivateModeration/  Development source and XenForo _output data
docs/                                  Installation, security and privacy documents
tools/                                 Release builder and validation scripts
_releases/                             Locally generated installation ZIP archives
.github/workflows/                     Release and local quality automation
```

### Build and validation

Run the following from the repository root on Windows PowerShell:

```powershell
./tools/Validate-Release.ps1
```

The validator checks JSON metadata, PHP syntax, generated XenForo XML, required archive paths, the add-on manifest and the generated SHA-256 hash map. GitHub Actions runs the same local package-quality gate on pushes, pull requests and manual dispatch.

Static/package validation is not a substitute for a licensed XenForo runtime installation. The final deployment should verify reporting, limited context, extended approval, expiration, closure, permissions and audit behaviour on the target XenForo version.

### Support

For questions, installation help and bug reports, use the [GitHub Issues](https://github.com/benjamin1734/Warext-Studios-Xenfero-Private-Messages-Moderation-Add-on/issues) page or the Warext Studios support Discord: [discord.gg/tgsV5XMcFS](https://discord.gg/tgsV5XMcFS).

---

## Türkçe

XenForo özel konuşma mesajlarına rapor kapsamında, sınırlı ve denetlenebilir moderasyon erişimi sağlar. Tasarım veri minimizasyonuna dayanır: moderatör ilk olarak yalnızca raporlanan mesajı görür, gerekli yakın bağlamı gerekçe yazarak açar; daha geniş erişim için kıdemli onayı gerekir.

> **Güncel sürüm:** [v1.2.3](https://github.com/benjamin1734/Warext-Studios-Xenfero-Private-Messages-Moderation-Add-on/releases/tag/v1.2.3)  
> **Kurulum ZIP'i:** [Warext-PrivateModeration-1.2.3.zip](https://github.com/benjamin1734/Warext-Studios-Xenfero-Private-Messages-Moderation-Add-on/releases/download/v1.2.3/Warext-PrivateModeration-1.2.3.zip)

### Özellikler

- İlk ekranda yalnızca raporlanan özel konuşma mesajını gösterir.
- Gerekçe yazıldıktan sonra sınırlı yakın bağlamı açar.
- Geniş bağlamı aynı konuşma ve aynı raporla sınırlar.
- Geniş bağlam için ayrı kıdemli onay yetkisi kullanır.
- Kullanıcının ilgisiz özel konuşmalarını görünür kılmaz.
- Moderatör rapor ekranı ve ayrı Admin CP paneli sunar.
- Bekleyen bağlam talepleri ve denetim kayıtları için Admin CP ekranları sunar.
- Yetkili moderatörün XenForo native mesaj silme işlemini uygulamasına izin verir.
- Rapor kapatıldığında aktif erişimleri ve bekleyen talepleri kapatır.
- Sınırlı ve geniş erişimi otomatik süre dolumu ile yönetir.
- Erişim, talep, karar ve moderasyon işlemlerini mesaj gövdesini ayrı tablolara kopyalamadan kaydeder.

### Gereksinimler

- XenForo 2.2.0 veya üzeri
- Kurulu XenForo sürümünün desteklediği PHP sürümü
- XenForo native rapor, konuşma ve moderasyon yetkileri

### Kurulum

1. [Güncel release ZIP'ini](https://github.com/benjamin1734/Warext-Studios-Xenfero-Private-Messages-Moderation-Add-on/releases) indirin.
2. XenForo Admin CP içinde **Eklentiler → Arşivden yükle/yükselt** bölümünü açın.
3. Warext-PrivateModeration-1.2.3.zip dosyasını olduğu gibi yükleyin; dosyayı açıp tekrar ZIP yapmayın.
4. [docs/ADMIN-SETUP.md](docs/ADMIN-SETUP.md) içindeki eklenti ve XenForo yetkilerini yapılandırın.
5. Forumun aydınlatma/gizlilik metnine özel mesaj moderasyonu açıklamasını ekleyin.

Arşiv XenForo'nun beklediği upload/src/addons/Warext/PrivateModeration/ yapısıyla hazırlanır. Arşivdeki _no_upload/ klasörü yalnızca dokümantasyon içindir.

### Moderasyon akışı

1. Kullanıcı bir özel konuşma mesajını raporlar.
2. İlk moderatör görünümünde yalnızca raporlanan mesaj bulunur.
3. Sınırlı bağlamı aç seçeneği gerekçe girildikten sonra yakın mesaj penceresini açar.
4. Geniş bağlam talep et rapora bağlı bir talep oluşturur.
5. Onay yetkisi ve XenForo gizlilik aşma yetkisi bulunan kıdemli moderatör talebi onaylar veya reddeder.
6. Moderatör izin verilen native moderasyon işlemini uygulayabilir veya raporu kapatabilir.
7. Erişim ve kararlar denetim kaydına yazılır; mesaj gövdeleri denetim tablolarına kopyalanmaz.

### Yetkiler

| Yetki | Amacı |
|---|---|
| warextPmViewReported | Rapor kapsamında raporlanan mesajı görüntülemek |
| warextPmOpenContext | Gerekçeyle sınırlı yakın bağlamı açmak |
| warextPmRequestExtended | Geniş bağlam talep etmek |
| warextPmApproveExtended | Geniş bağlamı onaylamak veya reddetmek |
| warextPmViewAudit | Admin CP denetim kayıtlarını görüntülemek |
| warextPmModerateMessage | Eklentinin native mesaj moderasyon işlemini uygulamak |

Geniş bağlam onayında XenForo'nun native bypassUserPrivacy yetkisi de kontrol edilir. Eklenti sınırsız özel konuşma taraması sağlamaz.

### Admin CP ve moderatör URL'leri

- Moderatör rapor sayfası: /warext-pm-moderation/reports/{report_id}/
- Admin CP paneli: admin.php?warext-pm-moderation/
- Bağlam talepleri: admin.php?warext-pm-moderation/requests/
- Denetim kayıtları: admin.php?warext-pm-moderation/logs/

### KVKK ve güvenlik modeli

- Erişim bir raporla başlar ve rapor ile konuşmaya bağlı kalır.
- İlk görünüm raporlanan mesajla sınırlıdır.
- Geniş erişim yazılı gerekçe ve kıdemli onayı ister.
- Erişim, gerekçe, karar, görülen mesaj kimlikleri, görevli ve zaman bilgileri kaydedilir.
- Mesaj içerikleri istek anında XenForo'dan okunur; eklentinin denetim kayıtlarında tekrar saklanmaz.
- Kapatılan raporlar aktif bağlam erişimlerini ve bekleyen talepleri geçersiz kılar.
- Denetim kayıtları varsayılan olarak 180 gün, süresi dolan erişim kayıtları operasyonel temizlik için 24 saat tutulur.
- Bu eklenti hukuki danışmanlık değildir. Forum işletmecisi kendi kurulumu için hukuki sebebi, aydınlatma metnini, saklama süresini, görevli gizlilik yükümlülüklerini ve ilgili kişi süreçlerini ayrıca belirlemelidir.

[docs/SECURITY-MODEL.md](docs/SECURITY-MODEL.md) ve [docs/KVKK-TURKISH-DRAFT.md](docs/KVKK-TURKISH-DRAFT.md) belgelerine bakın.

### Repo yapısı

```
src/addons/Warext/PrivateModeration/  Geliştirme kaynakları ve XenForo _output verileri
docs/                                  Kurulum, güvenlik ve gizlilik belgeleri
tools/                                 Release üretici ve doğrulama scriptleri
_releases/                             Yerel olarak üretilen kurulum ZIP'leri
.github/workflows/                     Release ve yerel kalite otomasyonu
```

### Paket üretme ve doğrulama

Repo kökünde Windows PowerShell ile çalıştırın:

```powershell
./tools/Validate-Release.ps1
```

Doğrulayıcı JSON metadatasını, PHP sözdizimini, üretilen XenForo XML'lerini, gerekli arşiv yollarını, eklenti manifestini ve SHA-256 hash listesini kontrol eder. GitHub Actions aynı yerel paket kalite kapısını push, pull request ve manuel çalıştırma sırasında uygular.

Statik/paket doğrulaması lisanslı XenForo çalışma zamanı kurulumunun yerine geçmez. Hedef XenForo sürümünde rapor, sınırlı bağlam, geniş onay, süre dolumu, kapatma, yetkiler ve audit akışı ayrıca doğrulanmalıdır.

### Destek

Sorular, kurulum desteği ve hata bildirimleri için [GitHub Issues](https://github.com/benjamin1734/Warext-Studios-Xenfero-Private-Messages-Moderation-Add-on/issues) sayfasını veya Warext Studios destek Discord sunucusunu kullanabilirsiniz: [discord.gg/tgsV5XMcFS](https://discord.gg/tgsV5XMcFS).

