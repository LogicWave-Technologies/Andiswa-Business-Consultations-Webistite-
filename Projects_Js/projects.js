/*=========================
    PROJECT FILTER
=========================*/

const filterButtons = document.querySelectorAll(".filter-btn");
const projects = document.querySelectorAll(".project-card");
const projectCount = document.getElementById("projectCount");

filterButtons.forEach(button => {

    button.addEventListener("click", () => {

        // Active Button
        filterButtons.forEach(btn => btn.classList.remove("active"));
        button.classList.add("active");

        const filter = button.dataset.filter;

        let visible = 0;

        projects.forEach(project => {

            if(filter === "all"){

                project.style.display = "block";
                visible++;

            }

            else if(project.classList.contains(filter)){

                project.style.display = "block";
                visible++;

            }

            else{

                project.style.display = "none";

            }

        });

        projectCount.textContent = visible;

    });

});


/*=========================
        SEARCH
=========================*/

const searchInput = document.getElementById("searchInput");

searchInput.addEventListener("keyup", function(){

    const search = this.value.toLowerCase();

    let visible = 0;

    projects.forEach(project=>{

        const title = project.querySelector("h3").textContent.toLowerCase();

        if(title.includes(search)){

            project.style.display="block";
            visible++;

        }

        else{

            project.style.display="none";

        }

    });

    projectCount.textContent = visible;

});


/*=========================
      PROJECT MODAL
=========================*/

const modal = document.getElementById("projectModal");

const modalImage = document.getElementById("modalImage");

const modalTitle = document.getElementById("modalTitle");

const modalCategory = document.getElementById("modalCategory");

const modalDescription = document.getElementById("modalDescription");

const closeModal = document.querySelector(".close-modal");

const viewButtons = document.querySelectorAll(".view-btn");

viewButtons.forEach(button=>{

    button.addEventListener("click",(e)=>{

        e.preventDefault();

        const card = button.closest(".project-card");

        const image = card.querySelector("img").src;

        const title = card.querySelector("h3").textContent;

        const category = card.querySelector("span").textContent;

        const description = card.querySelector("p").textContent;

        modal.style.display="flex";

        modalImage.src=image;

        modalTitle.textContent=title;

        modalCategory.textContent=category;

        modalDescription.textContent=description;

    });

});

closeModal.addEventListener("click",()=>{

    modal.style.display="none";

});

window.addEventListener("click",(e)=>{

    if(e.target===modal){

        modal.style.display="none";

    }

});

document.addEventListener("keydown",(e)=>{

    if(e.key==="Escape"){

        modal.style.display="none";

    }

});


/*=========================
        LOAD MORE
=========================*/

const loadMoreBtn = document.getElementById("loadMore");

let currentItems = 8;

projects.forEach((project,index)=>{

    if(index >= currentItems){

        project.style.display="none";

    }

});

loadMoreBtn.addEventListener("click",()=>{

    currentItems +=4;

    projects.forEach((project,index)=>{

        if(index < currentItems){

            project.style.display="block";

        }

    });

    if(currentItems >= projects.length){

        loadMoreBtn.style.display="none";

    }

});


/*=========================
      NAVBAR SHADOW
=========================*/

window.addEventListener("scroll",()=>{

    const header=document.querySelector("header");

    if(window.scrollY>50){

        header.style.boxShadow="0 8px 25px rgba(0,0,0,.15)";

    }

    else{

        header.style.boxShadow="0 3px 15px rgba(0,0,0,.05)";

    }

});


/*=========================
     SCROLL TO TOP
=========================*/

const topButton = document.querySelector(".back-top");

topButton.addEventListener("click",(e)=>{

    e.preventDefault();

    window.scrollTo({

        top:0,

        behavior:"smooth"

    });

});