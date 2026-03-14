/* Calendar */

const MONTHS = [
    'January','February','March','April','May','June',
    'July','August','September','October','November','December'
];

const today      = new Date();
let viewYear     = today.getFullYear();
let viewMonth    = today.getMonth();
let selectedDate = null;

const monthLabel = document.getElementById('cal-month-label');
const grid       = document.getElementById('cal-grid');
const footer     = document.getElementById('cal-footer');

function renderCalendar() {
    monthLabel.textContent = `${MONTHS[viewMonth]} ${viewYear}`;
    grid.innerHTML = '';

    const firstDay    = new Date(viewYear, viewMonth, 1).getDay();
    const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
    const daysInPrev  = new Date(viewYear, viewMonth, 0).getDate();

    for (let i = firstDay - 1; i >= 0; i--)
        addCell(daysInPrev - i, true);

    for (let d = 1; d <= daysInMonth; d++) {
        const isToday =
            d === today.getDate() &&
            viewMonth === today.getMonth() &&
            viewYear  === today.getFullYear();
        const isSelected =
            selectedDate &&
            d === selectedDate.day &&
            viewMonth === selectedDate.month &&
            viewYear  === selectedDate.year;
        addCell(d, false, isToday, isSelected, d);
    }

    const total    = firstDay + daysInMonth;
    const trailing = total % 7 === 0 ? 0 : 7 - (total % 7);
    for (let d = 1; d <= trailing; d++) addCell(d, true);
}

function addCell(label, otherMonth, isToday = false, isSelected = false, dayNum = null) {
    const el = document.createElement('div');
    el.classList.add('cal-day');
    el.textContent = label;
    if (otherMonth)  el.classList.add('other-month', 'empty');
    if (isToday)     el.classList.add('today');
    if (isSelected)  el.classList.add('selected');
    if (!otherMonth && dayNum) {
        el.addEventListener('click', () => {
            selectedDate = { day: dayNum, month: viewMonth, year: viewYear };
            footer.textContent = `${MONTHS[viewMonth]} ${dayNum}, ${viewYear}`;
            renderCalendar();
        });
    }
    grid.appendChild(el);
}

document.getElementById('cal-prev').addEventListener('click', () => {
    if (--viewMonth < 0) { viewMonth = 11; viewYear--; }
    renderCalendar();
});

document.getElementById('cal-next').addEventListener('click', () => {
    if (++viewMonth > 11) { viewMonth = 0; viewYear++; }
    renderCalendar();
});

renderCalendar();

const container = document.querySelector('.news-events-container');
const wrapper = document.querySelector('.news-events-card-wrapper');
const prevBtn = document.querySelector('.nav-buttons button:first-child');
const nextBtn = document.querySelector('.nav-buttons button:last-child');

let currentIndex = 0;

// --- Calculate how many cards are visible and total slides ---
function getVisibleCount() {
    const card = container.querySelector('.news-events-card');
    if (!card) return 1;
    const cardWidth = card.offsetWidth;
    const gap = parseFloat(getComputedStyle(container).gap) || 0;
    return Math.round(wrapper.offsetWidth / (cardWidth + gap));
}

function getTotalCards() {
    return container.querySelectorAll('.news-events-card').length;
}

function getMaxIndex() {
    return Math.max(0, getTotalCards() - getVisibleCount());
}

// --- Move to a specific index ---
function goTo(index) {
    const card = container.querySelector('.news-events-card');
    if (!card) return;

    const cardWidth = card.offsetWidth;
    const gap = parseFloat(getComputedStyle(container).gap) || 0;

    currentIndex = Math.max(0, Math.min(index, getMaxIndex()));
    const offset = currentIndex * (cardWidth + gap);
    container.style.transform = `translateX(-${offset}px)`;

    updateButtons();
}

// --- Update disabled state ---
function updateButtons() {
    prevBtn.disabled = currentIndex <= 0;
    nextBtn.disabled = currentIndex >= getMaxIndex();
}

// --- Button click ---
prevBtn.addEventListener('click', () => goTo(currentIndex - 1));
nextBtn.addEventListener('click', () => goTo(currentIndex + 1));

// --- Drag / Swipe support (mouse & touch) ---
let isDragging = false;
let startX = 0;
let endX = 0;
let startTranslate = 0;
const DRAG_THRESHOLD = 50; // px before registering as a swipe

function getClientX(e) {
    return e.touches ? e.touches[0].clientX : e.clientX;
}

function dragStart(e) {
    isDragging = true;
    startX = getClientX(e);
    endX = startX;

    const card = container.querySelector('.news-events-card');
    const gap = parseFloat(getComputedStyle(container).gap) || 0;
    startTranslate = currentIndex * (card.offsetWidth + gap);

    container.style.transition = 'none';
    container.style.cursor = 'grabbing';
}

function dragMove(e) {
    if (!isDragging) return;
    endX = getClientX(e);
    const deltaX = endX - startX;
    container.style.transform = `translateX(-${startTranslate - deltaX}px)`;
}

function dragEnd() {
    if (!isDragging) return;
    isDragging = false;
    container.style.transition = '';
    container.style.cursor = '';

    // Positive deltaX = dragged right = go to previous
    // Negative deltaX = dragged left  = go to next
    const deltaX = endX - startX;

    if (Math.abs(deltaX) >= DRAG_THRESHOLD) {
        goTo(deltaX < 0 ? currentIndex + 1 : currentIndex - 1);
    } else {
        // Snap back to current position
        goTo(currentIndex);
    }
}

// Mouse events
container.addEventListener('mousedown', dragStart);
window.addEventListener('mousemove', dragMove);
window.addEventListener('mouseup', dragEnd);

// Prevent image/link drag interference
container.addEventListener('dragstart', (e) => e.preventDefault());

// Touch events
container.addEventListener('touchstart', dragStart, { passive: true });
container.addEventListener('touchmove', dragMove, { passive: true });
container.addEventListener('touchend', dragEnd);

// --- Recalculate on resize ---
window.addEventListener('resize', () => goTo(currentIndex));

// --- Init ---
updateButtons();


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