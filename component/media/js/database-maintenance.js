(() => {
  'use strict';

  const bind = (root = document) => {
    root.querySelectorAll('[data-typed-confirm]').forEach((form) => {
      if (form.dataset.maintenanceBound === '1') return;
      form.dataset.maintenanceBound = '1';
      const expected = form.dataset.typedConfirm || '';
      const input = form.querySelector('[name="database_confirmation"]');
      const submit = form.querySelector('[data-typed-submit]');
      if (!input || !submit) return;
      const sync = () => { submit.disabled = input.disabled || input.value !== expected; };
      input.addEventListener('input', sync);
      sync();
    });

    root.querySelectorAll('[data-maintenance-confirm]').forEach((button) => {
      if (button.dataset.maintenanceConfirmBound === '1') return;
      button.dataset.maintenanceConfirmBound = '1';
      button.addEventListener('click', (event) => {
        const message = button.dataset.maintenanceConfirm || '';
        if (message && !window.confirm(message)) event.preventDefault();
      });
    });
  };

  document.addEventListener('DOMContentLoaded', () => bind());
  document.addEventListener('joomla:updated', (event) => bind(event.target || document));
})();
