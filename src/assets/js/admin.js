// assets/js/admin.js

// Toggle submenu
document.querySelectorAll('.submenu-toggle').forEach(toggle => {
    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        const parent = this.parentElement;
        parent.classList.toggle('active');
    });
});

document.addEventListener('DOMContentLoaded', () => {
    console.log('Admin JavaScript loaded');

    // ==============================
    // 📌 Mobile Menu & Smooth Scroll
    // ==============================
    const menuToggle = document.getElementById('menuToggle');
    const sidePanel = document.getElementById('sidePanel');
    const overlay = document.getElementById('overlay');

    if (menuToggle && sidePanel && overlay) {
        // Toggle menu
        menuToggle.addEventListener('click', function () {
            sidePanel.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = sidePanel.classList.contains('active') ? 'hidden' : '';
            
            // Tukar icon
            if (sidePanel.classList.contains('active')) {
                menuToggle.innerHTML = '✕';
            } else {
                menuToggle.innerHTML = '☰';
            }
        });

        // Close menu on overlay click
        overlay.addEventListener('click', function () {
            sidePanel.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            menuToggle.innerHTML = '☰';
        });

        // Close menu when clicking a link (mobile)
        const sideMenuLinks = document.querySelectorAll('.side-menu a:not(.submenu-toggle)');
        sideMenuLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 968) {
                    sidePanel.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                    menuToggle.innerHTML = '☰';
                }
            });
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 968) {
                sidePanel.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }

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

    // ==============================
    // 📌 ADMIN DASHBOARD - APPROVE/REJECT SYSTEM
    // ==============================
    const processActionsBtn = document.getElementById('process-actions-btn');
    const adminForm = document.getElementById('admin-form');

    // Update button state based on selections
    function updateProcessButtonState() {
        if (!processActionsBtn) return;
        
        const checkedRadios = document.querySelectorAll('#current-bookings-table input[type="radio"]:checked');
        const count = checkedRadios.length;
        
        if (count > 0) {
            processActionsBtn.disabled = false;
            processActionsBtn.textContent = `Process ${count} Booking${count > 1 ? 's' : ''}`;
            processActionsBtn.style.opacity = '1';
        } else {
            processActionsBtn.disabled = true;
            processActionsBtn.textContent = 'Process Actions';
            processActionsBtn.style.opacity = '0.6';
        }
    }

    // ==============================
    // 📌 Modern Button Action Handler
    // ==============================
    const actionButtons = document.querySelectorAll('.action-buttons button');

    actionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const bookingId = this.dataset.bookingId;
            const action = this.dataset.action;
            const row = this.closest('tr');
            
            if (!bookingId || !action) {
                console.error('Missing booking ID or action');
                return;
            }
            
            // Get BOTH radio inputs for this booking
            const approveRadio = document.getElementById(`approve_${bookingId}`);
            const rejectRadio = document.getElementById(`reject_${bookingId}`);
            const currentRadio = document.getElementById(`${action}_${bookingId}`);
            
            if (!currentRadio) {
                console.error(`Radio button not found: ${action}_${bookingId}`);
                return;
            }
            
            // Get all buttons in this row
            const rowButtons = row.querySelectorAll('.action-buttons button');
            
            // Check if this button is already selected
            const isCurrentlySelected = currentRadio.checked;
            
            // Clear ALL selections in this row first
            if (approveRadio) approveRadio.checked = false;
            if (rejectRadio) rejectRadio.checked = false;
            rowButtons.forEach(btn => btn.classList.remove('selected'));
            row.classList.remove('approve-selected', 'reject-selected');
            
            // If it wasn't selected, select it now
            if (!isCurrentlySelected) {
                currentRadio.checked = true;
                this.classList.add('selected');
                
                if (action === 'approve') {
                    row.classList.add('approve-selected');
                } else if (action === 'reject') {
                    row.classList.add('reject-selected');
                    const modal = document.getElementById('rejectionModal');
                    if (modal) {
                        document.getElementById('rejection_booking_id').value = bookingId;
                        modal.style.display = 'flex';
                    }
                }
                console.log(`✓ ${action.toUpperCase()} selected for booking ${bookingId}`);
            } else {                                   // ✅ else matches if (!isCurrentlySelected)
                console.log(`✗ ${action.toUpperCase()} deselected for booking ${bookingId}`);
            }
            
            // Update process button state
            updateProcessButtonState();
        });                                        
    });                                               

    // ==============================
    // 📌 Process Actions Button Handler
    // ==============================
    if (processActionsBtn && adminForm) {
        // Initialize button state on load
        updateProcessButtonState();
        
        // Process selected actions - Submit form to backend
        processActionsBtn.addEventListener('click', (e) => {
            e.preventDefault();
            console.log('Processing selected actions...');

            const checkedRadios = document.querySelectorAll('#current-bookings-table input[type="radio"]:checked');

            if (checkedRadios.length === 0) {
                alert('Please select at least one action (Approve or Reject) before processing.');
                return;
            }

            // Build summary of actions
            const approveCount = document.querySelectorAll('#current-bookings-table input[type="radio"][id^="approve_"]:checked').length;
            const rejectCount = document.querySelectorAll('#current-bookings-table input[type="radio"][id^="reject_"]:checked').length;
            
            let summaryMsg = `You are about to process ${checkedRadios.length} booking(s):\n\n`;
            if (approveCount > 0) summaryMsg += `✓ Approve: ${approveCount}\n`;
            if (rejectCount > 0) summaryMsg += `✗ Reject: ${rejectCount}\n`;
            summaryMsg += `\nContinue?`;
            
            // Confirm before submit
            if (confirm(summaryMsg)) {
                console.log('Submitting form to backend...');
                
                // Disable button to prevent double submission
                processActionsBtn.disabled = true;
                processActionsBtn.textContent = 'Processing...';
                processActionsBtn.style.opacity = '0.6';
                
                // Submit the form
                adminForm.submit();
            }
        });

        // Keyboard shortcut Ctrl + Enter
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                processActionsBtn.click();
            }
        });
    }

    // ==============================
    // 📌 DEBUGGING: Log Radio Button States
    // ==============================
    function debugRadioButtons() {
        const allRadios = document.querySelectorAll('#current-bookings-table input[type="radio"]');
        console.log('=== Radio Button States ===');
        allRadios.forEach(radio => {
            if (radio.checked) {
                console.log(`✓ ${radio.id} - CHECKED`);
            }
        });
        console.log('=========================');
    }

    // Auto-refresh notification count every 30 seconds
    setInterval(function() {
        fetch('get_pending_count.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.querySelector('.side-menu a[href="adminpage.php"] .notification-badge');
                    const menuLink = document.querySelector('.side-menu a[href="adminpage.php"]');
                    
                    if (data.count > 0) {
                        if (!badge) {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'notification-badge' + (data.count >= 10 ? ' high-count' : '');
                            newBadge.textContent = data.count > 99 ? '99+' : data.count;
                            menuLink.appendChild(newBadge);
                        } else {
                            badge.textContent = data.count > 99 ? '99+' : data.count;
                            badge.className = 'notification-badge' + (data.count >= 10 ? ' high-count' : '');
                        }
                    } else if (badge) {
                        badge.remove();
                    }
                }
            })
            .catch(error => console.error('Error:', error));
    }, 30000);

    // Optional: Call debug function when needed
    // window.debugRadios = debugRadioButtons;

    // ==============================
    // 📌 ADMIN MANAGEMENT FEATURES
    // ==============================
    
    // Admin Form Validation
    const adminManagementForm = document.querySelector('.admin-form');
    
    if (adminManagementForm) {
        adminManagementForm.addEventListener('submit', function(e) {
            const input = this.querySelector('input[name="new_staffid"]');
            const staffId = input.value.trim();
            
            // Validate staff ID is not empty
            if (!staffId) {
                e.preventDefault();
                alert('Please enter a Staff ID');
                input.focus();
                return false;
            }
            
            // Validate staff ID contains only numbers
            if (!/^\d+$/.test(staffId)) {
                e.preventDefault();
                alert('Staff ID must contain only numbers');
                input.focus();
                return false;
            }
        });
    }

    // Auto-hide Alert Messages
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        // Auto hide after 5 seconds
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });

    // Smooth Scroll to Admin Section (if there are alerts)
    const adminSection = document.querySelector('.admin-section');
    
    if (adminSection && alerts.length > 0) {
        setTimeout(() => {
            adminSection.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }, 100);
    }

    // Input Focus Animation for Admin Form
    const adminInputs = document.querySelectorAll('.admin-form input');
    
    adminInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
            this.parentElement.style.transition = 'transform 0.2s ease';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });

    // Stats Animation on Load
    const statCards = document.querySelectorAll('.stat-card');
    
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Action Buttons Animation
    const quickActionButtons = document.querySelectorAll('.action-btn');
    
    quickActionButtons.forEach((btn, index) => {
        btn.style.opacity = '0';
        btn.style.transform = 'scale(0.9)';
        
        setTimeout(() => {
            btn.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            btn.style.opacity = '1';
            btn.style.transform = 'scale(1)';
        }, 600 + (index * 50));
    });

    // Prevent Double Submit for All Forms (EXCEPT admin management forms)
    const allForms = document.querySelectorAll('form:not(.admin-form):not(.admin-section form)');
    
    allForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.dataset.submitted) {
                submitBtn.dataset.submitted = 'true';
                
                // Allow submission to complete before disabling
                setTimeout(() => {
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.6';
                    submitBtn.textContent = 'Processing...';
                }, 10);
                
                // Re-enable after 3 seconds as fallback
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    delete submitBtn.dataset.submitted;
                }, 3000);
            }
        });
    });

    console.log('✓ All admin features initialized');
});

// Rejection Modal Handling
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('rejectionModal');
    if (!modal) return;
    const closeModal = document.querySelector('.close-modal');
    const cancelBtn = document.querySelector('.cancel-rejection');
    const rejectionForm = document.getElementById('rejectionForm');

    // Close modal
    function closeRejectionModal() {
        modal.style.display = 'none';
        rejectionForm.reset();
    }
    
    closeModal.addEventListener('click', closeRejectionModal);
    cancelBtn.addEventListener('click', closeRejectionModal);
    
    // Close when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeRejectionModal();
        }
    });
    
    // Handle form submission
    rejectionForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const bookingId = document.getElementById('rejection_booking_id').value;
        const notes = document.getElementById('rejection_notes').value.trim();
        
        if (!notes) {
            alert('Please provide a reason for rejection');
            return;
        }
        
        // Set the rejection notes in hidden field
        const notesField = document.getElementById('rejection_notes_' + bookingId);
        if (notesField) {
            notesField.value = notes;
        }
        
        // Check the reject radio button
        const rejectRadio = document.getElementById('reject_' + bookingId);
        if (rejectRadio) {
            rejectRadio.checked = true;
        }
        
        closeRejectionModal();
        alert('Rejection reason saved. Click SUBMIT to process.');
    });
});