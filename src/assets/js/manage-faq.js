// Open Add Modal
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New FAQ';
    document.getElementById('formAction').value = 'add';
    document.getElementById('faqId').value = '';
    document.getElementById('faqForm').reset();
    document.getElementById('faqModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Open Edit Modal
function openEditModal(faq) {
    document.getElementById('modalTitle').textContent = 'Edit FAQ';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('faqId').value = faq.id;
    document.getElementById('question').value = faq.question;
    document.getElementById('answer').value = faq.answer;
    document.getElementById('faqModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Close Modal
function closeModal() {
    document.getElementById('faqModal').classList.remove('show');
    document.body.style.overflow = 'auto';
    document.getElementById('faqForm').reset();
}

// Form Submit
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('faqForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('faq_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                closeModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            showAlert('An error occurred. Please try again.', 'error');
        });
    });

    // Close modal on outside click
    window.onclick = function(event) {
        const modal = document.getElementById('faqModal');
        if (event.target === modal) {
            closeModal();
        }
    }
});

// Toggle FAQ (Updated for toggle switch)
function toggleFAQ(id) {
    const checkbox = document.querySelector(`input[data-faq-id="${id}"]`);
    const toggleSwitch = checkbox.closest('.toggle-switch');
    
    // Add loading state
    toggleSwitch.classList.add('loading');
    
    const formData = new FormData();
    formData.append('action', 'toggle');
    formData.append('id', id);
    
    fetch('faq_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        toggleSwitch.classList.remove('loading');
        
        if (data.success) {
            // Update tooltip text based on new status
            const newStatus = checkbox.checked ? 'Active' : 'Inactive';
            toggleSwitch.setAttribute('data-status', newStatus);
            showAlert(data.message, 'success');
        } else {
            // Revert checkbox if failed
            checkbox.checked = !checkbox.checked;
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        toggleSwitch.classList.remove('loading');
        // Revert checkbox on error
        checkbox.checked = !checkbox.checked;
        showAlert('An error occurred. Please try again.', 'error');
        console.error('Error:', error);
    });
}

// Delete FAQ
function deleteFAQ(id) {
    if (!confirm('Are you sure you want to delete this FAQ?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    
    fetch('faq_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        showAlert('An error occurred. Please try again.', 'error');
        console.error('Error:', error);
    });
}

// Reorder FAQ
function reorderFAQ(id, direction) {
    const formData = new FormData();
    formData.append('action', 'reorder');
    formData.append('id', id);
    formData.append('direction', direction);
    
    fetch('faq_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        showAlert('An error occurred. Please try again.', 'error');
        console.error('Error:', error);
    });
}

// Show Alert
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.getElementById('alertContainer');
    container.innerHTML = '';
    container.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}