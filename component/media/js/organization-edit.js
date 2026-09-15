document.addEventListener('DOMContentLoaded', () => {
  const country = document.getElementById('jform_country_code');
  const language = document.getElementById('jform_language');

  if (!country || !language) {
    return;
  }

  country.addEventListener('change', () => {
    if (country.value === 'IT') {
      language.value = 'it-IT';
      language.dispatchEvent(new Event('change', { bubbles: true }));
    }
  });
});
