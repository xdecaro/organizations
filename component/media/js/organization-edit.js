document.addEventListener('DOMContentLoaded', () => {
  const members = document.querySelector('.xdecaro-members[data-organization-id]');
  const bodies = document.querySelector('.xdecaro-bodies[data-organization-id]');
  const delegations = document.querySelector('.xdecaro-delegations[data-organization-id]');
  const affiliations = document.querySelector('.xdecaro-affiliations[data-organization-id]');
  const membersOrganizationId = Number(members?.dataset.organizationId || 0);
  const bodiesOrganizationId = Number(bodies?.dataset.organizationId || 0);
  const delegationsOrganizationId = Number(delegations?.dataset.organizationId || 0);
  const affiliationsOrganizationId = Number(affiliations?.dataset.organizationId || 0);
  if (membersOrganizationId < 1 && bodiesOrganizationId < 1 && delegationsOrganizationId < 1 && affiliationsOrganizationId < 1) {
    return;
  }

  const endpoints = {
    search: 'index.php?option=com_xdecaroorganizations&task=appointment.searchPeople&format=json',
    eligibility: 'index.php?option=com_xdecaroorganizations&task=appointment.eligibility&format=json',
    save: 'index.php?option=com_xdecaroorganizations&task=appointment.save&format=json',
    end: 'index.php?option=com_xdecaroorganizations&task=appointment.end&format=json',
    delete: 'index.php?option=com_xdecaroorganizations&task=appointment.delete&format=json',
    bodySave: 'index.php?option=com_xdecaroorganizations&task=body.save&format=json',
    bodyDelete: 'index.php?option=com_xdecaroorganizations&task=body.delete&format=json',
    delegationSave: 'index.php?option=com_xdecaroorganizations&task=delegation.save&format=json',
    affiliationSearch: 'index.php?option=com_xdecaroorganizations&task=affiliation.searchTargets&format=json',
    affiliationSave: 'index.php?option=com_xdecaroorganizations&task=affiliation.save&format=json',
    affiliationDelete: 'index.php?option=com_xdecaroorganizations&task=affiliation.delete&format=json',
  };

  const editModalElement = document.getElementById('appointment-edit-modal');
  const endModalElement = document.getElementById('appointment-end-modal');
  const bodyModalElement = document.getElementById('body-edit-modal');
  const delegationModalElement = document.getElementById('delegation-edit-modal');
  const affiliationModalElement = document.getElementById('affiliation-edit-modal');
  const editModal = editModalElement && window.bootstrap?.Modal
    ? window.bootstrap.Modal.getOrCreateInstance(editModalElement)
    : null;
  const endModal = endModalElement && window.bootstrap?.Modal
    ? window.bootstrap.Modal.getOrCreateInstance(endModalElement)
    : null;
  const bodyModal = bodyModalElement && window.bootstrap?.Modal
    ? window.bootstrap.Modal.getOrCreateInstance(bodyModalElement)
    : null;
  const delegationModal = delegationModalElement && window.bootstrap?.Modal
    ? window.bootstrap.Modal.getOrCreateInstance(delegationModalElement)
    : null;
  const affiliationModal = affiliationModalElement && window.bootstrap?.Modal
    ? window.bootstrap.Modal.getOrCreateInstance(affiliationModalElement)
    : null;

  const idField = document.getElementById('appointment-id');
  const appointmentBodyId = document.getElementById('appointment-body-id');
  const personUuid = document.getElementById('appointment-person-uuid');
  const personSearch = document.querySelector('[data-people-search]');
  const peopleResults = document.querySelector('[data-people-results]');
  const membershipRequirement = members?.dataset.membershipRequirement || 'none';
  const membershipEligibility = editModalElement?.querySelector('[data-membership-eligibility]') || null;
  const roleCode = document.getElementById('appointment-role-code');
  const roleCustom = document.getElementById('appointment-role-custom');
  const roleCustomWrap = document.querySelector('[data-role-custom-wrap]');
  const startsOn = document.getElementById('appointment-starts-on');
  const duration = document.querySelector('[data-duration-years]');
  const plannedEndsOn = document.getElementById('appointment-planned-ends-on');
  const notes = document.getElementById('appointment-notes');
  const showOnFrontend = document.getElementById('appointment-show-on-frontend');

  const endId = document.getElementById('appointment-end-id');
  const endReason = document.getElementById('appointment-end-reason');
  const endedOn = document.getElementById('appointment-ended-on');
  const endNote = document.getElementById('appointment-end-note');
  const endPerson = document.querySelector('[data-end-person]');
  const endRole = document.querySelector('[data-end-role]');

  const csrfTokenName = (() => {
    if (window.Joomla?.getOptions) {
      const option = window.Joomla.getOptions('csrf.token');
      if (typeof option === 'string' && option) {
        return option;
      }
    }

    const candidates = document.querySelectorAll('#organization-form input[type="hidden"][name][value="1"]');
    for (const input of candidates) {
      if (!input.name.startsWith('jform[') && input.name !== 'task') {
        return input.name;
      }
    }

    return '';
  })();

  const addToken = (body) => {
    if (csrfTokenName) {
      body.set(csrfTokenName, '1');
    }
  };

  const post = async (url, values) => {
    const body = new URLSearchParams();
    Object.entries(values).forEach(([key, value]) => {
      body.set(key, value == null ? '' : String(value));
    });
    addToken(body);

    const response = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body,
    });

    const json = await response.json();
    if (!response.ok || json.success === false) {
      throw new Error(json.message || 'Request failed.');
    }

    return json.data || {};
  };

  const reloadOrganizationTab = (tab) => {
    const url = new URL(window.location.href);
    url.searchParams.set('activeTab', tab);
    window.location.assign(url.toString());
  };

  const reloadMembersTab = () => reloadOrganizationTab('members');

  const parseAppointment = (button) => {
    try {
      return JSON.parse(button.dataset.appointment || '{}');
    } catch (error) {
      console.error(error);
      return {};
    }
  };

  const addYears = (dateValue, years) => {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(dateValue) || years < 1 || years > 5) {
      return '';
    }

    const date = new Date(`${dateValue}T00:00:00Z`);
    if (Number.isNaN(date.getTime())) {
      return '';
    }

    date.setUTCFullYear(date.getUTCFullYear() + years);
    return date.toISOString().slice(0, 10);
  };

  const detectDuration = (start, plannedEnd) => {
    if (!start || !plannedEnd) {
      return 'custom';
    }

    for (let years = 1; years <= 5; years += 1) {
      if (addYears(start, years) === plannedEnd) {
        return String(years);
      }
    }

    return 'custom';
  };

  const updateCustomRole = () => {
    if (!roleCode || !roleCustomWrap || !roleCustom) {
      return;
    }

    const custom = roleCode.value === 'custom';
    roleCustomWrap.classList.toggle('d-none', !custom);
    roleCustom.required = custom;
    if (!custom) {
      roleCustom.value = '';
    }
  };

  const updatePlannedEnd = () => {
    if (!duration || !startsOn || !plannedEndsOn || duration.value === 'custom') {
      return;
    }

    const years = Number(duration.value);
    const calculated = addYears(startsOn.value, years);
    if (calculated) {
      plannedEndsOn.value = calculated;
    }
  };

  const clearResults = () => {
    if (peopleResults) {
      peopleResults.replaceChildren();
    }
  };

  const clearMembershipEligibility = () => {
    if (!membershipEligibility) {
      return;
    }

    membershipEligibility.textContent = '';
    membershipEligibility.className = 'alert mt-2 mb-0 d-none';
    delete membershipEligibility.dataset.status;
  };

  const renderMembershipEligibility = (result) => {
    if (!membershipEligibility || membershipRequirement === 'none') {
      return;
    }

    const status = String(result?.status || 'unavailable');
    const labelMap = {
      eligible: membershipEligibility.dataset.labelEligible,
      not_member: membershipEligibility.dataset.labelNotMember,
      inactive_member: membershipEligibility.dataset.labelInactiveMember,
      fee_not_current: membershipEligibility.dataset.labelFeeNotCurrent,
      unavailable: membershipEligibility.dataset.labelUnavailable,
    };

    const classMap = {
      eligible: 'alert-success',
      not_member: 'alert-danger',
      inactive_member: 'alert-warning',
      fee_not_current: 'alert-warning',
      unavailable: 'alert-secondary',
    };

    membershipEligibility.textContent = labelMap[status] || labelMap.unavailable || status;
    membershipEligibility.className = `alert mt-2 mb-0 ${classMap[status] || 'alert-secondary'}`;
    membershipEligibility.dataset.status = status;
  };

  const checkMembershipEligibility = async (uuid) => {
    clearMembershipEligibility();

    if (membershipRequirement === 'none' || !uuid || membersOrganizationId < 1) {
      return;
    }

    try {
      const result = await post(endpoints.eligibility, {
        organization_id: membersOrganizationId,
        person_uuid: uuid,
      });
      renderMembershipEligibility(result);
    } catch (error) {
      console.error(error);
      renderMembershipEligibility({ status: 'unavailable' });
    }
  };

  const formatBirthDate = (value) => {
    const raw = String(value || '').trim();
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(raw);
    return match ? `${match[3]}/${match[2]}/${match[1]}` : raw;
  };

  const formatPersonBirthDetails = (person) => {
    const birthDate = formatBirthDate(person.birth_date);
    const birthPlace = String(person.birth_place || '').trim();
    return [birthDate, birthPlace].filter(Boolean).join(' · ');
  };

  const resetEdit = () => {
    if (idField) idField.value = '0';
    if (appointmentBodyId) appointmentBodyId.value = '0';
    if (personUuid) personUuid.value = '';
    if (personSearch) personSearch.value = '';
    if (roleCode) roleCode.value = 'councillor';
    if (roleCustom) roleCustom.value = '';
    if (startsOn) startsOn.value = '';
    if (duration) duration.value = '1';
    if (plannedEndsOn) plannedEndsOn.value = '';
    if (notes) notes.value = '';
    if (showOnFrontend) showOnFrontend.checked = false;
    clearResults();
    clearMembershipEligibility();
    updateCustomRole();
  };

  const fillEdit = (appointment) => {
    if (idField) idField.value = String(appointment.id || 0);
    if (appointmentBodyId) appointmentBodyId.value = String(appointment.body_id || 0);
    if (personUuid) personUuid.value = appointment.person_uuid || '';
    if (personSearch) personSearch.value = appointment.person_name_snapshot || '';
    if (roleCode) roleCode.value = appointment.role_code || 'councillor';
    if (roleCustom) roleCustom.value = appointment.role_custom || '';
    if (startsOn) startsOn.value = appointment.starts_on || '';
    if (plannedEndsOn) plannedEndsOn.value = appointment.planned_ends_on || '';
    if (duration) duration.value = detectDuration(appointment.starts_on || '', appointment.planned_ends_on || '');
    if (notes) notes.value = appointment.notes || '';
    if (showOnFrontend) showOnFrontend.checked = Number(appointment.show_on_frontend || 0) === 1;
    clearResults();
    clearMembershipEligibility();
    updateCustomRole();
    void checkMembershipEligibility(appointment.person_uuid || '');
  };

  document.querySelectorAll('[data-appointment-add]').forEach((button) => {
    button.addEventListener('click', () => {
      resetEdit();
      editModal?.show();
    });
  });

  document.querySelectorAll('[data-appointment-edit]').forEach((button) => {
    button.addEventListener('click', () => {
      fillEdit(parseAppointment(button));
      editModal?.show();
    });
  });

  roleCode?.addEventListener('change', updateCustomRole);
  duration?.addEventListener('change', updatePlannedEnd);
  startsOn?.addEventListener('change', updatePlannedEnd);

  let searchTimer = 0;
  personSearch?.addEventListener('input', () => {
    if (personUuid) {
      personUuid.value = '';
    }
    clearResults();
    clearMembershipEligibility();
    window.clearTimeout(searchTimer);

    const query = personSearch.value.trim();
    if (query.length < 2) {
      return;
    }

    searchTimer = window.setTimeout(async () => {
      try {
        const data = await post(endpoints.search, { search: query });
        clearResults();

        (data.items || []).forEach((person) => {
          const option = document.createElement('button');
          option.type = 'button';
          option.className = 'list-group-item list-group-item-action';
          option.dataset.personUuid = person.uuid;

          const name = document.createElement('span');
          name.className = 'd-block fw-semibold';
          name.textContent = person.name;
          option.appendChild(name);

          const birthDetails = formatPersonBirthDetails(person);
          if (birthDetails) {
            const details = document.createElement('span');
            details.className = 'd-block small text-body-secondary mt-1';
            details.textContent = birthDetails;
            option.appendChild(details);
          }

          option.addEventListener('click', () => {
            if (personUuid) personUuid.value = person.uuid;
            if (personSearch) personSearch.value = person.name;
            clearResults();
            void checkMembershipEligibility(person.uuid);
          });
          peopleResults?.appendChild(option);
        });
      } catch (error) {
        console.error(error);
      }
    }, 250);
  });

  document.querySelector('[data-appointment-save]')?.addEventListener('click', async () => {
    try {
      const selectedDuration = duration?.value || 'custom';
      await post(endpoints.save, {
        id: idField?.value || '0',
        organization_id: membersOrganizationId,
        body_id: appointmentBodyId?.value || '0',
        person_uuid: personUuid?.value || '',
        role_code: roleCode?.value || '',
        role_custom: roleCustom?.value || '',
        starts_on: startsOn?.value || '',
        duration_years: selectedDuration === 'custom' ? '' : selectedDuration,
        planned_ends_on: plannedEndsOn?.value || '',
        notes: notes?.value || '',
        show_on_frontend: showOnFrontend?.checked ? '1' : '0',
      });
      reloadMembersTab();
    } catch (error) {
      window.alert(error.message || String(error));
    }
  });

  document.querySelectorAll('[data-appointment-end]').forEach((button) => {
    button.addEventListener('click', () => {
      const appointment = parseAppointment(button);
      if (endId) endId.value = String(appointment.id || 0);
      if (endReason) endReason.value = button.dataset.endReason || '';
      if (endedOn) endedOn.value = new Date().toISOString().slice(0, 10);
      if (endNote) endNote.value = '';
      if (endPerson) endPerson.textContent = appointment.person_name_snapshot || '';
      if (endRole) endRole.textContent = button.closest('tr')?.children?.[1]?.textContent?.trim() || appointment.role_custom || appointment.role_code || '';
      endModal?.show();
    });
  });

  document.querySelector('[data-appointment-end-save]')?.addEventListener('click', async () => {
    try {
      await post(endpoints.end, {
        id: endId?.value || '0',
        end_reason: endReason?.value || '',
        ended_on: endedOn?.value || '',
        end_note: endNote?.value || '',
      });
      reloadMembersTab();
    } catch (error) {
      window.alert(error.message || String(error));
    }
  });

  document.querySelectorAll('[data-appointment-delete]').forEach((button) => {
    button.addEventListener('click', async () => {
      if (!window.confirm(button.dataset.confirm || 'Delete this appointment permanently?')) {
        return;
      }

      try {
        await post(endpoints.delete, { id: button.dataset.appointmentId || '0' });
        reloadMembersTab();
      } catch (error) {
        window.alert(error.message || String(error));
      }
    });
  });


  const bodyId = document.getElementById('body-id');
  const bodyName = document.getElementById('body-name');
  const bodyCode = document.getElementById('body-code');
  const bodyType = document.getElementById('body-type');
  const bodyParentId = document.getElementById('body-parent-id');
  const bodyStartsOn = document.getElementById('body-starts-on');
  const bodyEndsOn = document.getElementById('body-ends-on');
  const bodyState = document.getElementById('body-state');
  const bodyNotes = document.getElementById('body-notes');

  const resetBody = () => {
    if (bodyId) bodyId.value = '0';
    if (bodyName) bodyName.value = '';
    if (bodyCode) bodyCode.value = '';
    if (bodyType) bodyType.value = 'other';
    if (bodyParentId) {
      bodyParentId.value = '0';
      [...bodyParentId.options].forEach((option) => {
        option.disabled = false;
      });
    }
    if (bodyStartsOn) bodyStartsOn.value = '';
    if (bodyEndsOn) bodyEndsOn.value = '';
    if (bodyState) bodyState.value = '1';
    if (bodyNotes) bodyNotes.value = '';
  };

  const parseBody = (button) => {
    try {
      return JSON.parse(button.dataset.body || '{}');
    } catch (error) {
      console.error(error);
      return {};
    }
  };

  const fillBody = (body) => {
    if (bodyId) bodyId.value = String(body.id || 0);
    if (bodyName) bodyName.value = body.name || '';
    if (bodyCode) bodyCode.value = body.code || '';
    if (bodyType) bodyType.value = body.body_type || 'other';
    if (bodyParentId) {
      [...bodyParentId.options].forEach((option) => {
        option.disabled = Number(option.value || 0) === Number(body.id || 0);
      });
      bodyParentId.value = String(body.parent_id || 0);
    }
    if (bodyStartsOn) bodyStartsOn.value = body.starts_on || '';
    if (bodyEndsOn) bodyEndsOn.value = body.ends_on || '';
    if (bodyState) bodyState.value = String(Number(body.state ?? 1) === 1 ? 1 : 0);
    if (bodyNotes) bodyNotes.value = body.notes || '';
  };

  document.querySelectorAll('[data-body-add]').forEach((button) => {
    button.addEventListener('click', () => {
      resetBody();
      bodyModal?.show();
    });
  });

  document.querySelectorAll('[data-body-edit]').forEach((button) => {
    button.addEventListener('click', () => {
      fillBody(parseBody(button));
      bodyModal?.show();
    });
  });

  document.querySelector('[data-body-save]')?.addEventListener('click', async () => {
    try {
      await post(endpoints.bodySave, {
        id: bodyId?.value || '0',
        organization_id: bodiesOrganizationId,
        parent_id: bodyParentId?.value || '0',
        name: bodyName?.value || '',
        code: bodyCode?.value || '',
        body_type: bodyType?.value || 'other',
        starts_on: bodyStartsOn?.value || '',
        ends_on: bodyEndsOn?.value || '',
        state: bodyState?.value || '1',
        notes: bodyNotes?.value || '',
      });
      reloadOrganizationTab('bodies');
    } catch (error) {
      window.alert(error.message || String(error));
    }
  });


  document.querySelectorAll('[data-body-delete]').forEach((button) => {
    button.addEventListener('click', async () => {
      if (!window.confirm(button.dataset.confirm || 'Delete this body permanently?')) {
        return;
      }

      try {
        await post(endpoints.bodyDelete, { id: button.dataset.bodyId || '0' });
        reloadOrganizationTab('bodies');
      } catch (error) {
        window.alert(error.message || String(error));
      }
    });
  });


  const delegationId = document.getElementById('delegation-id');
  const delegationAppointmentId = document.getElementById('delegation-appointment-id');
  const delegationTitle = document.getElementById('delegation-title');
  const delegationScope = document.getElementById('delegation-scope');
  const delegationStartsOn = document.getElementById('delegation-starts-on');
  const delegationEndsOn = document.getElementById('delegation-ends-on');
  const delegationMandateLimit = document.querySelector('[data-delegation-mandate-limit]');
  const delegationState = document.getElementById('delegation-state');
  const delegationNotes = document.getElementById('delegation-notes');

  const resetDelegation = () => {
    if (delegationId) delegationId.value = '0';
    if (delegationAppointmentId) delegationAppointmentId.value = '0';
    if (delegationTitle) delegationTitle.value = '';
    if (delegationScope) delegationScope.value = '';
    if (delegationStartsOn) delegationStartsOn.value = '';
    if (delegationEndsOn) {
      delegationEndsOn.value = '';
      delegationEndsOn.removeAttribute('max');
    }
    if (delegationMandateLimit) delegationMandateLimit.textContent = '';
    if (delegationState) delegationState.value = '1';
    if (delegationNotes) delegationNotes.value = '';
  };

  const parseDelegation = (button) => {
    try {
      return JSON.parse(button.dataset.delegation || '{}');
    } catch (error) {
      console.error(error);
      return {};
    }
  };

  const fillDelegation = (delegation) => {
    if (delegationId) delegationId.value = String(delegation.id || 0);
    if (delegationAppointmentId) delegationAppointmentId.value = String(delegation.appointment_id || 0);
    if (delegationTitle) delegationTitle.value = delegation.title || '';
    if (delegationScope) delegationScope.value = delegation.scope || '';
    if (delegationStartsOn) delegationStartsOn.value = delegation.starts_on || '';
    if (delegationEndsOn) delegationEndsOn.value = delegation.ends_on || '';
    if (delegationState) delegationState.value = String(Number(delegation.state ?? 1) === 1 ? 1 : 0);
    if (delegationNotes) delegationNotes.value = delegation.notes || '';
  };

  const syncDelegationDatesWithAppointment = () => {
    if (!delegationAppointmentId) {
      return;
    }

    const selected = delegationAppointmentId.selectedOptions?.[0];
    if (!selected) {
      return;
    }

    const appointmentStart = selected.dataset.startsOn || '';
    const appointmentPlannedEnd = selected.dataset.plannedEndsOn || '';
    const appointmentActualEnd = selected.dataset.endedOn || '';
    const appointmentEnd = appointmentActualEnd || appointmentPlannedEnd;

    if (delegationStartsOn && !delegationStartsOn.value && appointmentStart) {
      delegationStartsOn.value = appointmentStart;
    }

    if (delegationEndsOn) {
      if (appointmentEnd) {
        delegationEndsOn.max = appointmentEnd;

        if (!delegationEndsOn.value || delegationEndsOn.value > appointmentEnd) {
          delegationEndsOn.value = appointmentEnd;
        }
      } else {
        delegationEndsOn.removeAttribute('max');
      }
    }

    if (delegationMandateLimit) {
      delegationMandateLimit.textContent = appointmentEnd
        ? `${delegationMandateLimit.dataset.label || 'Limite mandato'}: ${appointmentEnd}`
        : '';
    }
  };

  document.querySelectorAll('[data-delegation-add]').forEach((button) => {
    button.addEventListener('click', () => {
      resetDelegation();
      syncDelegationDatesWithAppointment();
      delegationModal?.show();
    });
  });

  document.querySelectorAll('[data-delegation-edit]').forEach((button) => {
    button.addEventListener('click', () => {
      fillDelegation(parseDelegation(button));
      syncDelegationDatesWithAppointment();
      delegationModal?.show();
    });
  });

  delegationAppointmentId?.addEventListener('change', syncDelegationDatesWithAppointment);

  document.querySelector('[data-delegation-save]')?.addEventListener('click', async () => {
    try {
      await post(endpoints.delegationSave, {
        id: delegationId?.value || '0',
        organization_id: delegationsOrganizationId,
        appointment_id: delegationAppointmentId?.value || '0',
        title: delegationTitle?.value || '',
        scope: delegationScope?.value || '',
        starts_on: delegationStartsOn?.value || '',
        ends_on: delegationEndsOn?.value || '',
        state: delegationState?.value || '1',
        notes: delegationNotes?.value || '',
      });
      reloadOrganizationTab('delegations');
    } catch (error) {
      window.alert(error.message || String(error));
    }
  });

  const affiliationId = document.getElementById('affiliation-id');
  const affiliationMode = document.getElementById('affiliation-mode');
  const affiliationTarget = document.getElementById('affiliation-target');
  const affiliationTargetSearch = document.querySelector('[data-affiliation-target-search]');
  const affiliationTargetResults = document.querySelector('[data-affiliation-target-results]');
  const affiliationTargetLabelElement = document.querySelector('[data-affiliation-target-label]');
  const affiliationModalTitle = document.getElementById('affiliation-edit-title');
  const affiliationType = document.getElementById('affiliation-type');
  const affiliationCode = document.getElementById('affiliation-code');
  const affiliationStartsOn = document.getElementById('affiliation-starts-on');
  const affiliationEndsOn = document.getElementById('affiliation-ends-on');
  const affiliationStatus = document.getElementById('affiliation-status');
  const affiliationNotes = document.getElementById('affiliation-notes');

  let affiliationTargetSearchTimer = 0;
  let affiliationTargetSearchRequest = 0;

  const setAffiliationMode = (mode) => {
    const normalized = mode === 'affiliate' ? 'affiliate' : 'affiliation';

    if (affiliationMode) {
      affiliationMode.value = normalized;
    }

    if (affiliationModalTitle && affiliationModalElement) {
      affiliationModalTitle.textContent = normalized === 'affiliate'
        ? (affiliationModalElement.dataset.titleAffiliate || affiliationModalTitle.textContent)
        : (affiliationModalElement.dataset.titleAffiliation || affiliationModalTitle.textContent);
    }

    if (affiliationTargetLabelElement) {
      affiliationTargetLabelElement.textContent = normalized === 'affiliate'
        ? (affiliationTargetLabelElement.dataset.labelAffiliate || affiliationTargetLabelElement.textContent)
        : (affiliationTargetLabelElement.dataset.labelAffiliation || affiliationTargetLabelElement.textContent);
    }

    if (affiliationTargetSearch) {
      affiliationTargetSearch.placeholder = normalized === 'affiliate'
        ? (affiliationTargetSearch.dataset.placeholderAffiliate || affiliationTargetSearch.placeholder)
        : (affiliationTargetSearch.dataset.placeholderAffiliation || affiliationTargetSearch.placeholder);
    }

    if (affiliationType) {
      if (normalized === 'affiliate') {
        affiliationType.value = 'sports_affiliation';
        affiliationType.disabled = true;
      } else {
        affiliationType.disabled = false;
      }
    }
  };

  const affiliationTargetLabel = (item) => {
    const name = String(item?.name || item?.target_name || '').trim();
    const code = String(item?.code || item?.target_code || '').trim();

    return code ? `${name} — ${code}` : name;
  };

  const clearAffiliationTargetResults = () => {
    if (affiliationTargetResults) {
      affiliationTargetResults.replaceChildren();
      affiliationTargetResults.classList.add('d-none');
    }

    affiliationTargetSearch?.setAttribute('aria-expanded', 'false');
  };

  const selectAffiliationTarget = (item) => {
    const id = Number(item?.id || item?.target_organization_id || 0);
    const label = affiliationTargetLabel(item);

    if (affiliationTarget) {
      affiliationTarget.value = id > 0 ? String(id) : '0';
    }

    if (affiliationTargetSearch) {
      affiliationTargetSearch.value = label;
      affiliationTargetSearch.dataset.selectedLabel = label;
      affiliationTargetSearch.removeAttribute('aria-invalid');
    }

    clearAffiliationTargetResults();
  };

  const renderAffiliationTargetResults = (items) => {
    if (!affiliationTargetResults) {
      return;
    }

    affiliationTargetResults.replaceChildren();

    if (!Array.isArray(items) || items.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'list-group-item text-body-secondary';
      empty.textContent = affiliationTargetResults.dataset.emptyLabel || 'No organizations found.';
      affiliationTargetResults.appendChild(empty);
    } else {
      items.forEach((item) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'list-group-item list-group-item-action';

        const top = document.createElement('div');
        top.className = 'd-flex align-items-center justify-content-between gap-2';

        const name = document.createElement('span');
        name.className = 'fw-semibold text-start';
        name.textContent = String(item?.name || '');
        top.appendChild(name);

        const code = String(item?.code || '').trim();
        if (code) {
          const badge = document.createElement('span');
          badge.className = 'badge text-bg-light border flex-shrink-0';
          badge.textContent = code;
          top.appendChild(badge);
        }

        button.appendChild(top);

        const metadata = [
          String(item?.type_label || '').trim(),
        ].filter(Boolean);

        if (metadata.length > 0) {
          const meta = document.createElement('div');
          meta.className = 'small text-body-secondary text-start mt-1';
          meta.textContent = metadata.join(' · ');
          button.appendChild(meta);
        }

        button.addEventListener('click', () => selectAffiliationTarget(item));
        affiliationTargetResults.appendChild(button);
      });
    }

    affiliationTargetResults.classList.remove('d-none');
    affiliationTargetSearch?.setAttribute('aria-expanded', 'true');
  };

  const searchAffiliationTargets = async () => {
    if (!affiliationTargetSearch) {
      return;
    }

    const query = affiliationTargetSearch.value.trim();
    if (query.length < 2) {
      clearAffiliationTargetResults();
      return;
    }

    const requestId = ++affiliationTargetSearchRequest;

    try {
      const data = await post(endpoints.affiliationSearch, {
        organization_id: affiliationsOrganizationId,
        q: query,
        relation_type: affiliationType?.value || 'sports_affiliation',
        mode: affiliationMode?.value || 'affiliation',
      });

      if (requestId !== affiliationTargetSearchRequest) {
        return;
      }

      renderAffiliationTargetResults(data.items || []);
    } catch (error) {
      if (requestId === affiliationTargetSearchRequest) {
        clearAffiliationTargetResults();
      }
      console.error(error);
    }
  };

  const queueAffiliationTargetSearch = () => {
    window.clearTimeout(affiliationTargetSearchTimer);
    affiliationTargetSearchTimer = window.setTimeout(() => {
      void searchAffiliationTargets();
    }, 250);
  };

  const resetAffiliation = () => {
    if (affiliationId) affiliationId.value = '0';
    setAffiliationMode('affiliation');
    if (affiliationTarget) affiliationTarget.value = '0';
    if (affiliationTargetSearch) {
      affiliationTargetSearch.value = '';
      delete affiliationTargetSearch.dataset.selectedLabel;
    }
    clearAffiliationTargetResults();
    if (affiliationType) affiliationType.value = 'sports_affiliation';
    if (affiliationCode) affiliationCode.value = '';
    if (affiliationStartsOn) affiliationStartsOn.value = '';
    if (affiliationEndsOn) affiliationEndsOn.value = '';
    if (affiliationStatus) affiliationStatus.value = 'active';
    if (affiliationNotes) affiliationNotes.value = '';
  };

  const parseAffiliation = (button) => {
    try {
      return JSON.parse(button.dataset.affiliation || '{}');
    } catch (error) {
      console.error(error);
      return {};
    }
  };

  const fillAffiliation = (item) => {
    setAffiliationMode('affiliation');
    if (affiliationId) affiliationId.value = String(item.id || 0);
    selectAffiliationTarget({
      id: item.target_organization_id || 0,
      name: item.target_name || '',
      code: item.target_code || '',
      type: item.target_type || '',
    });
    if (affiliationType) affiliationType.value = item.relation_type || 'sports_affiliation';
    if (affiliationCode) affiliationCode.value = item.relation_code || '';
    if (affiliationStartsOn) affiliationStartsOn.value = item.starts_on || '';
    if (affiliationEndsOn) affiliationEndsOn.value = item.ends_on || '';
    if (affiliationStatus) affiliationStatus.value = item.status || 'active';
    if (affiliationNotes) affiliationNotes.value = item.notes || '';
  };

  affiliationTargetSearch?.addEventListener('input', () => {
    const selectedLabel = affiliationTargetSearch.dataset.selectedLabel || '';
    if (affiliationTargetSearch.value !== selectedLabel) {
      if (affiliationTarget) {
        affiliationTarget.value = '0';
      }
      delete affiliationTargetSearch.dataset.selectedLabel;
      affiliationTargetSearch.removeAttribute('aria-invalid');
    }

    queueAffiliationTargetSearch();
  });

  affiliationTargetSearch?.addEventListener('focus', () => {
    if (affiliationTargetSearch.value.trim().length >= 2 && Number(affiliationTarget?.value || 0) < 1) {
      queueAffiliationTargetSearch();
    }
  });

  affiliationTargetSearch?.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      clearAffiliationTargetResults();
    }
  });

  affiliationType?.addEventListener('change', () => {
    if (Number(affiliationTarget?.value || 0) < 1 && (affiliationTargetSearch?.value.trim().length || 0) >= 2) {
      queueAffiliationTargetSearch();
    }
  });

  document.addEventListener('click', (event) => {
    const picker = affiliationTargetSearch?.closest('.xdecaro-affiliation-target-picker');
    if (picker && !picker.contains(event.target)) {
      clearAffiliationTargetResults();
    }
  });

  document.querySelectorAll('[data-affiliation-add]').forEach((button) => {
    button.addEventListener('click', () => {
      resetAffiliation();
      setAffiliationMode('affiliation');
      affiliationModal?.show();
    });
  });

  document.querySelectorAll('[data-affiliate-add]').forEach((button) => {
    button.addEventListener('click', () => {
      resetAffiliation();
      setAffiliationMode('affiliate');
      affiliationModal?.show();
    });
  });

  document.querySelectorAll('[data-affiliation-edit]').forEach((button) => {
    button.addEventListener('click', () => {
      fillAffiliation(parseAffiliation(button));
      affiliationModal?.show();
    });
  });

  document.querySelector('[data-affiliation-save]')?.addEventListener('click', async () => {
    try {
      if (Number(affiliationTarget?.value || 0) < 1) {
        if (affiliationTargetSearch) {
          affiliationTargetSearch.setAttribute('aria-invalid', 'true');
          window.alert(affiliationTargetSearch.dataset.requiredLabel || 'Select an organization from the search results.');
          affiliationTargetSearch.focus();
        }
        return;
      }

      const selectedOrganizationId = Number(affiliationTarget?.value || 0);
      const addingAffiliate = (affiliationMode?.value || 'affiliation') === 'affiliate';

      await post(endpoints.affiliationSave, {
        id: affiliationId?.value || '0',
        organization_id: addingAffiliate ? selectedOrganizationId : affiliationsOrganizationId,
        target_organization_id: addingAffiliate ? affiliationsOrganizationId : selectedOrganizationId,
        relation_type: affiliationType?.value || 'sports_affiliation',
        relation_code: affiliationCode?.value || '',
        starts_on: affiliationStartsOn?.value || '',
        ends_on: affiliationEndsOn?.value || '',
        status: affiliationStatus?.value || 'active',
        notes: affiliationNotes?.value || '',
        state: '1',
      });
      reloadOrganizationTab('affiliations');
    } catch (error) {
      window.alert(error.message || String(error));
    }
  });

  document.querySelectorAll('[data-affiliation-delete]').forEach((button) => {
    button.addEventListener('click', async () => {
      if (!window.confirm(button.dataset.confirm || 'Delete this affiliation permanently?')) {
        return;
      }
      try {
        await post(endpoints.affiliationDelete, { id: button.dataset.affiliationId || '0' });
        reloadOrganizationTab('affiliations');
      } catch (error) {
        window.alert(error.message || String(error));
      }
    });
  });

  updateCustomRole();
});
