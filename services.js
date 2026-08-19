document.addEventListener('DOMContentLoaded', () => {

    // ==========================================
    // DARK / LIGHT MODE TOGGLE
    // ==========================================

    // Find the moon button from the existing icon buttons
    const iconButtons = document.querySelectorAll('.icon-btn');

    let toggleBtn = null;
    let themeSvg = null;

    iconButtons.forEach((btn) => {

        const path = btn.querySelector('path');

        if (
            path &&
            path.getAttribute('d') &&
            path.getAttribute('d').startsWith('M21 12.8')
        ) {
            toggleBtn = btn;
            themeSvg = btn.querySelector('svg');
        }

    });

    // Stop if the moon button cannot be found
    if (toggleBtn && themeSvg) {

        // Sun icon
        const sunPath = `
            <circle cx="12" cy="12" r="4"/>
            <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>
        `;

        // Moon icon
        const moonPath = `
            <path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/>
        `;


        // Apply selected theme
        function applyTheme(theme) {

            document.documentElement.setAttribute(
                'data-theme',
                theme
            );

            // Change moon icon to sun in dark mode
            themeSvg.innerHTML =
                theme === 'dark' ? sunPath : moonPath;

            // Remember user's choice
            localStorage.setItem('theme', theme);
        }


        // Check saved theme
        const savedTheme = localStorage.getItem('theme');

        // Default to the site's light cream theme (matching every other
        // page) unless the visitor has explicitly chosen dark mode before.
        applyTheme(savedTheme || 'light');


        // Toggle when moon/sun button is clicked
        toggleBtn.addEventListener('click', () => {

            const currentTheme =
                document.documentElement.getAttribute(
                    'data-theme'
                );

            applyTheme(
                currentTheme === 'dark'
                    ? 'light'
                    : 'dark'
            );

        });

    }


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

});