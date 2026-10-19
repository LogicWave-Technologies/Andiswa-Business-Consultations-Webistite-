/* Shared navbar behaviour — hamburger toggle for the mobile menu.
   Used by every page so the navbar doesn't just look the same, it
   behaves the same too. */
document.addEventListener('DOMContentLoaded', function () {
  var toggle = document.getElementById('menuToggle');
  var links = document.getElementById('navLinks');
  if (!toggle || !links) return;

  toggle.addEventListener('click', function () {
    var isOpen = links.classList.toggle('active');
    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });

  // Close the mobile menu automatically once a link is tapped.
  links.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', function () {
      links.classList.remove('active');
      toggle.setAttribute('aria-expanded', 'false');
    });
  });
});
