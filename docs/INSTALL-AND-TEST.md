# Kurulum ve doğrulama

## Dosyalar

Eklenti kaynak yolu:

`src/addons/Warext/PrivateModeration`

Release ZIP'i Admin CP arşiv yükleyicisine doğrudan verilebilir; ZIP içinde `upload/src/addons/Warext/PrivateModeration` bulunur. Manuel kurulumda `upload` klasörünün içeriğini XenForo köküne birleştirin.

Bu klasörü XenForo kurulumundaki aynı konuma yerleştirin. XenForo geliştirme kurulumu kullanıyorsanız `_output` verilerini geliştirici içe aktarma komutuyla içeri alın.

## Yetkiler

- Raporu görecek moderatörler: addon `warextPmViewReported`.
- Sınırlı bağlam açacak moderatörler: addon `warextPmOpenContext`.
- Geniş bağlam talep edecek moderatörler: addon `warextPmRequestExtended`.
- Geniş bağlam onaylayacak kıdemli moderatörler: addon `warextPmApproveExtended` ve XenForo `general > bypassUserPrivacy`.
- Audit ekranını görecek yöneticiler: addon `warextPmViewAudit`.
- Raporlanan mesajda işlem uygulayacak moderatörler: addon `warextPmModerateMessage`; ayrıca XenForo'nun native conversation message silme kontrolü geçilmelidir.

Eklenti tüm özel mesajları taramaz ve başka conversation'lara erişim sağlamaz.

## Akış

1. Kullanıcı conversation mesajını XenForo'nun yerleşik raporlama sistemiyle raporlar.
2. Moderatör rapor ID'siyle `warext-pm-moderation/reports/{report_id}/` adresini açar.
3. İlk ekranda yalnızca raporlanan mesaj görünür.
4. En fazla 5 mesajlık bağlam gerekçeyle 30 dakika açılır.
5. 5 mesajdan fazla istenirse talep kıdemli onayına düşer.
6. Onaylı geniş bağlam talebi yalnızca talep eden moderatöre 60 dakika verilir.
7. Erişim kayıtları `xf_warext_pm_access_log`, talepler `xf_warext_pm_context_request`, audit olayları `xf_warext_pm_audit_log` tablolarında tutulur.

## Kurulum sonrası test

- Raporlanmamış conversation mesajının URL'de açılamadığını doğrulayın.
- Post/conversation raporlarının bu ekranda görünmediğini doğrulayın.
- 5 mesajlık bağlamın yalnızca ilgili conversation'dan geldiğini doğrulayın.
- 6+ mesaj talebinin otomatik olarak onay beklediğini doğrulayın.
- Yetkisiz hesabın rapor ve onay endpoint'lerine erişemediğini doğrulayın.
- Süresi geçmiş erişimin bağlam göstermediğini doğrulayın.
- Kapatılan raporun erişimi engellediğini doğrulayın.
- `warextPmModerateMessage` yetkili moderatörün en az 10 karakter gerekçeyle raporlanan mesajı silebildiğini, raporun kapandığını ve `delete_message` audit olayının oluştuğunu doğrulayın.
- Bu yetki olmayan hesabın mesaj işlemi endpoint'ine erişemediğini doğrulayın.

