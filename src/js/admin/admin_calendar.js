/* admin_calendar.js — page-specific scripts */

const allEvents  = window._calEvents  || [];
const imageBase  = window._imageBase  || '';

let curYear, curMonth;
let currentView = 'cal';

/* ── View switcher ─────────────────────────────────────── */

function setView(v) {
    currentView = v;
    document.getElementById('calView').style.display   = v === 'cal'  ? '' : 'none';
    document.getElementById('calAddBtn').style.display = v === 'cal'  ? 'flex' : 'none';
    document.getElementById('listView').style.display  = v === 'list' ? '' : 'none';
    document.getElementById('btnCalView').classList.toggle('active',  v === 'cal');
    document.getElementById('btnListView').classList.toggle('active', v === 'list');
}

/* ── Calendar builder ──────────────────────────────────── */

function buildCalendar(year, month) {
    curYear  = year;
    curMonth = month;

    const MONTHS = ['January','February','March','April','May','June',
                    'July','August','September','October','November','December'];
    document.getElementById('calMonthLabel').textContent = MONTHS[month] + ' ' + year;

    // Day-name row
    const dayNames = document.getElementById('calDayNames');
    dayNames.innerHTML = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']
        .map(d => `<div class="cal-day-name">${d}</div>`).join('');

    const cells       = document.getElementById('calCells');
    cells.innerHTML   = '';

    const first       = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrev  = new Date(year, month, 0).getDate();
    const today       = new Date();

    // Build event map, expanding multi-day ranges
    const evMap   = {};
    const localKey = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;

    allEvents.forEach(ev => {
        const start = ev.event_date ? ev.event_date.substring(0, 10) : null;
        const end   = ev.end_date   ? ev.end_date.substring(0, 10)   : start;
        if (!start) return;
        let cur  = new Date(start + 'T00:00:00');
        const endD = new Date((end || start) + 'T00:00:00');
        while (cur <= endD) {
            const key = localKey(cur);
            if (!evMap[key]) evMap[key] = [];
            evMap[key].push(ev);
            cur.setDate(cur.getDate() + 1);
        }
    });

    // Prev-month padding cells
    for (let i = 0; i < first; i++) {
        const d = daysInPrev - first + 1 + i;
        cells.innerHTML += `<div class="cal-cell other-month"><div class="cal-date">${d}</div></div>`;
    }

    // Current-month cells
    for (let d = 1; d <= daysInMonth; d++) {
        const isToday = today.getFullYear() === year &&
                        today.getMonth()    === month &&
                        today.getDate()     === d;
        const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const dayEvs  = evMap[dateStr] || [];

        let dots = dayEvs.slice(0, 3).map(ev =>
            `<div class="cal-event-dot" style="background:${ev.color}"
                  title="${escAttr(ev.title)}"
                  onclick="openView(${escAttrJson(ev)})">${escHtml(ev.title)}</div>`
        ).join('');
        if (dayEvs.length > 3) {
            dots += `<div class="cal-more">+${dayEvs.length - 3} more</div>`;
        }

        cells.innerHTML += `<div class="cal-cell${isToday ? ' today' : ''}">
            <div class="cal-date">${d}</div>${dots}</div>`;
    }

    // Next-month padding
    const total     = first + daysInMonth;
    const remaining = total % 7 === 0 ? 0 : 7 - (total % 7);
    for (let i = 1; i <= remaining; i++) {
        cells.innerHTML += `<div class="cal-cell other-month"><div class="cal-date">${i}</div></div>`;
    }
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function escAttr(str) {
    return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
function escAttrJson(ev) {
    return "'" + JSON.stringify(ev).replace(/'/g, "\\'").replace(/"/g, '&quot;') + "'";
}

function changeMonth(dir) {
    curMonth += dir;
    if (curMonth > 11) { curMonth = 0;  curYear++; }
    if (curMonth < 0)  { curMonth = 11; curYear--; }
    buildCalendar(curYear, curMonth);
}

function goToday() {
    const t = new Date();
    buildCalendar(t.getFullYear(), t.getMonth());
}

// Initialise on load
const _now = new Date();
buildCalendar(_now.getFullYear(), _now.getMonth());

/* ── Modal helpers ─────────────────────────────────────── */

const viewModal  = document.getElementById('viewModal');
const eventModal = document.getElementById('eventModal');

window.addEventListener('click', e => {
    if (e.target === viewModal)  closeViewModal();
    if (e.target === eventModal) closeModal();
});

function closeViewModal() { viewModal.style.display  = 'none'; }
function closeModal()     { eventModal.style.display = 'none'; }

/* ── View event modal ──────────────────────────────────── */

function openView(raw) {
    const ev = typeof raw === 'string'
        ? JSON.parse(raw.replace(/&quot;/g, '"'))
        : raw;

    const bannerWrap = document.getElementById('ev_view_banner_wrap');
    bannerWrap.innerHTML = ev.image
        ? `<img src="${imageBase}${ev.image}" class="ev-view-banner" alt="">`
        : '';

    document.getElementById('ev_view_strip').style.background = ev.color || '#8C1C24';
    document.getElementById('ev_view_title').textContent      = ev.title;

    const startFmt = ev.event_date
        ? new Date(ev.event_date + 'T00:00:00')
            .toLocaleDateString('en-US', { month:'long', day:'numeric', year:'numeric' })
        : '';
    const endFmt = ev.end_date &&
        ev.end_date.substring(0,10) !== ev.event_date.substring(0,10)
        ? ' – ' + new Date(ev.end_date + 'T00:00:00')
            .toLocaleDateString('en-US', { month:'long', day:'numeric', year:'numeric' })
        : '';

    const audLabel = { all:'All Users', students:'Students', faculty:'Faculty', applicants:'Applicants' };
    let meta = `<span><i class="fa-solid fa-calendar"></i> ${startFmt}${endFmt}</span>`;
    if (ev.event_time) meta += `<span><i class="fa-solid fa-clock"></i> ${ev.event_time}</span>`;
    meta += `<span><i class="fa-solid fa-users"></i> ${audLabel[ev.audience] || ev.audience}</span>`;

    document.getElementById('ev_view_meta').innerHTML        = meta;
    document.getElementById('ev_view_desc').textContent      = ev.description || '';
    document.getElementById('ev_view_edit_btn').onclick      = () => { closeViewModal(); openEdit(ev); };

    viewModal.style.display = 'block';
}

/* ── Add/Edit event modal ──────────────────────────────── */

let _durMode = 'single';

function setDuration(mode) {
    _durMode = mode;
    document.getElementById('durSingle').classList.toggle('active', mode === 'single');
    document.getElementById('durRange').classList.toggle('active',  mode === 'range');
    document.getElementById('ev_date_label').innerHTML = mode === 'range'
        ? 'Start Date <span style="color:var(--red)">*</span>'
        : 'Date <span style="color:var(--red)">*</span>';
    document.getElementById('ev_end_date_wrap').style.display = mode === 'range' ? '' : 'none';
    if (mode === 'single') document.getElementById('ev_end_date').value = '';
}

function openAdd(dateStr) {
    document.getElementById('eventModalTitle').textContent = 'Add Event';
    document.getElementById('evSubmitBtn').textContent     = 'Add Event';
    document.getElementById('ev_action').value             = 'add';
    document.getElementById('ev_id').value                 = '';
    document.getElementById('ev_title').value              = '';
    document.getElementById('ev_date').value               = dateStr || '';
    document.getElementById('ev_time').value               = '';
    document.getElementById('ev_description').value        = '';
    document.getElementById('ev_audience').value           = 'all';
    setDuration('single');
    pickColor('#8C1C24');
    eventModal.style.display = 'block';
}

function openEdit(raw) {
    const ev = typeof raw === 'string'
        ? JSON.parse(raw.replace(/&quot;/g, '"'))
        : raw;

    document.getElementById('eventModalTitle').textContent = 'Edit Event';
    document.getElementById('evSubmitBtn').textContent     = 'Save Changes';
    document.getElementById('ev_action').value             = 'edit';
    document.getElementById('ev_id').value                 = ev.event_id;
    document.getElementById('ev_title').value              = ev.title;
    document.getElementById('ev_date').value               = ev.event_date ? ev.event_date.substring(0,10) : '';
    document.getElementById('ev_time').value               = ev.event_time  || '';
    document.getElementById('ev_description').value        = ev.description || '';
    document.getElementById('ev_audience').value           = ev.audience    || 'all';
    pickColor(ev.color || '#8C1C24');

    const hasRange = ev.end_date &&
        ev.end_date.substring(0,10) !== ev.event_date.substring(0,10);
    setDuration(hasRange ? 'range' : 'single');
    if (hasRange) document.getElementById('ev_end_date').value = ev.end_date.substring(0,10);

    eventModal.style.display = 'block';
}

/* ── Color picker ──────────────────────────────────────── */

function pickColor(c) {
    document.getElementById('ev_color').value = c;
    document.querySelectorAll('.color-opt').forEach(el =>
        el.classList.toggle('selected', el.dataset.color === c)
    );
}

// Set default selection on load
pickColor('#8C1C24');