document.querySelectorAll('thead .select-all').forEach(selectAll => {
    selectAll.addEventListener('change', function () {
        const table = this.closest('table');
        table.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => {
            cb.checked = this.checked;
        });
    });
});

// Enrollment functionality
document.getElementById('enrollBtn')?.addEventListener('click', function() {
    const checkboxes = document.querySelectorAll('.subject-checkbox:checked');
    
    if (checkboxes.length === 0) {
        alert('Please select at least one subject to enroll');
        return;
    }
    
    const classIds = Array.from(checkboxes).map(cb => cb.dataset.classId);
    let enrolled = 0;
    let failed = 0;
    let failMessages = [];
    
    classIds.forEach((classId, index) => {
        const formData = new FormData();
        formData.append('class_id', classId);
        
        fetch('../../php/enroll_subject.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                enrolled++;
            } else {
                failed++;
                failMessages.push(data.message);
            }
            
            if (index === classIds.length - 1) {
                if (enrolled > 0) {
                    let msg = `Successfully enrolled in ${enrolled} subject(s).`;
                    if (failed > 0) msg += `\n\nFailed (${failed}):\n` + failMessages.join('\n');
                    alert(msg);
                    location.reload();
                } else {
                    alert('Enrollment failed:\n' + failMessages.join('\n'));
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            failed++;
        });
    });
});