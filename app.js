'use strict';

// ─── CONFIG ───────────────────────────────────────────────────────────────
// Mengambil base URL dinamis agar aplikasi tetap jalan meskipun nama folder diubah
const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
const API = window.location.origin + basePath + '/api';

// ─── FOOD DATABASE ────────────────────────────────────────────────────────
const FOOD_DB = {
    kurang_sehat: {
        keywords: ['mie instan','mie goreng','mie rebus','mie ayam','indomie','supermi','sarimi','pop mie','sedaap','ramen instan','cup noodle','nissin','gorengan','tahu goreng','tempe goreng','pisang goreng','bakwan','cireng','cimol','batagor','siomay goreng','risol goreng','burger','pizza','hot dog','kentang goreng','french fries','nugget','sosis','kfc','mcdonald','mcdo','jollibee','fried chicken','keripik','chips','chiki','pringles','cheetos','chitato','soda','softdrink','coca cola','pepsi','fanta','sprite','boba','teh manis','es teh manis','donat','donut','cake','kue manis','permen','coklat batang','snickers','kitkat','kornet','sarden kaleng','bacon','ham']
    },
    cukup_sehat: {
        keywords: ['nasi putih','nasi goreng','ayam goreng','soto','bakso','gado-gado','kwetiau','bihun','lontong','ketupat','martabak','siomay','pempek','nasi uduk','nasi kuning','nasi padang','rendang','gulai']
    },
    sehat: {
        keywords: ['salad','sayur','sayuran','bayam','brokoli','wortel','kangkung','sawi','tomat','timun','kacang panjang','buncis','buah','apel','pisang','jeruk','mangga','anggur','melon','semangka','pepaya','jambu','nanas','stroberi','alpukat','ikan bakar','ikan rebus','ikan kukus','ayam rebus','ayam kukus','ayam panggang','dada ayam','telur rebus','telur kukus','tahu kukus','tempe kukus','tempe bakar','tahu rebus','nasi merah','oatmeal','granola','roti gandum','quinoa','jus buah','smoothie','yogurt','susu rendah lemak','teh hijau','sup ayam','sup sayur','bubur ayam','pecel','urap']
    }
};

// ─── AUTH MODULE ──────────────────────────────────────────────────────────
const auth = {
    showPanel(panel) {
        ['login','register'].forEach(p => document.getElementById(`panel-${p}`).classList.toggle('d-none', p !== panel));
        this.clearErrors();
    },
    clearErrors() {
        ['login-error','register-error'].forEach(id => { const el = document.getElementById(id); if (el) { el.textContent=''; el.classList.add('d-none'); } });
    },
    showError(id, msg) {
        const el = document.getElementById(id);
        if (el) { el.textContent = msg; el.classList.remove('d-none'); }
    },
    setLoading(btnId, loading) {
        const btn = document.getElementById(btnId);
        if (!btn) return;
        btn.disabled = loading;
        btn.innerHTML = loading
            ? '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...'
            : (btnId === 'btn-login' ? '<i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Masuk' : '<i class="fa-solid fa-user-plus me-2"></i>Buat Akun');
    },
    async login(e) {
        e.preventDefault();
        const username = document.getElementById('login-username').value.trim().toLowerCase();
        const password = document.getElementById('login-password').value;
        if (!username || !password) { this.showError('login-error','Username dan password wajib diisi.'); return; }
        this.setLoading('btn-login', true);
        try {
            const res = await api.post('/auth/login.php', { username, password });
            if (res.success) {
                sessionStorage.setItem('kosmed_token', res.data.token);
                sessionStorage.setItem('kosmed_name',  res.data.name);
                sessionStorage.setItem('kosmed_username', res.data.username);
                this.enterApp(res.data.name);
            } else {
                this.showError('login-error', res.message || 'Login gagal.');
            }
        } catch { this.showError('login-error','Server tidak dapat dihubungi. Pastikan XAMPP aktif.'); }
        finally { this.setLoading('btn-login', false); }
    },
    async register(e) {
        e.preventDefault();
        const name     = document.getElementById('reg-name').value.trim();
        const username = document.getElementById('reg-username').value.trim().toLowerCase();
        const password = document.getElementById('reg-password').value;
        const confirm  = document.getElementById('reg-confirm').value;
        if (!name||!username||!password) { this.showError('register-error','Semua field wajib diisi.'); return; }
        if (username.length < 3) { this.showError('register-error','Username minimal 3 karakter.'); return; }
        if (password.length < 6) { this.showError('register-error','Password minimal 6 karakter.'); return; }
        if (password !== confirm) { this.showError('register-error','Konfirmasi password tidak cocok.'); return; }
        this.setLoading('btn-register', true);
        try {
            const res = await api.post('/auth/register.php', { name, username, password });
            if (res.success) {
                sessionStorage.setItem('kosmed_token', res.data.token);
                sessionStorage.setItem('kosmed_name',  res.data.name);
                sessionStorage.setItem('kosmed_username', res.data.username);
                
                document.getElementById('form-register').reset();
                this.enterApp(res.data.name);
            } else {
                this.showError('register-error', res.message || 'Registrasi gagal.');
            }
        } catch { this.showError('register-error','Server tidak dapat dihubungi.'); }
        finally { this.setLoading('btn-register', false); }
    },
    enterApp(name) {
        document.getElementById('auth-screen').classList.add('d-none');
        const ws = document.getElementById('welcome-screen');
        ws.classList.remove('d-none');
        const nameEl = document.getElementById('welcome-username');
        if (nameEl) nameEl.textContent = name;
    },
    async logout() {
        if (!confirm('Yakin mau keluar?')) return;
        try { await api.post('/auth/logout.php', {}); } catch {}
        sessionStorage.clear();
        document.getElementById('main-app').classList.add('d-none');
        document.getElementById('welcome-screen').classList.add('d-none');
        document.getElementById('auth-screen').classList.remove('d-none');
        this.showPanel('login');
        document.getElementById('form-login').reset();
        app.resetState();
    },
    togglePass(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const isPass = input.type === 'password';
        input.type = isPass ? 'text' : 'password';
        btn.innerHTML = `<i class="fa-solid fa-eye${isPass?'-slash':''}"></i>`;
    },
    checkSession() {
        const token = sessionStorage.getItem('kosmed_token');
        const name  = sessionStorage.getItem('kosmed_name');
        if (token && name) { this.enterApp(name); return true; }
        return false;
    }
};

// ─── API HELPER ───────────────────────────────────────────────────────────
const api = {
    token() { return sessionStorage.getItem('kosmed_token') || ''; },
    headers() { return { 'Content-Type':'application/json', 'Authorization':'Bearer '+this.token() }; },
    async get(endpoint) {
        const r = await fetch(API+endpoint, { headers: this.headers() });
        return r.json();
    },
    async post(endpoint, body) {
        const r = await fetch(API+endpoint, { method:'POST', headers:this.headers(), body:JSON.stringify(body) });
        return r.json();
    },
    async patch(endpoint, body) {
        const r = await fetch(API+endpoint, { method:'PATCH', headers:this.headers(), body:JSON.stringify(body) });
        return r.json();
    },
    async del(endpoint, body) {
        const r = await fetch(API+endpoint, { method:'DELETE', headers:this.headers(), body:JSON.stringify(body) });
        return r.json();
    }
};

// ─── APP MODULE ───────────────────────────────────────────────────────────
const app = {
    state: {
        obat: [], makanan: [], air: { jumlah:0, target:8 },
        reminder: [], keluhan: [], tidur: [], ringkasan: null,
        keluhanFilter: 'semua'
    },

    resetState() {
        this.state = { obat:[], makanan:[], air:{jumlah:0,target:8}, reminder:[], keluhan:[], tidur:[], ringkasan:null, keluhanFilter:'semua' };
    },

    init() {
        window.addEventListener('scroll', () => {
            const nav = document.getElementById('km-navbar');
            if (nav) nav.classList.toggle('scrolled', window.scrollY > 10);
        });
        auth.checkSession();
        // Set default tanggal untuk form
        const today = new Date().toISOString().split('T')[0];
        ['input-keluhan-tanggal','input-tidur-tanggal'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = today;
        });
        // Preview tidur live
        ['input-jam-tidur','input-jam-bangun'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', () => this.previewTidur());
        });
    },

    async startApp() {
        document.getElementById('welcome-screen').style.opacity = '0';
        document.getElementById('welcome-screen').style.transition = 'opacity .4s';
        await new Promise(r => setTimeout(r, 400));
        document.getElementById('welcome-screen').classList.add('d-none');
        document.getElementById('main-app').classList.remove('d-none');

        const name = sessionStorage.getItem('kosmed_name') || 'User';
        const avatarEl = document.getElementById('navbar-avatar');
        const userEl   = document.getElementById('navbar-username');
        if (avatarEl) avatarEl.textContent = name.charAt(0).toUpperCase();
        if (userEl)   userEl.textContent = name;

        await this.loadAll();
    },

    async loadAll() {
        await Promise.all([
            this.loadObat(), this.loadMakanan(), this.loadAir(),
            this.loadReminder(), this.loadKeluhan(), this.loadTidur()
        ]);
        this.renderAll();
    },

    switchTab(tabId) {
        document.querySelectorAll('.tab-view').forEach(el => el.classList.add('d-none'));
        const tab = document.getElementById(`tab-${tabId}`);
        if (tab) {
            tab.classList.remove('d-none');
            tab.classList.remove('animate-slide-up');
            void tab.offsetWidth;
            tab.classList.add('animate-slide-up');
        }
        document.querySelectorAll('.km-navlink').forEach(el => el.classList.remove('active'));
        const navLink = document.getElementById(`nav-${tabId}`);
        if (navLink) navLink.classList.add('active');
        if (tabId === 'ringkasan') this.loadRingkasan();
        window.scrollTo({ top:0, behavior:'smooth' });
    },

    renderAll() {
        this.renderDashboard();
        this.renderObat();
        this.renderMakanan();
        this.renderAir();
        this.renderReminder();
        this.renderKeluhan();
        this.renderTidur();
        this.renderSmartAlerts();
    },

    // ─── LOAD DATA ─────────────────────────────────────────────
    async loadObat() {
        try { const r = await api.get('/obat/index.php'); if (r.success) this.state.obat = r.data; } catch {}
    },
    async loadMakanan() {
        try { const r = await api.get('/makanan/index.php'); if (r.success) this.state.makanan = r.data; } catch {}
    },
    async loadAir() {
        try { const r = await api.get('/air/today.php'); if (r.success) this.state.air = r.data; } catch {}
    },
    async loadReminder() {
        try { const r = await api.get('/reminder/index.php'); if (r.success) this.state.reminder = r.data; } catch {}
    },
    async loadKeluhan() {
        try { const r = await api.get('/keluhan/index.php'); if (r.success) this.state.keluhan = r.data; } catch {}
    },
    async loadTidur() {
        try { const r = await api.get('/tidur/index.php'); if (r.success) this.state.tidur = r.data; } catch {}
    },
    async loadRingkasan() {
        const el = document.getElementById('skor-number');
        if (el) el.textContent = '...';
        try {
            const r = await api.get('/ringkasan/mingguan.php');
            if (r.success) { this.state.ringkasan = r.data; this.renderRingkasan(); }
        } catch {}
    },

    // ─── DASHBOARD ─────────────────────────────────────────────
    renderDashboard() {
        const { jumlah, target } = this.state.air;
        const setEl = (id,v) => { const e=document.getElementById(id); if(e) e.textContent=v; };
        setEl('dash-air-current', jumlah);
        setEl('dash-air-target', target);
        const bar = document.getElementById('dash-air-progress');
        if (bar) bar.style.width = `${Math.min((jumlah/target)*100,100)}%`;

        let sehat=0, kurang=0;
        this.state.makanan.forEach(m => {
            if (m.kategori==='sehat') sehat++;
            if (m.kategori==='kurang sehat') kurang++;
        });
        setEl('dash-makan-sehat', sehat);
        setEl('dash-makan-buruk', kurang);

        // Tidur terakhir
        if (this.state.tidur.length > 0) {
            const t = this.state.tidur[0];
            const h = Math.floor(t.durasi_menit/60), m = t.durasi_menit%60;
            setEl('dash-tidur-durasi', `${h}j${m>0?` ${m}m`:''}`);
            const s = this.getSleepStatus(t.durasi_menit);
            setEl('dash-tidur-status', s.label);
        }

        // Obat status
        const obatEl = document.getElementById('dash-obat-status');
        if (obatEl) {
            const habis  = this.state.obat.filter(o=>o.stok==0).length;
            const kritis = this.state.obat.filter(o=>o.stok==1).length;
            if (!this.state.obat.length)
                obatEl.innerHTML = `<div class="km-badge-status status-empty"><i class="fa-solid fa-minus me-1"></i>Belum ada data</div>`;
            else if (habis)
                obatEl.innerHTML = `<div class="km-badge-status status-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i>${habis} Habis!</div>`;
            else if (kritis)
                obatEl.innerHTML = `<div class="km-badge-status status-warn"><i class="fa-solid fa-exclamation-circle me-1"></i>${kritis} Kritis</div>`;
            else
                obatEl.innerHTML = `<div class="km-badge-status status-safe"><i class="fa-solid fa-check me-1"></i>Aman</div>`;
        }

        // Reminder ringkas di dashboard
        this.renderDashReminder();
    },

    renderDashReminder() {
        const el = document.getElementById('dash-reminder-list');
        if (!el) return;
        if (!this.state.reminder.length) { el.innerHTML = '<p class="text-muted mb-0" style="font-size:.85rem;">Tidak ada reminder hari ini.</p>'; return; }
        const done  = this.state.reminder.filter(r=>r.is_done==1).length;
        const total = this.state.reminder.length;
        el.innerHTML = `
            <div class="d-flex justify-content-between mb-2">
                <span style="font-size:.82rem;color:var(--text-muted);">${done}/${total} selesai</span>
                <div style="width:80px;height:5px;background:var(--ivory-mid);border-radius:50px;overflow:hidden;align-self:center;">
                    <div style="height:100%;width:${Math.round((done/total)*100)}%;background:var(--color-safe);border-radius:50px;"></div>
                </div>
            </div>
            ${this.state.reminder.slice(0,4).map(r=>`
                <div class="d-flex align-items-center gap-2 py-1">
                    <span style="font-size:.9rem;">${r.ikon}</span>
                    <span style="font-size:.84rem;${r.is_done==1?'text-decoration:line-through;color:var(--text-muted);':''}">${this.escHtml(r.teks)}</span>
                    ${r.is_done==1?'<i class="fa-solid fa-check text-success ms-auto" style="font-size:.75rem;"></i>':''}
                </div>
            `).join('')}`;
    },

    // ─── STOK OBAT ─────────────────────────────────────────────
    async tambahObat(e) {
        e.preventDefault();
        const nama     = document.getElementById('input-obat-nama').value.trim();
        const kegunaan = document.getElementById('input-obat-guna').value.trim();
        const stok     = parseInt(document.getElementById('input-obat-stok').value);
        if (!nama||!kegunaan||isNaN(stok)) return;
        const r = await api.post('/obat/create.php', {nama, kegunaan, stok});
        if (r.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalTambahObat'))?.hide();
            document.getElementById('form-obat').reset();
            document.getElementById('input-obat-stok').value = 5;
            await this.loadObat(); this.renderAll();
            this.showToast(`${nama} ditambahkan!`);
        }
    },
    async ubahStok(id, delta) {
        const r = await api.patch('/obat/update_stok.php', {id, delta});
        if (r.success) { await this.loadObat(); this.renderAll(); }
    },
    async hapusObat(id) {
        if (!confirm('Hapus obat ini?')) return;
        const r = await api.del('/obat/delete.php', {id});
        if (r.success) { await this.loadObat(); this.renderAll(); this.showToast('Obat dihapus.'); }
    },
    editObat(id) {
        const o = this.state.obat.find(x => x.id === id);
        if (!o) return;
        document.getElementById('edit-obat-id').value = o.id;
        document.getElementById('edit-obat-nama').value = o.nama;
        document.getElementById('edit-obat-guna').value = o.kegunaan || o.guna || '';
        new bootstrap.Modal(document.getElementById('modalEditObat')).show();
    },
    async simpanEditObat(e) {
        e.preventDefault();
        const id = document.getElementById('edit-obat-id').value;
        const nama = document.getElementById('edit-obat-nama').value.trim();
        const kegunaan = document.getElementById('edit-obat-guna').value.trim();
        const r = await api.patch('/obat/update.php', {id, nama, kegunaan});
        if (r.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalEditObat'))?.hide();
            await this.loadObat(); this.renderAll();
            this.showToast('Obat diperbarui!');
        }
    },
    renderObat() {
        const list = document.getElementById('obat-list');
        if (!list) return;
        if (!this.state.obat.length) {
            list.innerHTML = `<div class="col-12"><div class="km-card text-center py-5"><i class="fa-solid fa-pills" style="font-size:2.5rem;color:var(--text-muted);opacity:.3;"></i><p class="mt-3 text-muted mb-1">Belum ada data obat.</p></div></div>`;
            return;
        }
        list.innerHTML = this.state.obat.map(o => {
            let statusHtml, cardClass='';
            if (o.stok==0) { statusHtml=`<span class="km-badge-status status-danger" style="font-size:.75rem;padding:.18rem .7rem;"><i class="fa-solid fa-xmark me-1"></i>Habis!</span>`; cardClass='habis'; }
            else if (o.stok<=1) { statusHtml=`<span class="km-badge-status status-warn" style="font-size:.75rem;padding:.18rem .7rem;"><i class="fa-solid fa-exclamation me-1"></i>Kritis</span>`; cardClass='kritis'; }
            else { statusHtml=`<span class="km-badge-status status-safe" style="font-size:.75rem;padding:.18rem .7rem;"><i class="fa-solid fa-check me-1"></i>Aman</span>`; }
            return `<div class="col-md-6 col-lg-4"><div class="obat-card ${cardClass}">
                <div class="d-flex position-absolute top-0 end-0 m-2 gap-1">
                    <button class="btn-hapus-obat" onclick="app.editObat(${o.id})" title="Edit"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn-hapus-obat" onclick="app.hapusObat(${o.id})" title="Hapus"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="obat-nama">${this.escHtml(o.nama)}</div>
                <div class="obat-guna"><i class="fa-solid fa-stethoscope me-1"></i>${this.escHtml(o.kegunaan||o.guna||'')}</div>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <div><div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">Stok</div><div class="obat-stok-number">${o.stok}</div></div>
                    <div class="d-flex flex-column gap-1">
                        <button class="btn-stok" onclick="app.ubahStok(${o.id},1)">+</button>
                        <button class="btn-stok" onclick="app.ubahStok(${o.id},-1)" ${o.stok==0?'disabled':''}>−</button>
                    </div>
                </div>
                <div class="mt-3">${statusHtml}</div>
            </div></div>`;
        }).join('');
    },

    // ─── MAKANAN ───────────────────────────────────────────────
    detectFood(nama) {
        if (!nama||nama.trim().length<2) return {type:null,matched:''};
        const n = nama.toLowerCase().trim();
        for (const kw of FOOD_DB.kurang_sehat.keywords) if (n.includes(kw)) return {type:'kurang_sehat',matched:kw};
        for (const kw of FOOD_DB.cukup_sehat.keywords) if (n.includes(kw)) return {type:'cukup_sehat',matched:kw};
        for (const kw of FOOD_DB.sehat.keywords)       if (n.includes(kw)) return {type:'sehat',matched:kw};
        return {type:null,matched:''};
    },
    onMakananInput(value) {
        const result = this.detectFood(value);
        const box = document.getElementById('food-detect-alert');
        if (!box) return;
        if (!result.type) { box.className='food-detect-alert d-none'; return; }
        const map = {
            kurang_sehat: {cls:'food-detect-unhealthy',icon:'fa-triangle-exclamation',label:'Kurang Sehat'},
            cukup_sehat:  {cls:'food-detect-moderate', icon:'fa-circle-info',         label:'Cukup Sehat'},
            sehat:        {cls:'food-detect-healthy',  icon:'fa-circle-check',        label:'Sehat'}
        };
        const s = map[result.type];
        box.className = `food-detect-alert ${s.cls}`;
        box.innerHTML = `<i class="fa-solid ${s.icon}" style="flex-shrink:0;margin-top:2px;"></i><span>Terdeteksi sebagai <strong>${s.label}</strong> <em style="opacity:.65;">("${result.matched}")</em></span>`;
    },
    async tambahMakanan(e) {
        e.preventDefault();
        const nama  = document.getElementById('input-makanan-nama').value.trim();
        const waktu = document.getElementById('input-makanan-waktu').value;
        if (!nama) return;
        const det = this.detectFood(nama);
        const valMap = {kurang_sehat:'kurang sehat',cukup_sehat:'cukup sehat',sehat:'sehat'};
        const kategori = det.type ? valMap[det.type] : 'cukup sehat';
        const today = new Date().toISOString().split('T')[0];
        const r = await api.post('/makanan/create.php', {nama,kategori,waktu,tanggal:today,auto_detected:det.type?1:0});
        if (r.success) {
            document.getElementById('form-makanan').reset();
            this.onMakananInput('');
            await this.loadMakanan(); this.renderAll();
            this.showToast(`${nama} berhasil dicatat!`);
        }
    },
    async hapusMakanan(id) {
        if (!confirm('Hapus makanan ini?')) return;
        const r = await api.del('/makanan/delete.php', {id});
        if (r.success) { await this.loadMakanan(); this.renderAll(); }
    },
    editMakanan(id) {
        const m = this.state.makanan.find(x => x.id === id);
        if (!m) return;
        document.getElementById('edit-makanan-id').value = m.id;
        document.getElementById('edit-makanan-nama').value = m.nama;
        document.getElementById('edit-makanan-waktu').value = m.waktu;
        document.getElementById('edit-makanan-kategori').value = m.kategori;
        document.getElementById('edit-makanan-tanggal').value = m.tanggal;
        new bootstrap.Modal(document.getElementById('modalEditMakanan')).show();
    },
    async simpanEditMakanan(e) {
        e.preventDefault();
        const id = document.getElementById('edit-makanan-id').value;
        const nama = document.getElementById('edit-makanan-nama').value.trim();
        const waktu = document.getElementById('edit-makanan-waktu').value;
        const kategori = document.getElementById('edit-makanan-kategori').value;
        const tanggal = document.getElementById('edit-makanan-tanggal').value;
        const r = await api.patch('/makanan/update.php', {id, nama, waktu, kategori, tanggal});
        if (r.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalEditMakanan'))?.hide();
            await this.loadMakanan(); this.renderAll();
            this.showToast('Makanan diperbarui!');
        }
    },
    renderMakanan() {
        const list = document.getElementById('makanan-list');
        if (!list) return;
        if (!this.state.makanan.length) { list.innerHTML=`<tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">Belum ada riwayat makanan.</td></tr>`; return; }
        const we = {pagi:'🌅',siang:'☀️',malam:'🌙',snack:'🍪'};
        list.innerHTML = this.state.makanan.slice(0,20).map(m => {
            let badge;
            if (m.kategori==='sehat')        badge=`<span class="badge-sehat">🥗 Sehat</span>`;
            else if (m.kategori==='cukup sehat') badge=`<span class="badge-cukup">🍱 Cukup Sehat</span>`;
            else                             badge=`<span class="badge-kurang">🍔 Kurang Sehat</span>`;
            return `<tr>
                <td style="color:var(--text-muted);font-size:.8rem;">${m.tanggal}</td>
                <td>${we[m.waktu]||''} <span class="text-capitalize">${m.waktu}</span></td>
                <td class="fw-semibold">${this.escHtml(m.nama)}</td>
                <td>${badge}</td>
                <td>
                    <div class="d-flex gap-1 justify-content-end">
                        <button class="btn-hapus-obat" onclick="app.editMakanan(${m.id})" title="Edit"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn-hapus-obat" onclick="app.hapusMakanan(${m.id})" title="Hapus"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                </td>
            </tr>`;
        }).join('');
    },

    // ─── AIR MINUM ─────────────────────────────────────────────
    async tambahAir() {
        const r = await api.post('/air/tambah.php', {});
        if (r.success) { await this.loadAir(); this.renderAll(); }
    },
    async tambahAirDashboard() { await this.tambahAir(); this.showToast('1 gelas air ditambahkan! 💧'); },
    async updateTargetAir() {
        const val = parseInt(document.getElementById('input-target-air').value);
        if (val>0&&val<=20) {
            const r = await api.patch('/air/target.php', {target:val});
            if (r.success) { await this.loadAir(); this.renderAll(); this.showToast(`Target diubah ke ${val} gelas.`); }
        }
    },
    renderAir() {
        const { jumlah, target } = this.state.air;
        const pct = Math.min((jumlah/target)*100,100);
        const setEl = (id,v) => { const e=document.getElementById(id); if(e) e.textContent=v; };
        setEl('air-current',jumlah); setEl('air-target',target);
        const fill=document.getElementById('water-fill'); if(fill) fill.style.height=`${pct}%`;
        const inp=document.getElementById('input-target-air'); if(inp) inp.value=target;
        const st=document.getElementById('air-status-text');
        if (st) { if(jumlah>=target){st.textContent='🎉 Selamat! Target minum hari ini tercapai!';st.classList.add('achieved');}else{st.textContent=`Ayo minum lagi, kurang ${target-jumlah} gelas!`;st.classList.remove('achieved');} }
        const ic=document.getElementById('gelas-icons');
        if (ic) { let h=''; for(let i=1;i<=target;i++) h+=`<span class="gelas-icon ${i<=jumlah?'filled':'unfilled'}"><i class="fa-solid fa-glass-water"></i></span>`; ic.innerHTML=h; }
    },

    // ─── REMINDER ──────────────────────────────────────────────
    setIkon(val, btn, inputId = 'input-reminder-ikon') {
        document.getElementById(inputId).value = val;
        const parent = btn.closest('.ikon-selector');
        parent.querySelectorAll('.ikon-btn').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
    },
    async tambahReminder(e) {
        e.preventDefault();
        const teks = document.getElementById('input-reminder-teks').value.trim();
        const ikon = document.getElementById('input-reminder-ikon').value;
        if (!teks) return;
        const r = await api.post('/reminder/create.php', {teks,ikon});
        if (r.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalTambahReminder'))?.hide();
            document.getElementById('form-reminder').reset();
            document.getElementById('input-reminder-ikon').value = '🔔';
            await this.loadReminder(); this.renderAll();
        }
    },
    async toggleReminder(id) {
        const r = await api.patch('/reminder/toggle.php', {id});
        if (r.success) { await this.loadReminder(); this.renderAll(); }
    },
    async hapusReminder(id) {
        if (!confirm('Hapus reminder ini?')) return;
        const r = await api.del('/reminder/delete.php', {id});
        if (r.success) { await this.loadReminder(); this.renderAll(); }
    },
    editReminder(id) {
        const r = this.state.reminder.find(x => x.id === id);
        if (!r) return;
        document.getElementById('edit-reminder-id').value = r.id;
        document.getElementById('edit-reminder-teks').value = r.teks;
        document.getElementById('edit-reminder-ikon').value = r.ikon;
        
        // Update ikon button state
        const sel = document.getElementById('edit-ikon-selector');
        sel.querySelectorAll('.ikon-btn').forEach(b => {
            b.classList.toggle('active', b.dataset.val === r.ikon);
        });
        
        new bootstrap.Modal(document.getElementById('modalEditReminder')).show();
    },
    async simpanEditReminder(e) {
        e.preventDefault();
        const id = document.getElementById('edit-reminder-id').value;
        const teks = document.getElementById('edit-reminder-teks').value.trim();
        const ikon = document.getElementById('edit-reminder-ikon').value;
        const r = await api.patch('/reminder/update.php', {id, teks, ikon});
        if (r.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalEditReminder'))?.hide();
            await this.loadReminder(); this.renderAll();
            this.showToast('Reminder diperbarui!');
        }
    },
    renderReminder() {
        const list = document.getElementById('reminder-list');
        if (!list) return;
        const done  = this.state.reminder.filter(r=>r.is_done==1).length;
        const total = this.state.reminder.length;
        // Update progress
        const progText = document.getElementById('reminder-progress-text');
        const progBar  = document.getElementById('reminder-progress-bar');
        if (progText) progText.textContent = `${done}/${total} selesai`;
        if (progBar)  progBar.style.width  = total ? `${Math.round((done/total)*100)}%` : '0%';

        if (!total) { list.innerHTML=`<div class="col-12"><div class="km-card text-center py-5"><i class="fa-solid fa-bell" style="font-size:2.5rem;color:var(--text-muted);opacity:.3;"></i><p class="mt-3 text-muted">Belum ada reminder hari ini.</p><p class="text-muted" style="font-size:.85rem;">Klik tombol Tambah untuk menambahkan reminder.</p></div></div>`; return; }

        list.innerHTML = this.state.reminder.map(r => `
            <div class="col-md-6">
                <div class="reminder-card ${r.is_done==1?'done':''}" onclick="app.toggleReminder(${r.id})">
                    <div class="reminder-checkbox">${r.is_done==1?'<i class="fa-solid fa-check" style="font-size:.7rem;"></i>':''}</div>
                    <span class="reminder-ikon">${r.ikon}</span>
                    <span class="reminder-teks">${this.escHtml(r.teks)}</span>
                    <div class="d-flex gap-1 ms-auto" onclick="event.stopPropagation()">
                        <button class="btn-hapus-obat" onclick="app.editReminder(${r.id})" title="Edit"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn-hapus-obat" onclick="app.hapusReminder(${r.id})" title="Hapus"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                </div>
            </div>`).join('');
        this.renderDashReminder();
    },

    // ─── KELUHAN ───────────────────────────────────────────────
    setIntensitas(val) {
        document.getElementById('input-keluhan-intensitas').value = val;
        document.querySelectorAll('.intensitas-btn').forEach(b => {
            b.classList.remove('active-ringan','active-sedang','active-berat');
        });
        document.querySelector(`.intensitas-btn[data-val="${val}"]`)?.classList.add(`active-${val}`);
    },
    onKeluhanChange(val) {
        document.getElementById('keluhan-lainnya-wrap').style.display = val==='Lainnya' ? '' : 'none';
    },
    async tambahKeluhan(e) {
        e.preventDefault();
        let jenis = document.getElementById('input-keluhan-jenis').value;
        if (!jenis) { this.showToast('Pilih jenis keluhan terlebih dahulu!'); return; }
        if (jenis==='Lainnya') jenis = document.getElementById('input-keluhan-lainnya').value.trim() || 'Lainnya';
        const catatan    = document.getElementById('input-keluhan-catatan').value.trim();
        const intensitas = document.getElementById('input-keluhan-intensitas').value;
        const tanggal    = document.getElementById('input-keluhan-tanggal').value;
        const r = await api.post('/keluhan/create.php', {jenis,catatan,intensitas,tanggal});
        if (r.success) {
            document.getElementById('form-keluhan').reset();
            this.setIntensitas('ringan');
            document.getElementById('input-keluhan-tanggal').value = new Date().toISOString().split('T')[0];
            document.getElementById('keluhan-lainnya-wrap').style.display = 'none';
            await this.loadKeluhan(); this.renderAll();
            this.showToast('Keluhan dicatat!');
        }
    },
    async hapusKeluhan(id) {
        if (!confirm('Hapus keluhan ini?')) return;
        const r = await api.del('/keluhan/delete.php', {id});
        if (r.success) { await this.loadKeluhan(); this.renderAll(); }
    },
    editKeluhan(id) {
        const k = this.state.keluhan.find(x => x.id === id);
        if (!k) return;
        document.getElementById('edit-keluhan-id').value = k.id;
        document.getElementById('edit-keluhan-jenis').value = k.jenis;
        document.getElementById('edit-keluhan-intensitas').value = k.intensitas;
        document.getElementById('edit-keluhan-tanggal').value = k.tanggal;
        document.getElementById('edit-keluhan-catatan').value = k.catatan || '';
        new bootstrap.Modal(document.getElementById('modalEditKeluhan')).show();
    },
    async simpanEditKeluhan(e) {
        e.preventDefault();
        const id = document.getElementById('edit-keluhan-id').value;
        const jenis = document.getElementById('edit-keluhan-jenis').value.trim();
        const intensitas = document.getElementById('edit-keluhan-intensitas').value;
        const tanggal = document.getElementById('edit-keluhan-tanggal').value;
        const catatan = document.getElementById('edit-keluhan-catatan').value.trim();
        const r = await api.patch('/keluhan/update.php', {id, jenis, intensitas, tanggal, catatan});
        if (r.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalEditKeluhan'))?.hide();
            await this.loadKeluhan(); this.renderAll();
            this.showToast('Keluhan diperbarui!');
        }
    },
    filterKeluhan(filter, btn) {
        this.state.keluhanFilter = filter;
        document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
        if (btn) btn.classList.add('active');
        this.renderKeluhan();
    },
    renderKeluhan() {
        const el = document.getElementById('keluhan-list');
        if (!el) return;
        let data = this.state.keluhan;
        if (this.state.keluhanFilter !== 'semua') data = data.filter(k=>k.intensitas===this.state.keluhanFilter);
        if (!data.length) { el.innerHTML=`<div class="text-center py-4"><i class="fa-solid fa-notes-medical" style="font-size:2rem;color:var(--text-muted);opacity:.3;"></i><p class="mt-2 text-muted" style="font-size:.88rem;">Tidak ada keluhan.</p></div>`; return; }
        const iMap = {ringan:'🟢',sedang:'🟡',berat:'🔴'};
        el.innerHTML = data.map(k=>`
            <div class="keluhan-card ${k.intensitas}">
                <div style="font-size:1.4rem;flex-shrink:0;">${iMap[k.intensitas]||'❔'}</div>
                <div class="flex-1" style="flex:1;">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="keluhan-jenis">${this.escHtml(k.jenis)}</span>
                        <span class="badge-intensitas badge-${k.intensitas}">${k.intensitas.charAt(0).toUpperCase()+k.intensitas.slice(1)}</span>
                    </div>
                    ${k.catatan?`<div class="keluhan-catatan">${this.escHtml(k.catatan)}</div>`:''}
                    <div class="keluhan-meta"><i class="fa-regular fa-calendar me-1"></i>${k.tanggal}</div>
                </div>
                <div class="d-flex flex-column gap-1 ms-3">
                    <button class="btn-hapus-obat" onclick="app.editKeluhan(${k.id})" title="Edit"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn-hapus-obat" onclick="app.hapusKeluhan(${k.id})" title="Hapus"><i class="fa-solid fa-trash-can"></i></button>
                </div>
            </div>`).join('');
    },

    // ─── TRACKER TIDUR ─────────────────────────────────────────
    getSleepStatus(menit) {
        if (menit < 360) return { cls:'kurang',   badge:'status-tidur-kurang',   label:'😴 Kurang Tidur' };
        if (menit > 540) return { cls:'berlebih', badge:'status-tidur-berlebih', label:'🌙 Tidur Berlebih' };
        return                   { cls:'cukup',   badge:'status-tidur-cukup',    label:'😊 Cukup Tidur' };
    },
    previewTidur() {
        const tidur  = document.getElementById('input-jam-tidur').value;
        const bangun = document.getElementById('input-jam-bangun').value;
        const prev   = document.getElementById('tidur-preview');
        if (!tidur||!bangun||!prev) { if(prev) prev.classList.add('d-none'); return; }
        const menit = this.hitungDurasiMenit(tidur, bangun);
        const h = Math.floor(menit/60), m = menit%60;
        const s = this.getSleepStatus(menit);
        prev.className = 'tidur-preview mb-3';
        prev.innerHTML = `<i class="fa-solid fa-moon me-2"></i>Durasi tidur: <strong>${h} jam ${m} menit</strong> — ${s.label}`;
        prev.classList.remove('d-none');
    },
    hitungDurasiMenit(jamTidur, jamBangun) {
        const [th,tm]=[...jamTidur.split(':').map(Number)];
        const [bh,bm]=[...jamBangun.split(':').map(Number)];
        let tidurMenit  = th*60+tm;
        let bangunMenit = bh*60+bm;
        if (bangunMenit <= tidurMenit) bangunMenit += 24*60; // melewati tengah malam
        return bangunMenit - tidurMenit;
    },
    async tambahTidur(e) {
        e.preventDefault();
        const jam_tidur  = document.getElementById('input-jam-tidur').value;
        const jam_bangun = document.getElementById('input-jam-bangun').value;
        const tanggal    = document.getElementById('input-tidur-tanggal').value;
        const catatan    = document.getElementById('input-tidur-catatan').value.trim();
        if (!jam_tidur||!jam_bangun||!tanggal) return;
        const r = await api.post('/tidur/create.php', {jam_tidur,jam_bangun,tanggal,catatan});
        if (r.success) {
            document.getElementById('form-tidur').reset();
            document.getElementById('input-tidur-tanggal').value = new Date().toISOString().split('T')[0];
            document.getElementById('tidur-preview').classList.add('d-none');
            await this.loadTidur(); this.renderAll();
            this.showToast('Data tidur disimpan!');
        }
    },
    async hapusTidur(id) {
        if (!confirm('Hapus riwayat tidur ini?')) return;
        const r = await api.del('/tidur/delete.php', {id});
        if (r.success) { await this.loadTidur(); this.renderAll(); }
    },
    editTidur(id) {
        const t = this.state.tidur.find(x => x.id === id);
        if (!t) return;
        document.getElementById('edit-tidur-id').value = t.id;
        document.getElementById('edit-tidur-tanggal').value = t.tanggal;
        document.getElementById('edit-tidur-jam-tidur').value = t.jam_tidur.substring(0, 5); // ambil HH:MM
        document.getElementById('edit-tidur-jam-bangun').value = t.jam_bangun.substring(0, 5);
        document.getElementById('edit-tidur-catatan').value = t.catatan || '';
        new bootstrap.Modal(document.getElementById('modalEditTidur')).show();
    },
    async simpanEditTidur(e) {
        e.preventDefault();
        const id = document.getElementById('edit-tidur-id').value;
        const tanggal = document.getElementById('edit-tidur-tanggal').value;
        const jam_tidur = document.getElementById('edit-tidur-jam-tidur').value;
        const jam_bangun = document.getElementById('edit-tidur-jam-bangun').value;
        const catatan = document.getElementById('edit-tidur-catatan').value.trim();
        const r = await api.patch('/tidur/update.php', {id, tanggal, jam_tidur, jam_bangun, catatan});
        if (r.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalEditTidur'))?.hide();
            await this.loadTidur(); this.renderAll();
            this.showToast('Data tidur diperbarui!');
        }
    },
    renderTidur() {
        const list = document.getElementById('tidur-list');
        if (!list) return;
        if (!this.state.tidur.length) { list.innerHTML=`<div class="text-center py-5"><i class="fa-solid fa-moon" style="font-size:2.5rem;color:var(--text-muted);opacity:.3;"></i><p class="mt-3 text-muted">Belum ada riwayat tidur.</p></div>`; return; }
        // Statistik
        const durations = this.state.tidur.map(t=>parseInt(t.durasi_menit)||0);
        const avg  = durations.length ? Math.round(durations.reduce((a,b)=>a+b,0)/durations.length) : 0;
        const max  = Math.max(...durations);
        const min  = Math.min(...durations);
        const fmt  = m => m ? `${Math.floor(m/60)}j ${m%60}m` : '—';
        const setEl=(id,v)=>{const e=document.getElementById(id);if(e)e.textContent=v;};
        setEl('tidur-avg',fmt(avg)); setEl('tidur-max',fmt(max)); setEl('tidur-min',fmt(min));
        // Update dash
        const dEl=document.getElementById('dash-tidur-durasi');
        if(dEl&&this.state.tidur[0]) { const t=this.state.tidur[0]; const h=Math.floor(t.durasi_menit/60),m=t.durasi_menit%60; dEl.textContent=`${h}j${m>0?` ${m}m`:''}`; }

        list.innerHTML = this.state.tidur.slice(0,14).map(t => {
            const h = Math.floor(t.durasi_menit/60), m = t.durasi_menit%60;
            const s = this.getSleepStatus(t.durasi_menit);
            return `<div class="tidur-card ${s.cls}">
                <div style="font-size:1.8rem;flex-shrink:0;">🌙</div>
                <div style="flex:1;">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="tidur-durasi">${h}j ${m}m</span>
                        <span class="tidur-status-badge ${s.badge}">${s.label}</span>
                    </div>
                    <div class="tidur-jam"><i class="fa-solid fa-moon me-1"></i>${t.jam_tidur} → <i class="fa-solid fa-sun me-1"></i>${t.jam_bangun}</div>
                    ${t.catatan?`<div style="font-size:.78rem;color:var(--text-muted);margin-top:.2rem;">${this.escHtml(t.catatan)}</div>`:''}
                    <div style="font-size:.76rem;color:var(--text-muted);margin-top:.2rem;"><i class="fa-regular fa-calendar me-1"></i>${t.tanggal}</div>
                </div>
                <div class="d-flex flex-column gap-1 ms-3">
                    <button class="btn-hapus-obat" onclick="app.editTidur(${t.id})" title="Edit"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn-hapus-obat" onclick="app.hapusTidur(${t.id})" title="Hapus"><i class="fa-solid fa-trash-can"></i></button>
                </div>
            </div>`;
        }).join('');
    },

    // ─── RINGKASAN MINGGUAN ────────────────────────────────────
    renderRingkasan() {
        const d = this.state.ringkasan;
        if (!d) return;
        const setEl=(id,v)=>{const e=document.getElementById(id);if(e)e.textContent=v;};
        // Stats
        setEl('ring-total-air',     d.total_air||0);
        setEl('ring-makan-sehat',   d.total_makan_sehat||0);
        setEl('ring-total-keluhan', d.total_keluhan||0);
        const avgJam = d.rata_tidur_menit ? `${Math.floor(d.rata_tidur_menit/60)}j ${Math.round(d.rata_tidur_menit%60)}m` : '—';
        setEl('ring-avg-tidur', avgJam);
        // Skor
        const skor = d.skor_kesehatan || 0;
        const skorEl = document.getElementById('skor-number');
        if (skorEl) skorEl.textContent = Math.round(skor);
        const arc = document.getElementById('skor-arc');
        if (arc) { const offset = 314 - (314 * skor/100); arc.style.strokeDashoffset = offset; }
        let skorLabel = skor>=80?'🌟 Luar Biasa!':skor>=60?'😊 Cukup Baik':skor>=40?'⚠️ Perlu Perbaikan':'🔴 Perlu Perhatian';
        setEl('skor-label', skorLabel);
        // Bar chart air
        this.renderBarChart('chart-air', d.detail_air||[], item=>({val:item.jumlah,max:item.target||8,label:this.shortDate(item.tanggal),cls:'bar-air',valLabel:`${item.jumlah}gl`}));
        // Bar chart tidur
        const maxTidur = Math.max(...(d.detail_tidur||[]).map(t=>t.durasi_menit||0), 480);
        this.renderBarChart('chart-tidur', d.detail_tidur||[], item=>{
            const h=Math.floor((item.durasi_menit||0)/60),m=(item.durasi_menit||0)%60;
            return {val:item.durasi_menit||0,max:maxTidur,label:this.shortDate(item.tanggal),cls:'bar-tidur',valLabel:item.durasi_menit?`${h}j${m>0?m+'m':''}`:'-'};
        });
    },
    renderBarChart(containerId, data, mapper) {
        const el = document.getElementById(containerId);
        if (!el||!data.length) { if(el) el.innerHTML='<p class="text-muted" style="font-size:.82rem;">Belum ada data.</p>'; return; }
        el.innerHTML = data.map(item=>{
            const {val,max,label,cls,valLabel} = mapper(item);
            const pct = max>0?Math.max((val/max)*100,0):0;
            return `<div class="bar-item">
                <div class="bar-val">${valLabel}</div>
                <div class="bar-fill ${cls}" style="height:${Math.round(pct)}%;"></div>
                <div class="bar-label">${label}</div>
            </div>`;
        }).join('');
    },
    shortDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        return `${d.getDate()}/${d.getMonth()+1}`;
    },

    // ─── SMART ALERTS ──────────────────────────────────────────
    renderSmartAlerts() {
        const container = document.getElementById('alerts-container');
        if (!container) return;
        const alerts = [];
        this.state.obat.forEach(o=>{
            if (o.stok==0) alerts.push({type:'danger',icon:'fa-triangle-exclamation',text:`Stok <strong>${this.escHtml(o.nama)}</strong> habis. Segera beli!`});
            else if (o.stok==1) alerts.push({type:'warning',icon:'fa-exclamation-circle',text:`Stok <strong>${this.escHtml(o.nama)}</strong> tinggal 1.`});
        });
        const {jumlah,target} = this.state.air;
        if (jumlah<target) alerts.push({type:'info',icon:'fa-droplet',text:`Target air minum belum tercapai. Kurang <strong>${target-jumlah} gelas</strong>!`});
        const mieCount = this.state.makanan.filter(m=>['mie instan','indomie','mie goreng','mie rebus'].some(k=>m.nama.toLowerCase().includes(k))).length;
        if (mieCount>=3) alerts.push({type:'warning',icon:'fa-bowl-rice',text:`Kamu sudah makan mie instan <strong>${mieCount} kali</strong>. Kurangi ya!`});
        const beratCount = this.state.keluhan.filter(k=>k.intensitas==='berat').length;
        if (beratCount>0) alerts.push({type:'danger',icon:'fa-notes-medical',text:`Kamu punya <strong>${beratCount} keluhan berat</strong>. Jangan abaikan kesehatanmu!`});
        if (!alerts.length) { container.innerHTML=''; return; }
        const ic={danger:'text-danger',warning:'text-warning',info:'text-info'};
        container.innerHTML = alerts.map(a=>`<div class="km-alert km-alert-${a.type}"><div class="km-alert-icon ${ic[a.type]}"><i class="fa-solid ${a.icon}"></i></div><div class="km-alert-text">${a.text}</div></div>`).join('');
    },

    // ─── TOAST ─────────────────────────────────────────────────
    showToast(msg) {
        const el=document.getElementById('km-toast'),body=document.getElementById('toast-body');
        if(!el||!body) return;
        body.textContent=msg;
        new bootstrap.Toast(el,{delay:2500}).show();
    },

    // ─── HELPERS ───────────────────────────────────────────────
    escHtml(str) {
        if (!str) return '';
        const d=document.createElement('div');
        d.appendChild(document.createTextNode(str));
        return d.innerHTML;
    }
};

// ─── BOOT ─────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => app.init());
