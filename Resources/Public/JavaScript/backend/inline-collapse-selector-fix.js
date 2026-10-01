/**
 * Escapes data-bs-target of inline record headers before Bootstrap's collapse
 * handler reads it. See Classes/Form/FieldWizard/InlineCollapseSelectorFix.php
 */
const selector = '.form-irre-object > .panel-heading [data-bs-toggle="collapse"][aria-controls]';

document.addEventListener('click', (event) => {
  const button = event.target instanceof Element ? event.target.closest(selector) : null;
  if (button === null) {
    return;
  }
  const escaped = '#' + CSS.escape(button.getAttribute('aria-controls'));
  if (button.getAttribute('data-bs-target') !== escaped) {
    button.setAttribute('data-bs-target', escaped);
  }
}, true);
