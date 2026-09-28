(() => {
  'use strict';

  const submitControlForm = (control) => {
    const form = control.form || control.closest('form');

    if (!form) {
      return;
    }

    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
      return;
    }

    form.submit();
  };

  const getScope = (root) => (
    root instanceof Element || root instanceof Document ? root : document
  );

  const bindAutoSubmit = (root = document) => {
    const scope = getScope(root);

    scope.querySelectorAll('.xdecaro-global-list-filterbar select, [data-xdecaro-auto-submit="true"]').forEach((control) => {
      if (control.dataset.xdecaroAutoSubmitBound === '1') {
        return;
      }

      control.dataset.xdecaroAutoSubmitBound = '1';
      control.addEventListener('change', () => submitControlForm(control));
    });
  };

  const bindLiveSearch = (root = document) => {
    const scope = getScope(root);

    scope.querySelectorAll('.xdecaro-global-list-filterbar input[type="search"]').forEach((control) => {
      if (control.dataset.xdecaroLiveSearchBound === '1') {
        return;
      }

      control.dataset.xdecaroLiveSearchBound = '1';
      let timer = null;

      control.addEventListener('input', (event) => {
        if (event.isComposing) {
          return;
        }

        window.clearTimeout(timer);
        timer = window.setTimeout(() => submitControlForm(control), 400);
      });

      control.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
          window.clearTimeout(timer);
        }
      });
    });
  };

  const bindFilterBehavior = (root = document) => {
    bindAutoSubmit(root);
    bindLiveSearch(root);
  };

  document.addEventListener('DOMContentLoaded', () => bindFilterBehavior());
  document.addEventListener('joomla:updated', (event) => bindFilterBehavior(event.target || document));
})();
