# Database Backups

This directory contains database backups created through the Admin Settings page.

## Backup Format

- Files are named: `backup_[database]_[YYYY-MM-DD_HH-mm-ss].sql`
- Backups are created in SQL format and can be restored using phpMyAdmin or MySQL command line

## Restore Instructions

### Using phpMyAdmin:

1. Open phpMyAdmin
2. Select the database (medicare)
3. Click on "Import" tab
4. Choose the backup file
5. Click "Go"

### Using MySQL Command Line:

```bash
mysql -u root -p medicare < backup_medicare_YYYY-MM-DD_HH-mm-ss.sql
```

## Security Note

- Backup files may contain sensitive data
- Keep backups secure and do not share publicly
- This directory is excluded from version control via .gitignore
