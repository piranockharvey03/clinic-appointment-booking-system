# Backups and Recovery Module

## Path

- backups
- app/admin/backup-database.php

## Purpose

Supports database recovery by storing SQL dumps and exposing admin-triggered backup generation.

## Key Components

- backups/backup*medicare*\*.sql: point-in-time SQL snapshots.
- backups/README.md: backup and restore instructions.
- app/admin/backup-database.php: administrative backup operation entry point.

## Inbound Dependencies

- Admin portal action that triggers backup creation.

## Outbound Dependencies

- Filesystem storage in backups.
- Database dump output.

## Data and Entities

- Complete database export artifacts, including operational records and hashed credentials.

## Security Notes

- Backup files are sensitive assets and should be access-controlled.
- Recovery instructions are present but operational hardening is limited.

## Observed Risks

- Backups may be stored unencrypted.
- Retention and rotation policy is not visible in code.
- Access to backup artifacts can become a data leak vector.

## Recommended Improvements

1. Encrypt backup files at rest.
2. Add retention, rotation, and off-site copy policy.
3. Log backup creation, download, and restore events.
4. Restrict backups folder exposure at web server level.
