<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$item = $this->item;

$logoUrl = '';
if (trim((string) ($item->logo ?? '')) !== '') {
    $clean = HTMLHelper::_('cleanImageURL', (string) $item->logo);
    $logoUrl = is_object($clean) ? (string) ($clean->url ?? '') : (string) $clean;
}

$structureLabels = [
    'international' => 'COM_XDECAROORGANIZATIONS_SITE_STRUCTURE_INTERNATIONAL',
    'national' => 'COM_XDECAROORGANIZATIONS_SITE_STRUCTURE_NATIONAL',
    'regional' => 'COM_XDECAROORGANIZATIONS_SITE_STRUCTURE_REGIONAL',
    'provincial' => 'COM_XDECAROORGANIZATIONS_SITE_STRUCTURE_PROVINCIAL',
    'local' => 'COM_XDECAROORGANIZATIONS_SITE_STRUCTURE_LOCAL',
    'branch' => 'COM_XDECAROORGANIZATIONS_SITE_STRUCTURE_BRANCH',
    'other' => 'COM_XDECAROORGANIZATIONS_SITE_STRUCTURE_OTHER',
];

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

$bodyTypeLabels = [
    'congress' => 'COM_XDECAROORGANIZATIONS_SITE_BODY_CONGRESS',
    'assembly' => 'COM_XDECAROORGANIZATIONS_SITE_BODY_ASSEMBLY',
    'board' => 'COM_XDECAROORGANIZATIONS_SITE_BODY_BOARD',
    'presidency' => 'COM_XDECAROORGANIZATIONS_SITE_BODY_PRESIDENCY',
    'secretariat' => 'COM_XDECAROORGANIZATIONS_SITE_BODY_SECRETARIAT',
    'control_body' => 'COM_XDECAROORGANIZATIONS_SITE_BODY_CONTROL',
    'audit_body' => 'COM_XDECAROORGANIZATIONS_SITE_BODY_AUDIT',
    'disciplinary_body' => 'COM_XDECAROORGANIZATIONS_SITE_BODY_DISCIPLINARY',
    'youth_body' => 'COM_XDECAROORGANIZATIONS_SITE_BODY_YOUTH',
    'commission' => 'COM_XDECAROORGANIZATIONS_SITE_BODY_COMMISSION',
    'committee' => 'COM_XDECAROORGANIZATIONS_SITE_BODY_COMMITTEE',
    'department' => 'COM_XDECAROORGANIZATIONS_SITE_BODY_DEPARTMENT',
    'sector' => 'COM_XDECAROORGANIZATIONS_SITE_BODY_SECTOR',
    'office' => 'COM_XDECAROORGANIZATIONS_SITE_BODY_OFFICE',
    'other' => 'COM_XDECAROORGANIZATIONS_SITE_BODY_OTHER',
];

$roleLabels = [
    'president' => 'COM_XDECAROORGANIZATIONS_SITE_ROLE_PRESIDENT',
    'vice_president' => 'COM_XDECAROORGANIZATIONS_SITE_ROLE_VICE_PRESIDENT',
    'secretary' => 'COM_XDECAROORGANIZATIONS_SITE_ROLE_SECRETARY',
    'treasurer' => 'COM_XDECAROORGANIZATIONS_SITE_ROLE_TREASURER',
    'councillor' => 'COM_XDECAROORGANIZATIONS_SITE_ROLE_COUNCILLOR',
    'auditor' => 'COM_XDECAROORGANIZATIONS_SITE_ROLE_AUDITOR',
    'director' => 'COM_XDECAROORGANIZATIONS_SITE_ROLE_DIRECTOR',
    'coordinator' => 'COM_XDECAROORGANIZATIONS_SITE_ROLE_COORDINATOR',
    'representative' => 'COM_XDECAROORGANIZATIONS_SITE_ROLE_REPRESENTATIVE',
    'commissioner' => 'COM_XDECAROORGANIZATIONS_SITE_ROLE_COMMISSIONER',
    'vice_commissioner' => 'COM_XDECAROORGANIZATIONS_SITE_ROLE_VICE_COMMISSIONER',
    'delegate' => 'COM_XDECAROORGANIZATIONS_SITE_ROLE_DELEGATE',
    'control_member' => 'COM_XDECAROORGANIZATIONS_SITE_ROLE_CONTROL_MEMBER',
    'administrative_secretary' => 'COM_XDECAROORGANIZATIONS_SITE_ROLE_ADMINISTRATIVE_SECRETARY',
];

$roleText = static function (object $appointment) use ($roleLabels): string {
    $code = (string) ($appointment->role_code ?? '');

    if ($code === 'custom') {
        return trim((string) ($appointment->role_custom ?? '')) ?: Text::_('COM_XDECAROORGANIZATIONS_SITE_ROLE_OTHER');
    }

    return Text::_($roleLabels[$code] ?? 'COM_XDECAROORGANIZATIONS_SITE_ROLE_OTHER');
};

$appointmentsByBody = [];
foreach ($this->appointments as $appointment) {
    $appointmentsByBody[(int) ($appointment->body_id ?? 0)][] = $appointment;
}

$hasContacts = trim((string) ($item->email ?? '')) !== ''
    || trim((string) ($item->phone ?? '')) !== ''
    || trim((string) ($item->website ?? '')) !== '';

$structureKey = $structureLabels[(string) ($item->structure_level ?? '')] ?? '';
$typeKey = $typeLabels[(string) ($item->type ?? '')] ?? 'COM_XDECAROORGANIZATIONS_SITE_TYPE_ORGANIZATION';
?>
<div class="xo-public xo-organization-profile">
    <?php if ($this->hierarchyPath !== []) : ?>
        <nav class="xo-breadcrumb" aria-label="<?php echo Text::_('COM_XDECAROORGANIZATIONS_SITE_HIERARCHY'); ?>">
            <?php foreach ($this->hierarchyPath as $ancestor) : ?>
                <a href="<?php echo $this->organizationUrl((int) $ancestor->id); ?>"><?php echo $this->escape((string) $ancestor->name); ?></a>
                <span aria-hidden="true">›</span>
            <?php endforeach; ?>
            <span aria-current="page"><?php echo $this->escape((string) $item->name); ?></span>
        </nav>
    <?php endif; ?>

    <header class="xo-profile-header">
        <?php if ($logoUrl !== '') : ?>
            <img class="xo-profile-logo" src="<?php echo $this->escape($logoUrl); ?>" alt="">
        <?php endif; ?>
        <div>
            <div class="xo-badges">
                <span class="badge text-bg-secondary"><?php echo Text::_($typeKey); ?></span>
                <?php if ($structureKey !== '') : ?>
                    <span class="badge text-bg-light"><?php echo Text::_($structureKey); ?></span>
                <?php endif; ?>
            </div>
            <h1><?php echo $this->escape((string) $item->name); ?></h1>
            <?php if (trim((string) ($item->legal_name ?? '')) !== '' && $item->legal_name !== $item->name) : ?>
                <p class="xo-subtitle"><?php echo $this->escape((string) $item->legal_name); ?></p>
            <?php endif; ?>
            <?php if (trim((string) ($item->territory_name ?? '')) !== '') : ?>
                <p class="xo-territory"><?php echo $this->escape((string) $item->territory_name); ?></p>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($hasContacts) : ?>
        <section class="xo-section" aria-labelledby="xo-contacts-title">
            <div class="xo-section-heading">
                <p class="xo-eyebrow"><?php echo Text::_('COM_XDECAROORGANIZATIONS_SITE_INFORMATION'); ?></p>
                <h2 id="xo-contacts-title"><?php echo Text::_('COM_XDECAROORGANIZATIONS_SITE_CONTACTS'); ?></h2>
            </div>
            <div class="xo-contact-grid">
                <?php if (trim((string) ($item->email ?? '')) !== '') : ?>
                    <a class="xo-contact" href="mailto:<?php echo $this->escape((string) $item->email); ?>">
                        <span><?php echo Text::_('JGLOBAL_EMAIL'); ?></span>
                        <strong><?php echo $this->escape((string) $item->email); ?></strong>
                    </a>
                <?php endif; ?>
                <?php if (trim((string) ($item->phone ?? '')) !== '') : ?>
                    <a class="xo-contact" href="tel:<?php echo $this->escape(preg_replace('/[^0-9+]/', '', (string) $item->phone)); ?>">
                        <span><?php echo Text::_('COM_XDECAROORGANIZATIONS_SITE_PHONE'); ?></span>
                        <strong><?php echo $this->escape((string) $item->phone); ?></strong>
                    </a>
                <?php endif; ?>
                <?php if (trim((string) ($item->website ?? '')) !== '') : ?>
                    <a class="xo-contact" href="<?php echo $this->escape((string) $item->website); ?>" rel="noopener">
                        <span><?php echo Text::_('COM_XDECAROORGANIZATIONS_SITE_WEBSITE'); ?></span>
                        <strong><?php echo $this->escape((string) $item->website); ?></strong>
                    </a>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($this->bodies !== [] || !empty($appointmentsByBody[0])) : ?>
        <section class="xo-section" aria-labelledby="xo-bodies-title">
            <div class="xo-section-heading">
                <p class="xo-eyebrow"><?php echo Text::_('COM_XDECAROORGANIZATIONS_SITE_STRUCTURE'); ?></p>
                <h2 id="xo-bodies-title"><?php echo Text::_('COM_XDECAROORGANIZATIONS_SITE_BODIES_AND_ROLES'); ?></h2>
            </div>

            <div class="xo-body-grid">
                <?php foreach ($this->bodies as $body) : ?>
                    <?php $bodyAppointments = $appointmentsByBody[(int) $body->id] ?? []; ?>
                    <article class="xo-body-card">
                        <div>
                            <p class="xo-eyebrow"><?php echo Text::_($bodyTypeLabels[(string) ($body->body_type ?? '')] ?? 'COM_XDECAROORGANIZATIONS_SITE_BODY_OTHER'); ?></p>
                            <h3><?php echo $this->escape((string) $body->name); ?></h3>
                        </div>

                        <?php if ($bodyAppointments !== []) : ?>
                            <ul class="xo-role-list">
                                <?php foreach ($bodyAppointments as $appointment) : ?>
                                    <li>
                                        <strong><?php echo $this->escape((string) $appointment->person_name_snapshot); ?></strong>
                                        <span><?php echo $this->escape($roleText($appointment)); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>

                <?php if (!empty($appointmentsByBody[0])) : ?>
                    <article class="xo-body-card">
                        <div>
                            <p class="xo-eyebrow"><?php echo Text::_('COM_XDECAROORGANIZATIONS_SITE_ORGANIZATION'); ?></p>
                            <h3><?php echo Text::_('COM_XDECAROORGANIZATIONS_SITE_GENERAL_ROLES'); ?></h3>
                        </div>
                        <ul class="xo-role-list">
                            <?php foreach ($appointmentsByBody[0] as $appointment) : ?>
                                <li>
                                    <strong><?php echo $this->escape((string) $appointment->person_name_snapshot); ?></strong>
                                    <span><?php echo $this->escape($roleText($appointment)); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($this->delegations !== []) : ?>
        <section class="xo-section" aria-labelledby="xo-delegations-title">
            <div class="xo-section-heading">
                <p class="xo-eyebrow"><?php echo Text::_('COM_XDECAROORGANIZATIONS_SITE_RESPONSIBILITIES'); ?></p>
                <h2 id="xo-delegations-title"><?php echo Text::_('COM_XDECAROORGANIZATIONS_SITE_DELEGATIONS'); ?></h2>
            </div>
            <div class="xo-delegation-grid">
                <?php foreach ($this->delegations as $delegation) : ?>
                    <article class="xo-delegation-card">
                        <h3><?php echo $this->escape((string) $delegation->title); ?></h3>
                        <p class="xo-delegation-holder">
                            <strong><?php echo $this->escape((string) $delegation->person_name_snapshot); ?></strong>
                            · <?php echo $this->escape($roleText($delegation)); ?>
                        </p>
                        <?php if (trim((string) ($delegation->scope ?? '')) !== '') : ?>
                            <p><?php echo nl2br($this->escape((string) $delegation->scope)); ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($this->children !== []) : ?>
        <section class="xo-section" aria-labelledby="xo-children-title">
            <div class="xo-section-heading">
                <p class="xo-eyebrow"><?php echo Text::_('COM_XDECAROORGANIZATIONS_SITE_HIERARCHY'); ?></p>
                <h2 id="xo-children-title"><?php echo Text::_('COM_XDECAROORGANIZATIONS_SITE_LINKED_STRUCTURES'); ?></h2>
            </div>
            <div class="xo-linked-list">
                <?php foreach ($this->children as $child) : ?>
                    <a href="<?php echo $this->organizationUrl((int) $child->id); ?>">
                        <strong><?php echo $this->escape((string) $child->name); ?></strong>
                        <?php if (trim((string) ($child->territory_name ?? '')) !== '') : ?>
                            <span><?php echo $this->escape((string) $child->territory_name); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
