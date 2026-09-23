<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
?>
<div class="xdecaro-scope xdecaro-organizations-import">
    <div class="alert alert-info" role="status">
        <?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_NOTICE'); ?>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h5 mb-3"><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_STEP_FILE'); ?></h2>
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-8">
                    <label class="form-label" for="xdecaro-organizations-import-file"><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_FILE'); ?></label>
                    <input class="form-control" type="file" id="xdecaro-organizations-import-file" accept=".csv,text/csv">
                    <div class="form-text"><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_FILE_HELP'); ?></div>
                </div>
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="xdecaro-organizations-import-default-type"><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_DEFAULT_TYPE'); ?></label>
                    <select class="form-select" id="xdecaro-organizations-import-default-type">
                        <option value="club" selected><?php echo Text::_('COM_XDECAROORGANIZATIONS_TYPE_CLUB'); ?></option>
                        <option value="organization"><?php echo Text::_('COM_XDECAROORGANIZATIONS_TYPE_ORGANIZATION'); ?></option>
                        <option value="association"><?php echo Text::_('COM_XDECAROORGANIZATIONS_TYPE_ASSOCIATION'); ?></option>
                        <option value="federation"><?php echo Text::_('COM_XDECAROORGANIZATIONS_TYPE_FEDERATION'); ?></option>
                        <option value="company"><?php echo Text::_('COM_XDECAROORGANIZATIONS_TYPE_COMPANY'); ?></option>
                        <option value="public_body"><?php echo Text::_('COM_XDECAROORGANIZATIONS_TYPE_PUBLIC_BODY'); ?></option>
                        <option value="school"><?php echo Text::_('COM_XDECAROORGANIZATIONS_TYPE_SCHOOL'); ?></option>
                        <option value="sponsor"><?php echo Text::_('COM_XDECAROORGANIZATIONS_TYPE_SPONSOR'); ?></option>
                        <option value="supplier"><?php echo Text::_('COM_XDECAROORGANIZATIONS_TYPE_SUPPLIER'); ?></option>
                    </select>
                    <div class="form-text"><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_DEFAULT_TYPE_HELP'); ?></div>
                </div>
            </div>
            <div id="xdecaro-organizations-import-file-info" class="small text-body-secondary mt-2" aria-live="polite"></div>
        </div>
    </div>

    <div class="card mb-3" id="xdecaro-organizations-import-mapping-card" hidden>
        <div class="card-body">
            <h2 class="h5 mb-2"><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_STEP_MAPPING'); ?></h2>
            <p class="text-body-secondary"><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_MAPPING_HELP'); ?></p>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead><tr>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_ORGANIZATIONS_FIELD'); ?></th>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_SOURCE_COLUMN'); ?></th>
                    </tr></thead>
                    <tbody id="xdecaro-organizations-import-mapping"></tbody>
                </table>
            </div>
            <button type="button" class="btn btn-primary" id="xdecaro-organizations-import-analyze">
                <?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_ANALYZE'); ?>
            </button>
        </div>
    </div>

    <div class="card mb-3" id="xdecaro-organizations-import-summary-card" hidden>
        <div class="card-body">
            <h2 class="h5 mb-3"><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_STEP_REVIEW'); ?></h2>
            <div class="xdecaro-import-summary" id="xdecaro-organizations-import-summary" aria-live="polite"></div>

            <details class="xdecaro-import-review mt-3" id="xdecaro-organizations-import-duplicate-panel" hidden>
                <summary id="xdecaro-organizations-import-duplicate-title"><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_DUPLICATE_DETAILS_TITLE'); ?></summary>
                <p class="text-body-secondary mt-2 mb-2"><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_DUPLICATE_DETAILS_HELP'); ?></p>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead><tr>
                            <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_REPORT_ROW'); ?></th>
                            <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_ORGANIZATION'); ?></th>
                            <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_COUNTRY'); ?></th>
                            <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_DUPLICATE_OUTCOME'); ?></th>
                        </tr></thead>
                        <tbody id="xdecaro-organizations-import-duplicate-body"></tbody>
                    </table>
                </div>
            </details>

            <details class="xdecaro-import-review mt-3" id="xdecaro-organizations-import-invalid-panel" hidden>
                <summary id="xdecaro-organizations-import-invalid-title"><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_INVALID_DETAILS_TITLE'); ?></summary>
                <p class="text-body-secondary mt-2 mb-2"><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_INVALID_DETAILS_HELP'); ?></p>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead><tr>
                            <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_REPORT_ROW'); ?></th>
                            <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_ORGANIZATION'); ?></th>
                            <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_COUNTRY'); ?></th>
                            <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_PROBLEM'); ?></th>
                        </tr></thead>
                        <tbody id="xdecaro-organizations-import-invalid-body"></tbody>
                    </table>
                </div>
            </details>

            <div class="alert alert-warning mt-3 mb-3"><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_EXISTING_HELP'); ?></div>
            <button type="button" class="btn btn-success" id="xdecaro-organizations-import-start" disabled>
                <?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_START'); ?>
            </button>
        </div>
    </div>

    <div class="card mb-3" id="xdecaro-organizations-import-progress-card" hidden>
        <div class="card-body">
            <h2 class="h5 mb-3"><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_PROGRESS'); ?></h2>
            <div class="progress mb-2" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" id="xdecaro-organizations-import-progress" style="width:0%">0%</div>
            </div>
            <div id="xdecaro-organizations-import-progress-text" aria-live="polite"></div>
        </div>
    </div>

    <div class="card" id="xdecaro-organizations-import-report-card" hidden>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h2 class="h5 mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_REPORT'); ?></h2>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="xdecaro-organizations-import-download-report">
                    <?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_DOWNLOAD_REPORT'); ?>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead><tr>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_REPORT_ROW'); ?></th>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_REPORT_STATUS'); ?></th>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_IMPORT_REPORT_MESSAGE'); ?></th>
                    </tr></thead>
                    <tbody id="xdecaro-organizations-import-report"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
