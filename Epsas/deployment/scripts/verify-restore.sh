#!/usr/bin/env bash
set -euo pipefail

: "${RESTORE_DATABASE_URL:?RESTORE_DATABASE_URL debe apuntar a una base aislada}"
: "${BACKUP_FILE:?BACKUP_FILE es obligatorio}"

test -s "$BACKUP_FILE"
if [ -f "$BACKUP_FILE.sha256" ]; then
    sha256sum --check "$BACKUP_FILE.sha256"
fi

pg_restore --clean --if-exists --no-owner --dbname="$RESTORE_DATABASE_URL" "$BACKUP_FILE"
DB_URL="$RESTORE_DATABASE_URL" php artisan app:verify-restored-backup --restored-database --source-backup="$BACKUP_FILE" --acknowledge
