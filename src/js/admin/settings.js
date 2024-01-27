document.addEventListener('DOMContentLoaded', () => {

    const hiddenInput = document.querySelector('#wp_search_metrics_remove_data');
    const toggleSwitch = document.querySelector('[data-settings-remove-data]');
    const innerCircleIconNo = toggleSwitch.querySelector('[data-toggle-switch-icon="no"]');
    const innerCircleIconYes = toggleSwitch.querySelector('[data-toggle-switch-icon="yes"]');
    
    // Initialize toggle switch based on hidden input value
    const isEnabled = hiddenInput.value === 'yes';
    toggleSwitch.setAttribute('aria-checked', isEnabled);
    toggleSwitch.classList.toggle('bg-indigo-600', isEnabled);
    toggleSwitch.classList.toggle('bg-gray-200', !isEnabled);
    toggleSwitch.querySelector('[data-toggle-switch="data-settings-remove-data"]').classList.toggle('translate-x-5', isEnabled);
    toggleSwitch.querySelector('[data-toggle-switch="data-settings-remove-data"]').classList.toggle('translate-x-0', !isEnabled);
    
    innerCircleIconNo.classList.toggle('opacity-0', isEnabled);
    innerCircleIconNo.classList.toggle('opacity-100', !isEnabled);
    innerCircleIconYes.classList.toggle('opacity-100', isEnabled);
    innerCircleIconYes.classList.toggle('opacity-0', !isEnabled);

    // Event listener for when the toggle switch is clicked.
    toggleSwitch.addEventListener('click', () => {
        // Toggle the switch state
        const isCurrentlyEnabled = toggleSwitch.getAttribute('aria-checked') === 'true';
        const newState = !isCurrentlyEnabled;
        
        // Update hidden input value
        hiddenInput.value = newState ? 'yes' : 'no';

        // Update toggle switch UI and 'aria-checked' attribute
        toggleSwitch.setAttribute('aria-checked', String(newState));
        toggleSwitch.classList.toggle('bg-indigo-600', newState);
        toggleSwitch.classList.toggle('bg-gray-200', !newState);

        // Toggle classes for the switch's inner circle
        const innerCircle = toggleSwitch.querySelector('[data-toggle-switch="data-settings-remove-data"]');
        innerCircle.classList.toggle('translate-x-5', newState);
        innerCircle.classList.toggle('translate-x-0', !newState);

        // Toggle icon based on state
        innerCircleIconNo.classList.toggle('opacity-0', newState);
        innerCircleIconNo.classList.toggle('opacity-100', !newState);

        innerCircleIconYes.classList.toggle('opacity-100', newState);
        innerCircleIconYes.classList.toggle('opacity-0', !newState);

        // Add the rest of the toggles for the icons' visibility and transition as in the previous example
    });
});