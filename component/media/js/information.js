(() => {
  'use strict';

  const button = document.querySelector('[data-copy-diagnostics]');
  if (!button) {
    return;
  }

  const label = button.querySelector('[data-copy-label]');
  const originalLabel = label ? label.textContent : '';

  button.addEventListener('click', async () => {
    const targetId = button.dataset.copyTarget || '';
    const target = targetId ? document.getElementById(targetId) : null;
    if (!target) {
      return;
    }

    const text = target.value || target.textContent || '';

    try {
      await navigator.clipboard.writeText(text);
      if (label) {
        label.textContent = button.dataset.copySuccess || originalLabel;
        window.setTimeout(() => {
          label.textContent = originalLabel;
        }, 1800);
      }
    } catch (error) {
      target.classList.remove('visually-hidden');
      target.removeAttribute('aria-hidden');
      target.focus();
      target.select();
    }
  });
})();
