# Warext Private Messages Moderation System

XenForo 2.2+ için raporlanan özel konuşma mesajlarını sınırlı ve denetlenebilir şekilde incelemeye yardımcı olan eklenti.

## Uygulanan akış

- Raporlanan conversation mesajı rapor ID'si üzerinden açılır.
- İlk görünüm yalnızca raporlanan mesajdır.
- Moderatör gerekçe yazarak 5 mesajlık sınırlı bağlam erişimi açabilir.
- 5 mesajdan daha geniş erişim ayrı bir talep oluşturur.
- Geniş erişim için `warn` ve `bypassUserPrivacy` yetkilerine sahip kıdemli moderatör onayı gerekir.
- Geniş erişim talebi yalnızca aynı conversation ve aynı rapor için geçerlidir.
- Erişim süreleri sınırlıdır: sınırlı bağlam 30 dakika, geniş bağlam 60 dakika.
- Her erişim isteği, onay ve görüntüleme için audit kayıtları tutulur.
- Mesaj içerikleri addon tablolarına kopyalanmaz.
- Rapor kapandığında erişim kayıtlarının süresi sonlandırılabilir.

## Yönetim URL'si

Raporlanan conversation mesajı için:

`/warext-pm-moderation/reports/{report_id}/`

Örnek:

`https://forum.example.com/warext-pm-moderation/reports/123/`

Buradaki `123`, XenForo rapor ID'sidir. Kullanıcı bu URL'yi açabilmek için XenForo'nun `general > warn` yetkisine sahip olmalıdır.

## Admin CP ve moderatör paneli

- Moderatör paneli: `/warext-pm-moderation/reports/{report_id}/`
- Admin CP dashboard: `admin.php?warext-pm-moderation/`
- Bağlam talepleri: `admin.php?warext-pm-moderation/requests/`
- Audit logları: `admin.php?warext-pm-moderation/logs/`
- Admin CP üzerinden talepler onaylanabilir/reddedilebilir ve erişimler yönetilebilir.

## Kurulum

1. `src/addons/Warext/PrivateModeration` klasörünü XenForo kurulumundaki `src/addons/Warext/PrivateModeration` yoluna kopyalayın.
2. Admin CP > Add-ons bölümünden eklentiyi kurun veya yükseltin.
3. Moderatör grubunda raporları yönetme ve uyarı yetkilerini kontrol edin.
4. Geniş bağlam onayı verecek gruba ayrıca XenForo `Bypass user privacy` yetkisini verin.
5. Kullanıcı aydınlatma metninde raporlanan özel mesaj ve gerekli sınırlı bağlam erişimini açıkça belirtin.

## Güvenlik sınırları

- Rastgele özel konuşma arama veya tarama yoktur.
- Bir kullanıcının diğer conversations'ları görünmez.
- Audit log'a mesaj gövdesi yazılmaz.
- Bağlam erişimi gerekçe olmadan açılamaz.
- Geniş erişim tek moderatörün anlık yetkisiyle açılamaz; kıdemli onay gerekir.

## Doğrulama

Bu repo XenForo'nun çekirdek kaynak kodunu veya lisanslı bir test kurulumu içermez. Bu nedenle kurulum sonrası test kurulumu üzerinde şu akış doğrulanmalıdır:

- conversation mesajı raporlama
- report ID ile moderation URL'sine erişim
- 5 mesajlık bağlam
- geniş bağlam talebi
- kıdemli onay
- süresi dolan erişim
- kapatılmış raporda erişimin engellenmesi

Bu eklenti hukuki danışmanlık sağlamaz. Kurulumdan önce forumun aydınlatma metni, saklama politikası ve moderatör gizlilik prosedürü ayrıca gözden geçirilmelidir.
