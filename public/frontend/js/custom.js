// #################### SEARCH ####################
document.addEventListener('DOMContentLoaded', () => {
  const modalEl = document.getElementById('searchModalMain');
  const modalInput = document.getElementById('searchMainInput');
  const modalSubmit = document.getElementById('searchMainSubmit');
  const searchButtons = document.querySelectorAll('.search-main-button');

  if (!modalEl || !modalInput || !modalSubmit) return;

  const searchModal = new bootstrap.Modal(modalEl);

  // เปิด modal เมื่อคลิกปุ่มค้นหา (รองรับทั้ง mobile และ desktop)
  searchButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      searchModal.show();

      setTimeout(() => modalInput.focus(), 300);
    });
  });

  modalSubmit.addEventListener('click', () => {
    const query = modalInput.value.trim();
    if (query) {
      console.log('ค้นหา:', query);
      searchModal.hide();
    }
  });

  modalInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      modalSubmit.click();
    }
  });

  // #################### Register ####################
  // แสดง input อื่นๆ โปรดระบุ
  const affiliationSelect = document.getElementById('affiliation');
  const otherInputGroup = document.getElementById('otherInputGroup');
  const otherInput = document.getElementById('otherInput');

  // ตรวจสอบว่ามี element ก่อนเพิ่ม event listener
  if (affiliationSelect && otherInputGroup && otherInput) {
    affiliationSelect.addEventListener('change', function () {
      if (this.value === 'other') {
        otherInputGroup.style.display = 'block';
        otherInput.required = true;
      } else {
        otherInputGroup.style.display = 'none';
        otherInput.required = false;
        otherInput.value = ''; // ล้างค่าที่กรอกไว้
      }
    });

    // ตั้งค่าสถานะเริ่มต้นเมื่อหน้าโหลด
    if (affiliationSelect.value === 'other') {
      otherInputGroup.style.display = 'block';
      otherInput.required = true;
    }
  }

  // เพิ่มช่วงวัน-เวลาทำการ
  const addButton = document.getElementById('addWorkingHour');
  const container = document.getElementById('workingHoursContainer');

  if (addButton && container) {
    addButton.addEventListener('click', function() {
      const newItem = document.createElement('div');
      newItem.classList.add('row', 'g-2', 'align-items-center', 'mb-2', 'working-hours-item');
      newItem.innerHTML = `
        <div class="col-md-4">
          <select class="form-select" name="day[]">
            <option value="จันทร์">จันทร์</option>
            <option value="อังคาร">อังคาร</option>
            <option value="พุธ">พุธ</option>
            <option value="พฤหัสบดี">พฤหัสบดี</option>
            <option value="ศุกร์">ศุกร์</option>
            <option value="เสาร์">เสาร์</option>
            <option value="อาทิตย์">อาทิตย์</option>
          </select>
        </div>
        <div class="col-md-3">
          <input type="time" class="form-control" name="startTime[]" required>
        </div>
        <div class="col-md-3">
          <input type="time" class="form-control" name="endTime[]" required>
        </div>
        <div class="col-md-2">
          <button type="button" class="btn btn-danger btn-remove">ลบ</button>
        </div>
      `;
      container.appendChild(newItem);
    });

    // Event delegation สำหรับปุ่มลบ
    container.addEventListener('click', function(e) {
      if(e.target && e.target.classList.contains('btn-remove')) {
        e.target.closest('.working-hours-item').remove();
      }
    });
  }

  // ================= Cookie Consent =================
  const banner = document.getElementById('ddc-cookie-banner');
  const acceptBtn = document.getElementById('ddc-cookie-accept');

  if (banner && acceptBtn) {
    const hasConsent = localStorage.getItem('ddcCookieConsent') === 'true';
    console.log('Cookie consent status:', hasConsent);
    
    if (hasConsent) {
      banner.style.display = 'none';
    }

    acceptBtn.addEventListener('click', function() {
      console.log('Accept button clicked');
      localStorage.setItem('ddcCookieConsent', 'true');
      banner.style.display = 'none';
      console.log('Cookie consent saved:', localStorage.getItem('ddcCookieConsent'));
    });
  }


});