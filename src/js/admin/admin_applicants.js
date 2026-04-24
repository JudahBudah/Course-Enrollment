/* admin_applicants.js — page-specific scripts */

/* ── View Applicant Details ────────────────────────────── */

function viewApplicant(id) {
  fetch("../../php/get_applicant_details.php?id=" + id)
    .then((res) => res.json())
    .then((data) => {
      if (data.error) {
        alert(data.error);
        return;
      }
      const a = data;
      const docs = [
        { key: "doc_form138", label: "Form 138" },
        { key: "doc_birth_cert", label: "Birth Certificate" },
        { key: "doc_good_moral", label: "Good Moral" },
        { key: "doc_our_au001", label: "OUR AU001" },
        { key: "doc_our_au002", label: "OUR AU002" },
      ];
      let docHtml = "";
      docs.forEach((d) => {
        if (a[d.key]) {
          const ext = a[d.key].split(".").pop().toLowerCase();
          const filePath = `../../uploads/applicants/${a.applicant_id}/${a[d.key]}`;
          docHtml += `
                        <div class="doc-item">
                            <div class="doc-info">
                                <i class="fa-solid fa-file-${ext === "pdf" ? "pdf" : "image"}"></i>
                                <div>
                                    <strong>${d.label}</strong>
                                    <span>${a[d.key]}</span>
                                </div>
                            </div>
                            <button type="button" class="doc-view-btn" onclick="viewDocument('${filePath}', '${a[d.key]}', '${ext}')">
                                <i class="fa-solid fa-eye"></i> View
                            </button>
                        </div>
                    `;
        } else {
          docHtml += `
                        <div class="doc-item">
                            <div class="doc-info">
                                <i class="fa-solid fa-file" style="opacity:0.3;"></i>
                                <div>
                                    <strong>${d.label}</strong>
                                    <span class="doc-not-uploaded">Not uploaded</span>
                                </div>
                            </div>
                        </div>
                    `;
        }
      });
      const html = `
                <div class="view-sections">
                    <div class="view-section">
                        <h3 class="view-section-title">
                            <i class="fa-solid fa-id-card"></i> Basic Information
                        </h3>
                        <div class="view-grid">
                            <div class="view-field"><label>LRN:</label><span>${a.lrn || "N/A"}</span></div>
                            <div class="view-field"><label>Email:</label><span>${a.email || "N/A"}</span></div>
                            <div class="view-field"><label>1st Choice:</label><span>${a.first_choice || "N/A"}</span></div>
                            <div class="view-field"><label>2nd Choice:</label><span>${a.second_choice || "N/A"}</span></div>
                            <div class="view-field"><label>3rd Choice:</label><span>${a.third_choice || "N/A"}</span></div>
                        </div>
                    </div>
                    <div class="view-section">
                        <h3 class="view-section-title">
                            <i class="fa-solid fa-user"></i> Personal Information
                        </h3>
                        <div class="view-grid">
                            <div class="view-field"><label>Last Name:</label><span>${a.last_name || "N/A"}</span></div>
                            <div class="view-field"><label>First Name:</label><span>${a.first_name || "N/A"}</span></div>
                            <div class="view-field"><label>Middle Name:</label><span>${a.middle_name || "N/A"}</span></div>
                            <div class="view-field"><label>Suffix:</label><span>${a.suffix || "N/A"}</span></div>
                            <div class="view-field"><label>Married Name:</label><span>${a.married_name || "N/A"}</span></div>
                            <div class="view-field"><label>Birthdate:</label><span>${a.birthdate || "N/A"}</span></div>
                            <div class="view-field"><label>Place of Birth:</label><span>${a.place_of_birth || "N/A"}</span></div>
                            <div class="view-field"><label>Gender:</label><span>${a.gender ? a.gender.charAt(0).toUpperCase() + a.gender.slice(1) : "N/A"}</span></div>
                            <div class="view-field"><label>Nationality:</label><span>${a.nationality || "N/A"}</span></div>
                            <div class="view-field"><label>Civil Status:</label><span>${a.civil_status ? a.civil_status.charAt(0).toUpperCase() + a.civil_status.slice(1) : "N/A"}</span></div>
                            <div class="view-field"><label>Religion:</label><span>${a.religion || "N/A"}</span></div>
                            <div class="view-field"><label>Contact Number:</label><span>${a.contact_number || "N/A"}</span></div>
                            <div class="view-field"><label>Disability:</label><span>${a.disability || "None"}</span></div>
                        </div>
                    </div>
                    <div class="view-section">
                        <h3 class="view-section-title">
                            <i class="fa-solid fa-location-dot"></i> Permanent Address
                        </h3>
                        <div class="view-grid">
                            <div class="view-field"><label>Region:</label><span>${a.perm_region || "N/A"}</span></div>
                            <div class="view-field"><label>Province:</label><span>${a.perm_province || "N/A"}</span></div>
                            <div class="view-field"><label>Municipality:</label><span>${a.perm_municipality || "N/A"}</span></div>
                            <div class="view-field"><label>Barangay:</label><span>${a.perm_barangay || "N/A"}</span></div>
                            <div class="view-field"><label>Zip Code:</label><span>${a.perm_zipcode || "N/A"}</span></div>
                            <div class="view-field view-field-full"><label>Complete Address:</label><span>${a.perm_address || "N/A"}</span></div>
                        </div>
                    </div>
                    <div class="view-section">
                        <h3 class="view-section-title">
                            <i class="fa-solid fa-envelope"></i> Mailing Address
                        </h3>
                        <div class="view-grid">
                            <div class="view-field"><label>Region:</label><span>${a.mail_region || "N/A"}</span></div>
                            <div class="view-field"><label>Province:</label><span>${a.mail_province || "N/A"}</span></div>
                            <div class="view-field"><label>Municipality:</label><span>${a.mail_municipality || "N/A"}</span></div>
                            <div class="view-field"><label>Barangay:</label><span>${a.mail_barangay || "N/A"}</span></div>
                            <div class="view-field"><label>Zip Code:</label><span>${a.mail_zipcode || "N/A"}</span></div>
                            <div class="view-field view-field-full"><label>Complete Address:</label><span>${a.mail_address || "N/A"}</span></div>
                        </div>
                    </div>
                    <div class="view-section">
                        <h3 class="view-section-title">
                            <i class="fa-solid fa-file-arrow-up"></i> Submitted Documents
                        </h3>
                        <div class="doc-list">
                            ${docHtml}
                        </div>
                    </div>
                </div>
            `;
      document.getElementById("viewContent").innerHTML = html;
      document.getElementById("viewModal").style.display = "block";
    })
    .catch((err) => {
      alert("Failed to load applicant details.");
      console.error(err);
    });
}

function viewDocument(src, name, ext) {
  document.getElementById("docModalName").textContent = name;
  const body = document.getElementById("docModalBody");
  body.innerHTML =
    ext === "pdf"
      ? `<iframe src="${src}" style="width:100%;height:100%;border:none;"></iframe>`
      : `<img src="${src}" alt="${name}" style="max-width:100%;max-height:100%;object-fit:contain;">`;
  document.getElementById("docModal").style.display = "block";
}

function closeDocModal() {
  document.getElementById("docModal").style.display = "none";
  document.getElementById("docModalBody").innerHTML = "";
}

/* ── Status modal ──────────────────────────────────────── */

function updateStatus(id) {
  document.getElementById("applicant_id").value = id;
  document.getElementById("statusModal").style.display = "block";
}

/* ── Convert modal ─────────────────────────────────────── */

// Find an option whose text contains the course name (full name match)
function findOptionByCourseName(courseName) {
  if (!courseName) return null;
  const courseSelect = document.getElementById("convert_course");
  const needle = courseName.trim().toLowerCase();
  for (const opt of courseSelect.options) {
    if (opt.text.toLowerCase().includes(needle)) return opt;
  }
  return null;
}

function openConvertModal(id, name, choice1, choice2, choice3) {
  document.getElementById("convert_applicant_id").value = id;
  document.getElementById("convertName").textContent = name;

  const choices = [
    { label: "1st Choice", name: choice1 },
    { label: "2nd Choice", name: choice2 },
    { label: "3rd Choice", name: choice3 },
  ].filter(c => c.name);

  document.getElementById("convertChoices").innerHTML = choices.length ? `
    <div class="convert-choices-box">
      <p class="convert-choices-title"><i class="fa-solid fa-list-ol"></i> Program Choices</p>
      ${choices.map(c => {
        const opt = findOptionByCourseName(c.name);
        const label = opt ? opt.text : c.name;
        return `
        <div class="convert-choice-row">
          <span class="convert-choice-label">${c.label}</span>
          <button type="button" class="convert-choice-btn" onclick="applyChoice('${c.name.replace(/'/g, "\\'")}')"
                  title="Use this choice">${label}</button>
        </div>`;
      }).join('')}
    </div>` : '';

  // Pre-select first choice
  document.getElementById("convert_college").value = "";
  const firstOpt = findOptionByCourseName(choice1);
  if (firstOpt) {
    firstOpt.selected = true;
    document.getElementById("convert_college").value = firstOpt.dataset.college || "";
  } else {
    document.getElementById("convert_course").value = "";
  }
  document.getElementById("convertModal").style.display = "block";
}

function applyChoice(courseName) {
  const opt = findOptionByCourseName(courseName);
  if (opt) {
    opt.selected = true;
    document.getElementById("convert_college").value = opt.dataset.college || "";
  }
}

document.getElementById("convert_course").addEventListener("change", function () {
  const selected = this.options[this.selectedIndex];
  document.getElementById("convert_college").value = selected.dataset.college || "";
});

// Handle convert form submission
document.getElementById("convertForm").addEventListener("submit", function (e) {
  e.preventDefault();
  const formData = new FormData(this);

  fetch("../../php/convert_to_student.php", {
    method: "POST",
    body: formData,
  })
    .then((res) => res.text())
    .then((text) => {
      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        alert("Server error: " + text.substring(0, 300));
        return;
      }
      if (data.success) {
        alert(data.message);
        document.getElementById("convertModal").style.display = "none";
        location.href = '?filter=enrolled';
      } else {
        alert(data.error);
      }
    })
    .catch((err) => {
      alert("An error occurred. Please try again.");
      console.error(err);
    });
});

/* ── Close modals on outside click ────────────────────── */

window.addEventListener("click", function (e) {
  ["statusModal", "convertModal", "examModal", "viewModal", "docModal"].forEach(
    (id) => {
      const modal = document.getElementById(id);
      if (e.target === modal) modal.style.display = "none";
    },
  );
});

/* ── Batch selection ───────────────────────────────────── */

function getSelected() {
  return [...document.querySelectorAll(".row-check:checked")].map(
    (c) => c.value,
  );
}

function updateBatch() {
  const sel = getSelected();
  const bar = document.getElementById("batchBar");
  const total = document.querySelectorAll(".row-check").length;
  const selectAll = document.getElementById("selectAll");

  bar.classList.toggle("visible", sel.length > 0);
  document.getElementById("batchCount").textContent = sel.length + " selected";

  selectAll.indeterminate = sel.length > 0 && sel.length < total;
  selectAll.checked = sel.length === total && total > 0;
}

function toggleAll(cb) {
  document
    .querySelectorAll(".row-check")
    .forEach((c) => (c.checked = cb.checked));
  updateBatch();
}

function deselectAll() {
  document.querySelectorAll(".row-check").forEach((c) => (c.checked = false));
  document.getElementById("selectAll").checked = false;
  updateBatch();
}

/* ── Exam modal ────────────────────────────────────────── */

function openExamModal() {
  const sel = getSelected();
  if (sel.length === 0) {
    alert("Please select at least one applicant first.");
    return;
  }
  document.getElementById("examModalDesc").textContent =
    "Assigning exam schedule to " + sel.length + " applicant(s).";
  document.getElementById("examApplicantInputs").innerHTML = sel
    .map((id) => `<input type="hidden" name="applicant_ids[]" value="${id}">`)
    .join("");
  document.getElementById("examModal").style.display = "block";
}

function clearExam() {
  const sel = getSelected();
  if (sel.length === 0) return;
  if (!confirm("Clear exam schedule for " + sel.length + " applicant(s)?"))
    return;
  document.getElementById("clearExamInputs").innerHTML = sel
    .map((id) => `<input type="hidden" name="applicant_ids[]" value="${id}">`)
    .join("");
  document.getElementById("clearExamForm").submit();
}
