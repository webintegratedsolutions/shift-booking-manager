// This script validates the form fields on the Shift post type edit screen in WordPress admin.
// It checks if the required fields are filled out before submission.

document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('#post');
  if (!form || document.body.classList.contains('post-type-shift') === false) return;

  form.addEventListener('submit', function (e) {
    let isValid = true;

    // Clear previous errors
    form.querySelectorAll('.sbm-error').forEach(el => el.remove());

    const requiredFields = ['shift_date', 'start_time', 'end_time', 'service', 'hourly_rate'];

    requiredFields.forEach(field => {
      const input = form.querySelector(`[name="${field}"]`);
      if (input && input.value.trim() === '') {
        isValid = false;
        const error = document.createElement('div');
        error.className = 'sbm-error';
        error.style.color = 'red';
        error.textContent = 'This field is required.';
        input.parentNode.appendChild(error);
      }
    });

    if (!isValid) {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  });
});
