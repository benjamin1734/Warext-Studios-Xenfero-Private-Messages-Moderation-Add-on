# Entegrasyon yüzeyleri

Bu addon aşağıdaki XenForo yüzeylerini kullanır:

- Public route: moderatör rapor inceleme paneli
- Admin route: dashboard, talepler ve audit logları
- Report entity class extension: rapor kapanınca erişim iptali
- Cron entry: süresi geçmiş erişim ve karar kayıtlarının temizlenmesi
- Setup schema: erişim ve bağlam talebi tabloları

XenForo development output dosyaları kurulum sırasında import edilmelidir. Release ZIP'i hazırlanırken development output verileri XenForo'nun data XML dosyalarına export edilmelidir.

XenForo resmi geliştirme dokümanında development output dosyalarının development mode için olduğunu ve release sırasında data çıktısının kullanılması gerektiğini belirtir. Kurulumda addon rebuild/import işlemi uygulanmalıdır.
