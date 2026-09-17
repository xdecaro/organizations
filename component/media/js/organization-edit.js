document.addEventListener('DOMContentLoaded', () => {
  const country = document.getElementById('jform_country_code');
  const language = document.getElementById('jform_language');

  if (country && language) {
    country.addEventListener('change', () => {
      if (country.value === 'IT') {
        language.value = 'it-IT';
        language.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });
  }

  const members = document.querySelector('.xdecaro-members[data-organization-id]');
  const bodies = document.querySelector('.xdecaro-bodies[data-organization-id]');
  const membersOrganizationId = Number(members?.dataset.organizationId || 0);
  const bodiesOrganizationId = Number(bodies?.dataset.organizationId || 0);
  if (membersOrganizationId < 1 && bodiesOrganizationId < 1) {
    return;
  }

  const endpoints = {
    search: 'index.php?option=com_xdecaroorganizations&task=appointment.searchPeople&format=json',
    save: 'index.php?option=com_xdecaroorganizations&task=appointment.save&format=json',
    end: 'index.php?option=com_xdecaroorganizations&task=appointment.end&format=json',
    delete: 'index.php?option=com_xdecaroorganizations&task=appointment.delete&format=json',
    bodySave: 'index.php?option=com_xdecaroorganizations&task=body.save&format=json',
  };

  const editModalElement = document.getElementById('appointment-edit-modal');
  const endModalElement = document.getElementById('appointment-end-modal');
  const bodyModalElement = document.getElementById('body-edit-modal');
  const editModal = editModalElement && window.bootstrap?.Modal
    ? window.bootstrap.Modal.getOrCreateInstance(editModalElement)
    : null;
  const endModal = endModalElement && window.bootstrap?.Modal
    ? window.bootstrap.Modal.getOrCreateInstance(endModalElement)
    : null;
  const bodyModal = bodyModalElement && window.bootstrap?.Modal
    ? window.bootstrap.Modal.getOrCreateInstance(bodyModalElement)
    : null;

  const idField = document.getElementById('appointment-id');
  const personUuid = document.getElementById('appointment-person-uuid');
  const personSearch = document.querySelector('[data-people-search]');
  const peopleResults = document.querySelector('[data-people-results]');
  const roleCode = document.getElementById('appointment-role-code');
  const roleCustom = document.getElementById('appointment-role-custom');
  const roleCustomWrap = document.querySelector('[data-role-custom-wrap]');
  const startsOn = document.getElementById('appointment-starts-on');
  const duration = document.querySelector('[data-duration-years]');
  const plannedEndsOn = document.getElementById('appointment-planned-ends-on');
  const notes = document.getElementById('appointment-notes');

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
    if (personUuid) personUuid.value = '';
    if (personSearch) personSearch.value = '';
    if (roleCode) roleCode.value = 'councillor';
    if (roleCustom) roleCustom.value = '';
    if (startsOn) startsOn.value = '';
    if (duration) duration.value = '1';
    if (plannedEndsOn) plannedEndsOn.value = '';
    if (notes) notes.value = '';
    clearResults();
    updateCustomRole();
  };

  const fillEdit = (appointment) => {
    if (idField) idField.value = String(appointment.id || 0);
    if (personUuid) personUuid.value = appointment.person_uuid || '';
    if (personSearch) personSearch.value = appointment.person_name_snapshot || '';
    if (roleCode) roleCode.value = appointment.role_code || 'councillor';
    if (roleCustom) roleCustom.value = appointment.role_custom || '';
    if (startsOn) startsOn.value = appointment.starts_on || '';
    if (plannedEndsOn) plannedEndsOn.value = appointment.planned_ends_on || '';
    if (duration) duration.value = detectDuration(appointment.starts_on || '', appointment.planned_ends_on || '');
    if (notes) notes.value = appointment.notes || '';
    clearResults();
    updateCustomRole();
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
        organization_id: members.dataset.organizationId,
        person_uuid: personUuid?.value || '',
        role_code: roleCode?.value || '',
        role_custom: roleCustom?.value || '',
        starts_on: startsOn?.value || '',
        duration_years: selectedDuration === 'custom' ? '' : selectedDuration,
        planned_ends_on: plannedEndsOn?.value || '',
        notes: notes?.value || '',
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

  updateCustomRole();
});
