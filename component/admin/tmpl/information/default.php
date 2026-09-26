<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$info = (array) $this->diagnostics;
$installedVersion = (string) ($info['installed_version'] ?? '0.0.0');
$componentVersion = (string) ($info['component_version'] ?? '');
$packageVersion = (string) ($info['package_version'] ?? '');
$installationConsistent = !empty($info['installation_consistent']);
$environmentCompatible = !empty($info['environment_compatible']);
$tableHealth = (array) ($info['table_health'] ?? []);
$tablesPresent = !empty($info['tables_present']);
$core = (array) ($info['core'] ?? []);
$integrations = (array) ($info['integrations'] ?? []);
$criticalIssues = (array) ($info['critical_issues'] ?? []);
$warnings = (array) ($info['warnings'] ?? []);
$systemOk = $criticalIssues === [];
$updateSiteEnabled = !empty($info['update_site_enabled']);
$updateAvailable = !empty($info['update_available']);
$updateState = (string) ($info['update_state'] ?? 'inactive');
$latestVersion = (string) ($info['latest_version'] ?? '');
$lastCheck = (int) ($info['last_check_timestamp'] ?? 0);

$updateLabel = match ($updateState) {
    'current' => Text::_('COM_XDECAROORGANIZATIONS_INFO_UP_TO_DATE'),
    'available' => Text::_('COM_XDECAROORGANIZATIONS_INFO_UPDATE_AVAILABLE'),
    default => Text::_('COM_XDECAROORGANIZATIONS_INFO_INACTIVE'),
};
$updateClass = match ($updateState) {
    'current' => 'is-success',
    'available' => 'is-warning',
    default => 'is-muted',
};

$diagnosticLines = [
    'Organizations ' . $installedVersion,
    'Joomla: ' . (string) ($info['joomla_version'] ?? '—'),
    'PHP: ' . (string) ($info['php_version'] ?? '—'),
    'Database: ' . trim((string) ($info['database_type'] ?? '') . ' ' . (string) ($info['database_version'] ?? '')),
    'Componente: ' . ($componentVersion !== '' ? $componentVersion : '—'),
    'Pacchetto: ' . ($packageVersion !== '' ? $packageVersion : '—'),
    'Core: ' . ((string) ($core['version'] ?? '') ?: '—'),
    'Core API: ' . (!empty($core['api_available']) ? 'OK' : 'non disponibile'),
    'Tabelle: ' . (int) ($tableHealth['present_count'] ?? 0) . '/' . (int) ($tableHealth['expected_count'] ?? 0),
    'Update server: ' . ($updateSiteEnabled ? 'attivo' : 'non attivo'),
    'Problemi critici: ' . count($criticalIssues),
    'Avvisi: ' . count($warnings),
];
foreach ($integrations as $integration) {
    $diagnosticLines[] = (string) ($integration['name'] ?? 'Integrazione') . ': '
        . (!empty($integration['installed']) ? ((string) ($integration['version'] ?? '') ?: 'installato') : 'non installato')
        . ' / API ' . (!empty($integration['api_available']) ? 'OK' : 'non disponibile');
}
$diagnosticText = implode("\n", $diagnosticLines);
?>
<div class="xdecaro-scope xdecaro-information-page">
    <?php if (!$systemOk) : ?>
        <div class="alert alert-danger" role="alert">
            <strong><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_ATTENTION_REQUIRED'); ?></strong>
            <?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_CRITICAL_DESC'); ?>
        </div>
    <?php elseif ($updateAvailable) : ?>
        <div class="alert alert-info" role="status">
            <?php echo Text::sprintf('COM_XDECAROORGANIZATIONS_INFO_UPDATE_AVAILABLE_DESC', $this->escape($latestVersion)); ?>
        </div>
    <?php elseif (!$updateSiteEnabled) : ?>
        <div class="alert alert-warning" role="alert">
            <?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_UPDATE_SITE_WARNING'); ?>
        </div>
    <?php endif; ?>

    <div class="xdecaro-information-summary mb-3" aria-label="<?php echo $this->escape(Text::_('COM_XDECAROORGANIZATIONS_INFO_SYSTEM_STATUS')); ?>">
        <strong>Organizations <?php echo $this->escape($installedVersion); ?></strong>
        <span class="xdecaro-status-badge <?php echo $updateClass; ?>"><?php echo $updateLabel; ?></span>
        <span class="xdecaro-status-badge <?php echo $systemOk ? 'is-success' : 'is-danger'; ?>">
            <?php echo Text::_($systemOk ? 'COM_XDECAROORGANIZATIONS_INFO_SYSTEM_OK' : 'COM_XDECAROORGANIZATIONS_INFO_SYSTEM_CHECK'); ?>
        </span>
    </div>

    <div class="xdecaro-information-grid">
        <section class="card h-100">
            <div class="card-body">
                <div class="xdecaro-card-heading">
                    <div>
                        <span class="xdecaro-eyebrow"><?php echo Text::_('COM_XDECAROORGANIZATIONS_PRODUCT_ENVIRONMENT'); ?></span>
                        <h2 class="h5 mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_VERSIONS'); ?></h2>
                    </div>
                    <span class="xdecaro-status-badge <?php echo $installationConsistent ? 'is-success' : 'is-danger'; ?>">
                        <?php echo Text::_($installationConsistent ? 'COM_XDECAROORGANIZATIONS_INFO_CONSISTENT' : 'COM_XDECAROORGANIZATIONS_INFO_INCONSISTENT'); ?>
                    </span>
                </div>
                <dl class="xdecaro-information-list">
                    <div><dt><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_PACKAGE_VERSION'); ?></dt><dd><?php echo $this->escape($packageVersion ?: '—'); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_COMPONENT_VERSION'); ?></dt><dd><?php echo $this->escape($componentVersion ?: '—'); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_PACKAGE_ID'); ?></dt><dd><code><?php echo $this->escape((string) ($info['package_id'] ?? 'pkg_organizations')); ?></code></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_COMPONENT_ID'); ?></dt><dd><code><?php echo $this->escape((string) ($info['component_id'] ?? 'com_xdecaroorganizations')); ?></code></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_REPOSITORY'); ?></dt><dd><a href="https://github.com/xdecaro/organizations" target="_blank" rel="noopener noreferrer">xdecaro/organizations <span aria-hidden="true">↗</span></a></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_LICENSE'); ?></dt><dd><?php echo $this->escape((string) ($info['license'] ?? '')); ?></dd></div>
                </dl>
            </div>
        </section>

        <section class="card h-100">
            <div class="card-body">
                <div class="xdecaro-card-heading">
                    <div>
                        <span class="xdecaro-eyebrow"><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_ENVIRONMENT'); ?></span>
                        <h2 class="h5 mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_SYSTEM'); ?></h2>
                    </div>
                    <span class="xdecaro-status-badge <?php echo $environmentCompatible ? 'is-success' : 'is-danger'; ?>">
                        <?php echo Text::_($environmentCompatible ? 'COM_XDECAROORGANIZATIONS_INFO_COMPATIBLE' : 'COM_XDECAROORGANIZATIONS_INFO_INCOMPATIBLE'); ?>
                    </span>
                </div>
                <dl class="xdecaro-information-list">
                    <div><dt>Joomla</dt><dd><?php echo $this->escape((string) ($info['joomla_version'] ?? '—')); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_MINIMUM_JOOMLA'); ?></dt><dd><?php echo $this->escape((string) ($info['minimum_joomla'] ?? '—')); ?></dd></div>
                    <div><dt>PHP</dt><dd><?php echo $this->escape((string) ($info['php_version'] ?? '—')); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_MINIMUM_PHP'); ?></dt><dd><?php echo $this->escape((string) ($info['minimum_php'] ?? '—')); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROORGANIZATIONS_DATABASE'); ?></dt><dd><?php echo $this->escape(trim((string) ($info['database_type'] ?? '') . ' ' . (string) ($info['database_version'] ?? ''))); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_TABLES'); ?></dt><dd><span class="xdecaro-status-badge <?php echo $tablesPresent ? 'is-success' : 'is-danger'; ?>"><?php echo (int) ($tableHealth['present_count'] ?? 0); ?>/<?php echo (int) ($tableHealth['expected_count'] ?? 0); ?></span></dd></div>
                </dl>
            </div>
        </section>

        <section class="card h-100">
            <div class="card-body">
                <div class="xdecaro-card-heading">
                    <div>
                        <span class="xdecaro-eyebrow"><?php echo Text::_('COM_XDECAROORGANIZATIONS_UPDATES_CORE'); ?></span>
                        <h2 class="h5 mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_UPDATE_SERVER'); ?></h2>
                    </div>
                    <span class="xdecaro-status-badge <?php echo $updateSiteEnabled ? 'is-success' : 'is-danger'; ?>">
                        <?php echo Text::_($updateSiteEnabled ? 'COM_XDECAROORGANIZATIONS_INFO_ACTIVE' : 'COM_XDECAROORGANIZATIONS_INFO_INACTIVE'); ?>
                    </span>
                </div>
                <dl class="xdecaro-information-list">
                    <div><dt><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_CHANNEL'); ?></dt><dd><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_STABLE'); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_INSTALLED_VERSION'); ?></dt><dd><?php echo $this->escape($installedVersion); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_LATEST_VERSION'); ?></dt><dd><?php echo $latestVersion !== '' ? $this->escape($latestVersion) : Text::_('COM_XDECAROORGANIZATIONS_INFO_NOT_DETECTED'); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_LAST_CHECK'); ?></dt><dd><?php echo $lastCheck > 0 ? $this->escape(date('d/m/Y H:i', $lastCheck)) : Text::_('COM_XDECAROORGANIZATIONS_INFO_NEVER'); ?></dd></div>
                    <div><dt><?php echo Text::_('JSTATUS'); ?></dt><dd><span class="xdecaro-status-badge <?php echo $updateClass; ?>"><?php echo $updateLabel; ?></span></dd></div>
                </dl>
                <?php if ($this->canManageInstaller) : ?>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_installer&view=update'); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_OPEN_UPDATES'); ?></a>
                        <a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_installer&view=updatesites'); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_OPEN_UPDATE_SITES'); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card h-100">
            <div class="card-body">
                <div class="xdecaro-card-heading">
                    <div>
                        <span class="xdecaro-eyebrow">Core</span>
                        <h2 class="h5 mb-0">Core by xdecaro</h2>
                    </div>
                    <span class="xdecaro-status-badge <?php echo !empty($core['compatible']) && !empty($core['api_available']) ? 'is-success' : 'is-danger'; ?>">
                        <?php echo Text::_(!empty($core['compatible']) && !empty($core['api_available']) ? 'COM_XDECAROORGANIZATIONS_INFO_READY' : 'COM_XDECAROORGANIZATIONS_INFO_CHECK_REQUIRED'); ?>
                    </span>
                </div>
                <dl class="xdecaro-information-list">
                    <div><dt><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_INSTALLED'); ?></dt><dd><?php echo Text::_(!empty($core['installed']) ? 'JYES' : 'JNO'); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_VERSION'); ?></dt><dd><?php echo $this->escape((string) (($core['version'] ?? '') ?: '—')); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_MINIMUM_VERSION'); ?></dt><dd><?php echo $this->escape((string) ($core['minimum_version'] ?? '—')); ?></dd></div>
                    <div><dt><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_PUBLIC_API'); ?></dt><dd><span class="xdecaro-status-badge <?php echo !empty($core['api_available']) ? 'is-success' : 'is-danger'; ?>"><?php echo Text::_(!empty($core['api_available']) ? 'COM_XDECAROORGANIZATIONS_INFO_AVAILABLE' : 'COM_XDECAROORGANIZATIONS_INFO_UNAVAILABLE'); ?></span></dd></div>
                </dl>
                <?php if (!empty($core['capabilities'])) : ?>
                    <div class="xdecaro-capability-list mt-3" aria-label="Capabilities">
                        <?php foreach ((array) $core['capabilities'] as $capability) : ?>
                            <code><?php echo $this->escape((string) $capability); ?></code>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <section class="card mt-3" aria-labelledby="organizations-connected-components">
        <div class="card-body">
            <div class="xdecaro-card-heading">
                <div>
                    <span class="xdecaro-eyebrow"><?php echo Text::_('COM_XDECAROORGANIZATIONS_CONNECTED_COMPONENTS'); ?></span>
                    <h2 id="organizations-connected-components" class="h5 mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_INTEGRATIONS'); ?></h2>
                </div>
            </div>
            <p class="text-body-secondary"><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_INTEGRATIONS_DESC'); ?></p>
            <div class="xdecaro-integration-list">
                <?php foreach ($integrations as $integration) : ?>
                    <?php
                    $installed = !empty($integration['installed']);
                    $apiAvailable = !empty($integration['api_available']);
                    ?>
                    <div class="xdecaro-integration-row">
                        <div>
                            <strong><?php echo $this->escape((string) ($integration['name'] ?? '')); ?></strong>
                            <small><code><?php echo $this->escape((string) ($integration['element'] ?? '')); ?></code></small>
                        </div>
                        <span><?php echo $this->escape((string) (($integration['version'] ?? '') ?: '—')); ?></span>
                        <span class="xdecaro-status-badge <?php echo !$installed ? 'is-muted' : ($apiAvailable ? 'is-success' : 'is-warning'); ?>">
                            <?php echo Text::_(!$installed ? 'COM_XDECAROORGANIZATIONS_INFO_NOT_INSTALLED' : ($apiAvailable ? 'COM_XDECAROORGANIZATIONS_INFO_API_READY' : 'COM_XDECAROORGANIZATIONS_INFO_API_UNAVAILABLE')); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="card mt-3 xdecaro-information-diagnostics" aria-labelledby="organizations-diagnostics-title">
        <div class="card-body">
            <div class="xdecaro-card-heading">
                <div>
                    <span class="xdecaro-eyebrow"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DIAGNOSTICS'); ?></span>
                    <h2 id="organizations-diagnostics-title" class="h5 mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_SYSTEM_STATUS'); ?></h2>
                </div>
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    data-copy-diagnostics
                    data-copy-target="organizations-diagnostic-text"
                    data-copy-success="<?php echo $this->escape(Text::_('COM_XDECAROORGANIZATIONS_INFO_COPIED')); ?>"
                >
                    <span class="icon-copy" aria-hidden="true"></span>
                    <span data-copy-label><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_COPY_DIAGNOSTICS'); ?></span>
                </button>
            </div>

            <div class="xdecaro-diagnostics-list mt-3">
                <?php
                $checks = [
                    [Text::_('COM_XDECAROORGANIZATIONS_INFO_VERSION_CONSISTENCY'), $installationConsistent, $installationConsistent ? 'COM_XDECAROORGANIZATIONS_INFO_OK' : 'COM_XDECAROORGANIZATIONS_INFO_CHECK_REQUIRED'],
                    [Text::_('COM_XDECAROORGANIZATIONS_INFO_ENVIRONMENT'), $environmentCompatible, $environmentCompatible ? 'COM_XDECAROORGANIZATIONS_INFO_OK' : 'COM_XDECAROORGANIZATIONS_INFO_CHECK_REQUIRED'],
                    [Text::_('COM_XDECAROORGANIZATIONS_INFO_DATABASE_TABLES'), $tablesPresent, $tablesPresent ? 'COM_XDECAROORGANIZATIONS_INFO_OK' : 'COM_XDECAROORGANIZATIONS_INFO_CHECK_REQUIRED'],
                    [Text::_('COM_XDECAROORGANIZATIONS_INFO_CORE_API'), !empty($core['compatible']) && !empty($core['api_available']), (!empty($core['compatible']) && !empty($core['api_available'])) ? 'COM_XDECAROORGANIZATIONS_INFO_OK' : 'COM_XDECAROORGANIZATIONS_INFO_CHECK_REQUIRED'],
                    [Text::_('COM_XDECAROORGANIZATIONS_INFO_UPDATE_SERVER'), $updateSiteEnabled, $updateSiteEnabled ? 'COM_XDECAROORGANIZATIONS_INFO_OK' : 'COM_XDECAROORGANIZATIONS_INFO_WARNING'],
                ];
                ?>
                <?php foreach ($checks as [$label, $ok, $statusKey]) : ?>
                    <div class="xdecaro-diagnostic-row">
                        <span><?php echo $this->escape($label); ?></span>
                        <span class="xdecaro-status-badge <?php echo $ok ? 'is-success' : 'is-warning'; ?>"><?php echo Text::_($statusKey); ?></span>
                    </div>
                <?php endforeach; ?>
                <?php foreach ((array) ($tableHealth['tables'] ?? []) as $table => $present) : ?>
                    <div class="xdecaro-diagnostic-row is-detail">
                        <code><?php echo $this->escape((string) $table); ?></code>
                        <span class="xdecaro-status-badge <?php echo $present ? 'is-success' : 'is-danger'; ?>"><?php echo Text::_($present ? 'COM_XDECAROORGANIZATIONS_INFO_PRESENT' : 'COM_XDECAROORGANIZATIONS_INFO_MISSING'); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <textarea id="organizations-diagnostic-text" class="visually-hidden" tabindex="-1" aria-hidden="true"><?php echo $this->escape($diagnosticText); ?></textarea>
        </div>
    </section>
</div>
