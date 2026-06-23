// assets/js/admin_calendar_management.js - FIXED VERSION

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Admin Calendar Management loaded');
    
    // Get the existing dates data passed from PHP
    const existingDates = window.existingDatesData || {};
    console.log('📅 Existing dates loaded:', Object.keys(existingDates).length);

    function getStatusColor(status) {
        const colors = {
            'available': '#48bb78',
            'holiday': '#ed8936',
            'unavailable': '#e53e3e'
        };
        return colors[status] || '#48bb78';
    }

    function getStatusText(status) {
        const texts = {
            'available': 'AVAILABLE',
            'holiday': 'HOLIDAY / MAINTENANCE',
            'unavailable': 'UNAVAILABLE'
        };
        return texts[status] || 'AVAILABLE';
    }

    // Convert Date object to YYYY-MM-DD without timezone shift
    function getLocalDateString(dateObj) {
        const year = dateObj.getFullYear();
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const day = String(dateObj.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Helper function to convert DD-MM-YYYY to YYYY-MM-DD
    function convertToISODate(dateStr) {
        if (!dateStr) return '';
        const [day, month, year] = dateStr.split('-');
        return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
    }

    // Initialize Admin Calendar - for selecting dates
    const adminCalendarEl = document.getElementById('adminCalendar');
    if (adminCalendarEl) {
        // ✅ ADD: Get today's date
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        flatpickr('#adminCalendar', {
            inline: true,
            dateFormat: 'd-m-Y',
            locale: 'ms',
            // ✅ ADD: Disable past dates
            disable: [
                function(date) {
                    const dateObj = new Date(date);
                    dateObj.setHours(0, 0, 0, 0);
                    return dateObj < today;
                }
            ],
            enable: [
                function(date) {
                    return true; // Enable all dates for admin (except past dates already disabled above)
                }
            ],
            onChange: function(selectedDates, dateStr) {
                console.log('📅 Selected date:', dateStr);
                const isoDate = convertToISODate(dateStr);
                document.getElementById('singleDate').value = isoDate;
                console.log('✅ ISO date set to input:', isoDate);
                updatePreview();
            },
            onDayCreate: function(dObj, dStr, fpInstance, dayElem) {
                const date = getLocalDateString(dayElem.dateObj);
                const status = existingDates[date];
                
                const dateObj = new Date(dayElem.dateObj);
                dateObj.setHours(0, 0, 0, 0);
                
                // ✅ ADD: Style past dates (grey + strikethrough)
                if (dateObj < today) {
                    dayElem.style.backgroundColor = "#cbd5e0";
                    dayElem.style.color = "#a0aec0";
                    dayElem.style.textDecoration = "line-through";
                    dayElem.style.cursor = "not-allowed";
                    dayElem.classList.add('flatpickr-disabled', 'past-date');
                    dayElem.title = "Past date - cannot be selected";
                    return; // Skip other styling
                }
    
                if (status) {
                    dayElem.style.backgroundColor = getStatusColor(status);
                    dayElem.style.color = "white";
                    dayElem.style.fontWeight = "bold";
                }
                
                dayElem.classList.remove('flatpickr-disabled');
                dayElem.style.pointerEvents = 'auto';
                dayElem.style.cursor = 'pointer';
            }
        });
        console.log('✅ Admin calendar initialized');
    }

    // Initialize Preview Calendar
    const previewCalendarEl = document.getElementById('previewCalendar');
    if (previewCalendarEl) {
        flatpickr('#previewCalendar', {
            inline: true,
            dateFormat: 'd-m-Y',
            locale: 'ms',
            clickOpens: false,
            onDayCreate: function(dObj, dStr, fpInstance, dayElem) {
                const date = getLocalDateString(dayElem.dateObj);
                const status = existingDates[date];
                
                if (status) {
                    dayElem.style.backgroundColor = getStatusColor(status);
                    dayElem.style.color = "white";
                    dayElem.style.fontWeight = "bold";
                    if (status !== 'available') {
                        dayElem.classList.add('flatpickr-disabled');
                    }
                }
            }
        });
        console.log('✅ Preview calendar initialized');
    }

    function updatePreview() {
        const previewContainer = document.getElementById('previewCalendar');
        if (previewContainer) {
            previewContainer.parentElement.querySelectorAll('.flatpickr-calendar').forEach(cal => cal.remove());
            
            flatpickr('#previewCalendar', {
                inline: true,
                dateFormat: 'd-m-Y',
                locale: 'ms',
                clickOpens: false,
                onDayCreate: function(dObj, dStr, fpInstance, dayElem) {
                    const date = getLocalDateString(dayElem.dateObj);
                    const status = existingDates[date];
                    
                    if (status) {
                        dayElem.style.backgroundColor = getStatusColor(status);
                        dayElem.style.color = "white";
                        dayElem.style.fontWeight = "bold";
                        if (status !== 'available') {
                            dayElem.classList.add('flatpickr-disabled');
                        }
                    }
                }
            });
        }
    }

    // Update Single Date
    window.updateSingleDate = function() {
        const date = document.getElementById('singleDate').value;
        const status = document.getElementById('singleStatus').value;

        if (!date) {
            showMessage('Please select a date', 'error');
            return;
        }

        console.log('📝 Updating single date:', date, status);

        const formData = new FormData();
        formData.append('action', 'update_date');
        formData.append('date', date);
        formData.append('status', status);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('📡 Response:', data);
            if (data.success) {
                // Update local cache
                existingDates[date] = status;
                
                // Reload both calendars
                updatePreview();
                loadRecentUpdates();
                showMessage(data.message, 'success');
                
                // Clear form
                document.getElementById('singleDate').value = '';
                document.getElementById('singleStatus').value = 'available';
                
                // Reload the admin calendar
                reloadAdminCalendar();
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('❌ Error:', error);
            showMessage('Error: ' + error.message, 'error');
        });
    };

    // Helper function to reload admin calendar
    function reloadAdminCalendar() {
        const adminCalContainer = document.getElementById('adminCalendar');
        if (adminCalContainer) {
            adminCalContainer.parentElement.querySelectorAll('.flatpickr-calendar').forEach(cal => cal.remove());
            
            // ✅ ADD: Get today's date
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            flatpickr('#adminCalendar', {
                inline: true,
                dateFormat: 'd-m-Y',
                locale: 'ms',
                // ✅ ADD: Disable past dates
                disable: [
                    function(date) {
                        const dateObj = new Date(date);
                        dateObj.setHours(0, 0, 0, 0);
                        return dateObj < today;
                    }
                ],
                enable: [
                    function(date) {
                        return true;
                    }
                ],
                onChange: function(selectedDates, dateStr) {
                    const isoDate = convertToISODate(dateStr);
                    document.getElementById('singleDate').value = isoDate;
                    updatePreview();
                },
                onDayCreate: function(dObj, dStr, fpInstance, dayElem) {
                    const date = getLocalDateString(dayElem.dateObj);
                    const status = existingDates[date];
                    
                    const dateObj = new Date(dayElem.dateObj);
                    dateObj.setHours(0, 0, 0, 0);
                    
                    // ✅ ADD: Style past dates
                    if (dateObj < today) {
                        dayElem.style.backgroundColor = "#cbd5e0";
                        dayElem.style.color = "#a0aec0";
                        dayElem.style.textDecoration = "line-through";
                        dayElem.style.cursor = "not-allowed";
                        dayElem.classList.add('flatpickr-disabled', 'past-date');
                        dayElem.title = "Past date - cannot be selected";
                        return;
                    }
    
                    if (status) {
                        dayElem.style.backgroundColor = getStatusColor(status);
                        dayElem.style.color = "white";
                        dayElem.style.fontWeight = "bold";
                    }
                    
                    dayElem.classList.remove('flatpickr-disabled');
                    dayElem.style.pointerEvents = 'auto';
                    dayElem.style.cursor = 'pointer';
                }
            });
        }
    }

    // Update Date Range
    window.updateDateRange = function() {
        const startDateStr = document.getElementById('rangeStart').value;
        const endDateStr = document.getElementById('rangeEnd').value;
        const status = document.getElementById('rangeStatus').value;

        if (!startDateStr || !endDateStr) {
            showMessage('Please select both start and end dates', 'error');
            return;
        }

        console.log('📅 Input dates - Start:', startDateStr, 'End:', endDateStr);

        const parseLocalDate = (dateStr) => {
            const [year, month, day] = dateStr.split('-').map(Number);
            return new Date(year, month - 1, day);
        };

        const startDate = parseLocalDate(startDateStr);
        const endDate = parseLocalDate(endDateStr);

        if (startDate > endDate) {
            showMessage('Start date must be before end date', 'error');
            return;
        }

        const dates = [];
        let current = new Date(startDate);

        while (current <= endDate) {
            const dateIso = getLocalDateString(current);
            dates.push(dateIso);
            current.setDate(current.getDate() + 1);
        }

        if (dates.length === 0) {
            showMessage('No valid dates in range', 'error');
            return;
        }

        console.log('📝 Updating', dates.length, 'dates with status:', status);
        console.log('📅 Date range:', dates[0], 'to', dates[dates.length - 1]);

        const formData = new FormData();
        formData.append('action', 'bulk_update');
        formData.append('status', status);
        dates.forEach((date, index) => {
            formData.append(`dates[${index}]`, date);
        });

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('📡 Response:', data);
            if (data.success) {
                // Update local cache
                dates.forEach(date => {
                    existingDates[date] = status;
                });
                
                // Reload calendars
                updatePreview();
                loadRecentUpdates();
                reloadAdminCalendar();
                
                showMessage(data.message, 'success');
                
                // Clear form
                document.getElementById('rangeStart').value = '';
                document.getElementById('rangeEnd').value = '';
                document.getElementById('rangeStatus').value = 'available';
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('❌ Error:', error);
            showMessage('Error: ' + error.message, 'error');
        });
    };

    // Show Messages
    function showMessage(msg, type) {
        const messageEl = document.getElementById('message');
        if (!messageEl) return;
        
        messageEl.textContent = msg;
        messageEl.className = `alert ${type} show`;
        
        setTimeout(() => {
            messageEl.classList.remove('show');
        }, 5000);
    }

    // Load Recent Updates with Pagination
    let currentPage = 1;
    const itemsPerPage = 10;
    let allUpdates = [];

    function loadRecentUpdates() {
        const recentListEl = document.getElementById('recentList');
        if (!recentListEl) return;

        allUpdates = Object.entries(existingDates)
            .sort((a, b) => new Date(b[0]) - new Date(a[0]));

        if (allUpdates.length === 0) {
            recentListEl.innerHTML = '<p style="color: #a0aec0; margin: 0; padding: 20px;">No updates yet</p>';
            updatePaginationControls();
            return;
        }

        displayPage(1);
    }

    function displayPage(page) {
        currentPage = page;
        const recentListEl = document.getElementById('recentList');
        const startIndex = (page - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const pageItems = allUpdates.slice(startIndex, endIndex);

        const html = pageItems.map(([date, status]) => {
            const [year, month, day] = date.split('-').map(Number);
            const dateObj = new Date(year, month - 1, day);
            const formattedDate = dateObj.toLocaleDateString('en-MY', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            return `
                <div class="date-item">
                    <span class="date-item-date">${formattedDate}</span>
                    <span class="date-item-status ${status}">${getStatusText(status)}</span>
                </div>
            `;
        }).join('');

        recentListEl.innerHTML = html;
        updatePaginationControls();
    }

    function updatePaginationControls() {
        const totalPages = Math.ceil(allUpdates.length / itemsPerPage);
        let paginationHTML = '';

        let paginationEl = document.getElementById('recentPagination');
        if (!paginationEl) {
            return;
        }

        if (totalPages <= 1) {
            paginationEl.innerHTML = '';
            return;
        }

        if (currentPage > 1) {
            paginationHTML += `<a href="javascript:void(0)" onclick="goToPage(1)" class="pagination-btn" title="First Page">&laquo;&laquo;</a>`;
            paginationHTML += `<a href="javascript:void(0)" onclick="goToPage(${currentPage - 1})" class="pagination-btn" title="Previous Page">&laquo;</a>`;
        } else {
            paginationHTML += `<span class="pagination-btn disabled">&laquo;&laquo;</span>`;
            paginationHTML += `<span class="pagination-btn disabled">&laquo;</span>`;
        }

        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        if (startPage > 1) {
            paginationHTML += `<a href="javascript:void(0)" onclick="goToPage(1)" class="pagination-btn">1</a>`;
            if (startPage > 2) {
                paginationHTML += `<span class="pagination-btn">...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            if (i === currentPage) {
                paginationHTML += `<span class="pagination-btn active">${i}</span>`;
            } else {
                paginationHTML += `<a href="javascript:void(0)" onclick="goToPage(${i})" class="pagination-btn">${i}</a>`;
            }
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHTML += `<span class="pagination-btn">...</span>`;
            }
            paginationHTML += `<a href="javascript:void(0)" onclick="goToPage(${totalPages})" class="pagination-btn">${totalPages}</a>`;
        }

        if (currentPage < totalPages) {
            paginationHTML += `<a href="javascript:void(0)" onclick="goToPage(${currentPage + 1})" class="pagination-btn" title="Next Page">&raquo;</a>`;
            paginationHTML += `<a href="javascript:void(0)" onclick="goToPage(${totalPages})" class="pagination-btn" title="Last Page">&raquo;&raquo;</a>`;
        } else {
            paginationHTML += `<span class="pagination-btn disabled">&raquo;</span>`;
            paginationHTML += `<span class="pagination-btn disabled">&raquo;&raquo;</span>`;
        }

        const totalItems = allUpdates.length;
        const startItem = (currentPage - 1) * itemsPerPage + 1;
        const endItem = Math.min(currentPage * itemsPerPage, totalItems);
        paginationHTML = `<div class="pagination-info">Showing ${startItem} to ${endItem} of ${totalItems} updates</div><div class="pagination">${paginationHTML}</div>`;

        paginationEl.innerHTML = paginationHTML;
    }

    window.goToPage = function(page) {
        displayPage(page);
        const recentSection = document.querySelector('.calendar-section:last-of-type');
        if (recentSection) {
            recentSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    // Initial load
    loadRecentUpdates();
    console.log('✅ Admin Calendar Management initialization complete');
    console.log('📊 Total dates loaded:', Object.keys(existingDates).length);
});