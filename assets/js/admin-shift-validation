// File: admin-shift-validation.js
// Description: This script validates the shift form on the admin side of the website. It checks if required fields are filled out, ensures the start time is at least 1 hour from now, and displays error messages if validation fails.

document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('#post');

  if (!form || document.body.classList.contains('post-type-shift') === false) return;

  form.addEventListener('submit', function (e) {
    // Clear previous errors
    const errorEls = form.querySelectorAll('.sbm-error');
    errorEls.forEach(el => el.remove());

    let isValid = true;

    const requiredFields = [
      { id: 'shift_date', label: 'Date' },
      { id: 'start_time', label: 'Start Time' },
      { id: 'end_time', label: 'End Time' },
      { id: 'service', label: 'Service' },
      { id: 'hourly_rate', label: 'Hourly Rate' },
    ];

    requiredFields.forEach(field => {
      const input = form.querySelector(`[name="${field.id}"]`);
      if (input && input.value.trim() === '') {
        isValid = false;
        showError(input, `${field.label} is required.`);
      }
    });

    // Validate start time is at least 1 hour from now
    const dateInput = form.querySelector('[name="shift_date"]');
    const timeInput = form.querySelector('[name="start_time"]');

    if (dateInput && timeInput && dateInput.value && timeInput.value) {
      const now = new Date();
      const start = new Date(`${dateInput.value}T${timeInput.value}`);
      const oneHourLater = new Date(now.getTime() + 60 * 60 * 1000);

      if (start < oneHourLater) {
        isValid = false;
        showError(timeInput, 'Start time must be at least 1 hour from now.');
      }
    }

    if (!isValid) {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  });

  function showError(input, message) {
    const error = document.createElement('div');
    error.className = 'sbm-error';
    error.style.color = 'red';
    error.style.fontSize = '13px';
    error.style.marginTop = '4px';
    error.textContent = message;
    input.parentNode.appendChild(error);
  }
});
