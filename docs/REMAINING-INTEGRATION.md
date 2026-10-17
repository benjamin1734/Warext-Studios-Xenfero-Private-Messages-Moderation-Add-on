# Entegrasyon yüzeyleri

Bu addon aşağıdaki XenForo yüzeylerini kullanır:

- Public route: moderatör rapor inceleme paneli
- Admin route: dashboard, talepler ve audit logları
- Admin navigation: Admin CP > Content altında “Özel Mesaj Moderasyonu” bağlantısı
- Moderation action: raporlanan conversation mesajını native XenForo delete kontrolüyle silme, raporu kapatma ve audit kaydı
- Report entity class extension: rapor kapanınca erişim iptali
- Cron entry: süresi geçmiş erişim ve karar kayıtlarının temizlenmesi
- Setup schema: erişim ve bağlam talebi tabloları

Geliştirme çıktısı (`_output`) yalnızca geliştirme içindir. `tools/Build-Release.ps1` scripti bu çıktıyı `_data` XML dosyalarına dönüştürür, `_output` klasörünü release paketinden çıkarır ve `hashes.json` oluşturur.

XenForo resmi geliştirme dokümanında development output dosyalarının development mode için olduğu ve release sırasında `_data` çıktısının kullanılması gerektiği belirtilir. Paket kurulumunda addon install/upgrade işlemi uygulanmalıdır.

