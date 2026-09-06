// ============================================
// assets/js/main.js
// ============================================

document.addEventListener('DOMContentLoaded', function () {

    // ----- เมนูมือถือ -----
    const toggle = document.querySelector('.nav-toggle');
    const links = document.querySelector('.nav-links');
    if (toggle && links) {
        toggle.addEventListener('click', () => links.classList.toggle('open'));
    }

    // ----- ระบบสลับแท็บ (Tab switching) -----
    document.querySelectorAll('.tabs').forEach(function (tabGroup) {
        const buttons = tabGroup.querySelectorAll('.tab-btn');
        const panelWrap = tabGroup.nextElementSibling; // .tab-panels ต้องอยู่ถัดจาก .tabs
        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                buttons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const target = btn.getAttribute('data-tab');
                if (panelWrap) {
                    panelWrap.querySelectorAll('.tab-panel').forEach(p => {
                        p.classList.toggle('active', p.id === target);
                    });
                }
            });
        });
    });

    // ----- เพิ่ม/ลบ/แก้ไขจำนวนสินค้าในตะกร้าแบบ AJAX -----
    document.querySelectorAll('.add-to-cart-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = form.querySelector('button');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'กำลังเพิ่ม...';

            fetch('cart-action.php', {
                method: 'POST',
                body: new FormData(form)
            })
            .then(res => res.json())
            .then(data => {
                btn.textContent = data.success ? 'เพิ่มแล้ว ✓' : 'ผิดพลาด';
                const badge = document.querySelector('.cart-badge');
                if (data.success && badge) badge.textContent = data.cart_count;
                if (!data.success) alert(data.message || 'เกิดข้อผิดพลาด');
                setTimeout(() => { btn.textContent = originalText; btn.disabled = false; }, 1200);
            })
            .catch(() => {
                alert('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้');
                btn.disabled = false;
                btn.textContent = originalText;
            });
        });
    });

    // ----- อัปเดตจำนวนสินค้าในหน้าตะกร้า -----
    document.querySelectorAll('.qty-form').forEach(function (form) {
        const input = form.querySelector('.qty-input');
        form.querySelectorAll('.qty-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                let val = parseInt(input.value || '1', 10);
                val = btn.dataset.action === 'inc' ? val + 1 : Math.max(1, val - 1);
                input.value = val;
                form.requestSubmit ? form.requestSubmit() : form.submit();
            });
        });
    });

    // ----- ตรวจสอบความตรงกันของรหัสผ่านฝั่ง client (UX เสริม ไม่ทดแทนการตรวจฝั่งเซิร์ฟเวอร์) -----
    const regForm = document.querySelector('#register-form');
    if (regForm) {
        regForm.addEventListener('submit', function (e) {
            const pass = regForm.querySelector('[name=password]').value;
            const confirm = regForm.querySelector('[name=confirm_password]').value;
            if (pass !== confirm) {
                e.preventDefault();
                alert('รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน');
            }
            if (pass.length < 8) {
                e.preventDefault();
                alert('รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร');
            }
        });
    }

    // ----- ปิด alert อัตโนมัติ -----
    document.querySelectorAll('.alert').forEach(function (a) {
        setTimeout(() => { a.style.transition = 'opacity .5s'; a.style.opacity = '0'; }, 3500);
    });
});
