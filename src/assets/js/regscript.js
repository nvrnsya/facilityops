document.addEventListener('DOMContentLoaded', function() {
    console.log('Script loaded'); // Debug: Confirm script is running

    const toggleButtons = document.querySelectorAll('.toggle-btn');
    const publicForm = document.getElementById('public-form');
    const staffForm = document.getElementById('staff-form');

    // Error handling to ensure elements exist
    if (!publicForm || !staffForm || toggleButtons.length === 0) {
        console.error('Form toggle elements not found:', {
            publicForm: !!publicForm,
            staffForm: !!staffForm,
            toggleButtons: toggleButtons.length
        });
        return;
    }

    toggleButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default button behavior
            console.log('Button clicked:', this.dataset.target); // Debug: Confirm click

            // Remove active class and update aria-selected for all buttons
            toggleButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.setAttribute('aria-selected', 'false');
            });

            // Add active class and update aria-selected for clicked button
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');

            // Toggle form visibility
            const targetForm = this.dataset.target;
            if (targetForm === 'public-form') {
                publicForm.classList.remove('hidden');
                staffForm.classList.add('hidden');
                console.log('Showing public-form, hiding staff-form');
            } else if (targetForm === 'staff-form') {
                publicForm.classList.add('hidden');
                staffForm.classList.remove('hidden');
                console.log('Showing staff-form, hiding public-form');
            }
        });
    });
});