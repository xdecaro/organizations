<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$currentId = (int) ($this->item->id ?? 0);
?>
<div class="xdecaro-organization-hierarchy">
    <?php if ($currentId < 1) : ?>
        <div class="alert alert-info mb-0">
            <?php echo Text::_('COM_XDECAROORGANIZATIONS_HIERARCHY_SAVE_FIRST'); ?>
        </div>
    <?php else : ?>
        <div class="alert alert-info">
            <?php echo Text::_('COM_XDECAROORGANIZATIONS_HIERARCHY_ORGANIZATIONS_ONLY'); ?>
        </div>

        <section class="mb-4">
            <h3 class="h5 mb-2"><?php echo Text::_('COM_XDECAROORGANIZATIONS_HIERARCHY_PATH'); ?></h3>

            <?php if ($this->hierarchyPath === []) : ?>
                <p class="text-body-secondary mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_HIERARCHY_PATH_NONE'); ?></p>
            <?php else : ?>
                <nav aria-label="<?php echo htmlspecialchars(Text::_('COM_XDECAROORGANIZATIONS_HIERARCHY_PATH'), ENT_QUOTES, 'UTF-8'); ?>">
                    <ol class="breadcrumb mb-0">
                        <?php foreach ($this->hierarchyPath as $node) : ?>
                            <?php $nodeId = (int) ($node->id ?? 0); ?>
                            <li class="breadcrumb-item<?php echo $nodeId === $currentId ? ' active' : ''; ?>"<?php echo $nodeId === $currentId ? ' aria-current="page"' : ''; ?>>
                                <?php if ($nodeId === $currentId) : ?>
                                    <?php echo $this->escape((string) ($node->name ?? '')); ?>
                                <?php else : ?>
                                    <a href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&task=organization.edit&id=' . $nodeId . '&activeTab=hierarchy'); ?>">
                                        <?php echo $this->escape((string) ($node->name ?? '')); ?>
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            <?php endif; ?>
        </section>

        <section>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <div>
                    <h3 class="h5 mb-1"><?php echo Text::_('COM_XDECAROORGANIZATIONS_HIERARCHY_CHILDREN'); ?></h3>
                    <p class="text-body-secondary mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_HIERARCHY_CHILDREN_DESC'); ?></p>
                </div>
            </div>

            <?php if ($this->hierarchyDescendants === []) : ?>
                <div class="alert alert-light border mb-0">
                    <?php echo Text::_('COM_XDECAROORGANIZATIONS_HIERARCHY_CHILDREN_NONE'); ?>
                </div>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_NAME'); ?></th>
                                <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_STRUCTURE_LEVEL'); ?></th>
                                <th class="d-none d-lg-table-cell"><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_TERRITORY_NAME'); ?></th>
                                <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_OPERATIONAL_STATUS'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->hierarchyDescendants as $node) : ?>
                                <?php
                                $depth = max(0, (int) ($node->hierarchy_depth ?? 0));
                                $level = strtolower((string) ($node->structure_level ?? 'unspecified'));
                                $status = strtolower((string) ($node->operational_status ?? 'active'));
                                ?>
                                <tr>
                                    <td>
                                        <div
                                            class="xdecaro-organization-tree-name"
                                            style="--xdecaro-org-indent-desktop: <?php echo $depth * 1.5; ?>rem; --xdecaro-org-indent-mobile: <?php echo $depth * 1; ?>rem;"
                                        >
                                            <?php if ($depth > 0) : ?>
                                                <span class="xdecaro-organization-tree-branch" aria-hidden="true">↳</span>
                                            <?php endif; ?>
                                            <a href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&task=organization.edit&id=' . (int) $node->id . '&activeTab=hierarchy'); ?>">
                                                <?php echo $this->escape((string) $node->name); ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td><?php echo Text::_('COM_XDECAROORGANIZATIONS_STRUCTURE_' . strtoupper($level)); ?></td>
                                    <td class="d-none d-lg-table-cell"><?php echo $this->escape((string) ($node->territory_name ?? '')); ?></td>
                                    <td><?php echo Text::_('COM_XDECAROORGANIZATIONS_OPERATIONAL_' . strtoupper($status)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
