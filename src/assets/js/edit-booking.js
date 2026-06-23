// ===== BACK TO TOP BUTTON =====
const backToTopBtn = document.createElement('button');
backToTopBtn.id = 'backToTop';
backToTopBtn.innerHTML = '↑';
backToTopBtn.title = 'Back to Top';
document.body.appendChild(backToTopBtn);

window.addEventListener('scroll', function() {
    if (window.pageYOffset > 300) {
        backToTopBtn.classList.add('show');
    } else {
        backToTopBtn.classList.remove('show');
    }
});

backToTopBtn.addEventListener('click', function() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// ===== FORM VALIDATION =====
const form = document.querySelector('.edit-form');

if (form) {
    form.addEventListener('submit', function(e) {
        const inputs = form.querySelectorAll('input[required], textarea[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.style.borderColor = '#e53e3e';
                
                // Remove error styling after user starts typing
                input.addEventListener('input', function() {
                    this.style.borderColor = '#e2e8f0';
                });
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            
            // Scroll to first invalid input
            const firstInvalid = form.querySelector('input[style*="border-color: rgb(229, 62, 62)"]');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus();
            }
        }
    });
}

// ===== DATE VALIDATION =====
// Ensure return date is after select date
const selectDateInput = document.querySelector('input[name="select_date"], input[name="select_date_dd"]');
const returnDateInput = document.querySelector('input[name="return_date"], input[name="return_date_dd"]');

if (selectDateInput && returnDateInput) {
    returnDateInput.addEventListener('change', function() {
        const selectDate = new Date(selectDateInput.value);
        const returnDate = new Date(returnDateInput.value);

        if (returnDate < selectDate) {
            alert('Return date must be after or equal to the select date.');
            returnDateInput.value = selectDateInput.value;
        }
    });
}

// ===== TIME VALIDATION =====
// Ensure end time is after start time
const startTimeInput = document.querySelector('input[name="start_time"]');
const endTimeInput = document.querySelector('input[name="end_time"]');

if (startTimeInput && endTimeInput) {
    function validateTime() {
        const startTime = startTimeInput.value;
        const endTime = endTimeInput.value;

        if (startTime && endTime && endTime <= startTime) {
            alert('End time must be after start time.');
            endTimeInput.value = '';
        }
    }

    startTimeInput.addEventListener('change', validateTime);
    endTimeInput.addEventListener('change', validateTime);
}

// ===== CONFIRM BEFORE LEAVING =====
let formModified = false;

if (form) {
    const formInputs = form.querySelectorAll('input, textarea');
    
    formInputs.forEach(input => {
        input.addEventListener('change', () => {
            formModified = true;
        });
    });

    window.addEventListener('beforeunload', (e) => {
        if (formModified) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Don't show warning when submitting
    form.addEventListener('submit', () => {
        formModified = false;
    });
}

// ===== CANCEL BUTTON CONFIRMATION =====
const cancelBtn = document.querySelector('.btn-secondary');

if (cancelBtn) {
    cancelBtn.addEventListener('click', (e) => {
        if (formModified) {
            if (!confirm('You have unsaved changes. Are you sure you want to leave?')) {
                e.preventDefault();
            }
        }
    });
}

// ===== NUMBER INPUT VALIDATION =====
const numberInputs = document.querySelectorAll('input[type="number"]');

numberInputs.forEach(input => {
    input.addEventListener('input', function() {
        // Remove negative values
        if (this.value < 0) {
            this.value = 0;
        }
        
        // Remove decimals if not allowed
        if (this.step !== 'any' && this.value.includes('.')) {
            this.value = Math.floor(this.value);
        }
    });
});

// ===== AUTO-SAVE TO LOCAL STORAGE (OPTIONAL) =====
// Uncomment if you want to enable auto-save feature
/*
const formId = 'edit_booking_form';
const storageKey = `form_backup_${window.location.pathname}`;

// Save form data periodically
function saveFormData() {
    if (!form) return;
    
    const formData = {};
    const inputs = form.querySelectorAll('input, textarea');
    
    inputs.forEach(input => {
        if (input.name) {
            formData[input.name] = input.value;
        }
    });
    
    localStorage.setItem(storageKey, JSON.stringify(formData));
}

// Load saved form data
function loadFormData() {
    const savedData = localStorage.getItem(storageKey);
    
    if (savedData && confirm('Found saved form data. Would you like to restore it?')) {
        const formData = JSON.parse(savedData);
        
        Object.keys(formData).forEach(name => {
            const input = form.querySelector(`[name="${name}"]`);
            if (input) {
                input.value = formData[name];
            }
        });
        
        formModified = true;
    }
}

// Auto-save every 30 seconds
if (form) {
    loadFormData();
    setInterval(saveFormData, 30000);
    
    // Clear backup on successful submit
    form.addEventListener('submit', () => {
        localStorage.removeItem(storageKey);
    });
}
*/

// ===== SMOOTH SCROLL FOR INVALID FIELDS =====
function scrollToElement(element) {
    const yOffset = -100; // Offset from top
    const y = element.getBoundingClientRect().top + window.pageYOffset + yOffset;
    
    window.scrollTo({
        top: y,
        behavior: 'smooth'
    });
}

// ===== KEYBOARD SHORTCUTS =====
document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + S to save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        if (form) {
            form.submit();
        }
    }
    
    // ESC to cancel (with confirmation if modified)
    if (e.key === 'Escape') {
        if (cancelBtn) {
            cancelBtn.click();
        }
    }
});

console.log('Edit booking form initialized successfully');