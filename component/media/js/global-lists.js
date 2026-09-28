(() => {
  'use strict';

  const bindAutoSubmit = (root = document) => {
    const scope = root instanceof Element || root instanceof Document ? root : document;

    scope.querySelectorAll('[data-xdecaro-auto-submit="true"]').forEach((control) => {
      if (control.dataset.xdecaroAutoSubmitBound === '1') {
        return;
      }

      control.dataset.xdecaroAutoSubmitBound = '1';
      control.addEventListener('change', () => {
        const form = control.form || control.closest('form');

        if (!form) {
          return;
        }

        if (typeof form.requestSubmit === 'function') {
          form.requestSubmit();
          return;
        }

        form.submit();
      });
    });
  };

  document.addEventListener('DOMContentLoaded', () => bindAutoSubmit());
  document.addEventListener('joomla:updated', (event) => bindAutoSubmit(event.target || document));
})();
