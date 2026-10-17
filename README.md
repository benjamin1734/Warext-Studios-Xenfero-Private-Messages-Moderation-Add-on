# Warext Private Messages Moderation System

XenForo 2.2+ için raporlanan özel konuşma mesajlarını sınırlı ve denetlenebilir şekilde incelemeye yardımcı olan eklenti.

## Güvenlik modeli

- İlk görünüm yalnızca raporlanan mesajdır.
- Bağlam erişimi ayrı bir yetki ve gerekçe ile açılır.
- Varsayılan bağlam penceresi sınırlıdır.
- Daha geniş geçmiş için kıdemli moderatör onayı gerekir.
- Başka conversation'lar hiçbir koşulda bu eklentinin kapsamına girmez.
- Her görüntüleme ve moderasyon işlemi audit log'a yazılır.
- Mesaj içerikleri eklentiye kopyalanmaz; XenForo'nun mevcut conversation/message kayıtları okunur.
- Rapor kapandığında geçici bağlam erişimi sona erer.

## Durum

İlk iskelet oluşturuldu. Rapor entegrasyonu, izinler, bağlam akışı, log ekranı ve testler sonraki adımlarda eklenecektir.

## Hedef

Bu eklenti hukuki danışmanlık sağlamaz. Kurulumdan önce forumun aydınlatma metni, saklama politikası ve moderatör gizlilik prosedürü ayrıca gözden geçirilmelidir.
