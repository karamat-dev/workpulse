## Backup & Disaster Recovery (WorkPulse)

This project runs on **MySQL** + Laravel. A practical backup plan has two parts:

- **Database backup**: MySQL schema + data
- **File backup**: `storage/app` (employee documents/uploads, exports, etc.)

### Database backup (MySQL)

From the project root (`d:\wamp64\www\workplus\workpulse`), run:

```powershell
.\scripts\backup-mysql.ps1
```

Output is written to `storage\app\backups\mysql\`.

### Database restore (MySQL)

Pick a `.sql` file from `storage\app\backups\mysql\` and run:

```powershell
.\scripts\restore-mysql.ps1 -SqlFile "storage\\app\\backups\\mysql\\workpulse-YYYYMMDD-HHMMSS.sql"
```

### Storage backup (uploads)

If you store employee documents/uploads under `storage/app`, back up the folder:

- `storage/app`
- optionally `public/storage` (if you use `php artisan storage:link`)

### Recommended retention

- Keep **daily backups for 14 days**
- Keep **weekly backups for 8 weeks**
- Keep **monthly backups for 12 months**

### Disaster recovery checklist

- Restore MySQL database from latest `.sql`
- Restore `storage/app` from last file backup
- Verify `.env` DB settings (`DB_DATABASE=workpulse`)
- Run migrations only if needed:

```powershell
& "d:\wamp64\bin\php\php8.4.0\php.exe" artisan migrate
```

