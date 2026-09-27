# Organizations database maintenance — design

Date: 2026-09-27
Target: Organizations 1.2.17
Platform: Joomla 6.1.3

## Goal

Add a dedicated **Organizations → Gestione database** page for verified backups, controlled full restore and safe canonical-schema maintenance. The feature must operate only on Organizations-owned tables and must never mutate People, Membership, Competitions, Documents, Joomla core tables or any other extension.

## Owned tables

Functional tables, included in backup/restore and eligible for empty/recreate:

- `#__xdecaroorganizations_organizations`
- `#__xdecaroorganizations_bodies`
- `#__xdecaroorganizations_appointments`
- `#__xdecaroorganizations_delegations`
- `#__xdecaroorganizations_affiliations`

Maintenance tables, never included in functional restore and preserved by empty/recreate:

- `#__xdecaroorganizations_backups`
- `#__xdecaroorganizations_maintenance_log`

## Canonical schema

A PHP `DatabaseSchemaDefinition` is the runtime source of truth for the current seven Organizations-owned tables. It defines columns, primary keys, indexes, constraints and functional/maintenance role. `install.mysql.utf8mb4.sql` remains the Joomla install schema and CI must guard against drift.

Unknown columns/indexes/tables are reported but never deleted automatically. Repair may remove only exact objects explicitly listed in a version-controlled legacy-removal allowlist.

## Backup

A backup is a ZIP archive, not CSV/Excel. It contains exactly:

- `manifest.json`
- `data.json`
- `SHA256SUMS.txt`

The payload contains only the five functional tables and preserves IDs, UUIDs and relationships. Metadata includes component/schema version, UTC creation time, actor, reason, per-table counts, payload checksum, file checksum and size.

Files are stored in a private directory outside the Joomla web root. The default is `../private/xdecaroorganizations/backups`; administrators may configure another absolute path. Backup downloads go only through an ACL-protected controller.

## Restore

Before restore, the ZIP is validated for size, exact allowed entries, path safety, JSON structure, format/version, payload checksum, exact table whitelist and valid organization/entity UUIDs.

A full restore always creates and verifies a safety backup first. Current functional data is then replaced in dependency-safe order. IDs and UUIDs from the source backup are preserved. Self-references (`organizations.parent_id`, `bodies.parent_id`) are restored in two phases so foreign-key checks do not need to be globally disabled.

Maintenance history and backup metadata are never replaced by a functional restore.

## Database maintenance

### Check database

Read-only comparison of live schema against the canonical definition. It reports missing/unexpected tables, missing/incompatible/unknown columns, missing/incompatible/unknown indexes, engine and collation differences.

### Repair database

Conservative repair only. It may create missing canonical tables, add/adjust known columns and add/replace canonical indexes. Unknown custom objects are reported and preserved unless explicitly allowlisted as legacy.

### Empty Organizations data

Requires exact typed confirmation `SVUOTA`, CSRF, `core.manage` and `organizations.database_destructive`.

Flow: create + verify safety backup, delete only five functional tables in dependency-safe order, reset auto-increments when supported, preserve both maintenance tables, post-check and audit log.

### Recreate Organizations database

Requires exact typed confirmation `RICREA`, CSRF, `core.manage` and `organizations.database_destructive`.

Flow: create + verify safety backup, drop only the five functional tables using an explicit whitelist, recreate them from the canonical schema in dependency-safe order, preserve/check maintenance tables, post-check and audit log. Data is not automatically restored.

## ACL

- page/check: `core.manage`
- backup/download/verify/delete: `organizations.backup`
- restore: `organizations.restore`
- repair: `core.manage` + `organizations.database_repair`
- empty/recreate: `core.manage` + `organizations.database_destructive`

All mutating actions require CSRF server-side. JavaScript confirmation is only a UX aid; the server independently checks `SVUOTA`/`RICREA`.

## UI

New submenu order:

1. Dashboard
2. Organizzazioni
3. Duplicati
4. Gestione database
5. Informazioni

The page contains Backup, Restore, Manutenzione database, Differenze rilevate, Backup disponibili and Attività manutenzione. No “Cancellati” block is duplicated here: organization trash remains part of normal organization management.

Backup table columns: Data, Organizzazioni, Organi, Incarichi, Deleghe, Affiliazioni, Versione, Dimensione, SHA256, Azioni.

Destructive operations show a strong warning that external UUID references in Membership, Competitions, Documents or other extensions can remain unresolved until data is restored/relinked. Organizations never edits those external tables directly.

## Safety rules

- no wildcard/prefix DROP or DELETE;
- destructive operations use exact functional-table whitelist;
- safety backup must exist and pass SHA256 verification before destructive work;
- no secrets or full payloads in maintenance logs;
- restore and maintenance preserve IDs/UUIDs when restoring data;
- expected operational errors return a controlled Joomla message, never raw stack traces;
- responsive/light/dark UI consistent with existing Organizations administration.

## Success criteria

An authorized administrator can create/verify/download/delete backups, preview and fully restore a backup with automatic safety backup, inspect/repair schema drift, empty functional Organizations data with `SVUOTA`, recreate the five functional tables with `RICREA`, and review maintenance activity without touching any non-Organizations data.