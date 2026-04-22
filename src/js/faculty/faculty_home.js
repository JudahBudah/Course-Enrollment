/* ══════════════════════════════════════════════════════════
   CALENDAR
══════════════════════════════════════════════════════════ */

const MONTHS = [
  "January",
  "February",
  "March",
  "April",
  "May",
  "June",
  "July",
  "August",
  "September",
  "October",
  "November",
  "December",
];
const DAY_NAMES = [
  "Sunday",
  "Monday",
  "Tuesday",
  "Wednesday",
  "Thursday",
  "Friday",
  "Saturday",
];

const today = new Date();
let viewYear = today.getFullYear();
let viewMonth = today.getMonth();
let selectedDate = null;

// Build map: 'YYYY-MM-DD' -> [full event objects]
function buildEventMap() {
  const map = {};
  const localKey = (d) =>
    `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
  (window._calEvents || []).forEach((ev) => {
    const start = ev.event_date ? ev.event_date.substring(0, 10) : null;
    const end = ev.end_date ? ev.end_date.substring(0, 10) : start;
    if (!start) return;
    let cur = new Date(start + "T00:00:00");
    const endD = new Date((end || start) + "T00:00:00");
    while (cur <= endD) {
      const key = localKey(cur);
      if (!map[key]) map[key] = [];
      map[key].push(ev);
      cur.setDate(cur.getDate() + 1);
    }
  });
  return map;
}

function renderCalendar() {
  const monthLabel = document.getElementById("cal-month-label");
  const grid = document.getElementById("cal-grid");
  if (!monthLabel || !grid) return;

  monthLabel.textContent = `${MONTHS[viewMonth]} ${viewYear}`;
  grid.innerHTML = "";

  const eventMap = buildEventMap();
  const firstDay = new Date(viewYear, viewMonth, 1).getDay();
  const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
  const daysInPrev = new Date(viewYear, viewMonth, 0).getDate();

  for (let i = firstDay - 1; i >= 0; i--) addCell(daysInPrev - i, true);
  for (let d = 1; d <= daysInMonth; d++) {
    const isToday =
      d === today.getDate() &&
      viewMonth === today.getMonth() &&
      viewYear === today.getFullYear();
    const dateStr = `${viewYear}-${String(viewMonth + 1).padStart(2, "0")}-${String(d).padStart(2, "0")}`;
    const evs = eventMap[dateStr] || [];
    addCell(d, false, isToday, evs);
  }
  const trailing = (firstDay + daysInMonth) % 7;
  for (let d = 1; d <= (trailing === 0 ? 0 : 7 - trailing); d++)
    addCell(d, true);
}

function addCell(label, otherMonth, isToday = false, evs = []) {
  const grid = document.getElementById("cal-grid");
  const el = document.createElement("div");
  el.classList.add("cal-day");
  el.appendChild(document.createTextNode(label));

  if (otherMonth) {
    el.classList.add("other-month", "empty");
  } else {
    el.classList.add("has-event");
    el.addEventListener("click", (e) => onCalDayClick(label, e));
    if (evs.length > 0) {
      const dot = document.createElement("span");
      dot.className = "event-dot";
      el.appendChild(dot);
    }
  }
  if (isToday) el.classList.add("today");
  grid.appendChild(el);
}

function onCalDayClick(day, e) {
  const clickedDate = new Date(viewYear, viewMonth, day);
  const dayName = DAY_NAMES[clickedDate.getDay()];
  const dateStr = `${viewYear}-${String(viewMonth + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
  const evs = buildEventMap()[dateStr] || [];

  document
    .querySelectorAll(".cal-day.selected")
    .forEach((el) => el.classList.remove("selected"));
  e.currentTarget.classList.add("selected");
  selectedDate = { date: clickedDate, dayName };

  // Single event → open modal directly
  if (evs.length === 1) {
    openCalEventModal(evs[0]);
    return;
  }

  // Multiple events → show peek with clickable titles
  const peek = document.getElementById("cal-event-peek");
  const peekText = document.getElementById("cal-event-text");
  if (evs.length > 0 && peek && peekText) {
    peekText.innerHTML = evs
      .map(
        (ev) =>
          `<div class="cal-peek-item" onclick="openCalEventModal(window._calEvents.find(x=>x.event_id=='${ev.event_id}'))">` +
          `<span class="cal-peek-dot" style="background:${ev.color};"></span>` +
          `<span class="cal-peek-title">${escHtml(ev.title)}</span>` +
          `<svg class="cal-peek-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>` +
          `</div>`,
      )
      .join("");
    peek.style.display = "block";
  } else if (peek) {
    peek.style.display = "none";
  }
}

/* ══════════════════════════════════════════════════════════
   CALENDAR EVENT MODAL
══════════════════════════════════════════════════════════ */

function openCalEventModal(ev) {
  if (!ev) return;
  const modal = document.getElementById("calEventModal");
  const imageBase = window._evImageBase || "";

  document.getElementById("cem_banner_wrap").innerHTML = ev.image
    ? `<img src="${imageBase}${ev.image}" style="width:100%;height:200px;object-fit:cover;border-radius:12px 12px 0 0;display:block;" alt="">`
    : "";
  document.getElementById("cem_color_strip").style.background =
    ev.color || "#8C1C24";
  document.getElementById("cem_title").textContent = ev.title;

  const startFmt = ev.event_date
    ? new Date(ev.event_date + "T00:00:00").toLocaleDateString("en-US", {
        weekday: "long",
        month: "long",
        day: "numeric",
        year: "numeric",
      })
    : "";
  const endFmt =
    ev.end_date &&
    ev.end_date.substring(0, 10) !== ev.event_date.substring(0, 10)
      ? " – " +
        new Date(ev.end_date + "T00:00:00").toLocaleDateString("en-US", {
          month: "long",
          day: "numeric",
          year: "numeric",
        })
      : "";

  let meta = `<span style="display:flex;align-items:center;gap:4px;"><i class="fa-solid fa-calendar" style="color:#8C1C24;"></i> ${startFmt}${endFmt}</span>`;
  if (ev.event_time)
    meta += `<span style="display:flex;align-items:center;gap:4px;"><i class="fa-solid fa-clock" style="color:#8C1C24;"></i> ${ev.event_time}</span>`;
  document.getElementById("cem_meta").innerHTML = meta;
  document.getElementById("cem_desc").textContent = ev.description || "";

  modal.style.display = "block";
}

function closeCalEventModal() {
  document.getElementById("calEventModal").style.display = "none";
}

document
  .getElementById("calEventModal")
  ?.addEventListener("click", function (e) {
    if (e.target === this) closeCalEventModal();
  });

/* ══════════════════════════════════════════════════════════
   SHOW SCHEDULE FOR CLICKED CALENDAR DAY
══════════════════════════════════════════════════════════ */

function showCalendarDay(dayName, date) {
  const weekSchedule = window._weekSchedule || {};
  const classes = weekSchedule[dayName] || [];

  document.getElementById("btnToday").classList.remove("active");
  document.getElementById("btnWeek").classList.remove("active");

  const listToday = document.getElementById("listToday");
  const listWeek = document.getElementById("listWeek");
  listWeek.style.display = "none";
  listToday.style.display = "";

  const dateStr = date.toLocaleDateString("en-US", {
    weekday: "long",
    month: "long",
    day: "numeric",
  });

  if (classes.length === 0) {
    listToday.innerHTML = `<div class="sched-empty"><i class="fa-solid fa-calendar-xmark"></i> No classes on ${dateStr}.</div>`;
  } else {
    listToday.innerHTML =
      `<div class="sched-day-label" style="border-top:none;">${dateStr}</div>` +
      classes
        .map(
          (s) => `
                <div class="schedule-item">
                    <div class="schedule-time">${escHtml(s.schedule_time || "TBA")}</div>
                    <div class="schedule-details">
                        <h4>${escHtml(s.subject_name)}</h4>
                        <p>${escHtml(s.room || "TBA")} &bull; ${escHtml(s.section || "TBA")}</p>
                    </div>
                </div>`,
        )
        .join("");
  }
}

function escHtml(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

document.getElementById("cal-prev")?.addEventListener("click", () => {
  if (--viewMonth < 0) {
    viewMonth = 11;
    viewYear--;
  }
  renderCalendar();
});
document.getElementById("cal-next")?.addEventListener("click", () => {
  if (++viewMonth > 11) {
    viewMonth = 0;
    viewYear++;
  }
  renderCalendar();
});

renderCalendar();

/* ══════════════════════════════════════════════════════════
   SCHEDULE TOGGLES (Today / Week · List / Grid)
══════════════════════════════════════════════════════════ */

const weekSchedule = window._weekSchedule || {};
let currentRange = "today";
let currentMode = "list";

function setRange(range) {
  currentRange = range;
  selectedDate = null;
  document
    .querySelectorAll(".cal-day.selected")
    .forEach((el) => el.classList.remove("selected"));
  const peek = document.getElementById("cal-event-peek");
  if (peek) peek.style.display = "none";

  document
    .getElementById("btnToday")
    .classList.toggle("active", range === "today");
  document
    .getElementById("btnWeek")
    .classList.toggle("active", range === "week");
  document.getElementById("listToday").style.display =
    range === "today" ? "" : "none";
  document.getElementById("listWeek").style.display =
    range === "week" ? "" : "none";

  if (currentMode === "grid") renderMiniGrid();
}

function setMode(mode) {
  currentMode = mode;
  document
    .getElementById("btnList")
    .classList.toggle("active", mode === "list");
  document
    .getElementById("btnGrid")
    .classList.toggle("active", mode === "grid");
  document.getElementById("schedListView").style.display =
    mode === "list" ? "" : "none";
  document.getElementById("schedGridView").style.display =
    mode === "grid" ? "" : "none";
  if (mode === "grid") renderMiniGrid();
}

/* ══════════════════════════════════════════════════════════
   MINI WEEKLY GRID (grid view)
══════════════════════════════════════════════════════════ */

function renderMiniGrid() {
  const container = document.getElementById("miniWeeklyGrid");
  if (!container) return;
  container.innerHTML = "";
  document.getElementById("gridEmpty").style.display = "none";

  const toMin = (t) => {
    const [h, m] = t.split(":").map(Number);
    return h * 60 + m;
  };
  const to24 = (t) => {
    t = t.trim();
    const m = t.match(/(\d+):(\d+)\s*(AM|PM)/i);
    if (!m) return t;
    let h = parseInt(m[1]);
    if (m[3].toUpperCase() === "PM" && h !== 12) h += 12;
    if (m[3].toUpperCase() === "AM" && h === 12) h = 0;
    return `${String(h).padStart(2, "0")}:${m[2]}`;
  };

  const dayMap = {
    Monday: "M",
    Tuesday: "T",
    Wednesday: "W",
    Thursday: "TH",
    Friday: "F",
    Saturday: "S",
    Sunday: "SU",
  };

  let allowedDays = null;
  if (currentRange === "today" && !selectedDate) {
    const todayName = DAY_NAMES[today.getDay()];
    allowedDays = new Set([dayMap[todayName]].filter(Boolean));
  } else if (selectedDate) {
    const key = dayMap[selectedDate.dayName];
    allowedDays = key ? new Set([key]) : new Set();
  }

  const scheduleData = [];
  Object.entries(weekSchedule).forEach(([dayName, classes]) => {
    const dayKey = dayMap[dayName];
    if (!dayKey) return;
    if (allowedDays && !allowedDays.has(dayKey)) return;
    classes.forEach((cls) => {
      if (!cls.schedule_time) return;
      const parts = cls.schedule_time.split(/\s*-\s*/);
      if (parts.length < 2) return;
      const start = to24(parts[0]),
        end = to24(parts[1]);
      let entry = scheduleData.find(
        (e) => e.code === cls.subject_code && e.start === start,
      );
      if (entry) {
        if (!entry.slots.find((sl) => sl.day === dayKey))
          entry.slots.push({ day: dayKey, start, end });
      } else {
        const name = cls.subject_name || "";
        scheduleData.push({
          code: cls.subject_code,
          shortName: name.length > 14 ? name.slice(0, 14) + "\u2026" : name,
          room: cls.room || "TBA",
          start,
          slots: [{ day: dayKey, start, end }],
        });
      }
    });
  });

  if (!scheduleData.length) {
    document.getElementById("gridEmpty").style.display = "";
    return;
  }

  const PX = 0.75;
  const allStarts = scheduleData.flatMap((s) =>
    s.slots.map((sl) => (toMin(sl.start) / 60) | 0),
  );
  const allEnds = scheduleData.flatMap((s) =>
    s.slots.map((sl) => Math.ceil(toMin(sl.end) / 60)),
  );
  const S_HOUR = Math.max(0, Math.min(...allStarts) - 1);
  const E_HOUR = Math.min(24, Math.max(...allEnds) + 1);
  const GRID_H = (E_HOUR - S_HOUR) * 60 * PX;

  const ALL_DAYS = ["M", "T", "W", "TH", "F", "S", "SU"];
  const ALL_LABELS = {
    M: "MON",
    T: "TUE",
    W: "WED",
    TH: "THU",
    F: "FRI",
    S: "SAT",
    SU: "SUN",
  };
  const DAYS = allowedDays
    ? ALL_DAYS.filter((d) => allowedDays.has(d))
    : ALL_DAYS;
  const colCount = DAYS.length;

  const toTop = (t) => (toMin(t) - S_HOUR * 60) * PX;
  const toHgt = (s, e) => (toMin(e) - toMin(s)) * PX;

  function mk(tag, cls, txt) {
    const e = document.createElement(tag);
    if (cls) e.className = cls;
    if (txt !== undefined) e.textContent = txt;
    return e;
  }

  container.style.cssText = `display:grid;grid-template-columns:36px repeat(${colCount},1fr);min-width:${colCount > 1 ? 420 : 160}px;padding-bottom:12px;`;

  const tcol = mk("div", "wg-time-col");
  tcol.appendChild(mk("div", "wg-time-header"));
  const tb = mk("div");
  tb.style.cssText = `position:relative;height:${GRID_H}px;`;
  for (let h = S_HOUR; h <= E_HOUR; h++) {
    const lbl = mk("div", "wg-time-label");
    lbl.style.top = `${(h - S_HOUR) * 60 * PX}px`;
    const dh = h > 12 ? h - 12 : h === 0 ? 12 : h;
    lbl.textContent = `${dh}${h >= 12 ? "PM" : "AM"}`;
    tb.appendChild(lbl);
  }
  tcol.appendChild(tb);
  container.appendChild(tcol);

  DAYS.forEach((day) => {
    const col = mk("div", "wg-day-col");
    col.appendChild(mk("div", "wg-day-header", ALL_LABELS[day]));
    const body = mk("div", "wg-body-area");
    body.style.height = `${GRID_H}px`;

    for (let h = S_HOUR; h < E_HOUR; h++) {
      const min = (h - S_HOUR) * 60;
      const hl = mk("div", "wg-hour-line");
      hl.style.top = `${min * PX}px`;
      body.appendChild(hl);
      const hf = mk("div", "wg-half-line");
      hf.style.top = `${(min + 30) * PX}px`;
      body.appendChild(hf);
    }

    scheduleData.forEach((subj) => {
      subj.slots.forEach((slot) => {
        if (slot.day !== day) return;
        const top = toTop(slot.start),
          hgt = toHgt(slot.start, slot.end);
        const block = mk("div", "wg-block");
        block.style.cssText = `top:${top}px;height:${hgt - 4}px;background:#1A3A8F;color:#fff;`;
        block.appendChild(mk("div", "wg-block-code", subj.code));
        if (hgt > 28)
          block.appendChild(mk("div", "wg-block-name", subj.shortName));
        if (hgt > 50) block.appendChild(mk("div", "wg-block-room", subj.room));
        body.appendChild(block);
      });
    });

    col.appendChild(body);
    container.appendChild(col);
  });
}

/* ══════════════════════════════════════════════════════════
   NEWS & EVENTS CAROUSEL
══════════════════════════════════════════════════════════ */

const newsContainer = document.querySelector(".news-events-container");
const newsWrapper = document.querySelector(".news-events-card-wrapper");
const prevBtn = document.querySelector(".nav-buttons button:first-child");
const nextBtn = document.querySelector(".nav-buttons button:last-child");

let currentIndex = 0;

function getVisibleCount() {
  const card = newsContainer?.querySelector(".news-events-card");
  if (!card) return 1;
  const gap = parseFloat(getComputedStyle(newsContainer).gap) || 0;
  return Math.round(newsWrapper.offsetWidth / (card.offsetWidth + gap));
}

function getTotalCards() {
  return newsContainer?.querySelectorAll(".news-events-card").length || 0;
}

function getMaxIndex() {
  return Math.max(0, getTotalCards() - getVisibleCount());
}

function goTo(index) {
  const card = newsContainer?.querySelector(".news-events-card");
  if (!card) return;
  const gap = parseFloat(getComputedStyle(newsContainer).gap) || 0;
  currentIndex = Math.max(0, Math.min(index, getMaxIndex()));
  newsContainer.style.transform = `translateX(-${currentIndex * (card.offsetWidth + gap)}px)`;
  updateButtons();
}

function updateButtons() {
  if (prevBtn) prevBtn.disabled = currentIndex <= 0;
  if (nextBtn) nextBtn.disabled = currentIndex >= getMaxIndex();
}

if (prevBtn) prevBtn.addEventListener("click", () => goTo(currentIndex - 1));
if (nextBtn) nextBtn.addEventListener("click", () => goTo(currentIndex + 1));

/* ── Drag / Swipe ── */

let isDragging = false,
  startX = 0,
  endX = 0,
  startTranslate = 0;
const DRAG_THRESHOLD = 50;

const getClientX = (e) => (e.touches ? e.touches[0].clientX : e.clientX);

function dragStart(e) {
  if (!newsContainer) return;
  isDragging = true;
  startX = endX = getClientX(e);
  const card = newsContainer.querySelector(".news-events-card");
  const gap = parseFloat(getComputedStyle(newsContainer).gap) || 0;
  startTranslate = currentIndex * ((card?.offsetWidth || 0) + gap);
  newsContainer.style.transition = "none";
  newsContainer.style.cursor = "grabbing";
}

function dragMove(e) {
  if (!isDragging || !newsContainer) return;
  endX = getClientX(e);
  newsContainer.style.transform = `translateX(-${startTranslate - (endX - startX)}px)`;
}

function dragEnd() {
  if (!isDragging || !newsContainer) return;
  isDragging = false;
  newsContainer.style.transition = "";
  newsContainer.style.cursor = "";
  const delta = endX - startX;
  goTo(
    Math.abs(delta) >= DRAG_THRESHOLD
      ? delta < 0
        ? currentIndex + 1
        : currentIndex - 1
      : currentIndex,
  );
}

if (newsContainer) {
  newsContainer.addEventListener("mousedown", dragStart);
  newsContainer.addEventListener("dragstart", (e) => e.preventDefault());
  newsContainer.addEventListener("touchstart", dragStart, { passive: true });
  newsContainer.addEventListener("touchmove", dragMove, { passive: true });
  newsContainer.addEventListener("touchend", dragEnd);
}

window.addEventListener("mousemove", dragMove);
window.addEventListener("mouseup", dragEnd);
window.addEventListener("resize", () => goTo(currentIndex));

updateButtons();
