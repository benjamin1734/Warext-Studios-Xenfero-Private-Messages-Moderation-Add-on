# Admin CP ve moderatör paneli

## Admin CP

Addon kurulduktan sonra yönetim paneli:

- `admin.php?warext-pm-moderation/`
- `admin.php?warext-pm-moderation/requests/`
- `admin.php?warext-pm-moderation/logs/`

Admin CP tarafında:

- Bekleyen bağlam talepleri listelenir.
- Talepler onaylanır veya reddedilir.
- Onay, talep eden moderatör için süreli erişim kaydı oluşturur.
- Audit logları incelenir.
- Rapor bazında aktif erişimler iptal edilebilir.

## Moderatör paneli

- `warext-pm-moderation/reports/{report_id}/`

Moderatör yalnızca XenForo rapor ID'siyle ilişkili conversation mesajını görür. Bağlam açma işlemi gerekçe ister.

## Güvenlik

Admin CP erişimi yalnızca XenForo yöneticilerine açıktır. Geniş bağlam onayı verildiğinde erişim, talep sahibi moderatörün kullanıcı ID'sine bağlanır; onaylayan yönetici otomatik olarak mesaj içeriğine erişim kazanmaz.
