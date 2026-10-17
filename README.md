# Warext Private Messages Moderation System

XenForo 2.2+ için raporlanan özel konuşma mesajlarını sınırlı ve denetlenebilir şekilde incelemeye yardımcı olan eklenti.

## Uygulanan akış

- Raporlanan conversation mesajı rapor ID'si üzerinden açılır.
- İlk görünüm yalnızca raporlanan mesajdır.
- Moderatör gerekçe yazarak 5 mesajlık sınırlı bağlam erişimi açabilir.
- 5 mesajdan daha geniş erişim ayrı bir talep oluşturur.
- Geniş erişim için addon `warextPmApproveExtended` ve XenForo `bypassUserPrivacy` yetkilerine sahip kıdemli moderatör onayı gerekir.
- Geniş erişim talebi yalnızca aynı conversation ve aynı rapor için geçerlidir.
- Erişim süreleri sınırlıdır: sınırlı bağlam 30 dakika, geniş bağlam 60 dakika.
- Her rapor görüntüleme, bağlam açma, talep, onay, ret, iptal ve rapor kapatma işlemi ayrı audit olayı olarak tutulur.
- Mesaj içerikleri addon tablolarına kopyalanmaz.
- Audit kaydı yalnızca rapor/conversation/mesaj kimliklerini ve işlem metadatasını içerir; mesaj gövdesi saklanmaz.
- Rapor kapandığında aktif erişimler ve bekleyen talepler sonlandırılır.
- Audit logları varsayılan olarak 180 gün, erişim kayıtları ise süresi dolduktan sonra 24 saat tutulur.

## Yönetim URL'si

Raporlanan conversation mesajı için:

`/warext-pm-moderation/reports/{report_id}/`

Örnek:

`https://forum.example.com/warext-pm-moderation/reports/123/`

Buradaki `123`, XenForo rapor ID'sidir. Kullanıcı bu URL'yi açabilmek için addon `warextPmViewReported` yetkisine sahip olmalıdır.

## Admin CP ve moderatör paneli

- Moderatör paneli: `/warext-pm-moderation/reports/{report_id}/`
- Admin CP dashboard: `admin.php?warext-pm-moderation/`
- Bağlam talepleri: `admin.php?warext-pm-moderation/requests/`
- Audit logları: `admin.php?warext-pm-moderation/logs/`
- Admin CP üzerinden talepler onaylanabilir/reddedilebilir ve erişimler yönetilebilir.
- Admin CP içinde doğrudan “Özel Mesaj Moderasyonu” menüsü bulunur.
- `warextPmModerateMessage` yetkisi olan moderatör, raporlanan mesaj için gerekçe yazarak XenForo'nun native `canDelete()` kontrolünden geçen silme işlemini uygulayabilir; rapor otomatik kapanır ve işlem audit'e yazılır.
- Moderatör panelinden rapor kapatılabilir; kapatma işlemi aktif erişimleri ve bekleyen talepleri iptal eder.

## Kurulum

1. `_releases/Warext-PrivateModeration-1.2.1.zip` paketini Admin CP > Add-ons > Install/upgrade bölümünden yükleyin.
2. Moderatör grubunda `warextPmViewReported` ve `warextPmOpenContext` izinlerini açın.
3. Geniş bağlam talep edecek gruba `warextPmRequestExtended` verin.
4. Onaylayacak kıdemli gruba `warextPmApproveExtended` ve XenForo `Bypass user privacy` yetkisini verin.
5. Audit yöneticilerine `warextPmViewAudit` verin.
6. Mesaj üzerinde işlem yapacak moderatörlere `warextPmModerateMessage` verin; XenForo native içerik silme kontrolü ayrıca zorunludur.
7. Kullanıcı aydınlatma metninde raporlanan özel mesaj ve gerekli sınırlı bağlam erişimini açıkça belirtin.

## Güvenlik sınırları

- Rastgele özel konuşma arama veya tarama yoktur.
- Bir kullanıcının diğer conversations'ları görünmez.
- Audit log'a mesaj gövdesi yazılmaz.
- Bağlam erişimi gerekçe olmadan açılamaz.
- Geniş erişim tek moderatörün anlık yetkisiyle açılamaz; kıdemli onay gerekir.

## Release paketleme

Geliştirme çıktısı (`_output`) yalnızca geliştirme içindir. XenForo release paketinde `upload/src/addons/Warext/PrivateModeration/` altında `_data` XML dosyaları ve `hashes.json` bulunmalıdır. Paket üretmek için repo kökünde `tools/Build-Release.ps1` scriptini çalıştırın. Script, `_output` içeriğinden `_data` XML’lerini üretir, `_output` klasörünü release ZIP’ine koymaz ve XenForo'nun beklediği upload kökünü oluşturur.

## Doğrulama

Bu repo XenForo'nun çekirdek kaynak kodunu veya lisanslı bir test kurulumu içermez. Bu nedenle kurulum sonrası test kurulumu üzerinde şu akış doğrulanmalıdır:

- conversation mesajı raporlama
- report ID ile moderation URL'sine erişim
- 5 mesajlık bağlam
- geniş bağlam talebi
- kıdemli onay
- süresi dolan erişim
- kapatılmış raporda erişimin engellenmesi
- her görüntüleme ve karar için audit olayının oluşması
- kapatılan raporda bekleyen talebin `cancelled` olması
- addon izinleri olmayan yöneticinin log/talep ekranlarına erişememesi

Bu eklenti hukuki danışmanlık sağlamaz. Kurulumdan önce forumun aydınlatma metni, saklama politikası ve moderatör gizlilik prosedürü ayrıca gözden geçirilmelidir.

