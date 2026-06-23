// assets/js/bookingdetailed.js - CORRECTED FINAL VERSION
document.addEventListener('DOMContentLoaded', async () => {
    console.log('JavaScript loaded - Dynamic Form Version');
    
    // ===== DECLARE ALL VARIABLES FIRST =====
    let selectedDates = []; // array of { date, startTime, endTime }
    let currentDateForTime = null;
    let selectedTimeSlots = [];
    let formModified = false;
    let isDragging = false;
    
    // Get DOM elements early
    const summaryDateStd = document.getElementById('summary-date-std');
    const facilityInput = document.getElementById('facilityName');
    const nextBtn = document.getElementById('nextBtn');
    const backBtn = document.getElementById('backBtn');
    const submitBtn = document.getElementById('submitBtn');
    const stepIndicators = document.querySelectorAll('.step');
    const page1Standard = document.getElementById('page1-standard');
    const page2Standard = document.getElementById('summary-page-standard');
    
    // ===== LOAD FORM FIELDS FIRST =====
    await loadDynamicFormFields();
    
    // ===== TIME PICKER MODAL (CREATE EARLY) =====
    const timePickerModal = document.createElement('div');
    timePickerModal.className = 'time-picker-modal';
    timePickerModal.innerHTML = `
        <div class="time-picker-content">
            <button class="time-picker-close">×</button>
            <h3>Select Time Slot</h3>
            <p class="selected-date-display"></p>
            <div class="time-slots-container">
                <div class="time-slots-grid" id="timeSlotsList"></div>
            </div>
            <div class="time-picker-summary">
                <p><strong>Selected:</strong> <span id="selectedTimeRange">No time selected</span></p>
            </div>
            <div class="time-picker-actions">
                <button class="btn btn-secondary" id="cancelTimeBtn">Cancel</button>
                <button class="btn btn-primary" id="confirmTimeBtn" disabled>Confirm Time</button>
            </div>
        </div>
    `;
    document.body.appendChild(timePickerModal);
    
    // ==============================
    // 📌 Back to Top Button
    // ==============================
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
    
    // ===== CALENDAR DATA =====
    let calendarDates = {
        available: [],
        holiday: [],
        unavailable: []
    };
    
    let timeBookings = {};
    
    const facilityFromUrl = new URLSearchParams(window.location.search).get('facility');
    const currentFacility = facilityInput ? facilityInput.value : facilityFromUrl;
    const facilitySource = window.FACILITY_SOURCE;
    
    // Variable to hold flatpickr instance
    let calendarInstance = null;
    
    try {
        const apiUrl = `../admin/api/get_calendar_dates.php?facility=${currentFacility}&source=${facilitySource}`;
        console.log('📍 API URL:', apiUrl);
        
        const response = await fetch(apiUrl);
        const data = await response.json();
        
        if (data.success) {
            calendarDates = data.dates;
            timeBookings = data.time_bookings || {}; 
            console.log('✅ Calendar dates loaded:', calendarDates);
            console.log('✅ Time bookings loaded:', timeBookings);
            
            // Initialize calendar AFTER data loaded
            initializeCalendar();
        }
    } catch (error) {
        console.error('❌ Error fetching calendar dates:', error);
    }
    
    // ===== HELPER FUNCTIONS =====
    function convertToISODate(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        const [day, month, year] = parts;
        return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
    }
    
    // ===== CALENDAR INITIALIZATION =====
    function initializeCalendar() {
        if (typeof flatpickr === 'undefined') {
            console.error('❌ Flatpickr not loaded');
            return;
        }
        
        const bigCalendar = document.getElementById('big-calendar');
        if (!bigCalendar) {
            console.error('❌ Calendar element not found');
            return;
        }
        
        if (calendarInstance) {
            calendarInstance.destroy();
        }
        
        // ✅ ADD: Get today's date
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        const availableDatesObj = calendarDates.available.map(dateStr => {
            const [year, month, day] = dateStr.split('-').map(Number);
            return new Date(`${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}T00:00:00`);
        });
    
        const holidayDates = calendarDates.holiday;
        const unavailableDates = calendarDates.unavailable;
        
        console.log('🗓️ Initializing calendar with timeBookings:', timeBookings);
        
        calendarInstance = flatpickr('#big-calendar', {
            inline: true,
            dateFormat: 'd-m-Y',
            enable: availableDatesObj,
            // ✅ ADD: Disable past dates
            disable: [
                function(date) {
                    const dateObj = new Date(date);
                    dateObj.setHours(0, 0, 0, 0);
                    return dateObj < today;
                }
            ],
            onChange: function(selectedDates, dateStr) {
                const isoDate = convertToISODate(dateStr);
                currentDateForTime = dateStr;
                
                document.querySelector('.selected-date-display').textContent = `Event Date: ${dateStr}`;
                const bookedSlots = timeBookings[isoDate] || [];
                console.log(`📍 Selected ${dateStr} (${isoDate}), Booked Slots:`, bookedSlots);
                generateTimeSlots(isoDate, bookedSlots);
                timePickerModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            },
            onMonthChange: function() {
                setTimeout(() => {
                    refreshCalendarHighlights();
                }, 50); 
            },
            
            onYearChange: function() {
                setTimeout(() => {
                    refreshCalendarHighlights();
                }, 50);
            },
            onDayCreate: function(dObj, dStr, fpInstance, dayElem) {
                const date = dayElem.dateObj.getFullYear() + '-' +
                            String(dayElem.dateObj.getMonth() + 1).padStart(2, '0') + '-' +
                            String(dayElem.dateObj.getDate()).padStart(2, '0');
                
                const dateObj = new Date(dayElem.dateObj);
                dateObj.setHours(0, 0, 0, 0);
                
                // ✅ ADD: Check if date is in the past FIRST
                if (dateObj < today) {
                    dayElem.style.backgroundColor = "#e2e8f0";
                    dayElem.style.color = "#94a3b8";
                    dayElem.style.textDecoration = "line-through";
                    dayElem.style.cursor = "not-allowed";
                    dayElem.style.opacity = "0.6";
                    dayElem.classList.add('flatpickr-disabled', 'past-date');
                    dayElem.title = "Date has passed - cannot be booked";
                    return; // ✅ Stop here, don't apply other styles
                }
            
                const hasBookings = timeBookings[date] && timeBookings[date].length > 0;
                
                if (hasBookings) {
                    console.log(`🟡 ${date} has ${timeBookings[date].length} bookings - SHOULD BE YELLOW`);
                }
                
                if (calendarDates.unavailable.includes(date)) {
                    dayElem.style.backgroundColor = "#e53e3e";
                    dayElem.style.color = "white";
                    dayElem.style.textDecoration = "line-through";
                    dayElem.classList.add('flatpickr-disabled', 'unavailable');
                } else if (holidayDates.includes(date)) {
                    dayElem.style.backgroundColor = "#ed8936";
                    dayElem.style.color = "white";
                    dayElem.classList.add('flatpickr-disabled', 'limited');
                } else if (calendarDates.available.includes(date)) {
                    if (hasBookings) {
                        dayElem.style.backgroundColor = "#fbd38d";
                        dayElem.style.color = "#744210";
                        dayElem.style.fontWeight = "600";
                        dayElem.title = `${timeBookings[date].length} time slot(s) booked`;
                    } else {
                        dayElem.style.backgroundColor = "#48bb78";
                        dayElem.style.color = "white";
                    }
                    dayElem.classList.add('available');
                }  // ← tutup else if dulu

                // Lepas tu baru check user-booked-date
                const d = dayElem.dateObj;
                if (d) {
                    const iso = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
                    const selectedISO = selectedDates.map(e => convertToISODate(e.date));
                    if (selectedISO.includes(iso)) {
                        dayElem.classList.add('user-booked-date');
                    }
                }
            }
        });
        
        console.log('✅ Calendar initialized successfully');
    }
    
    // ===== HIGHLIGHT SELECTED DATE =====
    function highlightSelectedDate() {
        if (!selectedDate || !calendarInstance) return;
        
        // Convert selectedDate (d-m-Y) to Date object
        const [day, month, year] = selectedDate.split('-').map(Number);
        const dateObj = new Date(year, month - 1, day);
        
        // Set the date in flatpickr (this will trigger visual update)
        calendarInstance.setDate(dateObj, false); // false = don't trigger onChange
        
        // Find and highlight the selected date element
        setTimeout(() => {
            const allDays = document.querySelectorAll('.flatpickr-day');
            allDays.forEach(dayElem => {
                const elemDate = dayElem.dateObj;
                if (elemDate && 
                    elemDate.getDate() === day && 
                    elemDate.getMonth() === month - 1 && 
                    elemDate.getFullYear() === year) {
                    
                    // Add blue highlight with higher priority
                    dayElem.style.backgroundColor = "#3b82f6 !important";
                    dayElem.style.color = "white !important";
                    dayElem.style.fontWeight = "700 !important";
                    dayElem.style.border = "3px solid #1e40af !important";
                    dayElem.classList.add('user-selected-date');
                }
            });
        }, 100);
    }
    
    // ===== TIME SLOT FUNCTIONS =====
    function generateTimeSlots(date, bookedSlots = []) {
        const container = document.getElementById('timeSlotsList');
        if (!container) return;
        
        container.innerHTML = '';
        selectedTimeSlots = [];
        
        const startHour = 8;
        const endHour = 22;
        
        for (let hour = startHour; hour < endHour; hour++) {
            for (let min = 0; min < 60; min += 30) {
                const timeStart = `${String(hour).padStart(2, '0')}:${String(min).padStart(2, '0')}`;
                const timeEnd = min === 30 
                    ? `${String(hour + 1).padStart(2, '0')}:00` 
                    : `${String(hour).padStart(2, '0')}:30`;
                
                const isBooked = isTimeSlotBooked(timeStart, timeEnd, bookedSlots);
                
                const slotDiv = document.createElement('div');
                slotDiv.className = `time-slot ${isBooked ? 'booked' : 'available'}`;
                slotDiv.textContent = `${timeStart} - ${timeEnd}`;
                slotDiv.dataset.start = timeStart;
                slotDiv.dataset.end = timeEnd;
                
                if (!isBooked) {
                    slotDiv.addEventListener('click', () => toggleTimeSlot(slotDiv));
                }
                
                container.appendChild(slotDiv);
            }
        }
    }
    
    function isTimeSlotBooked(start, end, bookedSlots) {
        for (let booking of bookedSlots) {
            const bookStart = booking.start;
            const bookEnd = booking.end;
            
            if (start < bookEnd && end > bookStart) {
                return true;
            }
        }
        return false;
    }
    
    function toggleTimeSlot(slotDiv) {
        const start = slotDiv.dataset.start;
        const end = slotDiv.dataset.end;
        
        if (slotDiv.classList.contains('selected')) {
            slotDiv.classList.remove('selected');
            selectedTimeSlots = selectedTimeSlots.filter(s => s.start !== start);
        } else {
            slotDiv.classList.add('selected');
            selectedTimeSlots.push({ start, end });
        }
        
        selectedTimeSlots.sort((a, b) => a.start.localeCompare(b.start));
        updateTimeRangeDisplay();
    }
    
    function updateTimeRangeDisplay() {
        const confirmBtn = document.getElementById('confirmTimeBtn');
        const rangeDisplay = document.getElementById('selectedTimeRange');
        
        if (!confirmBtn || !rangeDisplay) return;
        
        if (selectedTimeSlots.length === 0) {
            rangeDisplay.textContent = 'No time selected';
            confirmBtn.disabled = true;
            return;
        }
        
        // ALLOW SINGLE SLOT - NO VALIDATION
        const startTime = selectedTimeSlots[0].start;
        const endTime = selectedTimeSlots[selectedTimeSlots.length - 1].end;
        
        if (selectedTimeSlots.length === 1) {
            rangeDisplay.textContent = `${startTime} - ${endTime} (30 min)`;
        } else {
            rangeDisplay.textContent = `${selectedTimeSlots.length} slots selected: ${startTime} - ${endTime}`;
        }
        
        rangeDisplay.style.color = '#48bb78';
        confirmBtn.disabled = false; 
    }

    // ===== TIME PICKER MODAL EVENTS =====
    document.querySelector('.time-picker-close').addEventListener('click', () => {
        timePickerModal.classList.remove('active');
        document.body.style.overflow = '';
    });
    
    document.getElementById('cancelTimeBtn').addEventListener('click', () => {
        timePickerModal.classList.remove('active');
        document.body.style.overflow = '';
    });
    
    document.getElementById('confirmTimeBtn').addEventListener('click', () => {
        if (selectedTimeSlots.length >= 1) {
            const startTime = selectedTimeSlots[0].start;
            const endTime = selectedTimeSlots[selectedTimeSlots.length - 1].end;
            
            // Check duplicate date
            const exists = selectedDates.find(d => d.date === currentDateForTime);
            if (exists) {
                exists.startTime = startTime;
                exists.endTime = endTime;
            } else {
                selectedDates.push({ date: currentDateForTime, startTime, endTime });
            }
            
            renderSelectedDatesList(); // update UI
            timePickerModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // ===== FORM FIELD FUNCTIONS =====
    async function loadDynamicFormFields() {
        try {
            console.log('📋 Loading dynamic form fields...');
            
            const apiUrl = './api/get_form_fields.php';
            const response = await fetch(apiUrl);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error('API returned success: false');
            }
            
            renderBookingDetails(data.grouped.booking_details || []);
            renderKeyHandover(data.grouped.key_handover || []);
            
            setTimeout(() => {
                attachFieldListeners();
                updateSummary();
            }, 100);
            
        } catch (error) {
            console.error('❌ Error loading form fields:', error);
            showFormError(error.message);
        }
    }
    
    function showFormError(message) {
        const containers = ['booking-details-container', 'key-handover-container'];
        containers.forEach(id => {
            const container = document.getElementById(id);
            if (container) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #e53e3e; background: #fff5f5; border-radius: 8px;">
                        <div style="font-size: 48px; margin-bottom: 12px;">❌</div>
                        <h3 style="margin-bottom: 8px;">Failed to Load Form</h3>
                        <p style="color: #666; margin-bottom: 16px;">${message}</p>
                        <button onclick="location.reload()" style="background: #4299e1; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">
                            Refresh Page
                        </button>
                    </div>
                `;
            }
        });
    }
    
    function renderBookingDetails(fields) {
        const container = document.getElementById('booking-details-container');
        if (!container) return;
        
        container.innerHTML = '';
        fields.forEach(field => {
            container.appendChild(createInputField(field));
        });
    }
    
    function renderKeyHandover(fields) {
        const container = document.getElementById('key-handover-container');
        if (!container) return;
        
        container.innerHTML = '';
        fields.forEach(field => {
            container.appendChild(createInputField(field));
        });
    }
    
    function createInputField(field) {
        const inputGroup = document.createElement('div');
        // ✅ Add special class for checkbox/radio groups
        const needsTopLabel = ['checkbox', 'radio'].includes(field.field_type);
        inputGroup.className = needsTopLabel ? 'input-group checkbox-radio-group' : 'input-group';
        
        let inputElement = '';
        const fieldId = field.field_name;
        const isRequired = field.is_required == 1 ? 'required' : '';
        const isReadonly = field.is_readonly == 1 ? 'readonly' : '';
        const placeholder = field.placeholder || '';
        
        let autoFillValue = '';
        if (field.auto_fill_field && window.userData && window.userData[field.auto_fill_field]) {
            autoFillValue = window.userData[field.auto_fill_field];
        }
        
        switch(field.field_type) {
            case 'textarea':
                inputElement = `<textarea name="${field.field_name}" id="${fieldId}" placeholder="${placeholder}" ${isRequired} ${isReadonly}>${autoFillValue}</textarea>`;
                break;
                
            case 'select':
                const selectOptions = field.field_options ? field.field_options.split('\n') : [];
                const selectOptionsHtml = selectOptions.map(opt => `<option value="${opt.trim()}">${opt.trim()}</option>`).join('');
                inputElement = `<select name="${field.field_name}" id="${fieldId}" ${isRequired} ${isReadonly}><option value="">Select ${field.field_label}</option>${selectOptionsHtml}</select>`;
                break;
                
            case 'checkbox':
                const checkboxOptions = field.field_options ? field.field_options.split('\n') : [];
                inputElement = '<div class="options-container">';
                checkboxOptions.forEach((opt, index) => {
                    const optValue = opt.trim();
                    if (optValue) {
                        inputElement += `
                            <label class="option-item" for="${fieldId}_${index}">
                                <input 
                                    type="checkbox" 
                                    name="${field.field_name}"
                                    value="${optValue}" 
                                    id="${fieldId}_${index}"
                                    class="checkbox-input"
                                    ${isRequired && index === 0 ? 'required' : ''}>
                                <span class="option-text">${optValue}</span>
                            </label>
                        `;
                    }
                });
                inputElement += '</div>';
                break;
                
            case 'radio':
                const radioOptions = field.field_options ? field.field_options.split('\n') : [];
                inputElement = '<div class="options-container">';
                radioOptions.forEach((opt, index) => {
                    const optValue = opt.trim();
                    if (optValue) {
                        inputElement += `
                            <label class="option-item" for="${fieldId}_${index}">
                                <input 
                                    type="radio" 
                                    name="${field.field_name}" 
                                    value="${optValue}" 
                                    id="${fieldId}_${index}"
                                    ${isRequired ? 'required' : ''}>
                                <span class="option-text">${optValue}</span>
                            </label>
                        `;
                    }
                });
                inputElement += '</div>';
                break;
                
            case 'date':
                inputElement = `<input type="date" name="${field.field_name}" id="${fieldId}" ${isRequired} ${isReadonly} value="${autoFillValue}">`;
                break;
                
            default:
                inputElement = `<input type="${field.field_type}" name="${field.field_name}" id="${fieldId}" placeholder="${placeholder}" ${isRequired} ${isReadonly} value="${autoFillValue}">`;
        }
        
        if (needsTopLabel) {
            inputGroup.innerHTML = `
                <label class="field-label-top">
                    ${field.field_label}${isRequired ? '<span class="required-mark">*</span>' : ''}
                </label>
                ${inputElement}
                ${field.help_text ? `<small class="help-text">${field.help_text}</small>` : ''}
            `;
        } else {
            inputGroup.innerHTML = `
                ${inputElement}
                <label for="${fieldId}">${field.field_label}</label>
                ${field.help_text ? `<small style="color: #666; display: block; margin-top: 5px;">${field.help_text}</small>` : ''}
            `;
        }
        
        return inputGroup;
    }

    function renderSelectedDatesList() {
        const container = document.getElementById('selected-dates-list');
        if (!container) return;
        
        container.innerHTML = '';
        
        if (selectedDates.length === 0) {
            container.innerHTML = '<p class="no-dates">No dates selected yet.</p>';
            refreshCalendarHighlights();
            return;
        }
        
        selectedDates.sort((a, b) => a.date.localeCompare(b.date));
        
        selectedDates.forEach((entry, index) => {
            const item = document.createElement('div');
            item.className = 'date-entry';
            item.innerHTML = `
                <span class="date-entry-info">
                    📅 ${entry.date} &nbsp; 🕐 ${entry.startTime} – ${entry.endTime}
                </span>
                <button class="remove-date-btn" data-index="${index}">✕</button>
            `;
            item.querySelector('.remove-date-btn').addEventListener('click', () => {
                selectedDates.splice(index, 1);
                renderSelectedDatesList();
                updateSummary();
            });
            container.appendChild(item);
        });

        refreshCalendarHighlights();
    }

    function refreshCalendarHighlights() {
        // Kumpul semua ISO dates yang dah selected
        const selectedISO = selectedDates.map(entry => convertToISODate(entry.date));
        
        // Loop semua calendar day elements
        const allDays = document.querySelectorAll('.flatpickr-day');
        
        allDays.forEach(dayElem => {
            if (!dayElem.dateObj) return;
            
            const d = dayElem.dateObj;
            const iso = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
            
            dayElem.classList.remove('user-booked-date');
            
            if (selectedISO.includes(iso)) {
                dayElem.classList.add('user-booked-date');
            }
        });
    }
    
    function attachFieldListeners() {
        const allInputs = document.querySelectorAll('#booking-details-container input, #booking-details-container textarea, #booking-details-container select, #key-handover-container input, #key-handover-container textarea, #key-handover-container select');
        allInputs.forEach(input => {
            input.addEventListener('input', updateSummary);
            input.addEventListener('change', updateSummary);
        });
    }
    
    function updateSummary() {
       // Update dates list dalam summary
        const summaryDates = document.getElementById('summary-dates-list');
        if (summaryDates) {
            if (selectedDates.length === 0) {
                summaryDates.innerHTML = '<span>—</span>';
            } else {
                summaryDates.innerHTML = selectedDates
                    .map(entry => `
                        <span style="display:block; background:#f9fafb; border:1px solid #e2e8f0; border-radius:6px; padding:8px 12px;">
                            📅 ${entry.date} &nbsp;|&nbsp; 🕐 ${entry.startTime} – ${entry.endTime}
                        </span>
                    `).join('');
            }
        }
        
        // ✅ Update ALL form inputs including checkboxes - FOR SUMMARY DISPLAY ONLY
        const allInputs = document.querySelectorAll('#booking-details-container input, #booking-details-container textarea, #booking-details-container select, #key-handover-container input, #key-handover-container textarea, #key-handover-container select');
        
        allInputs.forEach(input => {
            const fieldName = input.getAttribute('name') || input.id;
            if (!fieldName) return;
            
            // Remove [] from name if it's a checkbox array
            const cleanFieldName = fieldName.replace('[]', '');
            const summaryElement = document.getElementById(`summary-${cleanFieldName}`);
            
            if (summaryElement) {
                // ✅ Handle checkboxes - UPDATE SUMMARY ELEMENT ONLY
                if (input.type === 'checkbox') {
                    const allCheckboxes = document.querySelectorAll(`input[name="${fieldName}"]:checked`);
                    const values = Array.from(allCheckboxes).map(cb => cb.value);
                    summaryElement.textContent = values.length > 0 ? values.join(', ') : '—';
                }
                // Handle radio buttons
                else if (input.type === 'radio') {
                    if (input.checked) {
                        summaryElement.textContent = input.value || '—';
                    }
                }
                // Handle regular inputs
                else {
                    summaryElement.textContent = input.value || '—';
                }
            }
        });
    }

    // ===== VALIDATION =====
    function validatePage1Standard() {
        if (selectedDates.length === 0) {
            alert('Please select at least one date and time slot.');
            return false;
        }

        const requiredInputs = document.querySelectorAll('input[required], textarea[required], select[required]');
        for (let input of requiredInputs) {
            if (input.id === 'big-calendar') continue;
            if (input.type === 'checkbox' || input.type === 'radio') continue;
            if (!input.value.trim()) {
                const label = document.querySelector(`label[for="${input.id}"]`);
                alert(`Please fill in: ${label ? label.textContent.trim() : input.name}`);
                input.focus();
                return false;
            }
        }

        return true;
    }

    // ===== PAGE NAVIGATION =====
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (!validatePage1Standard()) return;
            updateSummary();
            document.getElementById('hero-section')?.style.setProperty('display', 'none');
            page1Standard?.style.setProperty('display', 'none');
            page2Standard?.style.setProperty('display', 'block');
            stepIndicators[0]?.classList.remove('active');
            stepIndicators[1]?.classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    if (backBtn) {
        backBtn.addEventListener('click', () => {
            document.getElementById('hero-section')?.style.setProperty('display', 'flex');
            page2Standard?.style.setProperty('display', 'none');
            page1Standard?.style.setProperty('display', 'block');
            stepIndicators[1]?.classList.remove('active');
            stepIndicators[0]?.classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    if (submitBtn) {
        submitBtn.addEventListener('click', (e) => {
            e.preventDefault(); // Stop default submit
            
            console.log('🚀 SUBMIT BUTTON CLICKED!');
            
            // Run populate function
            populateHiddenFormStandard();
            
            // Wait a bit then submit
            setTimeout(() => {
                console.log('📤 Submitting form...');
                document.getElementById('hiddenForm')?.submit();
            }, 500);
        });
    }

    function populateHiddenFormStandard() {
        console.log('🔧 populateHiddenFormStandard START');
    
        // Get ALL visible form inputs
        const visibleInputs = document.querySelectorAll('#page1-standard input, #page1-standard textarea, #page1-standard select');
        
        // Track which fields we've processed (to avoid duplicates)
        const processedFields = new Set();
        
        visibleInputs.forEach(input => {
            const fieldName = input.getAttribute('name');
            if (!fieldName) return;
            
            // Get clean field name (remove [] if present)
            const cleanFieldName = fieldName.replace('[]', '');
            
            // Skip if already processed
            if (processedFields.has(cleanFieldName)) return;
            
            // Get corresponding hidden input
            const hiddenInput = document.getElementById(`form_${cleanFieldName}`);
            if (!hiddenInput) {
                console.warn(`⚠️ No hidden input for: ${cleanFieldName}`);
                return;
            }
            
            // Handle different input types
            if (input.type === 'checkbox') {
                // ✅ CHECKBOX: Get all checked values
                const checkedBoxes = document.querySelectorAll(`input[name="${fieldName}"]:checked`);
                const values = Array.from(checkedBoxes).map(cb => cb.value);
                
                if (values.length > 0) {
                    hiddenInput.value = values.join(', ');
                    console.log(`✅ Checkbox ${cleanFieldName}: [${values.join(', ')}]`);
                } else {
                    hiddenInput.value = '';
                    console.log(`⚠️ Checkbox ${cleanFieldName}: NONE SELECTED`);
                }
                
                processedFields.add(cleanFieldName);
            }
            else if (input.type === 'radio') {
                // ✅ RADIO: Only get the checked one
                const checkedRadio = document.querySelector(`input[name="${fieldName}"]:checked`);
                if (checkedRadio) {
                    hiddenInput.value = checkedRadio.value;
                    console.log(`✅ Radio ${cleanFieldName}: ${checkedRadio.value}`);
                } else {
                    hiddenInput.value = '';
                    console.log(`⚠️ Radio ${cleanFieldName}: NONE SELECTED`);
                }
                
                processedFields.add(cleanFieldName);
            }
            else {
                // ✅ REGULAR INPUT: Direct value
                if (!processedFields.has(cleanFieldName)) {
                    hiddenInput.value = input.value || '';
                    console.log(`✅ Regular ${cleanFieldName}: ${input.value || '(empty)'}`);
                    processedFields.add(cleanFieldName);
                }
            }
        });

        const hiddenDates = document.getElementById('form_booking_dates');
        if (hiddenDates) {
            hiddenDates.value = JSON.stringify(selectedDates);
            console.log('✅ booking_dates:', hiddenDates.value);
        }
        console.log('✅✅✅ populateHiddenFormStandard COMPLETE');
    }

    // ===== PREVENT PAGE LEAVE =====
    document.querySelectorAll('input, textarea, select').forEach(input => {
        input.addEventListener('change', () => formModified = true);
    });

    window.addEventListener('beforeunload', (e) => {
        if (formModified && page1Standard?.style.display !== 'none') {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    if (submitBtn) {
        submitBtn.addEventListener('click', () => formModified = false);
    }

    console.log('✅ JavaScript initialization complete');
    
    // ===== IMAGE LIGHTBOX =====
    // Create lightbox modal
    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox-modal';
    lightbox.innerHTML = `
        <div class="lightbox-content">
            <button class="lightbox-close">×</button>
            <button class="lightbox-nav lightbox-prev">‹</button>
            <img src="" alt="Gallery Image">
            <button class="lightbox-nav lightbox-next">›</button>
            <div class="lightbox-counter"><span class="current">1</span> / <span class="total">1</span></div>
        </div>
    `;
    document.body.appendChild(lightbox);

    // Get all slide images
    const slides = document.querySelectorAll('.slide');
    const lightboxImg = lightbox.querySelector('img');
    const currentCounter = lightbox.querySelector('.lightbox-counter .current');
    const totalCounter = lightbox.querySelector('.lightbox-counter .total');
    let currentLightboxIndex = 0;

    // Gallery images array
    const galleryImages = Array.from(slides).map(slide => 
        slide.querySelector('img').src
    );

    totalCounter.textContent = galleryImages.length;

    // Open lightbox
    slides.forEach((slide, index) => {
        slide.addEventListener('click', (e) => {
            currentLightboxIndex = index;
            showLightboxImage(currentLightboxIndex);
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });

    // Show specific image
    function showLightboxImage(index) {
        lightboxImg.src = galleryImages[index];
        currentCounter.textContent = index + 1;
    }

    // Close lightbox
    lightbox.querySelector('.lightbox-close').addEventListener('click', () => {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    });

    // Click outside to close
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // Previous image
    lightbox.querySelector('.lightbox-prev').addEventListener('click', () => {
        currentLightboxIndex = (currentLightboxIndex - 1 + galleryImages.length) % galleryImages.length;
        showLightboxImage(currentLightboxIndex);
    });

    // Next image
    lightbox.querySelector('.lightbox-next').addEventListener('click', () => {
        currentLightboxIndex = (currentLightboxIndex + 1) % galleryImages.length;
        showLightboxImage(currentLightboxIndex);
    });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (!lightbox.classList.contains('active')) return;
        
        if (e.key === 'Escape') {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
        } else if (e.key === 'ArrowLeft') {
            currentLightboxIndex = (currentLightboxIndex - 1 + galleryImages.length) % galleryImages.length;
            showLightboxImage(currentLightboxIndex);
        } else if (e.key === 'ArrowRight') {
            currentLightboxIndex = (currentLightboxIndex + 1) % galleryImages.length;
            showLightboxImage(currentLightboxIndex);
        }
    });

});