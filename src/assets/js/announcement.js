document.addEventListener('DOMContentLoaded', function() {
    // AJAX Toggle Handler for Status
    const toggleSwitches = document.querySelectorAll('.status-toggle-ajax');
    
    toggleSwitches.forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            const announcementId = this.dataset.announcementId;
            const newStatus = this.checked ? 'active' : 'inactive';
            const toggleSwitch = this.closest('.toggle-switch');
            
            const action = newStatus === 'active' ? 'activate' : 'deactivate';
            if (!confirm(`Are you sure you want to ${action} this announcement?`)) {
                this.checked = !this.checked;
                return;
            }
            
            toggleSwitch.classList.add('loading');
            
            const formData = new FormData();
            formData.append('ajax', 'true');
            formData.append('action', 'toggle_status');
            formData.append('announcement_id', announcementId);
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
                    toggleSwitch.setAttribute('data-status', newStatus === 'active' ? 'Active' : 'Inactive');
                } else {
                    this.checked = !this.checked;
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                toggleSwitch.classList.remove('loading');
                this.checked = !this.checked;
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
});