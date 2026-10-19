/*=========================
    TESTIMONIALS DATA
=========================*/

function placeholderAvatar(bg, initials){
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120">
        <rect width="100%" height="100%" fill="${bg}"/>
        <text x="50%" y="54%" font-family="Poppins, sans-serif" font-size="42"
              fill="#ffffff" text-anchor="middle" dominant-baseline="middle">${initials}</text>
    </svg>`;
    return "data:image/svg+xml;utf8," + encodeURIComponent(svg);
}

const testimonials = [
    {
        quote:"They transformed our brand from forgettable to unforgettable. The logo they designed is now recognised across Johannesburg. Truly world-class creative work at a price that made sense for our stage.",
        rating:5,
        name:"Naledi Khumalo",
        role:"CEO, Bloom Skincare",
        avatar:placeholderAvatar("#5B6472", "NK")
    },
    {
        quote:"From our first call to the final handover, the process felt effortless. They understood exactly what our brand needed to say and said it better than we could have imagined.",
        rating:5,
        name:"Thabo Mokoena",
        role:"Founder, Vukauzenzele Services",
        avatar:placeholderAvatar("#3E6690", "TM")
    },
    {
        quote:"Professional, fast and genuinely creative. Our flyer designs increased foot traffic to our pop-up within the first week. We keep coming back for every campaign.",
        rating:5,
        name:"Lerato Molea",
        role:"Author & Entrepreneur",
        avatar:placeholderAvatar("#8891A0", "LM")
    },
    {
        quote:"A team that actually listens. They took a vague idea and turned it into a business card and identity that gets compliments at every meeting.",
        rating:5,
        name:"Sphokuhle Dlamini",
        role:"Director, NACWO",
        avatar:placeholderAvatar("#1B2A41", "SD")
    }
];


/*=========================
    ELEMENTS
=========================*/

const quoteEl = document.getElementById("testimonialQuote");
const ratingEl = document.getElementById("testimonialRating");
const avatarEl = document.getElementById("testimonialAvatar");
const nameEl = document.getElementById("testimonialName");
const roleEl = document.getElementById("testimonialRole");
const dotsWrap = document.getElementById("testimonialDots");
const prevBtn = document.getElementById("testimonialPrev");
const nextBtn = document.getElementById("testimonialNext");
const autoplayBtn = document.getElementById("autoplayToggle");
const autoplayIcon = autoplayBtn.querySelector("i");

let current = 0;
let isPlaying = true;
let autoplayTimer = null;

const AUTOPLAY_DELAY = 6000;


/*=========================
    RENDER
=========================*/

function buildDots(){

    dotsWrap.innerHTML = "";

    testimonials.forEach((_, index) => {

        const dot = document.createElement("button");
        dot.className = "dot" + (index === current ? " active" : "");
        dot.setAttribute("aria-label", "Go to testimonial " + (index + 1));
        dot.addEventListener("click", () => goTo(index));

        dotsWrap.appendChild(dot);

    });

}

function buildStars(rating){

    let html = "";

    for(let i = 1; i <= 5; i++){

        html += i <= rating
            ? '<i class="fa-solid fa-star"></i>'
            : '<i class="fa-solid fa-star inactive"></i>';

    }

    ratingEl.innerHTML = html;

}

function render(){

    const item = testimonials[current];

    quoteEl.style.opacity = 0;

    setTimeout(() => {

        quoteEl.textContent = item.quote;
        buildStars(item.rating);
        avatarEl.src = item.avatar;
        avatarEl.alt = item.name;
        nameEl.textContent = item.name;
        roleEl.textContent = item.role;

        quoteEl.style.opacity = 1;

    }, 150);

    [...dotsWrap.children].forEach((dot, index) => {
        dot.classList.toggle("active", index === current);
    });

}


/*=========================
    NAVIGATION
=========================*/

function goTo(index){

    current = (index + testimonials.length) % testimonials.length;
    render();
    restartAutoplay();

}

function next(){
    goTo(current + 1);
}

function prev(){
    goTo(current - 1);
}


/*=========================
    AUTOPLAY
=========================*/

function startAutoplay(){

    autoplayTimer = setInterval(next, AUTOPLAY_DELAY);
    isPlaying = true;
    autoplayIcon.className = "fa-solid fa-pause";
    autoplayBtn.setAttribute("aria-label", "Pause autoplay");

}

function stopAutoplay(){

    clearInterval(autoplayTimer);
    isPlaying = false;
    autoplayIcon.className = "fa-solid fa-play";
    autoplayBtn.setAttribute("aria-label", "Resume autoplay");

}

function restartAutoplay(){

    if(isPlaying){
        clearInterval(autoplayTimer);
        startAutoplay();
    }

}

function toggleAutoplay(){
    isPlaying ? stopAutoplay() : startAutoplay();
}


/*=========================
    EVENTS + INIT
=========================*/

prevBtn.addEventListener("click", prev);
nextBtn.addEventListener("click", next);
autoplayBtn.addEventListener("click", toggleAutoplay);

buildDots();
render();
startAutoplay();