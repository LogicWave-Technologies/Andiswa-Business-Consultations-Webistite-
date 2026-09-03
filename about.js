// NEWSLETTER FORM VALIDATION

const newsletterForm = document.querySelector(".newsletter-form");

if (newsletterForm) {
    newsletterForm.addEventListener("submit", function (event) {

        const emailInput = newsletterForm.querySelector('input[type="email"]');
        const email = emailInput.value.trim();

        // Email validation pattern
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        // Check if email is empty
        if (email === "") {
            event.preventDefault();
            alert("Please enter your email address.");
            emailInput.focus();
            return;
        }

        // Check if email is valid
        if (!emailPattern.test(email)) {
            event.preventDefault();
            alert("Please enter a valid email address.");
            emailInput.focus();
            return;
        }

        // Valid email
        alert("Thank you for subscribing!");
    });
}