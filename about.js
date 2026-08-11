document.addEventListener('DOMContentLoaded', () => {
  // Find the moon button: it's the .icon-btn whose SVG path starts with "M21 12.8"
  const iconButtons = document.querySelectorAll('.icon-btn');
  let toggleBtn = null;
  let themeSvg = null;

  iconButtons.forEach((btn) => {
    const path = btn.querySelector('path');
    if (path && path.getAttribute('d').startsWith('M21 12.8')) {
      toggleBtn = btn;
      themeSvg = btn.querySelector('svg');
    }
  });

  if (!toggleBtn) return;

  const sunPath = '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>';
  const moonPath = '<path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/>';

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    themeSvg.innerHTML = theme === 'dark' ? sunPath : moonPath;
    localStorage.setItem('theme', theme);
  }

  const saved = localStorage.getItem('theme');
  const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  applyTheme(saved || (systemPrefersDark ? 'dark' : 'light'));

  toggleBtn.addEventListener('click', () => {
    const current = document.documentElement.getAttribute('data-theme');
    applyTheme(current === 'dark' ? 'light' : 'dark');
  });
});


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