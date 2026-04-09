<?php
/**
 * Reusable announcement feed — news card layout.
 * Requires: $con (mysqli), $ann_audience ('students'|'faculty'|'applicants'|'all')
 * Optional: $ann_limit (int, default 12)
 */
$ann_audience = $ann_audience ?? 'all';
$ann_limit    = (int)($ann_limit ?? 12);
$_aud  = mysqli_real_escape_string($con, $ann_audience);
$_anns = [];
$_q    = mysqli_query($con,
    "SELECT * FROM announcements
     WHERE status = 'active'
       AND (target_audience = 'all' OR target_audience = '$_aud')
     ORDER BY FIELD(priority,'urgent','important','normal'), created_at DESC
     LIMIT $ann_limit"
);
while ($_r = mysqli_fetch_assoc($_q)) $_anns[] = $_r;
$_media_base = '../../uploads/announcements/';

// Priority colours
$_pri_colors = ['urgent'=>'var(--red)','important'=>'var(--red)','normal'=>'var(----block-blue)'];
$_pri_icons  = ['urgent'=>'fa-triangle-exclamation','important'=>'fa-circle-info','normal'=>'fa-bullhorn'];
?>

<?php if (empty($_anns)): ?>
    <div class="ann-feed-empty"><i class="fa-solid fa-bullhorn"></i> No announcements at this time.</div>
<?php else: ?>

<!-- Cards row -->
<div class="ann-news-wrapper">
    <div class="ann-news-row" id="annNewsRow">
        <?php foreach ($_anns as $_i => $_a):
            $_media  = json_decode($_a['media'] ?? '[]', true) ?: [];
            $_thumb  = null;
            $_has_vid = false;
            foreach ($_media as $_m) {
                if ($_m['type'] === 'image' && !$_thumb) $_thumb = $_media_base . $_m['file'];
                if ($_m['type'] === 'video') $_has_vid = true;
            }
            $_pri   = $_a['priority'];
            $_color = $_pri_colors[$_pri];
            $_icon  = $_pri_icons[$_pri];
            $_data  = htmlspecialchars(json_encode($_a), ENT_QUOTES);
            $_media_data = htmlspecialchars(json_encode($_media), ENT_QUOTES);
        ?>
        <div class="ann-news-card" onclick="openAnnModal(<?php echo $_data; ?>, <?php echo $_media_data; ?>)">
            <div class="ann-news-thumb">
                <?php if ($_thumb): ?>
                    <img src="<?php echo htmlspecialchars($_thumb); ?>" alt="" loading="lazy">
                <?php else: ?>
                    <div class="ann-news-thumb-placeholder">
                        <i class="fa-solid <?php echo $_icon; ?>" style="color:<?php echo $_color; ?>;"></i>
                    </div>
                <?php endif; ?>
                <span class="ann-news-badge" style="background:<?php echo $_color; ?>;">
                    <?php echo ucfirst($_pri); ?>
                </span>
                <?php if ($_has_vid || count($_media) > 1): ?>
                <span class="ann-news-media-count">
                    <i class="fa-solid fa-photo-film"></i> <?php echo count($_media); ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="ann-news-body">
                <p class="ann-news-title"><?php echo htmlspecialchars($_a['title']); ?></p>
                <p class="ann-news-date"><?php echo date('M j, Y', strtotime($_a['created_at'])); ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Detail Modal -->
<div id="annDetailModal" class="ann-modal-overlay" onclick="if(event.target===this)closeAnnModal()">
    <div class="ann-modal-box">
        <button class="ann-modal-close" onclick="closeAnnModal()">&times;</button>
        <div id="annModalInner"></div>
    </div>
</div>

<?php endif; ?>

<style>
.ann-feed-empty { color:var(--off); font-size:0.9rem; padding:1.5rem 0; text-align:center; }

/* Cards row */
.ann-news-wrapper { overflow:hidden; margin-top:16px; }
.ann-news-row { display:flex; gap:12px; overflow-x:auto; scroll-behavior:smooth; padding-bottom:8px; }
.ann-news-row::-webkit-scrollbar { height:4px; }
.ann-news-row::-webkit-scrollbar-thumb { background:rgba(0,0,0,0.15); border-radius:4px; }

.ann-news-card {
    flex-shrink:0;
    width:220px;
    background:var(--white-pr);
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 2px 10px rgba(0,0,0,0.08);
    cursor:pointer;
    transition:transform 0.2s, box-shadow 0.2s;
}
.ann-news-card:hover { transform:translateY(-2px); }

.ann-news-thumb {
    position:relative;
    width:100%;
    aspect-ratio:16/9;
    background:var(--white);
    overflow:hidden;
}
.ann-news-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
.ann-news-thumb-placeholder {
    width:100%; height:100%;
    display:flex; align-items:center; justify-content:center;
    font-size:2rem; background:var(--white);
}
.ann-news-badge {
    position:absolute; top:8px; left:8px;
    color:var(--white-pr); font-size:0.65rem; font-weight:700;
    padding:2px 8px; border-radius:20px; letter-spacing:0.04em;
}
.ann-news-media-count {
    position:absolute; bottom:6px; right:8px;
    background:rgba(0,0,0,0.55); color:var(--white-pr);
    font-size:0.68rem; padding:2px 7px; border-radius:20px;
}
.ann-news-body { padding:10px 12px 12px; }
.ann-news-title {
    font-size:0.82rem; font-weight:600; color:var(--dark);
    margin:0 0 5px; line-height:1.4;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
}
.ann-news-date { font-size:0.7rem; color:var(--text); margin:0; }

/* Modal overlay */
.ann-modal-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,0.6); z-index:9999;
    align-items:center; justify-content:center; padding:1rem;
}
.ann-modal-overlay.open { display:flex; }
.ann-modal-box {
    background:var(--white-pr); border-radius:12px;
    width:100%; max-width:640px; max-height:90vh;
    overflow-y:auto; position:relative;
    box-shadow:0 20px 60px rgba(0,0,0,0.3);
}
.ann-modal-close {
    position:sticky; top:0; float:right;
    background:rgba(0,0,0,0.07); border:none;
    font-size:1.4rem; width:34px; height:34px;
    border-radius:50%; cursor:pointer; margin:10px 10px 0 0;
    display:flex; align-items:center; justify-content:center;
    transition:background 0.2s; z-index:1;
}
.ann-modal-close:hover { background:rgba(0,0,0,0.15); }

/* Modal inner content */
.ann-detail-hero { width:100%; max-height:420px; object-fit:contain; display:block; background:var(--off); }
.ann-detail-hero-placeholder {
    width:100%; height:180px;
    display:flex; align-items:center; justify-content:center;
    font-size:3rem; background:var(--off);
}
.ann-detail-body { padding:1.25rem 1.5rem 1.5rem; }
.ann-detail-badges { display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:0.75rem; }
.ann-detail-badge {
    font-size:0.7rem; font-weight:700; padding:3px 10px;
    border-radius:20px; letter-spacing:0.04em;
}
.ann-detail-title { font-size:1.2rem; font-weight:700; color:var(--dark); margin:0 0 0.4rem; line-height:1.4; }
.ann-detail-meta  { font-size:0.78rem; color:var(--text); margin:0 0 1rem; }
.ann-detail-message { font-size:0.92rem; color:var(--text); line-height:1.7; white-space:pre-wrap; margin:0 0 1.25rem; }

/* Media grid inside modal */
.ann-detail-media { display:grid; gap:4px; border-radius:8px; overflow:hidden; margin-top:0.75rem; }
.ann-detail-media.mc-1 { grid-template-columns:1fr; }
.ann-detail-media.mc-2 { grid-template-columns:1fr 1fr; }
.ann-detail-media.mc-3 { grid-template-columns:1fr 1fr; }
.ann-detail-media.mc-3 .ann-dm-item:first-child { grid-column:1/-1; }
.ann-detail-media.mc-4, .ann-detail-media.mc-many { grid-template-columns:1fr 1fr; }
.ann-dm-item { position:relative; overflow:hidden; background:var(--dark); cursor:pointer; }
.ann-dm-item img { width:100%; height:180px; object-fit:cover; display:block; transition:0.2s; }
.ann-dm-item:hover img { opacity:0.85; }
.ann-dm-item video { width:100%; max-height:220px; display:block; }
.ann-dm-more {
    height:180px; background:rgba(0,0,0,0.65); color:var(--white-pr);
    display:flex; align-items:center; justify-content:center;
    font-size:1.5rem; font-weight:700; cursor:pointer;
}

/* Lightbox */
.ann-lb {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,0.93); z-index:10000;
    align-items:center; justify-content:center; flex-direction:column;
}
.ann-lb.open { display:flex; }
.ann-lb-content { max-width:90vw; max-height:85vh; display:flex; align-items:center; justify-content:center; }
.ann-lb-content img { max-width:90vw; max-height:85vh; object-fit:contain; border-radius:4px; }
.ann-lb-content video { max-width:90vw; max-height:85vh; border-radius:4px; }
.ann-lb-counter { color:rgba(255,255,255,0.6); margin-top:0.75rem; font-size:0.85rem; }
.ann-lb-btn {
    position:absolute; background:none; border:none;
    color:var(--white-pr); font-size:2rem; cursor:pointer; padding:1rem;
}
.ann-lb-btn.close { top:0.5rem; right:0.5rem; }
.ann-lb-btn.prev  { left:0; top:50%; transform:translateY(-50%); }
.ann-lb-btn.next  { right:0; top:50%; transform:translateY(-50%); }
</style>

<!-- Lightbox (shared) -->
<div id="annLightbox" class="ann-lb">
    <button class="ann-lb-btn close" onclick="annLbClose()">&times;</button>
    <button class="ann-lb-btn prev"  onclick="annLbNav(-1)"><i class="fa-solid fa-chevron-left"></i></button>
    <button class="ann-lb-btn next"  onclick="annLbNav(1)"><i class="fa-solid fa-chevron-right"></i></button>
    <div class="ann-lb-content" id="annLbContent"></div>
    <div class="ann-lb-counter" id="annLbCounter"></div>
</div>

<script>
const _annMediaBase = '<?php echo $_media_base; ?>';
const _priColors = {urgent:'var(--red-lt)', important:'var(--red)', normal:'var(--block-blue)'};
const _priLabels = {urgent:'Urgent', important:'Important', normal:'Normal'};
const _audLabels = {all:'All Users', students:'Students', faculty:'Faculty', applicants:'Applicants'};

function openAnnModal(ann, media) {
    const box = document.getElementById('annModalInner');
    const pri = ann.priority || 'normal';
    const color = _priColors[pri];

    // Hero
    let hero = '';
    const firstImg = media.find(m => m.type === 'image');
    if (firstImg) {
        hero = `<img class="ann-detail-hero" src="${_annMediaBase}${firstImg.file}" alt="">`;
    } else {
        const icon = {urgent:'fa-triangle-exclamation',important:'fa-circle-info',normal:'fa-bullhorn'}[pri];
        hero = `<div class="ann-detail-hero-placeholder"><i class="fa-solid ${icon}" style="color:${color}"></i></div>`;
    }

    // Badges
    const audLabel = _audLabels[ann.target_audience] || ann.target_audience;
    const badges = `
        <span class="ann-detail-badge" style="background:${color};color:var(--white-pr)">${_priLabels[pri]}</span>
        <span class="ann-detail-badge" style="background:var(--white);color:var(--text)">${audLabel}</span>`;

    // Date
    const d = new Date(ann.created_at);
    const dateStr = d.toLocaleDateString('en-US',{month:'long',day:'numeric',year:'numeric'});

    // Media grid — skip the first image since it's already shown as hero
    const gridMedia = firstImg ? media.filter((m, i) => !(m.type === 'image' && m.file === firstImg.file && i === media.indexOf(firstImg))) : media;
    let mediaHtml = '';
    if (gridMedia.length) {
        const cnt = gridMedia.length;
        const cls = cnt === 1 ? 'mc-1' : cnt === 2 ? 'mc-2' : cnt === 3 ? 'mc-3' : cnt === 4 ? 'mc-4' : 'mc-many';
        mediaHtml = `<div class="ann-detail-media ${cls}">`;
        gridMedia.forEach((m, i) => {
            const origIdx = media.indexOf(m);
            if (i >= 4) {
                if (i === 4) mediaHtml += `<div class="ann-dm-more" onclick="annLbOpen(${JSON.stringify(media)},${origIdx})">+${gridMedia.length - 4} more</div>`;
                return;
            }
            if (m.type === 'image') {
                mediaHtml += `<div class="ann-dm-item" onclick="annLbOpen(${JSON.stringify(media)},${origIdx})"><img src="${_annMediaBase}${m.file}" alt="" loading="lazy"></div>`;
            } else {
                mediaHtml += `<div class="ann-dm-item"><video controls preload="metadata"><source src="${_annMediaBase}${m.file}"></video></div>`;
            }
        });
        mediaHtml += '</div>';
    }

    box.innerHTML = `
        ${hero}
        <div class="ann-detail-body">
            <div class="ann-detail-badges">${badges}</div>
            <h2 class="ann-detail-title">${escHtml(ann.title)}</h2>
            <p class="ann-detail-meta">${dateStr}</p>
            <p class="ann-detail-message">${escHtml(ann.message)}</p>
            ${mediaHtml}
        </div>`;

    document.getElementById('annDetailModal').classList.add('open');
}

function closeAnnModal() {
    document.getElementById('annDetailModal').classList.remove('open');
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Lightbox
let _annLbMedia = [], _annLbIdx = 0;
function annLbOpen(media, idx) { _annLbMedia = media; _annLbIdx = idx; annLbRender(); document.getElementById('annLightbox').classList.add('open'); }
function annLbClose() { document.getElementById('annLightbox').classList.remove('open'); }
function annLbNav(d) { _annLbIdx = (_annLbIdx + d + _annLbMedia.length) % _annLbMedia.length; annLbRender(); }
function annLbRender() {
    const m = _annLbMedia[_annLbIdx];
    const el = document.getElementById('annLbContent');
    el.innerHTML = m.type === 'image'
        ? `<img src="${_annMediaBase}${m.file}">`
        : `<video controls autoplay><source src="${_annMediaBase}${m.file}"></video>`;
    document.getElementById('annLbCounter').textContent = `${_annLbIdx+1} / ${_annLbMedia.length}`;
}
document.getElementById('annLightbox').addEventListener('click', e => { if (e.target === document.getElementById('annLightbox')) annLbClose(); });
</script>
