/* admin_blocks.js — page-specific scripts */

/* ── Close modal on outside click ─────────────────────── */

window.addEventListener('click', function(e) {
    ['createBlockModal', 'editBlockModal'].forEach(id => {
        const modal = document.getElementById(id);
        if (e.target === modal) modal.style.display = 'none';
    });
});

/* ── Edit block ───────────────────────────────────────────────── */

function editBlock(blockId) {
    fetch('../../php/get_block.php?block_id=' + blockId)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            document.getElementById('edit_block_id').value = data.block_id;
            document.getElementById('edit_block_name').value = data.block_name;
            document.getElementById('edit_course').value = data.course;
            document.getElementById('edit_year_level').value = data.year_level;
            document.getElementById('edit_semester').value = data.semester;
            document.getElementById('edit_school_year').value = data.school_year;
            document.getElementById('edit_max_students').value = data.max_students;
            document.getElementById('edit_status').value = data.status;
            document.getElementById('editBlockModal').style.display = 'block';
        })
        .catch(err => {
            alert('Failed to load block data.');
            console.error(err);
        });
}

/* ── Delete block ─────────────────────────────────────────────── */

function deleteBlock(blockId, blockName) {
    if (!confirm('Are you sure you want to delete block "' + blockName + '"? This action cannot be undone.')) {
        return;
    }
    window.location.href = '../../php/delete_block.php?block_id=' + blockId;
}