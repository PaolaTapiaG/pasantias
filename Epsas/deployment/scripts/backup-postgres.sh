#!/usr/bin/env bash
set -euo pipefail

: "${DATABASE_URL:?DATABASE_URL es obligatorio}"
: "${BACKUP_DIR:=/var/backups/epsas}"

mkdir -p "$BACKUP_DIR"
umask 077
filename="$BACKUP_DIR/epsas-$(date -u +%Y%m%dT%H%M%SZ).dump"
pg_dump "$DATABASE_URL" --format=custom --compress=9 --file="$filename"
test -s "$filename"
sha256sum "$filename" > "$filename.sha256"
find "$BACKUP_DIR" -type f -name 'epsas-*.dump' -mtime +30 -delete
find "$BACKUP_DIR" -type f -name 'epsas-*.dump.sha256' -mtime +30 -delete
echo "$filename"
