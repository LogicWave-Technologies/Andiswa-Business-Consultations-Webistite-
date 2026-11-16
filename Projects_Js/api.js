/* Shared browser helpers for the website's same-origin PHP endpoints.
   PHP handles API-backed quote and account flows server-side. Studio settings
   and chatbot logging remain on their existing PHP/MySQL endpoints. */


/* -----------------------------------------------------------------------
   1. CONFIGURATION
   ----------------------------------------------------------------------- */

// Bound requests to the same-origin PHP endpoints; PHP calls the remote API
// server-side where it can keep authentication tokens private.
const API_TIMEOUT_MS = 4000;

const PHP_BASE_URL = "";

const API_STORAGE_KEYS = {
  chatLeads: "abc-chat-leads"
};


/* -----------------------------------------------------------------------
   2. LOW-LEVEL HELPERS
   ----------------------------------------------------------------------- */

/**
 * Sends one HTTP request to a same-origin PHP endpoint.
 *
 * @param {string} path    Endpoint path, e.g. "site_settings.php"
 * @param {object} options fetch() options (method, body, etc.)
 * @returns {Promise<any>} the parsed JSON response body
 * @throws if the endpoint fails or times out.
 */
async function apiRequest(path, options = {}, baseUrl = PHP_BASE_URL) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), API_TIMEOUT_MS);

  try {
    const response = await fetch(baseUrl + path, {
      headers: { "Content-Type": "application/json" },
      signal: controller.signal,
      ...options
    });

    if (!response.ok) {
      // The PHP endpoint responded with an error status.
      throw new Error(`API request failed: ${response.status} ${response.statusText}`);
    }

    return await response.json();
  } finally {
    clearTimeout(timeout);
  }
}

/** Reads a named record from browser storage for the chatbot's demo fallback. */
async function readLocal(storageKey) {
  try {
    const value = window.localStorage.getItem(storageKey);
    return value ? JSON.parse(value) : null;
  } catch (err) {
    return null; // nothing saved yet — first visit
  }
}

/** Writes a named record into browser storage for the chatbot's demo fallback. */
async function writeLocal(storageKey, value) {
  try {
    window.localStorage.setItem(storageKey, JSON.stringify(value));
  } catch (err) {
    console.warn("Local demo storage unavailable — data will not persist:", err);
  }
}


/* -----------------------------------------------------------------------
   3. PUBLIC DATA FUNCTIONS
   Helpers for the small set of browser-side data flows.
   ----------------------------------------------------------------------- */

/**
 * The API does not expose a team-roster route, so team.html uses its built-in roster.
 */
async function getTeamMembers() {
  return null;
}

/**
 * STUDIO SETTINGS → Settings table
 * Fetches the studio's contact details (email, phone, address, hours)
 * so they can be shown on the Contact page without hard-coding them.
 *
 * The PHP endpoint reads the existing settings table.
 */
async function getStudioSettings() {
  return await apiRequest("site_settings.php", { method: "GET" }, PHP_BASE_URL);
}

/**
 * CHATBOT → ChatLeads table
 * Every time a visitor sends the chatbot a message, this saves a
 * transcript entry — so, in production, the studio could see every
 * chat conversation from the admin dashboard, not just the messages
 * sent through the contact form.
 *
 * Chat persistence remains on its existing PHP/MySQL endpoint.
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
    // Chat messages still go through their existing PHP/MySQL endpoint.
    return await apiRequest("chat_log.php", {
      method: "POST",
      body: JSON.stringify({ message: entry.message, sender: entry.sender, sessionId: entry.sessionId })
    }, PHP_BASE_URL);
  } catch (err) {
    // DEMO FALLBACK: keep a running transcript in local storage instead.
    const list = (await readLocal(API_STORAGE_KEYS.chatLeads)) || [];
    list.push(record);
    await writeLocal(API_STORAGE_KEYS.chatLeads, list);
    return record;
  }
}
