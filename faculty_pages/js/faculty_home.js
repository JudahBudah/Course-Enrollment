// Simulated admin events — replace with PHP/DB data
const events = {
    "2026-03-14": "University Foundation Day — No Classes",
    "2026-03-16": "Midterm Examinations Begin",
    "2026-03-20": "Eid al-Fitr — No Classes",
};

let current = new Date();

function renderCalendar(date) {
    const year = date.getFullYear();
    const month = date.getMonth();
    const today = new Date();

    document.getElementById("cal-month-label").textContent =
        date.toLocaleString("default", { month: "long", year: "numeric" });

    const grid = document.getElementById("cal-grid");
    grid.innerHTML = "";

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    // Empty leading cells
    for (let i = 0; i < firstDay; i++) {
        const empty = document.createElement("div");
        empty.className = "cal-day empty";
        grid.appendChild(empty);
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const key = `${year}-${String(month + 1).padStart(2, "0")}-${String(d).padStart(2, "0")}`;
        const cell = document.createElement("div");
        cell.className = "cal-day";
        cell.textContent = d;

        const isToday = d === today.getDate() && month === today.getMonth() && year === today.getFullYear();
        if (isToday) cell.classList.add("today");

        if (events[key]) {
            cell.classList.add("has-event");
            const dot = document.createElement("span");
            dot.className = "event-dot";
            cell.appendChild(dot);

            cell.addEventListener("click", () => {
                const peek = document.getElementById("cal-event-peek");
                const text = document.getElementById("cal-event-text");
                text.textContent = `${key} — ${events[key]}`;
                peek.style.display = "block";
            });
        }

        grid.appendChild(cell);
    }

    document.getElementById("cal-event-peek").style.display = "none";
}

function changeMonth(dir) {
    current.setMonth(current.getMonth() + dir);
    renderCalendar(current);
}

renderCalendar(current);

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
