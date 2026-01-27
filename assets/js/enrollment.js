/**
 * Enrollment Form Handler
 * Bossify Academy
 */

(function() {
    'use strict';

    // Initialize enrollment modal functionality
    document.addEventListener('DOMContentLoaded', function() {
        const enrollmentModal = new bootstrap.Modal(document.getElementById('enrollmentModal'));
        const enrollmentForm = document.getElementById('enrollmentForm');
        const packageTypeSelect = document.getElementById('package_type');
        const courseTrackWrapper = document.getElementById('course_track_wrapper');
        const courseTrackSelect = document.getElementById('course_track');

        // Handle enroll button clicks
        document.querySelectorAll('.enroll-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const packageType = this.getAttribute('data-package');
                
                // Set package type in form
                packageTypeSelect.value = packageType;
                
                // Show/hide course track based on package
                if (packageType === 'single_track') {
                    courseTrackWrapper.style.display = 'block';
                    courseTrackSelect.required = true;
                } else {
                    courseTrackWrapper.style.display = 'none';
                    courseTrackSelect.required = false;
                    courseTrackSelect.value = '';
                }
                
                // Open modal
                enrollmentModal.show();
            });
        });

        // Handle package type change
        packageTypeSelect.addEventListener('change', function() {
            if (this.value === 'single_track') {
                courseTrackWrapper.style.display = 'block';
                courseTrackSelect.required = true;
            } else {
                courseTrackWrapper.style.display = 'none';
                courseTrackSelect.required = false;
                courseTrackSelect.value = '';
            }
        });

        // Handle form submission
        if (enrollmentForm) {
            enrollmentForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Client-side validation
                const motivationField = document.getElementById('motivation');
                if (motivationField && motivationField.value.trim().length < 20) {
                    const errorMessage = this.querySelector('.error-message');
                    errorMessage.textContent = 'Motivation must be at least 20 characters. Please provide more details about your goals and interest.';
                    errorMessage.classList.add('d-block');
                    motivationField.focus();
                    return;
                }
                
                // Split full name into first and last name
                const fullName = document.getElementById('first_name').value.trim();
                const nameParts = fullName.split(' ');
                const firstName = nameParts[0] || '';
                const lastName = nameParts.slice(1).join(' ') || firstName; // Use first name as last if only one word
                
                // Update hidden last_name field
                document.getElementById('last_name').value = lastName;
                
                const formData = new FormData(this);
                // Update first_name with just first part
                formData.set('first_name', firstName);
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const loading = this.querySelector('.loading');
                const errorMessage = this.querySelector('.error-message');
                const sentMessage = this.querySelector('.sent-message');
                
                // Reset messages
                loading.classList.add('d-block');
                errorMessage.classList.remove('d-block');
                sentMessage.classList.remove('d-block');
                submitBtn.disabled = true;
                
                // Submit to API
                fetch('api/enroll.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // Check if response is ok
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.message || `HTTP ${response.status}: ${response.statusText}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    loading.classList.remove('d-block');
                    
                    if (data.success) {
                        // Update success message
                        const successText = data.message || 'Your enrollment application has been submitted successfully! We will contact you within 24 hours.';
                        sentMessage.innerHTML = `<span>${successText}</span>`;
                        sentMessage.classList.add('d-block');
                        
                        // Hide form fields smoothly
                        const formFields = enrollmentForm.querySelectorAll('.row, button[type="submit"], .text-muted');
                        formFields.forEach(field => {
                            field.style.transition = 'opacity 0.3s ease';
                            field.style.opacity = '0';
                            setTimeout(() => {
                                field.style.display = 'none';
                            }, 300);
                        });
                        
                        // Reset form (hidden)
                        enrollmentForm.reset();
                        courseTrackWrapper.style.display = 'none';
                        
                        // Scroll to success message
                        setTimeout(() => {
                            sentMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }, 100);
                        
                        // Close modal after 4 seconds
                        setTimeout(() => {
                            enrollmentModal.hide();
                            // Reset form display after modal closes
                            setTimeout(() => {
                                formFields.forEach(field => {
                                    field.style.display = '';
                                    field.style.opacity = '1';
                                });
                                sentMessage.classList.remove('d-block');
                                sentMessage.innerHTML = 'Your enrollment application has been submitted successfully! We will contact you within 24 hours.';
                            }, 300);
                        }, 4000);
                    } else {
                        // Handle validation errors
                        let errorText = data.message || 'An error occurred. Please try again.';
                        if (data.errors) {
                            const errorList = Object.values(data.errors).join(', ');
                            errorText = errorList || errorText;
                        }
                        errorMessage.textContent = errorText;
                        errorMessage.classList.add('d-block');
                        submitBtn.disabled = false;
                    }
                })
                .catch(error => {
                    loading.classList.remove('d-block');
                    let errorText = 'Network error. Please check your connection and try again.';
                    
                    // Try to parse error message
                    if (error.message) {
                        errorText = error.message;
                    }
                    
                    // Try to parse JSON error if it's a JSON string
                    try {
                        const jsonError = JSON.parse(error.message);
                        if (jsonError.message) {
                            errorText = jsonError.message;
                        } else if (jsonError.errors) {
                            errorText = Object.values(jsonError.errors).join(', ');
                        }
                    } catch (e) {
                        // Not JSON, use error message as is
                    }
                    
                    errorMessage.textContent = errorText;
                    errorMessage.classList.add('d-block');
                    submitBtn.disabled = false;
                    console.error('Enrollment error:', error);
                });
            });
        }
    });
})();
