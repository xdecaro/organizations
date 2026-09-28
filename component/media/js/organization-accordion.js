document.addEventListener('DOMContentLoaded', () => {
  const organizationAccordion = document.getElementById('organizationAccordion');
  const activeTabInput = document.getElementById('organization-active-tab');

  organizationAccordion?.addEventListener('shown.bs.collapse', (event) => {
    const section = event.target?.dataset?.organizationSection || '';
    if (!section) {
      return;
    }

    if (activeTabInput) {
      activeTabInput.value = section;
    }

    const url = new URL(window.location.href);
    url.searchParams.set('activeTab', section);
    window.history.replaceState(window.history.state, '', url.toString());
  });
});
