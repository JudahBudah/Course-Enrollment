/* admin_blocks.js — page-specific scripts */

/* ── Close modal on outside click ─────────────────────── */

window.addEventListener('click', function(e) {
    const modal = document.getElementById('createBlockModal');
    if (e.target === modal) modal.style.display = 'none';
});

/* ── Edit block (placeholder — wire up as needed) ─────── */

function editBlock(blockId) {
    // TODO: populate an edit modal or redirect to edit page
    console.log('Edit block:', blockId);
}