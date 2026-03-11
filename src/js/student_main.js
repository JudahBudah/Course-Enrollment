/* Mobile Nav */

const mobile_burger = document.getElementById("mobile-burger");
const mobile_menu = document.getElementById("mobile-menu");
const trans_bars = document.getElementById("trans-bars");
const trans_xmark = document.getElementById("trans-xmark");

mobile_burger.addEventListener("click", event => {
    if (mobile_menu.style.opacity === "1") {
        mobile_menu.style.opacity = "0";
        mobile_menu.style.pointerEvents = "none";
        mobile_menu.style.top = "50px";
        trans_xmark.style.display = "none";
        trans_bars.style.display = "inline-block";
    } else {
        mobile_menu.style.opacity = "1";
        mobile_menu.style.pointerEvents = "initial";
        mobile_menu.style.top = "60px";
        trans_xmark.style.display = "inline-block";
        trans_bars.style.display = "none";
    }
});


/* Navbar */

var lastScrollTop = 0;
const navbar = document.querySelector("nav");

window.addEventListener("scroll", function() {
    var scrollTop = window.pageYOffset || document.documentElement.scrollTop;

    if (scrollTop > lastScrollTop) {
        navbar.style.top = "-60px";
    } else {
        navbar.style.top = "0";
    }
    lastScrollTop = scrollTop;
});


/* Course Dropdown */

const course_dropdown = document.getElementById("course-dropdown");
const course_dropdown_menu = document.getElementById("course-dropdown-menu");
const course_dropdown_icon = document.getElementById("course-dropdown-icon");

course_dropdown.addEventListener("click", event => {
    if (course_dropdown_menu.style.opacity === "1") {
        course_dropdown_menu.style.opacity = "0";
        course_dropdown_menu.style.pointerEvents = "none";
        course_dropdown_menu.style.top = "30px";
        course_dropdown_icon.style.transform = "rotate(0deg)";
    } 
    else {
        course_dropdown_menu.style.opacity = "1";
        course_dropdown_menu.style.pointerEvents = "initial";
        course_dropdown_menu.style.top = "40px";
        course_dropdown_icon.style.transform = "rotate(-180deg)";
    }
})


/* Account Menu */

const account_button = document.getElementById("account-button");
const account_menu = document.getElementById("account-menu");

/* Hover Option

account_button.addEventListener("mouseover", event => {
    
    if(account_menu.style.opacity = "0") {
        account_menu.style.opacity = "1";
        account_menu.style.pointerEvents = "initial";
    }
}) 

account_button.addEventListener("mouseout", event => {
    if(account_menu.style.opacity = "1") {
        account_menu.style.opacity = "0";
        account_menu.style.pointerEvents = "none";
    }
})

*/

account_button.addEventListener("click", event => {
    if (account_menu.style.opacity === "1") {
        account_menu.style.opacity = "0";
        account_menu.style.pointerEvents = "none";
    } 
    else {
        account_menu.style.opacity = "1";
        account_menu.style.pointerEvents = "initial";
    }
})
