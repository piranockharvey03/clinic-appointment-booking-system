// Feedback form functionality
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('feedbackForm');
    if (!form) return;
    
    const successMessage = document.getElementById('successMessage');
    const ratingStars = document.querySelectorAll('.rating-star');
    
    // Star rating functionality
    ratingStars.forEach((star, index) => {
        star.addEventListener('click', function() {
            // Clear all stars
            ratingStars.forEach(s => s.classList.remove('text-yellow-400'));
            
            // Fill stars up to clicked star
            for (let i = 0; i <= index; i++) {
                ratingStars[i].classList.add('text-yellow-400');
            }
            
            // Set the corresponding radio button
            const radioButton = document.getElementById(`rating-${5 - index}`);
            if (radioButton) radioButton.checked = true;
        });
        
        star.addEventListener('mouseenter', function() {
            // Clear all stars
            ratingStars.forEach(s => s.classList.remove('text-yellow-400'));
            
            // Fill stars up to hovered star
            for (let i = 0; i <= index; i++) {
                ratingStars[i].classList.add('text-yellow-400');
            }
        });
    });
    
    // Reset stars on mouse leave
    const ratingContainer = document.querySelector('.mt-2.flex.space-x-2');
    if (ratingContainer) {
        ratingContainer.addEventListener('mouseleave', function() {
            const checkedRating = document.querySelector('input[name="rating"]:checked');
            if (checkedRating) {
                const ratingValue = parseInt(checkedRating.value);
                ratingStars.forEach(s => s.classList.remove('text-yellow-400'));
                for (let i = 0; i < ratingValue; i++) {
                    ratingStars[4 - i].classList.add('text-yellow-400');
                }
            } else {
                ratingStars.forEach(s => s.classList.remove('text-yellow-400'));
            }
        });
    }
    
    // Form submission with AJAX
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Basic validation
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('border-red-500');
                isValid = false;
            } else {
                field.classList.remove('border-red-500');
            }
        });
        
        // Email validation
        const emailField = document.getElementById('email');
        if (emailField) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailField.value && !emailRegex.test(emailField.value)) {
                emailField.classList.add('border-red-500');
                isValid = false;
            }
        }
        
        if (isValid) {
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i data-feather="loader" class="mr-2 h-5 w-5 animate-spin"></i>Submitting...';
            submitBtn.disabled = true;
            
            // Submit form via AJAX
            const formData = new FormData(form);
            
            fetch('../php/feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    form.style.display = 'none';
                    if (successMessage) {
                        successMessage.classList.remove('hidden');
                        successMessage.scrollIntoView({ behavior: 'smooth' });
                    }
                    
                    // Reset form after 5 seconds
                    setTimeout(() => {
                        form.reset();
                        form.style.display = 'block';
                        if (successMessage) successMessage.classList.add('hidden');
                        ratingStars.forEach(s => s.classList.remove('text-yellow-400'));
                    }, 5000);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting your feedback. Please try again.');
            })
            .finally(() => {
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                if (typeof feather !== 'undefined') feather.replace();
            });
        } else {
            // Show error message
            alert('Please fill in all required fields correctly.');
        }
    });
    
    // Remove error styling on input
    form.querySelectorAll('input, textarea, select').forEach(field => {
        field.addEventListener('input', function() {
            this.classList.remove('border-red-500');
        });
    });
});
