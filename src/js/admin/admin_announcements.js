/* admin_announcements.js — page-specific scripts */

const modal       = document.getElementById('annModal');
const fileInput   = document.getElementById('ann_media');
const previewGrid = document.getElementById('previewGrid');
const uploadZone  = document.getElementById('uploadZone');

let _newFiles = [];

/* ── Modal open / close ────────────────────────────────── */

window.addEventListener('click', e => { if (e.target === modal) closeModal(); });

function closeModal() {
    modal.style.display = 'none';
    resetForm();
}

function resetForm() {
    document.getElementById('annForm').reset();
    previewGrid.innerHTML = '';
    document.getElementById('existing_media_wrap').style.display  = 'none';
    document.getElementById('existing_media_list').innerHTML      = '';
    document.getElementById('ann_status_group').style.display     = 'none';
    _newFiles = [];
}

/* ── Add modal ─────────────────────────────────────────── */

function openAdd() {
    resetForm();
    document.getElementById('annModalTitle').textContent = 'New Announcement';
    document.getElementById('annSubmitBtn').textContent  = 'Post Announcement';
    document.getElementById('ann_action').value          = 'add';
    document.getElementById('ann_id').value              = '';
    modal.style.display = 'block';
}

/* ── Edit modal ────────────────────────────────────────── */

function openEdit(ann) {
    resetForm();
    document.getElementById('annModalTitle').textContent  = 'Edit Announcement';
    document.getElementById('annSubmitBtn').textContent   = 'Save Changes';
    document.getElementById('ann_action').value           = 'edit';
    document.getElementById('ann_id').value               = ann.announcement_id;
    document.getElementById('ann_title').value            = ann.title;
    document.getElementById('ann_message').value          = ann.message;
    document.getElementById('ann_audience').value         = ann.target_audience;
    document.getElementById('ann_priority').value         = ann.priority;
    document.getElementById('ann_status').value           = ann.status;
    document.getElementById('ann_status_group').style.display = '';

    // Render existing media thumbnails
    let media = [];
    try { media = JSON.parse(ann.media || '[]') || []; } catch (e) {}

    if (media.length) {
        document.getElementById('existing_media_wrap').style.display = '';
        const list = document.getElementById('existing_media_list');
        list.innerHTML = '';

        media.forEach(m => {
            const wrap = document.createElement('div');
            wrap.className = 'existing-media-item';
            wrap.dataset.file = m.file;

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = ''; // only named when marked for removal

            if (m.type === 'image') {
                const img = document.createElement('img');
                img.src = window._mediaBase + m.file;
                wrap.appendChild(img);
            } else {
                const vd = document.createElement('div');
                vd.className = 'ann-thumb-video';
                vd.innerHTML = '<i class="fa-solid fa-play"></i>';
                wrap.appendChild(vd);
            }

            const btn = document.createElement('button');
            btn.type      = 'button';
            btn.className = 'existing-remove';
            btn.innerHTML = '&times;';
            btn.title     = 'Remove this file';
            btn.onclick   = () => {
                const marked = wrap.classList.toggle('marked-del');
                if (marked) {
                    hidden.name  = 'remove_media[]';
                    hidden.value = m.file;
                    wrap.appendChild(hidden);
                    btn.classList.add('marked');
                } else {
                    hidden.name = '';
                    btn.classList.remove('marked');
                }
            };

            wrap.appendChild(btn);
            list.appendChild(wrap);
        });
    }

    modal.style.display = 'block';
}

/* ── File selection & preview ──────────────────────────── */

if (fileInput) {
    fileInput.addEventListener('change', () => addFiles(fileInput.files));
}

function addFiles(files) {
    Array.from(files).forEach(f => {
        _newFiles.push(f);
        const idx  = _newFiles.length - 1;
        const item = document.createElement('div');
        item.className = 'preview-item';

        const btn  = document.createElement('button');
        btn.type      = 'button';
        btn.className = 'preview-remove';
        btn.innerHTML = '&times;';
        btn.onclick   = () => { _newFiles.splice(idx, 1); item.remove(); syncFileInput(); };

        if (f.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src   = URL.createObjectURL(f);
            item.appendChild(img);
        } else {
            const vid = document.createElement('video');
            vid.src   = URL.createObjectURL(f);
            item.appendChild(vid);
        }

        item.appendChild(btn);
        previewGrid.appendChild(item);
    });
    syncFileInput();
}

function syncFileInput() {
    const dt = new DataTransfer();
    _newFiles.forEach(f => dt.items.add(f));
    if (fileInput) fileInput.files = dt.files;
}

/* ── Drag & drop ───────────────────────────────────────── */

if (uploadZone) {
    uploadZone.addEventListener('dragover',  e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
    uploadZone.addEventListener('dragleave', ()  => uploadZone.classList.remove('drag-over'));
    uploadZone.addEventListener('drop', e => {
        e.preventDefault();
        uploadZone.classList.remove('drag-over');
        addFiles(e.dataTransfer.files);
    });
}