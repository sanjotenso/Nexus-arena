(function () {
    const toastContainer = document.getElementById('toast-container');
    const baseUrl = document.body.dataset.baseUrl || '';

    function showToast(message, type = 'success') {
        if (!toastContainer) return;
        const el = document.createElement('div');
        el.className = `toast ${type}`;
        el.textContent = message;
        toastContainer.appendChild(el);
        setTimeout(() => { el.remove(); }, 3000);
    }

    function api(url, options = {}) {
        return fetch(`${baseUrl}${url}`, {
            headers: { 'Content-Type': 'application/json' },
            ...options,
            body: options.body ? JSON.stringify(options.body) : undefined
        }).then(async r => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok) throw new Error(data.error || `Request failed: ${r.status}`);
            return data;
        });
    }

    const navToggle = document.getElementById('navToggle');
    const navLinks = document.getElementById('navLinks');
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', () => navLinks.classList.toggle('open'));
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => navLinks.classList.remove('open'));
        });
    }

    const authTabs = document.querySelectorAll('.auth-tab');
    const authPanels = {
        login: document.getElementById('auth-login-panel'),
        register: document.getElementById('auth-register')
    };
    function selectAuthTab(name) {
        authTabs.forEach(tab => {
            const selected = tab.dataset.authTab === name;
            tab.classList.toggle('active', selected);
            tab.setAttribute('aria-selected', String(selected));
        });
        Object.entries(authPanels).forEach(([tabName, panel]) => {
            if (panel) panel.classList.toggle('active', tabName === name);
        });
    }

    document.querySelectorAll('[data-auth-tab]').forEach(tab => {
        tab.addEventListener('click', () => selectAuthTab(tab.dataset.authTab));
    });
    if (window.location.hash === '#auth-register') {
        selectAuthTab('register');
        document.getElementById('auth')?.scrollIntoView();
    } else if (window.location.hash === '#auth-login') {
        selectAuthTab('login');
        document.getElementById('auth')?.scrollIntoView();
    }

    window.openModal = function(id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.add('active');
    };
    window.closeModal = function(id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.remove('active');
    };
    document.querySelectorAll('.modal-backdrop').forEach(b => {
        b.addEventListener('click', (e) => e.target.closest('.modal').classList.remove('active'));
    });

    window.switchTab = function(event, id) {
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(id)?.classList.add('active');
        event.currentTarget.classList.add('active');
    };

    const btnRegister = document.getElementById('btnRegister');
    if (btnRegister) {
        btnRegister.addEventListener('click', () => {
            const tid = btnRegister.dataset.tid;
            btnRegister.disabled = true;
            btnRegister.textContent = 'Registering...';
            api(`/api/tournaments/${tid}/register`, { method: 'POST' })
                .then(d => { showToast(d.message); btnRegister.textContent = 'Registered'; })
                .catch(e => { showToast(e.message, 'error'); btnRegister.disabled = false; btnRegister.textContent = 'Register'; });
        });
    }

    const createTeamForm = document.getElementById('createTeamForm');
    if (createTeamForm) {
        createTeamForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const name = document.getElementById('teamName').value.trim();
            const tag = document.getElementById('teamTag').value.trim();
            if (!name || !tag) return showToast('Name and tag are required', 'error');
            api('/api/teams', { method: 'POST', body: { name, tag } })
                .then(d => { showToast(d.message); closeModal('createTeamModal'); setTimeout(() => location.reload(), 600); })
                .catch(e => showToast(e.message, 'error'));
        });
    }

    const btnJoinTeam = document.getElementById('btnJoinTeam');
    if (btnJoinTeam) {
        btnJoinTeam.addEventListener('click', () => {
            const tid = btnJoinTeam.dataset.tid;
            api('/api/teams/join', { method: 'POST', body: { team_id: parseInt(tid) } })
                .then(d => { showToast(d.message); setTimeout(() => location.reload(), 600); })
                .catch(e => showToast(e.message, 'error'));
        });
    }

    const createTournamentForm = document.getElementById('createTournamentForm');
    if (createTournamentForm) {
        createTournamentForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const payload = {
                title: document.getElementById('tTitle').value.trim(),
                game: document.getElementById('tGame').value.trim(),
                start_date: document.getElementById('tStart').value,
                max_teams: parseInt(document.getElementById('tMax').value),
                description: document.getElementById('tDesc').value.trim()
            };
            api('/api/tournaments', { method: 'POST', body: payload })
                .then(d => { showToast(d.message); closeModal('createTournamentModal'); setTimeout(() => location.reload(), 600); })
                .catch(e => showToast(e.message, 'error'));
        });
    }

    window.deleteTournament = function(id) {
        if (!confirm('Delete this tournament?')) return;
        api(`/api/admin/tournaments/${id}`, { method: 'DELETE' })
            .then(d => { showToast(d.message); setTimeout(() => location.reload(), 600); })
            .catch(e => showToast(e.message, 'error'));
    };

    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            let invalid = false;
            form.querySelectorAll('input[required], textarea[required]').forEach(inp => {
                if (!inp.value.trim()) {
                    invalid = true;
                    inp.style.borderColor = 'var(--danger)';
                } else {
                    inp.style.borderColor = '';
                }
            });
            if (invalid) {
                e.preventDefault();
                showToast('Please fill all required fields', 'error');
            }
        });
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(en => { if (en.isIntersecting) { en.target.classList.add('revealed'); } });
    }, { threshold: 0.1 });
    document.querySelectorAll('.card, .feature').forEach(el => observer.observe(el));
})();