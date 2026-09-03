/* =============================================================================
   api.js — DATA ACCESS LAYER
   Andiswa Business Consultation website
   =============================================================================

   ARCHITECTURE (matches the 3-tier design used across this project):

       HTML + CSS + JavaScript   →   Backend / API   →   Azure SQL Database
       (the browser — this file)     (ASP.NET Core        (Inquiries,
                                       Web API, hosted       TeamMembers,
                                       on Azure App          ChatLeads,
                                       Service)               Settings tables)

   WHY THIS FILE EXISTS
   ---------------------
   Every page on the site (Home, Contact, Team) needs to read and write the
   same pieces of data — enquiries from the contact form, the team roster,
   studio settings, and chatbot leads. Instead of each page talking to
   storage directly (which is how earlier versions of this site worked),
   every page now calls a function from THIS file, and this file is the
   only place that knows how the data is actually fetched or saved.

   That's the same reason a real ASP.NET Core Web API sits in front of an
   Azure SQL Database in production: the browser never talks to the
   database directly. It calls an API endpoint, and the API is the only
   thing with a connection string to the database. This file plays that
   same "single point of contact" role on the front-end side, one level
   up — every page calls apiRequest()/get()/set() from here instead of
   reaching into storage itself.

   HOW A REQUEST FLOWS IN A REAL DEPLOYMENT
   -----------------------------------------
     1. A page (e.g. contact.php) calls submitInquiry({...}) below.
     2. submitInquiry() sends an HTTPS POST to
        `${API_BASE_URL}/inquiries` with the form data as JSON.
     3. The ASP.NET Core Web API receives the request, validates it,
        and uses Entity Framework Core to INSERT a row into the
        `Inquiries` table in Azure SQL Database.
     4. The API responds with the saved record (including the new row's
        ID), and this file resolves the Promise so the calling page can
        show a success message.

   WHY THERE'S A "DEMO FALLBACK" IN EVERY FUNCTION
   --------------------------------------------------
   This project doesn't currently have that backend deployed — there is
   no live Azure App Service or Azure SQL Database behind it. So every
   function below:
     (a) ALWAYS tries the real network call first, exactly as it would
         work against a live API, and
     (b) if that call fails (because, right now, there's nothing running
         at API_BASE_URL) it falls back to the browser's own storage
         (`window.storage`, provided by the Claude Artifacts runtime)
         so the site still works end-to-end for demos and grading.
   Every fallback is clearly labelled "DEMO FALLBACK" below. Swapping in
   a real backend later only means changing API_BASE_URL and deleting
   the fallback branch — none of the pages that call these functions
   would need to change at all. That's the whole point of having this
   file in the first place.
   ============================================================================= */


/* -----------------------------------------------------------------------
   1. CONFIGURATION
   ----------------------------------------------------------------------- */

// Base URL of the backend API. In a real deployment this would point at
// the ASP.NET Core Web API hosted on Azure, e.g.:
//   "https://abc-consulting-api.azurewebsites.net/api"
// During local development against a backend running on your own machine
// it would instead be something like "https://localhost:5001/api".
// It is intentionally left pointing at a placeholder here since no
// backend is deployed for this static front-end demo.
const API_BASE_URL = "https://abc-consulting-api.azurewebsites.net/api";

// How long (in milliseconds) to wait for the API before giving up and
// falling back to local storage. Keeps the site feeling responsive even
// if a real backend is slow to respond or unreachable.
const API_TIMEOUT_MS = 4000;

// Local storage keys — kept identical to the keys the admin panel on
// index.html already uses, so contact form submissions and team-page
// data line up with exactly the same records the admin dashboard reads.
// Named API_STORAGE_KEYS (rather than plain STORAGE_KEYS) specifically
// because index.html's own admin-panel script declares its own
// STORAGE_KEYS constant — since both scripts run in the same page's
// global scope, two top-level `const STORAGE_KEYS` declarations would
// clash and throw a SyntaxError, breaking the whole page.
const API_STORAGE_KEYS = {
  inquiries: "abc-inquiries",
  team: "abc-team",
  settings: "abc-settings",
  chatLeads: "abc-chat-leads"
};


/* -----------------------------------------------------------------------
   2. LOW-LEVEL HELPERS
   ----------------------------------------------------------------------- */

/**
 * Sends one HTTP request to the backend API.
 *
 * This is the ONLY function in the whole site that would ever call
 * fetch() against the real backend — every other function below is
 * written in terms of this one, so if the API's base URL, headers, or
 * auth scheme ever change, there's exactly one place to update.
 *
 * @param {string} path    API path, e.g. "/inquiries" (appended to API_BASE_URL)
 * @param {object} options fetch() options (method, body, etc.)
 * @returns {Promise<any>} the parsed JSON response body
 * @throws  if the network request fails or times out — callers are
 *          expected to catch this and fall back to local storage.
 */
async function apiRequest(path, options = {}) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), API_TIMEOUT_MS);

  try {
    const response = await fetch(API_BASE_URL + path, {
      headers: { "Content-Type": "application/json" },
      signal: controller.signal,
      ...options
    });

    if (!response.ok) {
      // The API responded, but with an error status (e.g. 500 from a
      // database problem, or 400 from a validation failure).
      throw new Error(`API request failed: ${response.status} ${response.statusText}`);
    }

    return await response.json();
  } finally {
    clearTimeout(timeout);
  }
}

/**
 * Reads a named record from the browser's own storage.
 * DEMO FALLBACK helper — stands in for the database when there's no
 * backend running. Never called directly by pages; only used inside
 * the functions below when apiRequest() fails.
 */
async function readLocal(storageKey) {
  try {
    const result = await window.storage.get(storageKey, false);
    return result ? JSON.parse(result.value) : null;
  } catch (err) {
    return null; // nothing saved yet — first visit
  }
}

/**
 * Writes a named record into the browser's own storage.
 * DEMO FALLBACK helper — see readLocal() above.
 */
async function writeLocal(storageKey, value) {
  try {
    await window.storage.set(storageKey, JSON.stringify(value), false);
  } catch (err) {
    console.warn("Local demo storage unavailable — data will not persist:", err);
  }
}


/* -----------------------------------------------------------------------
   3. PUBLIC DATA FUNCTIONS
   Every page on the site should call these instead of touching
   fetch(), window.storage, or STORAGE_KEYS directly.
   ----------------------------------------------------------------------- */

/**
 * CONTACT FORM → Inquiries table
 * Submits a new enquiry from the Contact page (or the chatbot's lead
 * capture) so it shows up in the admin dashboard's Inquiries list.
 *
 *   Real backend call : POST {API_BASE_URL}/inquiries
 *   Azure SQL table    : Inquiries (Id, Name, Service, Status, Date, Message)
 *
 * @param {{name:string, service:string, message?:string}} entry
 */
async function submitInquiry(entry) {
  const record = {
    id: Date.now(),               // stand-in for the database-generated Id
    status: "New",
    date: new Date().toISOString().slice(0, 10),
    ...entry
  };

  try {
    // Real path: hand the enquiry to the backend, which inserts it into
    // Azure SQL Database and returns the saved row (with its real Id).
    return await apiRequest("/inquiries", {
      method: "POST",
      body: JSON.stringify(record)
    });
  } catch (err) {
    // DEMO FALLBACK: no backend is deployed, so save it into the same
    // local list the admin panel already reads from.
    console.warn("No live backend — saving inquiry to local demo storage instead:", err);
    const list = (await readLocal(API_STORAGE_KEYS.inquiries)) || [];
    list.unshift(record);
    await writeLocal(API_STORAGE_KEYS.inquiries, list);
    return record;
  }
}

/**
 * TEAM PAGE → TeamMembers table
 * Fetches the current team roster to display on team.html.
 *
 *   Real backend call : GET {API_BASE_URL}/team
 *   Azure SQL table    : TeamMembers (Id, Name, Role, Bio, PhotoUrl)
 *
 * @returns {Promise<Array|null>} the team list, or null if nothing is
 *          saved yet (the calling page should fall back to its own
 *          built-in default roster in that case).
 */
async function getTeamMembers() {
  try {
    // Real path: ask the backend for the roster straight out of the
    // TeamMembers table in Azure SQL Database.
    return await apiRequest("/team", { method: "GET" });
  } catch (err) {
    // DEMO FALLBACK: read whatever the admin panel last saved locally.
    console.warn("No live backend — reading team roster from local demo storage:", err);
    return await readLocal(API_STORAGE_KEYS.team);
  }
}

/**
 * STUDIO SETTINGS → Settings table
 * Fetches the studio's contact details (email, phone, address, hours)
 * so they can be shown on the Contact page without hard-coding them.
 *
 *   Real backend call : GET {API_BASE_URL}/settings
 *   Azure SQL table    : Settings (Id, Email, Phone, Address, Hours, StudioName)
 */
async function getStudioSettings() {
  try {
    return await apiRequest("/settings", { method: "GET" });
  } catch (err) {
    console.warn("No live backend — reading settings from local demo storage:", err);
    return await readLocal(API_STORAGE_KEYS.settings);
  }
}

/**
 * CHATBOT → ChatLeads table
 * Every time a visitor sends the chatbot a message, this saves a
 * transcript entry — so, in production, the studio could see every
 * chat conversation from the admin dashboard, not just the messages
 * sent through the contact form.
 *
 *   Real backend call : POST {API_BASE_URL}/chat-leads
 *   Azure SQL table    : ChatLeads (Id, Message, Sender, Timestamp, SessionId)
 *
 * @param {{message:string, sender:"user"|"bot", sessionId:string}} entry
 */
async function saveChatMessage(entry) {
  const record = {
    id: Date.now() + Math.random(), // avoids collisions with rapid messages
    timestamp: new Date().toISOString(),
    ...entry
  };

  try {
    // Real path: log the message against the current chat session in
    // Azure SQL Database, so a staff member could review it later.
    return await apiRequest("/chat-leads", {
      method: "POST",
      body: JSON.stringify(record)
    });
  } catch (err) {
    // DEMO FALLBACK: keep a running transcript in local storage instead.
    const list = (await readLocal(API_STORAGE_KEYS.chatLeads)) || [];
    list.push(record);
    await writeLocal(API_STORAGE_KEYS.chatLeads, list);
    return record;
  }
}
