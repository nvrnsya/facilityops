// Auto-generate slug from facility name
document.getElementById('facility_name').addEventListener('input', function() {
    const name = this.value;
    const slug = name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    document.getElementById('facility_slug').value = slug || 'will-be-generated...';
});

document.addEventListener('DOMContentLoaded', function() {
    // Initialize slug field on page load (for edit mode)
    const nameField = document.getElementById('facility_name');
    const slugField = document.getElementById('facility_slug');
    
    if (nameField && slugField) {
        // If slug is empty but name exists, generate it
        if (!slugField.value && nameField.value) {
            const slug = nameField.value
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            slugField.value = slug;
        }
    }
    
    // ========================================
    // FIXED: File Upload Preview - MAIN IMAGE
    // ========================================
    const mainImageInput = document.getElementById('main_image');
    const mainImageWrapper = mainImageInput ? mainImageInput.closest('.file-input-wrapper') : null;
    const mainImagePlaceholder = mainImageWrapper ? mainImageWrapper.querySelector('.file-placeholder') : null;

    if (mainImageInput && mainImagePlaceholder) {
        mainImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                mainImagePlaceholder.textContent = `Selected: ${file.name}`;
                mainImagePlaceholder.classList.add('has-file');
                
                // Validate file
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                
                if (!allowedTypes.includes(file.type)) {
                    alert('Only image files (JPG, JPEG, PNG, GIF) are allowed for main image.');
                    mainImageInput.value = '';
                    mainImagePlaceholder.textContent = 'Choose main image...';
                    mainImagePlaceholder.classList.remove('has-file');
                    return;
                }
                
                const fileSize = file.size / 1024 / 1024; // Convert to MB
                if (fileSize > 5) {
                    alert(`Main image "${file.name}" exceeds 5MB limit.`);
                    mainImageInput.value = '';
                    mainImagePlaceholder.textContent = 'Choose main image...';
                    mainImagePlaceholder.classList.remove('has-file');
                    return;
                }
            } else {
                mainImagePlaceholder.textContent = 'Choose main image...';
                mainImagePlaceholder.classList.remove('has-file');
            }
        });
        
        // Make the wrapper clickable
        mainImagePlaceholder.addEventListener('click', function() {
            mainImageInput.click();
        });
    }
    
    // ========================================
    // FIXED: File Upload Preview - GALLERY IMAGES
    // ========================================
    const galleryInput = document.getElementById('gallery_images');
    const galleryWrapper = galleryInput ? galleryInput.closest('.file-input-wrapper') : null;
    const galleryPlaceholder = galleryWrapper ? galleryWrapper.querySelector('.file-placeholder') : null;

    if (galleryInput && galleryPlaceholder) {
        galleryInput.addEventListener('change', function(e) {
            const files = e.target.files;
            
            if (files.length > 0) {
                const fileNames = Array.from(files).map(f => f.name).join(', ');
                galleryPlaceholder.textContent = `${files.length} file(s) selected`;
                galleryPlaceholder.classList.add('has-file');
                
                // Validate each file
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    
                    if (!allowedTypes.includes(file.type)) {
                        alert(`Only image files (JPG, JPEG, PNG, GIF) are allowed. Invalid file: ${file.name}`);
                        galleryInput.value = '';
                        galleryPlaceholder.textContent = 'Choose gallery images...';
                        galleryPlaceholder.classList.remove('has-file');
                        return;
                    }
                    
                    const fileSize = file.size / 1024 / 1024;
                    if (fileSize > 5) {
                        alert(`Gallery image "${file.name}" exceeds 5MB limit.`);
                        galleryInput.value = '';
                        galleryPlaceholder.textContent = 'Choose gallery images...';
                        galleryPlaceholder.classList.remove('has-file');
                        return;
                    }
                }
            } else {
                galleryPlaceholder.textContent = 'Choose gallery images...';
                galleryPlaceholder.classList.remove('has-file');
            }
        });
        
        // Make the wrapper clickable
        galleryPlaceholder.addEventListener('click', function() {
            galleryInput.click();
        });
    }
    
    // Form Reset Handler
    const facilityForm = document.getElementById('facilityForm');
    const resetButtons = document.querySelectorAll('.cancel-btn[type="reset"]');
    
    resetButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to reset the form?')) {
                facilityForm.reset();
                
                // Reset main image placeholder
                if (mainImagePlaceholder) {
                    mainImagePlaceholder.textContent = 'Choose main image...';
                    mainImagePlaceholder.classList.remove('has-file'); 
                }
                
                // Reset gallery placeholder
                if (galleryPlaceholder) {
                    galleryPlaceholder.textContent = 'Choose gallery images...';
                    galleryPlaceholder.classList.remove('has-file');
                }
            }
        });
    });
    
    // Auto-hide success messages
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-success');
        alerts.forEach(function(alert) {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        });
    }, 5000);
    
    // AJAX Toggle Handler for Status
    const toggleSwitches = document.querySelectorAll('.status-toggle-ajax');
    
    toggleSwitches.forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            const facilityId = this.dataset.facilityId;
            const newStatus = this.checked ? 1 : 0;
            const toggleSwitch = this.closest('.toggle-switch');
            
            const action = newStatus ? 'activate' : 'deactivate';
            if (!confirm(`Are you sure you want to ${action} this facility?`)) {
                this.checked = !newStatus;
                return;
            }
            
            toggleSwitch.classList.add('loading');
            
            const formData = new FormData();
            formData.append('ajax', 'true');
            formData.append('action', 'toggle_status');
            formData.append('facility_id', facilityId);
            formData.append('status', newStatus);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                toggleSwitch.classList.remove('loading');
                
                if (data.success) {
                    showNotification(data.message, 'success');
                    toggleSwitch.setAttribute('data-status', newStatus ? 'Active' : 'Inactive');
                } else {
                    this.checked = !newStatus;
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                toggleSwitch.classList.remove('loading');
                this.checked = !newStatus;
                showNotification('Connection error. Please try again.', 'error');
                console.error('Error:', error);
            });
        });
    });
    
    // Delete Gallery Image Handler
    const deleteGalleryBtns = document.querySelectorAll('.delete-gallery-btn');
    
    deleteGalleryBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const galleryId = this.dataset.galleryId;
            const galleryItem = this.closest('.gallery-item');
            
            if (!confirm('Are you sure you want to delete this gallery image?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax', 'true');
            formData.append('action', 'delete_gallery_image');
            formData.append('gallery_id', galleryId);
            
            // Add loading state
            galleryItem.style.opacity = '0.5';
            this.disabled = true;
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Animate and remove the gallery item
                    galleryItem.style.transition = 'all 0.3s ease';
                    galleryItem.style.transform = 'scale(0)';
                    setTimeout(() => galleryItem.remove(), 300);
                    showNotification(data.message, 'success');
                } else {
                    galleryItem.style.opacity = '1';
                    this.disabled = false;
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                galleryItem.style.opacity = '1';
                this.disabled = false;
                showNotification('Connection error. Please try again.', 'error');
                console.error('Error:', error);
            });
        });
    });
    
    // Notification function
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} notification`;
        notification.innerHTML = `${type === 'success' ? '✓' : '✗'} ${message}`;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.5s ease';
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    }
    
    // Confirm Delete
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to permanently delete this facility? This action cannot be undone!')) {
                e.preventDefault();
            }
        });
    });
    
    // Back to Top Button
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