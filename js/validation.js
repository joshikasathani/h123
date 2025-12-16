// Form Validation Functions

// Hospital Registration Form Validation
function validateHospitalForm() {
    let isValid = true;
    
    // Clear previous errors
    document.querySelectorAll('.error').forEach(element => {
        element.textContent = '';
    });
    
    // Hospital Name validation
    const hospitalName = document.getElementById('hospitalName').value.trim();
    if (hospitalName === '') {
        document.getElementById('hospitalNameError').textContent = 'Hospital name is required';
        isValid = false;
    } else if (hospitalName.length < 3) {
        document.getElementById('hospitalNameError').textContent = 'Hospital name must be at least 3 characters';
        isValid = false;
    }
    
    // Address validation
    const address = document.getElementById('address').value.trim();
    if (address === '') {
        document.getElementById('addressError').textContent = 'Address is required';
        isValid = false;
    } else if (address.length < 10) {
        document.getElementById('addressError').textContent = 'Address must be at least 10 characters';
        isValid = false;
    }
    
    // Phone Number validation
    const phoneNumber = document.getElementById('phoneNumber').value.trim();
    const phoneRegex = /^[\d\s\-\+\(\)]+$/;
    if (phoneNumber === '') {
        document.getElementById('phoneNumberError').textContent = 'Phone number is required';
        isValid = false;
    } else if (!phoneRegex.test(phoneNumber)) {
        document.getElementById('phoneNumberError').textContent = 'Please enter a valid phone number';
        isValid = false;
    } else if (phoneNumber.length < 10) {
        document.getElementById('phoneNumberError').textContent = 'Phone number must be at least 10 digits';
        isValid = false;
    }
    
    // Email validation
    const email = document.getElementById('email').value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email === '') {
        document.getElementById('emailError').textContent = 'Email is required';
        isValid = false;
    } else if (!emailRegex.test(email)) {
        document.getElementById('emailError').textContent = 'Please enter a valid email address';
        isValid = false;
    }
    
    // Specialization validation
    const specialization = document.getElementById('specialization').value.trim();
    if (specialization === '') {
        document.getElementById('specializationError').textContent = 'Specialization is required';
        isValid = false;
    } else if (specialization.length < 3) {
        document.getElementById('specializationError').textContent = 'Specialization must be at least 3 characters';
        isValid = false;
    }
    
    // Available Days validation
    const availableDays = document.getElementById('availableDays').value.trim();
    if (availableDays === '') {
        document.getElementById('availableDaysError').textContent = 'Available days are required';
        isValid = false;
    }
    
    // Available Timings validation
    const availableTimings = document.getElementById('availableTimings').value.trim();
    if (availableTimings === '') {
        document.getElementById('availableTimingsError').textContent = 'Available timings are required';
        isValid = false;
    }
    
    return isValid;
}

// Appointment Booking Form Validation
function validateAppointmentForm() {
    let isValid = true;
    
    // Clear previous errors
    document.querySelectorAll('.error').forEach(element => {
        element.textContent = '';
    });
    
    // Patient Name validation
    const patientName = document.getElementById('patientName').value.trim();
    if (patientName === '') {
        document.getElementById('patientNameError').textContent = 'Patient name is required';
        isValid = false;
    } else if (patientName.length < 3) {
        document.getElementById('patientNameError').textContent = 'Patient name must be at least 3 characters';
        isValid = false;
    }
    
    // Phone Number validation
    const phoneNumber = document.getElementById('phoneNumber').value.trim();
    const phoneRegex = /^[\d\s\-\+\(\)]+$/;
    if (phoneNumber === '') {
        document.getElementById('phoneNumberError').textContent = 'Phone number is required';
        isValid = false;
    } else if (!phoneRegex.test(phoneNumber)) {
        document.getElementById('phoneNumberError').textContent = 'Please enter a valid phone number';
        isValid = false;
    } else if (phoneNumber.length < 10) {
        document.getElementById('phoneNumberError').textContent = 'Phone number must be at least 10 digits';
        isValid = false;
    }
    
    // Appointment Date validation
    const appointmentDate = document.getElementById('appointmentDate').value;
    if (appointmentDate === '') {
        document.getElementById('appointmentDateError').textContent = 'Appointment date is required';
        isValid = false;
    } else {
        const selectedDate = new Date(appointmentDate);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate < today) {
            document.getElementById('appointmentDateError').textContent = 'Appointment date cannot be in the past';
            isValid = false;
        }
    }
    
    // Appointment Time validation
    const appointmentTime = document.getElementById('appointmentTime').value;
    if (appointmentTime === '') {
        document.getElementById('appointmentTimeError').textContent = 'Appointment time is required';
        isValid = false;
    }
    
    return isValid;
}

// Utility function to show messages
function showMessage(message, type) {
    const messageDiv = document.getElementById('message');
    if (messageDiv) {
        messageDiv.textContent = message;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
    }
}

// Initialize form validation when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Hospital registration form
    const hospitalForm = document.getElementById('hospitalForm');
    if (hospitalForm) {
        hospitalForm.addEventListener('submit', function(e) {
            if (!validateHospitalForm()) {
                e.preventDefault();
            }
        });
    }
    
    // Appointment booking form
    const appointmentForm = document.getElementById('appointmentForm');
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', function(e) {
            if (!validateAppointmentForm()) {
                e.preventDefault();
            }
        });
    }
    
    // Set minimum date for appointment booking
    const dateInput = document.getElementById('appointmentDate');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }
});
