// ==========================================
    // NEWSLETTER VALIDATION
    // ==========================================

    const newsletterForm =
        document.querySelector('.newsletter-form');

    if (newsletterForm) {

        newsletterForm.addEventListener(
            'submit',
            function (event) {

                event.preventDefault();

                const emailInput =
                    newsletterForm.querySelector(
                        'input[type="email"]'
                    );

                const email =
                    emailInput.value.trim();

                const emailPattern =
                    /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


                // Empty email
                if (email === '') {

                    alert(
                        'Please enter your email address.'
                    );

                    emailInput.focus();

                    return;
                }


                // Invalid email
                if (!emailPattern.test(email)) {

                    alert(
                        'Please enter a valid email address.'
                    );

                    emailInput.focus();

                    return;
                }


                // Valid email
                alert(
                    'Thank you for subscribing!'
                );

                newsletterForm.reset();

            }
        );

    }

