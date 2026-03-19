/* Academic Dropdown */

const acad_dropdown = document.getElementById("acad-dropdown");
const acad_dropdown_menu = document.getElementById("acad-dropdown-menu");

acad_dropdown.addEventListener("click", event => {
    acad_dropdown_menu.classList.toggle("open");
    acad_dropdown.classList.toggle("open");
});


