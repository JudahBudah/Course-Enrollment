/* Nav scrolled */

const nav = document.getElementById('mainNav');
    window.addEventListener('scroll', () => {
      nav.classList.toggle('scrolled', window.scrollY > 30);
    });


/* Nav Popover */

const popButton = document.getElementById('nav-popover');
const popMenu = document.getElementById('nav-popover-menu');

popButton.addEventListener("click", event => {
    popMenu.classList.toggle("clicked");
});

