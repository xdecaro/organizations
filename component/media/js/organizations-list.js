document.addEventListener('DOMContentLoaded', () => {
  const picker = document.querySelector('[data-org-column-picker]');
  if (!picker) {
    return;
  }

  const toggles = [...picker.querySelectorAll('[data-org-column-toggle]')];
  const reset = picker.querySelector('[data-org-columns-reset]');
  const storageKey = 'com_xdecaroorganizations.organizations.columns.v1';
  const configurable = toggles.filter((toggle) => !toggle.disabled);
  const defaultColumns = configurable.map((toggle) => toggle.value);

  const apply = () => {
    toggles.forEach((toggle) => {
      const column = toggle.value;
      const visible = toggle.disabled || toggle.checked;

      document.querySelectorAll('[data-org-column="' + column + '"]').forEach((cell) => {
        cell.hidden = !visible;
      });
    });
  };

  const save = () => {
    try {
      const selected = configurable.filter((toggle) => toggle.checked).map((toggle) => toggle.value);
      window.localStorage.setItem(storageKey, JSON.stringify(selected));
    } catch (error) {
      console.debug('Organizations column preferences could not be saved.', error);
    }
  };

  try {
    const stored = JSON.parse(window.localStorage.getItem(storageKey) || 'null');
    if (Array.isArray(stored)) {
      configurable.forEach((toggle) => {
        toggle.checked = stored.includes(toggle.value);
      });
    }
  } catch (error) {
    console.debug('Organizations column preferences could not be loaded.', error);
  }

  toggles.forEach((toggle) => {
    toggle.addEventListener('change', () => {
      apply();
      save();
    });
  });

  reset?.addEventListener('click', () => {
    configurable.forEach((toggle) => {
      toggle.checked = defaultColumns.includes(toggle.value);
    });

    try {
      window.localStorage.removeItem(storageKey);
    } catch (error) {
      console.debug('Organizations column preferences could not be reset.', error);
    }

    apply();
  });

  apply();
});
