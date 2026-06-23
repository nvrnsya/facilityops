document.addEventListener("DOMContentLoaded", () => {
    // =======================
    // 1. Facility Selection
    // =======================
    let selectedId = null;
    const items = document.querySelectorAll(".container li[data-id]");
    const confirmBtn = document.querySelector(".btn");

    console.log("Found facility items:", items.length);

    items.forEach(item => {
        item.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const facilityId = item.getAttribute("data-id");
            console.log("Clicked facility:", facilityId);
            console.log("Current selectedId before:", selectedId); 
            
            if (item.classList.contains("selected")) {
                item.classList.remove("selected");
                selectedId = null;
                console.log("Deselected facility");
            } else {
                items.forEach(i => i.classList.remove("selected"));
                item.classList.add("selected");
                selectedId = facilityId;
                console.log("Selected facility:", selectedId);
            }
            
            console.log("Current selectedId after:", selectedId); 
        });
    });

    if (confirmBtn) {
        confirmBtn.addEventListener("click", (e) => {
            e.preventDefault();
            
            console.log("Confirm clicked, selectedId:", selectedId);
            
            if (!selectedId || selectedId === null || selectedId === "") {
                alert("Please select a facility first.");
                return;
            }
            
            if (typeof userHasEmail !== 'undefined' && !userHasEmail) {
                if (confirm("You need to add your email address before booking.\n\nWould you like to update your profile now?")) {
                    window.location.href = "edit-profile.php?redirect=bookingpage";
                }
                return;
            }
            
            console.log("Redirecting to:", `bookingdetailed.php?facility=${encodeURIComponent(selectedId)}`);
            window.location.href = `bookingdetailed.php?facility=${encodeURIComponent(selectedId)}`;
        });
    }

    // =======================
    // 2. Calendar Setup with Cache Busting
    // =======================
    const calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        let allEvents = [];
        let calendar;
        
        // ✅ ADD TIMESTAMP TO PREVENT CACHING
        const timestamp = Date.now();
        
        Promise.all([
            fetch(`../admin/api/get_all_bookings.php?t=${timestamp}`).then(r => r.json()),
            fetch(`../admin/api/get_facility_colors.php?t=${timestamp}`).then(r => r.json()),
            fetch(`../admin/api/get_calendar_dates.php?all=true&t=${timestamp}`).then(r => r.json())
        ])
        .then(([bookingsData, facilitiesData, calendarDatesData]) => {
            console.log('Bookings loaded:', bookingsData);
            console.log('Facilities loaded:', facilitiesData);
            console.log('Calendar dates loaded:', calendarDatesData);
            
            allEvents = bookingsData.bookings || [];
            const facilities = facilitiesData.facilities || [];
            
            const legendContainer = document.getElementById('legendContainer');
            if (legendContainer) {
                if (facilities.length > 0) {
                    legendContainer.innerHTML = facilities.map(facility => `
                        <div style="display: flex; align-items: center; gap: 6px; white-space: nowrap;">
                            <span style="width: 16px; height: 16px; background: ${facility.color}; border-radius: 3px; display: inline-block;"></span>
                            <span style="font-size: 13px; color: #4a5568;">${facility.name}</span>
                        </div>
                    `).join('');
                } else {
                    legendContainer.innerHTML = '<span style="color: #a0aec0; font-size: 13px;">No bookings yet</span>';
                }
            }
            
            const filterSelect = document.getElementById('facilityFilter');
            if (filterSelect && facilities.length > 0) {
                facilities.forEach(facility => {
                    const option = document.createElement('option');
                    option.value = facility.name;
                    option.textContent = facility.name;
                    filterSelect.appendChild(option);
                });
            }
            
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: 700,
                selectable: false,
                events: allEvents,
                eventClick: function(info) {
                    const modal = document.createElement('div');
                    modal.className = 'booking-modal';
                    modal.innerHTML = `
                        <div class="booking-modal-overlay" onclick="this.parentElement.remove()">
                            <div class="booking-modal-content" onclick="event.stopPropagation()">
                                <button class="modal-close" onclick="this.closest('.booking-modal').remove()">×</button>
                                <h3>Booking Details</h3>
                                <div class="modal-body">
                                    <p><strong>Booked By:</strong> ${info.event.extendedProps.bookedBy || 'N/A'}</p>
                                    <p><strong>Facility:</strong> ${info.event.extendedProps.facilityName}</p>
                                    <p><strong>Program:</strong> ${info.event.extendedProps.programName}</p>
                                    <p><strong>Date:</strong> ${info.event.start.toLocaleDateString('en-MY', { 
                                        weekday: 'long', 
                                        year: 'numeric', 
                                        month: 'long', 
                                        day: 'numeric' 
                                    })}</p>
                                    ${info.event.extendedProps.startTime && info.event.extendedProps.endTime 
                                        ? `<p><strong>Time:</strong> ${info.event.extendedProps.startTime} - ${info.event.extendedProps.endTime}</p>` 
                                        : '<p><strong>Status:</strong> <span class="status-badge approved">Approved</span></p>'
                                    }
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                },
                eventDidMount: function(info) {
                    info.el.style.fontSize = '13px';
                    info.el.style.fontWeight = '600';
                    info.el.style.padding = '4px';
                }
            });

            calendar.render();
            
            // Scroll to calendar after render if hash exists
            if (window.location.hash === '#calendar') {
                setTimeout(() => {
                    const calendarEl = document.getElementById('calendar');
                    if (calendarEl) {
                        calendarEl.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'start' 
                        });
                        window.scrollBy({ top: -80, behavior: 'smooth' });
                    }
                }, 100);
            }
                        
            if (filterSelect) {
                filterSelect.addEventListener('change', function() {
                    const selectedFacility = this.value;
                    
                    if (selectedFacility === '') {
                        calendar.removeAllEvents();
                        calendar.addEventSource(allEvents);
                    } else {
                        const filtered = allEvents.filter(event => 
                            event.extendedProps.facilityName === selectedFacility
                        );
                        calendar.removeAllEvents();
                        calendar.addEventSource(filtered);
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error loading calendar:', error);
        });
    }

    // =======================
    // 3. Back to Top 
    // =======================
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
});