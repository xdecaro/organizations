# Organizations 1.2.17 database maintenance implementation plan

**Goal:** add safe backup/restore and canonical database maintenance on a dedicated administrator page without touching external extension data.

**Architecture:** follow the proven People maintenance pattern, but keep Organizations services, schema, ACL and storage independent. Five functional tables are the only backup/destructive target; two new maintenance tables store backup metadata and audit activity.

**Tech:** Joomla 6 MVC/DI, DatabaseInterface, ACL/CSRF, ZipArchive, Web Asset Manager, PHP canonical schema, GitHub Actions contracts/runtime installation tests.

## Task 1 — Lock the safety contract with failing tests

Files:
- create `tests/organizations-1.2.17-database-maintenance-contract.php`
- create `.github/workflows/organizations-1.2.17-database-maintenance.yml`

Assertions:
- exactly five functional and two maintenance tables in canonical definition;
- explicit typed confirmations `SVUOTA` and `RICREA`;
- dedicated ACL actions and menu entry;
- backup/restore/controller/services/view/assets exist;
- maintenance tables are excluded from functional destructive whitelist;
- no prefix/wildcard destructive logic.

Run contract and confirm RED before implementation.

## Task 2 — Canonical schema + maintenance infrastructure

Files:
- create `component/admin/src/Service/DatabaseSchemaDefinition.php`
- create `component/admin/src/Service/DatabaseSchemaInspector.php`
- create `component/admin/src/Service/DatabaseMaintenanceService.php`
- create `component/admin/src/Service/MaintenanceLogService.php`
- update `component/admin/sql/install.mysql.utf8mb4.sql`
- create `component/admin/sql/updates/mysql/1.2.17.sql`

Implement seven-table canonical definition. Add backup/log tables. Inspector is read-only. Repair is conservative. Empty/recreate target exact five functional tables in dependency-safe order and preserve maintenance tables.

## Task 3 — Verified backup and controlled restore

Files:
- create `component/admin/src/Service/BackupStorageService.php`
- create `component/admin/src/Service/BackupService.php`
- create `component/admin/src/Service/RestoreService.php`
- create `component/admin/src/Controller/MaintenanceController.php`

Implement private storage outside webroot, exact ZIP entries, SHA256, table whitelist, per-table counts, preview verification, automatic verified safety backup, full restore preserving IDs/UUIDs and two-phase self-parent restoration.

## Task 4 — Joomla DI, ACL and administrator UI

Files:
- update `component/admin/access.xml`
- update `component/admin/config.xml`
- update `component/admin/services/provider.php`
- update `component/admin/src/Extension/OrganizationsComponent.php`
- create `component/admin/src/View/Maintenance/HtmlView.php`
- create `component/admin/tmpl/maintenance/default.php`
- create `component/media/js/database-maintenance.js`
- update `component/media/css/admin.css`
- update `component/media/joomla.asset.json`
- update `component/xdecaroorganizations.xml`
- update language files IT/EN

Add submenu `Gestione database`, status/differences UI, backup table, activity log, typed confirmations and responsive/dark-mode layout.

## Task 5 — Version/release and verification

Files:
- bump `VERSION`, component/package manifests/assets to `1.2.17`
- update release workflow to run the new contract
- update release notes

Verification:
- PHP syntax on changed PHP files;
- contract GREEN;
- existing CI GREEN on PHP matrices;
- Joomla clean install and supported upgrade runtime GREEN;
- release ZIP deterministic/installable;
- review PR diff for accidental cross-component table access or broad destructive queries.

Merge only after all checks are green; release workflow then publishes 1.2.17 and updater metadata.