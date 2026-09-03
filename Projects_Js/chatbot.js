/* =============================================================================
   chatbot.js — SITE-WIDE CHAT ASSISTANT WIDGET
   Andiswa Business Consultation website
   =============================================================================

   WHAT THIS FILE DOES
   --------------------
   This file builds the little chat bubble in the bottom-right corner that
   appears on every page (Home, Contact, Team) and everything inside it:
   the floating button, the message panel, the typing indicator, the
   rule-based reply engine, and the code that saves each message so the
   studio could review conversations later.

   It is loaded as a single shared <script> tag on every page instead of
   being copy-pasted into each page's HTML. That means there is exactly
   ONE chatbot implementation for the whole site — fix a bug or add a
   new topic here, and every page picks it up automatically, and every
   page's chat bubble is guaranteed to look and behave identically.

   HOW IT FITS THE SITE'S ARCHITECTURE
   -------------------------------------
       HTML + CSS + JavaScript   →   Backend / API   →   Azure SQL Database
       (this file, running            (ASP.NET Core        (ChatLeads table)
        in the visitor's browser)      Web API)

   Every message the visitor sends is handed to saveChatMessage() from
   api.js, which is responsible for actually getting it to the backend
   (see api.js for the full explanation of that layer, including why
   there's a local-storage fallback since no backend is deployed yet).
   This file never talks to storage or the network directly — it only
   ever calls functions from api.js. That separation is intentional:
   this file's only job is the CONVERSATION (what to show, what to say
   back); api.js's only job is PERSISTENCE (where the data ends up).

   IS THIS A "REAL" CHATBOT?
   ---------------------------
   It's a rule-based (a.k.a. "intent matching") chatbot, not an AI model.
   It looks at the words in each message, checks them against a list of
   topics it knows how to answer (pricing, hours, services, team,
   location, projects, greetings, thanks), and replies with a matching
   pre-written answer. That's a completely standard, genuinely
   functional design for a small business site — it doesn't require a
   paid AI API key, it never says anything the studio hasn't approved,
   and it always knows to point trickier questions at a human via the
   Contact page. See findBestReply() below for exactly how the matching
   works.
   ============================================================================= */


/* -----------------------------------------------------------------------
   1. CONVERSATION STATE
   ----------------------------------------------------------------------- */

// A random ID generated once per browser tab, so every message sent in
// this session can be grouped together when it's saved (see api.js's
// saveChatMessage). Doesn't identify the visitor personally — it just
// keeps one conversation's messages bundled together for the studio.
const chatSessionId = "session-" + Date.now() + "-" + Math.random().toString(36).slice(2, 8);

// Tracks whether the greeting message has already been shown, so it
// only appears the first time a visitor opens the chat, not every time.
let chatHasGreeted = false;


/* -----------------------------------------------------------------------
   2. REPLY ENGINE
   This is the "brain" of the chatbot: a list of topics it recognises,
   each with a set of trigger keywords and a matching pre-written reply.
   ----------------------------------------------------------------------- */

// Each entry: { keywords: [...], reply: "..." }
// findBestReply() below checks the visitor's message against every
// entry's keyword list and returns the reply for whichever topic
// matches the most keywords — so a message like "what are your prices
// for logo design" matches BOTH the pricing topic and the services
// topic, and the chatbot picks whichever one it matched more strongly.
const chatTopics = [
  {
    name: "pricing",
    keywords: ["price", "prices", "pricing", "cost", "costs", "quote", "quotation", "budget", "expensive", "cheap", "rate", "rates"],
    reply: "Pricing depends on the scope of the project, so there isn't a single fixed rate. The quickest way to get an accurate quote is our <a href=\"contact.php\">Contact page</a> — tell us a bit about what you need and someone from the team will follow up within a business day."
  },
  {
    name: "hours",
    keywords: ["hour", "hours", "open", "opening", "close", "closing", "time", "today", "weekend", "available"],
    reply: "We're generally open Monday to Friday, 08:00 – 17:00 SAST. You're welcome to leave a message any time on the <a href=\"contact.php\">Contact page</a> and we'll reply the next business day."
  },
  {
    name: "services",
    keywords: ["service", "services", "design", "branding", "brand", "logo", "web", "website", "print", "flyer", "poster", "social", "marketing", "strategy", "consult", "consultation", "consulting"],
    reply: "We work across brand identity, web &amp; digital design, print, marketing strategy, social content and business consultation. You can see examples on our <a href=\"projects.html\">Projects page</a>, or tell us what you're working on via the <a href=\"contact.php\">Contact page</a> and we'll point you to the right person."
  },
  {
    name: "team",
    keywords: ["team", "who", "founder", "staff", "people", "meet", "designer", "consultant"],
    reply: "You can meet the people behind the studio on our <a href=\"team.html\">Team page</a>."
  },
  {
    name: "location",
    keywords: ["where", "location", "address", "based", "office", "port elizabeth", "directions"],
    reply: "We're based at 2 Belmont Terrace, Port Elizabeth, 6001. Full contact details are on our <a href=\"contact.php\">Contact page</a>."
  },
  {
    name: "portfolio",
    keywords: ["portfolio", "project", "projects", "work", "examples", "case study", "clients", "testimonial", "testimonials", "review", "reviews"],
    reply: "Take a look at our <a href=\"projects.html\">Projects page</a> for recent work, or our <a href=\"testimonials.html\">Testimonials page</a> to hear from past clients."
  },
  {
    name: "greeting",
    keywords: ["hi", "hello", "hey", "howzit", "morning", "afternoon", "evening", "sawubona", "molo"],
    reply: "Hi there! 👋 How can I help — are you after pricing, our services, or something else?"
  },
  {
    name: "thanks",
    keywords: ["thank", "thanks", "cheers", "appreciate"],
    reply: "You're very welcome! Let us know if there's anything else we can help with."
  }
];

/**
 * Picks the best-matching reply for a visitor's message.
 *
 * Scores every topic by counting how many of its keywords appear
 * somewhere in the message, then returns the reply for the topic with
 * the highest score. If nothing scores above zero (the chatbot doesn't
 * recognise anything in the message), it falls back to a generic
 * response that still points the visitor toward a real person.
 *
 * @param {string} message  the raw text the visitor typed
 * @returns {string} an HTML string to display as the bot's reply
 */
function findBestReply(message) {
  const lowerMessage = message.toLowerCase();

  // PRIORITY OVERRIDE — pricing questions win outright.
  // Plain keyword-count scoring (below) has a blind spot: a message
  // like "how much for a logo design?" contains ONE pricing word
  // ("much"-adjacent "cost"/"price") but THREE services words ("logo",
  // "design"), so the broader "services" topic would out-score
  // pricing and the visitor's actual question — "what does this
  // cost?" — never gets answered. Since asking about cost is almost
  // always the visitor's real intent whenever it's present at all
  // (even alongside a specific service name), it's checked first and,
  // if matched, short-circuits the rest of the scoring below.
  const pricingTopic = chatTopics.find(topic => topic.name === "pricing");
  if (pricingTopic.keywords.some(keyword => lowerMessage.includes(keyword))) {
    return pricingTopic.reply;
  }

  let bestTopic = null;
  let bestScore = 0;

  for (const topic of chatTopics) {
    const score = topic.keywords.reduce((count, keyword) => {
      return lowerMessage.includes(keyword) ? count + 1 : count;
    }, 0);

    if (score > bestScore) {
      bestScore = score;
      bestTopic = topic;
    }
  }

  if (bestTopic) {
    return bestTopic.reply;
  }

  // Fallback for anything the chatbot doesn't recognise at all.
  return "Thanks for the message! For anything specific, the fastest way to reach the team is our <a href=\"contact.php\">Contact page</a> — we typically reply within one business day.";
}


/* -----------------------------------------------------------------------
   3. WIDGET CONSTRUCTION
   Builds the chat bubble's HTML and CSS and inserts them into the page.
   Doing this in JS (rather than pasting the same markup into three
   separate HTML files) is what keeps every page's chatbot identical.
   ----------------------------------------------------------------------- */


function injectChatbotStyles() {
  const style = document.createElement("style");
  style.textContent = `
    .chat-fab{
      position:fixed; right:24px; bottom:24px; z-index:1000;
      width:56px; height:56px; border-radius:50%; border:none; cursor:pointer;
      background:var(--tan); color:#ffffff;
      display:flex; align-items:center; justify-content:center;
      box-shadow:0 10px 26px rgba(0,0,0,0.25);
      transition:transform .25s var(--ease), background .25s ease;
    }
    .chat-fab:hover{ transform:translateY(-3px); background:var(--tan-bright); }
    .chat-fab svg{ width:24px; height:24px; }

    .chat-panel{
      position:fixed; right:24px; bottom:92px; z-index:1000;
      width:340px; max-width:calc(100vw - 32px);
      background:var(--espresso); color:var(--on-dark);
      border:1px solid var(--line); border-radius:16px;
      box-shadow:0 20px 50px rgba(0,0,0,0.35);
      opacity:0; transform:translateY(12px) scale(0.97);
      pointer-events:none;
      transition:opacity .25s var(--ease), transform .25s var(--ease);
      display:flex; flex-direction:column; overflow:hidden;
    }
    .chat-panel.open{ opacity:1; transform:translateY(0) scale(1); pointer-events:auto; }

    .chat-panel-head{
      display:flex; align-items:center; justify-content:space-between;
      padding:14px 16px; border-bottom:1px solid var(--line);
    }
    .chat-panel-head strong{ font-size:13.5px; color:var(--on-dark); }
    .chat-panel-head span{ display:block; font-size:11px; color:var(--stone); margin-top:2px; }
    .chat-panel-close{
      width:26px; height:26px; border:none; background:none; cursor:pointer;
      border-radius:50%; display:flex; align-items:center; justify-content:center;
      color:var(--stone); transition:background .2s ease, color .2s ease;
    }
    .chat-panel-close:hover{ background:rgba(255,255,255,0.08); color:var(--on-dark); }
    .chat-panel-close svg{ width:14px; height:14px; }

    .chat-messages{
      padding:14px 16px; display:flex; flex-direction:column; gap:10px;
      max-height:280px; overflow-y:auto;
    }
    .chat-msg{
      max-width:85%; padding:9px 12px; border-radius:12px;
      font-size:13px; line-height:1.5;
    }
    .chat-msg a{ color:inherit; text-decoration:underline; }
    .chat-msg.bot{
      align-self:flex-start;
      background:rgba(255,255,255,0.08);
      border-bottom-left-radius:4px;
    }
    .chat-msg.user{
      align-self:flex-end;
      background:var(--tan); color:#ffffff;
      border-bottom-right-radius:4px;
    }
    .chat-msg.typing{ display:flex; gap:4px; align-items:center; padding:11px 14px; }
    .chat-msg.typing span{
      width:6px; height:6px; border-radius:50%; background:var(--stone);
      animation:chatTypingBounce 1.1s infinite ease-in-out;
    }
    .chat-msg.typing span:nth-child(2){ animation-delay:.15s; }
    .chat-msg.typing span:nth-child(3){ animation-delay:.3s; }
    @keyframes chatTypingBounce{
      0%, 60%, 100%{ transform:translateY(0); opacity:.5; }
      30%{ transform:translateY(-4px); opacity:1; }
    }

    .chat-input-row{
      display:flex; align-items:center; gap:8px;
      padding:12px 14px; border-top:1px solid var(--line);
    }
    .chat-input-row input{
      flex:1; border:none; background:rgba(255,255,255,0.08);
      color:var(--on-dark); border-radius:10px; padding:10px 12px; font-size:13px;
    }
    .chat-input-row input::placeholder{ color:var(--stone); }
    .chat-input-row input:focus{ outline:2px solid var(--tan-bright); outline-offset:1px; }
    .chat-input-row button{
      width:36px; height:36px; border:none; border-radius:10px; cursor:pointer;
      background:var(--tan); color:#ffffff;
      display:flex; align-items:center; justify-content:center;
      transition:background .2s ease;
    }
    .chat-input-row button:hover{ background:var(--tan-bright); }
    .chat-input-row button svg{ width:15px; height:15px; }

    @media (max-width:480px){
      .chat-panel{ left:16px; right:16px; width:auto; }
    }
  `;
  document.head.appendChild(style);
}

/**
 * Injects the widget's HTML (floating button + message panel) at the
 * end of <body>, and returns references to the elements the rest of
 * this file needs to control.
 */
function injectChatbotMarkup() {
  const wrapper = document.createElement("div");
  wrapper.innerHTML = `
    <button class="chat-fab" id="chatFabBtn" aria-label="Open chat">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5 8.4 8.4 0 0 1-3.9-.94L3 20l1.05-4.1A8.4 8.4 0 0 1 3 11.5 8.5 8.5 0 0 1 11.5 3 8.5 8.5 0 0 1 21 11.5Z"/>
      </svg>
    </button>

    <div class="chat-panel" id="chatPanel">
      <div class="chat-panel-head">
        <div>
          <strong>Andiswa Support</strong>
          <span>Usually replies within a day</span>
        </div>
        <button class="chat-panel-close" id="chatCloseBtn" aria-label="Close chat">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="chat-messages" id="chatMessages"></div>
      <form class="chat-input-row" id="chatForm">
        <input type="text" id="chatInput" placeholder="Type a message…" autocomplete="off" required>
        <button type="submit" aria-label="Send">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 2-7 20-4-9-9-4 20-7Z"/></svg>
        </button>
      </form>
    </div>
  `;
  document.body.appendChild(wrapper);
}


/* -----------------------------------------------------------------------
   4. WIDGET BEHAVIOUR
   Open/close, sending messages, showing the typing indicator, and
   handing each message off to api.js to be saved.
   ----------------------------------------------------------------------- */

/**
 * Appends one message bubble to the chat window and scrolls to it.
 * @param {string} html  message content (may include links, so this
 *                        intentionally uses innerHTML rather than
 *                        textContent — every string passed in comes
 *                        from this file's own fixed replies or from
 *                        the visitor's own typed text, never from an
 *                        untrusted third party).
 * @param {"user"|"bot"} from  who sent it, for styling.
 */
function addChatMessage(html, from) {
  const messagesEl = document.getElementById("chatMessages");
  const bubble = document.createElement("div");
  bubble.className = "chat-msg " + from;
  bubble.innerHTML = html;
  messagesEl.appendChild(bubble);
  messagesEl.scrollTop = messagesEl.scrollHeight;
}

/**
 * Shows the animated "..." typing indicator while a reply is "on its
 * way" — this is purely a UX touch (the reply itself is instant) but
 * it makes the exchange feel like a real conversation rather than a
 * lookup table, and gives the eye a moment to register that the
 * visitor's own message was received before the reply appears.
 * @returns {HTMLElement} the indicator element, so it can be removed
 *          once the real reply is ready to show.
 */
function showTypingIndicator() {
  const messagesEl = document.getElementById("chatMessages");
  const bubble = document.createElement("div");
  bubble.className = "chat-msg bot typing";
  bubble.innerHTML = "<span></span><span></span><span></span>";
  messagesEl.appendChild(bubble);
  messagesEl.scrollTop = messagesEl.scrollHeight;
  return bubble;
}

/** Opens the chat panel, showing the one-time greeting on first open. */
function openChat() {
  document.getElementById("chatPanel").classList.add("open");
  if (!chatHasGreeted) {
    chatHasGreeted = true;
    addChatMessage(
      "Hi! 👋 I'm the Andiswa Business Consultation chat assistant. Ask me about our services, pricing, hours or team — or leave a message and the team will follow up.",
      "bot"
    );
  }
  document.getElementById("chatInput").focus();
}

/** Closes the chat panel (the conversation stays intact if reopened). */
function closeChat() {
  document.getElementById("chatPanel").classList.remove("open");
}

/**
 * Handles a visitor sending a message: shows it, saves it, shows the
 * typing indicator, works out a reply, then shows and saves that too.
 * @param {string} messageText
 */
async function handleChatSend(messageText) {
  addChatMessage(messageText, "user");

  // Hand the visitor's message to the data layer — in production this
  // is what lets the studio review chat conversations from the admin
  // dashboard (see api.js's saveChatMessage for the full explanation).
  saveChatMessage({ message: messageText, sender: "user", sessionId: chatSessionId });

  const typingBubble = showTypingIndicator();

  // Small delay so the reply feels like it was actually "typed" rather
  // than appearing instantly — purely cosmetic, doesn't affect logic.
  await new Promise(resolve => setTimeout(resolve, 700));

  typingBubble.remove();

  const reply = findBestReply(messageText);
  addChatMessage(reply, "bot");
  saveChatMessage({ message: reply, sender: "bot", sessionId: chatSessionId });
}


/* -----------------------------------------------------------------------
   5. INITIALISATION
   Runs once the page has loaded: builds the widget and wires up events.
   ----------------------------------------------------------------------- */

function initChatbot() {
  injectChatbotStyles();
  injectChatbotMarkup();

  const fabBtn   = document.getElementById("chatFabBtn");
  const closeBtn = document.getElementById("chatCloseBtn");
  const form     = document.getElementById("chatForm");
  const input    = document.getElementById("chatInput");

  fabBtn.addEventListener("click", () => {
    document.getElementById("chatPanel").classList.contains("open") ? closeChat() : openChat();
  });

  closeBtn.addEventListener("click", closeChat);

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const value = input.value.trim();
    if (!value) return;
    input.value = "";
    handleChatSend(value);
  });
}

// Wait for the DOM to be ready before building the widget, since it
// needs to append elements to <body>.
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initChatbot);
} else {
  initChatbot();
}
