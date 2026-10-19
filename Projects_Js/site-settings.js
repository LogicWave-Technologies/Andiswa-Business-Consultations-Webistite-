/* =============================================================================
   site-settings.js  -  shows the studio details from the shared database
   -----------------------------------------------------------------------------
   The phone number, email, address, hours, tagline and social links live in
   the database's "settings" table. The admin edits them in
   Admin > Website & App, and the mobile app reads the same rows.

   HOW IT WORKS
     1. Any element with  data-setting="phone"  (or email, address, ...) gets
        that value as its text.
     2. Any link with  data-setting-href="instagram_url"  gets that address.
     3. data-setting-format="lines" puts a line break after the first comma,
        e.g. "2 Belmont Terrace, Port Elizabeth, 6001" ->
             2 Belmont Terrace,
             Port Elizabeth, 6001

   The text already written in the HTML stays as a fallback, so the page
   still looks right if PHP or the database is not running (for example when
   you open the .html file straight from your folder).
   ============================================================================= */
(function () {
  "use strict";

  // Puts "text" inside "el" safely (textContent never runs any HTML).
  function fill(el, text) {
    if (el.getAttribute("data-setting-format") === "lines") {
      var cut = text.indexOf(", ");
      if (cut > -1) {
        el.textContent = "";
        el.appendChild(document.createTextNode(text.slice(0, cut + 1)));
        el.appendChild(document.createElement("br"));
        el.appendChild(document.createTextNode(text.slice(cut + 2)));
        return;
      }
    }
    el.textContent = text;
  }

  // Only accept normal web links for the social buttons.
  function safeUrl(url) {
    return /^https?:\/\//i.test(url) ? url : "";
  }

  function apply(settings) {
    document.querySelectorAll("[data-setting]").forEach(function (el) {
      var value = settings[el.getAttribute("data-setting")];
      if (typeof value === "string" && value.trim() !== "") fill(el, value.trim());
    });

    document.querySelectorAll("[data-setting-href]").forEach(function (link) {
      var url = safeUrl(settings[link.getAttribute("data-setting-href")] || "");
      if (url) {
        link.href = url;
        link.target = "_blank";
        link.rel = "noopener";
      }
    });

    // Let other scripts (for example the chatbot) use the same details.
    window.STUDIO_SETTINGS = settings;
    document.dispatchEvent(new CustomEvent("studio-settings", { detail: settings }));
  }

  fetch("site_settings.php", { headers: { Accept: "application/json" }, cache: "no-store" })
    .then(function (res) {
      var type = res.headers.get("Content-Type") || "";
      if (!res.ok || type.indexOf("application/json") === -1) throw new Error("Settings not available");
      return res.json();
    })
    .then(apply)
    .catch(function () {
      /* No PHP / database: keep the text written in the HTML. */
    });
})();
