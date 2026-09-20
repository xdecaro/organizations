<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$search = trim((string) $this->state->get('filter.search', ''));

$typeLabels = [
    'organization' => 'COM_XDECAROORGANIZATIONS_SITE_TYPE_ORGANIZATION',
    'association' => 'COM_XDECAROORGANIZATIONS_SITE_TYPE_ASSOCIATION',
    'club' => 'COM_XDECAROORGANIZATIONS_SITE_TYPE_CLUB',
    'federation' => 'COM_XDECAROORGANIZATIONS_SITE_TYPE_FEDERATION',
    'company' => 'COM_XDECAROORGANIZATIONS_SITE_TYPE_COMPANY',
    'public_body' => 'COM_XDECAROORGANIZATIONS_SITE_TYPE_PUBLIC_BODY',
    'school' => 'COM_XDECAROORGANIZATIONS_SITE_TYPE_SCHOOL',
    'sponsor' => 'COM_XDECAROORGANIZATIONS_SITE_TYPE_SPONSOR',
    'supplier' => 'COM_XDECAROORGANIZATIONS_SITE_TYPE_SUPPLIER',
];

$structureLabels = [
    'international' => 'COM_XDECAROORGANIZATIONS_SITE_STRUCTURE_INTERNATIONAL',
    'national' => 'COM_XDECAROORGANIZATIONS_SITE_STRUCTURE_NATIONAL',
    'regional' => 'COM_XDECAROORGANIZATIONS_SITE_STRUCTURE_REGIONAL',
    'provincial' => 'COM_XDECAROORGANIZATIONS_SITE_STRUCTURE_PROVINCIAL',
    'local' => 'COM_XDECAROORGANIZATIONS_SITE_STRUCTURE_LOCAL',
    'branch' => 'COM_XDECAROORGANIZATIONS_SITE_STRUCTURE_BRANCH',
    'other' => 'COM_XDECAROORGANIZATIONS_SITE_STRUCTURE_OTHER',
];
?>
<div class="xo-public xo-organizations">
    <header class="xo-page-header">
        <div>
            <p class="xo-eyebrow"><?php echo Text::_('COM_XDECAROORGANIZATIONS_SITE_DIRECTORY'); ?></p>
            <h1><?php echo $this->escape((string) ($this->params->get('page_heading') ?: Text::_('COM_XDECAROORGANIZATIONS_SITE_ORGANIZATIONS'))); ?></h1>
            <p><?php echo Text::_('COM_XDECAROORGANIZATIONS_SITE_ORGANIZATIONS_DESC'); ?></p>
        </div>
    </header>

    <?php if ((int) $this->params->get('show_search', 1) === 1) : ?>
        <form class="xo-search" method="get" action="<?php echo Route::_('index.php'); ?>">
            <input type="hidden" name="option" value="com_xdecaroorganizations">
            <input type="hidden" name="view" value="organizations">
            <label class="visually-hidden" for="xo-organization-search"><?php echo Text::_('JSEARCH_FILTER'); ?></label>
            <input
                id="xo-organization-search"
                class="form-control"
                type="search"
                name="search"
                value="<?php echo $this->escape($search); ?>"
                placeholder="<?php echo $this->escape(Text::_('COM_XDECAROORGANIZATIONS_SITE_SEARCH_PLACEHOLDER')); ?>"
            >
            <button class="btn btn-primary" type="submit"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
            <?php if ($search !== '') : ?>
                <a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=organizations'); ?>">
                    <?php echo Text::_('JCLEAR'); ?>
                </a>
            <?php endif; ?>
        </form>
    <?php endif; ?>

    <?php if ($this->items === []) : ?>
        <div class="alert alert-info"><?php echo Text::_('COM_XDECAROORGANIZATIONS_SITE_NO_ORGANIZATIONS'); ?></div>
    <?php else : ?>
        <div class="xo-card-grid">
            <?php foreach ($this->items as $item) : ?>
                <?php
                $logoUrl = '';
                if (trim((string) ($item->logo ?? '')) !== '') {
                    $clean = HTMLHelper::_('cleanImageURL', (string) $item->logo);
                    $logoUrl = is_object($clean) ? (string) ($clean->url ?? '') : (string) $clean;
                }
                $typeKey = $typeLabels[(string) ($item->type ?? '')] ?? 'COM_XDECAROORGANIZATIONS_SITE_TYPE_ORGANIZATION';
                $structureKey = $structureLabels[(string) ($item->structure_level ?? '')] ?? '';
                ?>
                <article class="xo-card">
                    <a class="xo-card-link" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=organization&id=' . (int) $item->id); ?>">
                        <div class="xo-card-head">
                            <?php if ($logoUrl !== '') : ?>
                                <img class="xo-logo" src="<?php echo $this->escape($logoUrl); ?>" alt="">
                            <?php else : ?>
                                <span class="xo-logo xo-logo-placeholder" aria-hidden="true">O</span>
                            <?php endif; ?>
                            <div>
                                <h2><?php echo $this->escape((string) $item->name); ?></h2>
                                <?php if (trim((string) ($item->legal_name ?? '')) !== '' && $item->legal_name !== $item->name) : ?>
                                    <p class="xo-muted"><?php echo $this->escape((string) $item->legal_name); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="xo-badges">
                            <span class="badge text-bg-secondary"><?php echo Text::_($typeKey); ?></span>
                            <?php if ($structureKey !== '') : ?>
                                <span class="badge text-bg-light"><?php echo Text::_($structureKey); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (trim((string) ($item->territory_name ?? '')) !== '') : ?>
                            <p class="xo-territory"><?php echo $this->escape((string) $item->territory_name); ?></p>
                        <?php endif; ?>

                        <span class="xo-more"><?php echo Text::_('COM_XDECAROORGANIZATIONS_SITE_VIEW_PROFILE'); ?> →</span>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($this->pagination && $this->pagination->pagesTotal > 1) : ?>
            <nav class="xo-pagination" aria-label="<?php echo Text::_('JLIB_HTML_PAGINATION'); ?>">
                <?php echo $this->pagination->getPagesLinks(); ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
