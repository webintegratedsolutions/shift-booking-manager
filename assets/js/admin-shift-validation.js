// This script validates the form fields on the Shift post type edit screen in WordPress admin.
// It checks if the required fields are filled out before submission, and disables time slots as needed.
console.log('[SBM] Admin validation script loaded!');
console.log('Current body class:', document.body.className);

document.addEventListener('DOMContentLoaded', function () {
  const now = new Date();
  console.log('🕒 Page loaded - Local Time:', now.toString());
  console.log('🕒 Page loaded - ISO:', now.toISOString());
  console.log('🕒 Page loaded - Hours:', now.getHours());

  const form = document.querySelector('#post');
  if (!form || !document.body.classList.contains('post-type-shift')) return;

  const shiftDateInput = document.querySelector('[name="shift_date"]');
  const startTimeSelect = document.querySelector('[name="start_time"]');
  const endTimeSelect = document.querySelector('[name="end_time"]');

  const messagesContainer = document.createElement('div');
  messagesContainer.id = 'sbm-warnings';
  messagesContainer.style.cssText = `
    display: none;
    margin: 15px 0;
    padding: 12px 16px;
    border-left: 4px solid #007cba;
    background: #f0f8ff;
    color: #000;
    font-size: 14px;
    line-height: 1.5;
  `;
  
  // Insert below the admin page title
  const adminTitle = document.querySelector('.wrap h1');
  if (adminTitle && adminTitle.parentNode) {
    adminTitle.parentNode.insertBefore(messagesContainer, adminTitle.nextSibling);
  }  

  const fullTimeOptions = Array.from(startTimeSelect.options).map(option => ({
    value: option.value,
    label: option.label
  }));

  // Initially disable time fields
  startTimeSelect.disabled = true;
  endTimeSelect.disabled = true;

  function showWarning(msg) {
    clearWarnings(); // Make sure only one message is shown at a time
    const div = document.createElement('div');
    div.textContent = msg;
    div.style.marginBottom = '5px';
    messagesContainer.appendChild(div);
    messagesContainer.style.display = 'block';
  }  

  function clearWarnings() {
    messagesContainer.innerHTML = '';
    messagesContainer.style.display = 'none';
  }

  function filterStartTimes(selectedDate) {
    const now = new Date();
    const currentHour = now.getHours();
  
    const parts = selectedDate.split('-');
    const selected = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
  
    const isSameLocalDate = selected.getFullYear() === now.getFullYear() &&
                            selected.getMonth() === now.getMonth() &&
                            selected.getDate() === now.getDate();
  
    console.log('====================');
    console.log('🕒 Current Time:', now.toString());
    console.log('📅 Selected Date:', selected.toString());
    console.log('🔍 isSameLocalDate:', isSameLocalDate);
    console.log('🕐 currentHour:', currentHour);
    console.log('🕐 currentHour + 1:', currentHour + 1);
  
    // 🔄 Reset and re-add clean placeholder
    startTimeSelect.innerHTML = '';
    const placeholder = new Option('-- Select Start Time --', '');
    placeholder.disabled = true;
    placeholder.selected = true;
    startTimeSelect.appendChild(placeholder);
    console.log('✅ Added clean placeholder');
  
    fullTimeOptions.forEach(({ value, label }) => {
      // Skip adding empty options (already handled with the placeholder)
      if (!value) return;
  
      const hour = parseInt(value.split(':')[0], 10);
      const minutes = parseInt(value.split(':')[1], 10);
  
      if (isSameLocalDate) {
        if (hour >= currentHour + 1) {
          const opt = new Option(label, value);
          startTimeSelect.appendChild(opt);
          console.log(`✅ Added [${value}] (today, hour >= ${currentHour + 1})`);
        } else {
          console.log(`⛔ Skipped [${value}] (today, hour < ${currentHour + 1})`);
        }
      } else {
        const opt = new Option(label, value);
        startTimeSelect.appendChild(opt);
        console.log(`✅ Added [${value}] (future date)`);
      }
    });
  
    console.log('🧾 Final start options count:', startTimeSelect.options.length);
    console.log('====================');
  }  

  function filterEndTimes(startVal) {
    endTimeSelect.innerHTML = '';
    endTimeSelect.appendChild(new Option('-- Select End Time --', ''));

    if (!startVal) return;

    const startParts = startVal.split(':');
    const startMinutes = parseInt(startParts[0]) * 60 + parseInt(startParts[1]);

    fullTimeOptions.forEach(({ value, label }) => {
      const parts = value.split(':');
      const total = parseInt(parts[0]) * 60 + parseInt(parts[1]);
      if (total >= startMinutes + 60) {
        const opt = new Option(label, value);
        endTimeSelect.appendChild(opt);
      }
    });
  }

  shiftDateInput.addEventListener('change', function () {
    // Do not clear immediately, allow previous warning to remain
    const selectedDate = this.value;
    if (!selectedDate) return;
  
    // Once the user makes a change to shift date, clear warnings
    setTimeout(() => clearWarnings(), 3000); // small delay to allow any pending messages  

    startTimeSelect.disabled = false;

    // Save current selections
    const oldStart = startTimeSelect.value;
    const oldEnd = endTimeSelect.value;

    // Refresh start times
    filterStartTimes(selectedDate);

    const now = new Date();
    const parts = selectedDate.split('-');
    const selected = new Date(
      parseInt(parts[0]),
      parseInt(parts[1]) - 1,
      parseInt(parts[2]),
      0, 0, 0, 0
    );

    const isSameLocalDate = selected.getFullYear() === now.getFullYear() &&
                            selected.getMonth() === now.getMonth() &&
                            selected.getDate() === now.getDate();
    const currentHour = now.getHours();

    // Check if old start time is still valid
    let adjustedStart = oldStart;
    let adjustedEnd = oldEnd;

    if (isSameLocalDate && oldStart) {
      const hour = parseInt(oldStart.split(':')[0]);
      if (hour < currentHour + 1) {
        // Bump start time
        adjustedStart = `${String(currentHour + 1).padStart(2, '0')}:00`;
        showWarning(`Start time adjusted to ${adjustedStart} since earlier times are no longer valid today.`);
      }
    }

// 🧠 Validate: If today, and adjusted start is not in options, reset
if (isSameLocalDate && !isTimeValid(adjustedStart, startTimeSelect)) {
  adjustedStart = '';
  startTimeSelect.value = '';
  endTimeSelect.value = '';
  endTimeSelect.disabled = true;
  showWarning(`Start and end times have been reset because the selected times are no longer valid for today.`);
} else {
  // 🕐 Set start only if valid
  startTimeSelect.value = adjustedStart;
  filterEndTimes(adjustedStart);

  // 🎯 Adjust end time if no longer valid for updated start
  const interval = getTimeDiffMinutes(oldStart, oldEnd);
  const newEndTime = getTimePlusMinutes(adjustedStart, interval);

  if (interval && !isTimeValid(newEndTime, endTimeSelect)) {
    adjustedEnd = '';
    endTimeSelect.value = '';
    showWarning(`End time reset. Please select a new one at least 1 hour after the start.`);
  } else {
    adjustedEnd = newEndTime;
    endTimeSelect.value = adjustedEnd;
  }

  endTimeSelect.disabled = !adjustedStart;
}
  });

  startTimeSelect.addEventListener('change', function () {
    clearWarnings();
    const start = this.value;
    filterEndTimes(start);
    endTimeSelect.disabled = !start;
    endTimeSelect.value = '';
  });
  
  endTimeSelect.addEventListener('change', function () {
    clearWarnings(); // Hide previous warning when end time is changed
  });

  form.addEventListener('submit', function (e) {
    let isValid = true;
    clearWarnings();
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

  function getTimeDiffMinutes(start, end) {
    if (!start || !end) return null;
    const [sh, sm] = start.split(':').map(Number);
    const [eh, em] = end.split(':').map(Number);
    return (eh * 60 + em) - (sh * 60 + sm);
  }

  function getTimePlusMinutes(time, minutes) {
    if (!time || minutes == null) return '';
    const [h, m] = time.split(':').map(Number);
    const total = h * 60 + m + minutes;
    const hh = String(Math.floor(total / 60)).padStart(2, '0');
    const mm = String(total % 60).padStart(2, '0');
    return `${hh}:${mm}`;
  }

  function isTimeValid(time, selectEl) {
    return Array.from(selectEl.options).some(opt => opt.value === time);
  }
});
