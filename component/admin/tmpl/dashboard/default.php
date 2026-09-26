<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$dashboard = (array) $this->dashboard;
$counts = (array) ($dashboard['counts'] ?? []);
$typeCounts = (array) ($dashboard['type_counts'] ?? []);
$structureCounts = (array) ($dashboard['structure_counts'] ?? []);
$statusCounts = (array) ($dashboard['status_counts'] ?? []);
$qualityCounts = (array) ($dashboard['quality_counts'] ?? []);
$recent = (array) ($dashboard['recent'] ?? []);
$duplicates = (int) ($dashboard['duplicates'] ?? 0);

$metric = static function (array $source, string $key): int {
    return (int) ($source[$key] ?? 0);
};
?>
<div class="xdecaro-scope xdecaro-dashboard-page">
    <div class="xdecaro-dashboard-toolbar mb-3">
        <form class="xdecaro-dashboard-search" action="<?php echo Route::_('index.php'); ?>" method="get">
            <input type="hidden" name="option" value="com_xdecaroorganizations">
            <input type="hidden" name="view" value="organizations">
            <label class="visually-hidden" for="organizations-dashboard-search"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_QUICK_SEARCH'); ?></label>
            <div class="input-group">
                <input
                    id="organizations-dashboard-search"
                    type="search"
                    name="filter_search"
                    class="form-control"
                    placeholder="<?php echo $this->escape(Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_SEARCH_PLACEHOLDER')); ?>"
                >
                <button class="btn btn-primary" type="submit"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
            </div>
        </form>
        <div class="xdecaro-dashboard-actions">
            <a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&task=organization.add'); ?>">
                <span class="icon-plus" aria-hidden="true"></span>
                <?php echo Text::_('COM_XDECAROORGANIZATIONS_ORGANIZATION_NEW'); ?>
            </a>
            <a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=organizations'); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_ORGANIZATIONS'); ?></a>
            <a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=duplicates'); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DUPLICATES'); ?></a>
            <a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=information'); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFORMATION'); ?></a>
        </div>
    </div>

    <section class="mb-4" aria-labelledby="organizations-dashboard-overview">
        <div class="xdecaro-section-heading">
            <div>
                <span class="xdecaro-eyebrow"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD'); ?></span>
                <h2 id="organizations-dashboard-overview" class="h4 mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_OVERVIEW'); ?></h2>
            </div>
        </div>
        <div class="xdecaro-dashboard-grid xdecaro-dashboard-summary mt-3">
            <a class="xdecaro-dashboard-stat" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=organizations'); ?>">
                <span class="xdecaro-dashboard-stat-label"><?php echo Text::_('COM_XDECAROORGANIZATIONS_ORGANIZATIONS'); ?></span>
                <strong><?php echo $metric($counts, 'total'); ?></strong>
            </a>
            <a class="xdecaro-dashboard-stat" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=organizations&filter_state=1'); ?>">
                <span class="xdecaro-dashboard-stat-label"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_PUBLISHED'); ?></span>
                <strong><?php echo $metric($counts, 'published'); ?></strong>
            </a>
            <a class="xdecaro-dashboard-stat" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=organizations&filter_state=0'); ?>">
                <span class="xdecaro-dashboard-stat-label"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_UNPUBLISHED'); ?></span>
                <strong><?php echo $metric($counts, 'unpublished'); ?></strong>
            </a>
            <a class="xdecaro-dashboard-stat" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=organizations&filter_operational=active'); ?>">
                <span class="xdecaro-dashboard-stat-label"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_OPERATIONAL_ACTIVE'); ?></span>
                <strong><?php echo $metric($counts, 'operational_active'); ?></strong>
            </a>
            <a class="xdecaro-dashboard-stat" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=duplicates'); ?>">
                <span class="xdecaro-dashboard-stat-label"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_DUPLICATE_ALERTS'); ?></span>
                <strong><?php echo $duplicates; ?></strong>
            </a>
        </div>
    </section>

    <section class="mb-4" aria-labelledby="organizations-dashboard-structure">
        <div class="xdecaro-section-heading">
            <h2 id="organizations-dashboard-structure" class="h4 mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_STRUCTURE'); ?></h2>
        </div>
        <div class="xdecaro-dashboard-grid mt-3">
            <a class="xdecaro-dashboard-stat is-compact" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=organizations&filter_relation=bodies'); ?>">
                <span class="xdecaro-dashboard-stat-label"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_BODIES_ACTIVE'); ?></span>
                <strong><?php echo $metric($counts, 'bodies'); ?></strong>
            </a>
            <a class="xdecaro-dashboard-stat is-compact" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=organizations&filter_relation=appointments'); ?>">
                <span class="xdecaro-dashboard-stat-label"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_APPOINTMENTS_ACTIVE'); ?></span>
                <strong><?php echo $metric($counts, 'appointments_active'); ?></strong>
            </a>
            <a class="xdecaro-dashboard-stat is-compact" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=organizations&filter_relation=delegations'); ?>">
                <span class="xdecaro-dashboard-stat-label"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_DELEGATIONS_ACTIVE'); ?></span>
                <strong><?php echo $metric($counts, 'delegations_active'); ?></strong>
            </a>
            <a class="xdecaro-dashboard-stat is-compact" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=organizations&filter_relation=affiliations'); ?>">
                <span class="xdecaro-dashboard-stat-label"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_AFFILIATIONS_ACTIVE'); ?></span>
                <strong><?php echo $metric($counts, 'affiliations_active'); ?></strong>
            </a>
        </div>
    </section>

    <div class="xdecaro-dashboard-distribution mb-4">
        <section class="card h-100" aria-labelledby="organizations-dashboard-types">
            <div class="card-body">
                <h2 id="organizations-dashboard-types" class="h5"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_BY_TYPE'); ?></h2>
                <div class="xdecaro-dashboard-metric-list">
                    <?php foreach ($typeCounts as $type => $total) : ?>
                        <a href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=organizations&filter_type=' . urlencode((string) $type)); ?>">
                            <span><?php echo Text::_('COM_XDECAROORGANIZATIONS_TYPE_' . strtoupper((string) $type)); ?></span>
                            <strong><?php echo (int) $total; ?></strong>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="card h-100" aria-labelledby="organizations-dashboard-levels">
            <div class="card-body">
                <h2 id="organizations-dashboard-levels" class="h5"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_BY_STRUCTURE'); ?></h2>
                <div class="xdecaro-dashboard-metric-list">
                    <?php foreach ($structureCounts as $level => $total) : ?>
                        <a href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=organizations&filter_structure=' . urlencode((string) $level)); ?>">
                            <span><?php echo Text::_('COM_XDECAROORGANIZATIONS_STRUCTURE_' . strtoupper((string) $level)); ?></span>
                            <strong><?php echo (int) $total; ?></strong>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="card h-100" aria-labelledby="organizations-dashboard-statuses">
            <div class="card-body">
                <h2 id="organizations-dashboard-statuses" class="h5"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_BY_STATUS'); ?></h2>
                <div class="xdecaro-dashboard-metric-list">
                    <?php foreach ($statusCounts as $status => $total) : ?>
                        <a href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=organizations&filter_operational=' . urlencode((string) $status)); ?>">
                            <span><?php echo Text::_('COM_XDECAROORGANIZATIONS_OPERATIONAL_' . strtoupper((string) $status)); ?></span>
                            <strong><?php echo (int) $total; ?></strong>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <section class="card h-100 xdecaro-dashboard-quality" aria-labelledby="organizations-dashboard-quality-title">
                <div class="card-body">
                    <h2 id="organizations-dashboard-quality-title" class="h5"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_DATA_QUALITY'); ?></h2>
                    <p class="text-body-secondary mb-3"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_DATA_QUALITY_DESC'); ?></p>
                    <div class="xdecaro-dashboard-quality-grid">
                        <?php
                        $qualityLabels = [
                            'country' => 'COM_XDECAROORGANIZATIONS_DASHBOARD_MISSING_COUNTRY',
                            'code' => 'COM_XDECAROORGANIZATIONS_DASHBOARD_MISSING_CODE',
                            'logo' => 'COM_XDECAROORGANIZATIONS_DASHBOARD_MISSING_LOGO',
                            'contact' => 'COM_XDECAROORGANIZATIONS_DASHBOARD_MISSING_CONTACT',
                            'website' => 'COM_XDECAROORGANIZATIONS_DASHBOARD_MISSING_WEBSITE',
                            'structure' => 'COM_XDECAROORGANIZATIONS_DASHBOARD_MISSING_STRUCTURE',
                        ];
                        ?>
                        <?php foreach ($qualityLabels as $quality => $label) : ?>
                            <a class="xdecaro-quality-item" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=organizations&filter_quality=' . urlencode($quality)); ?>">
                                <span><?php echo Text::_($label); ?></span>
                                <strong><?php echo $metric($qualityCounts, $quality); ?></strong>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-6">
            <section class="card h-100 xdecaro-dashboard-recent" aria-labelledby="organizations-dashboard-recent-title">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                        <h2 id="organizations-dashboard-recent-title" class="h5 mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_RECENT'); ?></h2>
                        <a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=organizations&list[ordering]=a.modified&list[direction]=DESC'); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_OPEN_ALL'); ?></a>
                    </div>
                    <?php if ($recent === []) : ?>
                        <p class="text-body-secondary mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD_RECENT_NONE'); ?></p>
                    <?php else : ?>
                        <div class="xdecaro-recent-list">
                            <?php foreach ($recent as $item) : ?>
                                <?php $date = (string) (($item['modified'] ?? '') ?: ($item['created'] ?? '')); ?>
                                <a href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&task=organization.edit&id=' . (int) ($item['id'] ?? 0)); ?>">
                                    <span class="xdecaro-recent-main">
                                        <strong><?php echo $this->escape((string) ($item['name'] ?? '')); ?></strong>
                                        <small><?php echo Text::_('COM_XDECAROORGANIZATIONS_TYPE_' . strtoupper((string) ($item['type'] ?? 'organization'))); ?> · <?php echo Text::_('COM_XDECAROORGANIZATIONS_OPERATIONAL_' . strtoupper((string) ($item['operational_status'] ?? 'active'))); ?></small>
                                    </span>
                                    <time datetime="<?php echo $this->escape($date); ?>"><?php echo $date !== '' ? HTMLHelper::_('date', $date, 'd/m/Y H:i') : '—'; ?></time>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>
