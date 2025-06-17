var sidenav = document.getElementById("mySidenav");
var openBtn = document.getElementById("openBtn");
var closeBtn = document.getElementById("closeBtn");

openBtn.onclick = openNav;
closeBtn.onclick = closeNav;

/* Fonction pour ouvrir le sidenav */
function openNav() {
    sidenav.classList.add("active");
}

/* Fonction pour fermer le sidenav */
function closeNav() {
    sidenav.classList.remove("active");
}
