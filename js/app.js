// GO-FITNESS - GLOBAL JS (no CDN)

// Loader
window.addEventListener('load', () => {
  const loader = document.getElementById('gfLoader');
  if (!loader) return;
  loader.style.opacity = '0';
  loader.style.transition = 'opacity .2s ease';
  setTimeout(() => loader.remove(), 220);
});

// Sidebar collapse + mobile open
document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('gfSidebar');
  const main = document.getElementById('gfMain');
  const btnToggle = document.getElementById('btnSidebarToggle');
  const btnMobile = document.getElementById('btnSidebarMobile');

  // Persist state
  const saved = localStorage.getItem('gf_sidebar') || 'expanded';
  if (sidebar && main && saved === 'collapsed') {
    sidebar.classList.add('is-collapsed');
    main.classList.add('is-collapsed');
  }

  if (btnToggle && sidebar && main) {
    btnToggle.addEventListener('click', () => {
      sidebar.classList.toggle('is-collapsed');
      main.classList.toggle('is-collapsed');
      localStorage.setItem('gf_sidebar', sidebar.classList.contains('is-collapsed') ? 'collapsed' : 'expanded');
    });
  }

  if (btnMobile && sidebar) {
    btnMobile.addEventListener('click', () => {
      sidebar.classList.toggle('is-open');
    });
  }

  // SweetAlert: confirm before delete (forms marked .gf-delete-form)
  document.querySelectorAll('form.gf-delete-form').forEach((form) => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (typeof Swal === 'undefined') {
        // fallback
        if (confirm(form.dataset.title || 'Supprimer ?')) form.submit();
        return;
      }

      const res = await Swal.fire({
        title: form.dataset.title || 'Supprimer ?',
        text: form.dataset.text || 'Cette action est irréversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler',
        reverseButtons: true,
      });

      if (res.isConfirmed) form.submit();
    });
  });

  // FullCalendar (Planning)
  const calEl = document.getElementById('gfCalendar');
  if (calEl && typeof FullCalendar !== 'undefined') {
    const csrf = calEl.dataset.csrf || (window.GF && GF.csrf) || '';

    const modalEl = document.getElementById('eventModal');
    const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

    const pick = (id) => document.getElementById(id);
    const evTitle = pick('evTitle');
    const evStart = pick('evStart');
    const evEnd = pick('evEnd');
    const evDesc = pick('evDesc');
    const evImg = pick('evImg');

    const btnNew = document.getElementById('btnNewEvent');
    const btnSave = document.getElementById('btnSaveEvent');

    const calendar = new FullCalendar.Calendar(calEl, {
      initialView: 'dayGridMonth',
      height: 'auto',
      themeSystem: 'standard',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
      },
      nowIndicator: true,
      navLinks: true,
      selectable: true,
      events: (info, success, failure) => {
        const url = `${GF.baseUrl}/planning/events?start=${encodeURIComponent(info.startStr)}&end=${encodeURIComponent(info.endStr)}`;
        fetch(url, { credentials: 'same-origin' })
          .then(r => r.json())
          .then(success)
          .catch(failure);
      },
      dateClick: (arg) => {
        if (!modal) return;
        if (evTitle) evTitle.value = '';
        if (evDesc) evDesc.value = '';
        if (evImg) evImg.value = '';
        // default start/end
        const d = arg.date;
        const pad = (n) => String(n).padStart(2, '0');
        const toLocal = (dt) => `${dt.getFullYear()}-${pad(dt.getMonth()+1)}-${pad(dt.getDate())}T${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
        const s = new Date(d);
        s.setHours(8,0,0,0);
        const e = new Date(d);
        e.setHours(9,0,0,0);
        if (evStart) evStart.value = toLocal(s);
        if (evEnd) evEnd.value = toLocal(e);
        modal.show();
      },
      eventClick: async (info) => {
        const p = info.event.extendedProps || {};
        if (typeof Swal === 'undefined') return;

        const html = `
          <div class="text-start">
            <div class="fw-semibold mb-1">${info.event.title}</div>
            <div class="small text-muted mb-2">${info.event.start?.toLocaleString() || ''} → ${info.event.end?.toLocaleString() || ''}</div>
            ${p.description ? `<div class="mb-2">${p.description}</div>` : ''}
            ${p.image_url ? `<img src="${p.image_url}" alt="" style="max-width:100%;border-radius:12px;" />` : ''}
          </div>
        `;

        const res = await Swal.fire({
          title: 'Activité',
          html,
          showCancelButton: true,
          showDenyButton: false,
          confirmButtonText: 'Supprimer',
          cancelButtonText: 'Fermer',
          icon: 'info'
        });

        if (!res.isConfirmed) return;

        const delRes = await fetch(`${GF.baseUrl}/planning/events/delete`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          credentials: 'same-origin',
          body: new URLSearchParams({ _csrf: csrf, id: String(info.event.id) }).toString()
        }).then(r => r.json()).catch(() => ({ ok: false }));

        if (delRes.ok) {
          info.event.remove();
          Swal.fire({ icon: 'success', title: 'Supprimé', timer: 1200, showConfirmButton: false });
        }
      }
    });

    calendar.render();

    if (btnNew && modal) {
      btnNew.addEventListener('click', () => {
        if (evTitle) evTitle.value = '';
        if (evDesc) evDesc.value = '';
        if (evImg) evImg.value = '';
        modal.show();
      });
    }

    if (btnSave && modal) {
      btnSave.addEventListener('click', async () => {
        const payload = {
          _csrf: csrf,
          titre: evTitle ? evTitle.value.trim() : '',
          start_at: evStart ? evStart.value : '',
          end_at: evEnd ? evEnd.value : '',
          description: evDesc ? evDesc.value : '',
          image_url: evImg ? evImg.value : '',
        };

        if (!payload.titre || !payload.start_at || !payload.end_at) {
          if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', title: 'Champs requis', text: 'Titre, début et fin sont obligatoires.' });
          return;
        }

        const res = await fetch(`${GF.baseUrl}/planning/events/create`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          credentials: 'same-origin',
          body: new URLSearchParams(payload).toString()
        }).then(r => r.json()).catch(() => ({ ok: false }));

        if (!res.ok) {
          if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Erreur', text: res.error || 'Impossible de créer.' });
          return;
        }

        modal.hide();
        calendar.refetchEvents();
        if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'Ajouté', timer: 1200, showConfirmButton: false });
      });
    }
  }
});

// GO-FITNESS v7: notifications auto-hide, recherche dynamique, actions temporaires
window.addEventListener('DOMContentLoaded', () => {
  // Recherche progressive locale: ne soumet plus le formulaire à chaque lettre.
  document.querySelectorAll('.gf-client-search, .gf-live-search').forEach(input => {
    input.addEventListener('input', () => {
      const card = input.closest('.card') || document;
      const q = input.value.toLowerCase().trim();
      const rows = card.querySelectorAll('table tbody tr');
      rows.forEach(row => {
        if (row.children.length <= 1) return;
        row.style.display = (!q || row.textContent.toLowerCase().includes(q)) ? '' : 'none';
      });
    });
  });

  // Notifications temporaires: 3 secondes puis suppression réelle du DOM.
  const autoHideNotice = (notice) => {
    if (!notice || notice.dataset.gfAutoHideBound === '1') return;
    notice.dataset.gfAutoHideBound = '1';
    const delay = parseInt(notice.dataset.autoHide || '3000', 10);
    setTimeout(() => {
      notice.classList.add('gf-hide');
      setTimeout(() => notice.remove(), 350);
    }, Number.isFinite(delay) ? delay : 3000);
  };
  document.querySelectorAll('.gf-temp-notice,[data-auto-hide]').forEach(autoHideNotice);

  // Actions modifier/supprimer: cachées par défaut, visibles sur clic ou survol de la ligne, puis disparaissent après 3 secondes.
  document.querySelectorAll('.gf-action-row').forEach(row => {
    const actions = row.querySelector('.gf-row-actions');
    const btn = row.querySelector('.gf-options-btn');
    if (!actions) return;
    let timer = null;
    const hide = () => {
      actions.classList.remove('is-visible');
      row.classList.remove('gf-action-open');
    };
    const show = () => {
      document.querySelectorAll('.gf-row-actions.is-visible').forEach(a => {
        if (a !== actions) a.classList.remove('is-visible');
      });
      document.querySelectorAll('.gf-action-row.gf-action-open').forEach(r => {
        if (r !== row) r.classList.remove('gf-action-open');
      });
      actions.classList.add('is-visible');
      row.classList.add('gf-action-open');
      clearTimeout(timer);
      timer = setTimeout(hide, 3000);
    };
    row.addEventListener('mouseenter', show);
    row.addEventListener('click', (e) => {
      if (e.target.closest('a,button,form,input,select,textarea')) return;
      show();
    });
    if (btn) btn.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); show(); });
    actions.addEventListener('click', () => clearTimeout(timer));
  });

  // Confirmation déconnexion oui/non.
  document.querySelectorAll('a[href$="/logout"]').forEach(a => {
    if (a.dataset.gfLogoutBound === '1') return;
    a.dataset.gfLogoutBound = '1';
    a.addEventListener('click', (e) => {
      if (!confirm('Voulez-vous vraiment vous déconnecter ?')) e.preventDefault();
    });
  });
});


// GO-FITNESS v9 - fallback robuste pour masquer Modifier/Supprimer après 3 secondes
document.addEventListener('DOMContentLoaded', function(){
  function gfHide(row){
    if(!row) return;
    var actions = row.querySelector('.gf-row-actions');
    if(actions) actions.classList.remove('is-visible');
    row.classList.remove('gf-action-open');
  }
  function gfShow(row){
    var actions = row.querySelector('.gf-row-actions');
    if(!actions) return;
    document.querySelectorAll('.gf-action-row').forEach(function(other){ if(other !== row) gfHide(other); });
    actions.classList.add('is-visible');
    row.classList.add('gf-action-open');
    clearTimeout(row._gfActionTimer);
    row._gfActionTimer = setTimeout(function(){ gfHide(row); }, 3000);
  }
  document.querySelectorAll('.gf-action-row').forEach(function(row){
    var actions = row.querySelector('.gf-row-actions');
    if(!actions) return;
    gfHide(row);
    row.addEventListener('mouseenter', function(){ gfShow(row); });
    row.addEventListener('click', function(e){
      if(e.target.closest('.gf-row-actions a,.gf-row-actions button,.gf-row-actions input,.gf-row-actions form')) return;
      gfShow(row);
    });
    var btn = row.querySelector('.gf-options-btn');
    if(btn) btn.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); gfShow(row); });
    actions.addEventListener('click', function(){ clearTimeout(row._gfActionTimer); row._gfActionTimer=setTimeout(function(){ gfHide(row); }, 3000); });
  });
});


// GO-FITNESS v10 - affichage stable des actions Modifier/Supprimer sans bouton trois points
document.addEventListener('DOMContentLoaded', function(){
  function hide(row){
    if(!row) return;
    const actions = row.querySelector('.gf-row-actions');
    if(actions) actions.classList.remove('is-visible');
    row.classList.remove('gf-action-open');
  }
  function show(row){
    const actions = row.querySelector('.gf-row-actions');
    if(!actions) return;
    document.querySelectorAll('.gf-action-row').forEach(other => { if(other !== row) hide(other); });
    actions.classList.add('is-visible');
    row.classList.add('gf-action-open');
    clearTimeout(row._gfActionTimerV10);
    row._gfActionTimerV10 = setTimeout(() => hide(row), 3000);
  }
  document.querySelectorAll('.gf-action-row').forEach(row => {
    const actions = row.querySelector('.gf-row-actions');
    if(!actions) return;
    hide(row);
    row.addEventListener('mouseenter', () => show(row));
    row.addEventListener('click', (e) => {
      if(e.target.closest('.gf-row-actions a,.gf-row-actions button,.gf-row-actions input,.gf-row-actions form')) return;
      show(row);
    });
    actions.addEventListener('mouseenter', () => { clearTimeout(row._gfActionTimerV10); row._gfActionTimerV10 = setTimeout(() => hide(row), 3000); });
    actions.addEventListener('click', () => { clearTimeout(row._gfActionTimerV10); row._gfActionTimerV10 = setTimeout(() => hide(row), 3000); });
  });
});

// GO-FITNESS v15 - recherche instantanée dashboard gérant sans refresh page
(function(){
  function applyDashboardFilters(form){
    if(!form) return;
    const card = form.closest('.gf-dashboard-list-card') || document;
    const q = (form.querySelector('[name="q"]')?.value || '').toLowerCase().trim();
    const statut = form.querySelector('[name="statut"]')?.value || '';
    const op = form.querySelector('[name="days_op"]')?.value || '';
    const daysRaw = form.querySelector('[name="days"]')?.value || '';
    const daysVal = daysRaw === '' ? null : Number(daysRaw);
    let visible = 0;
    card.querySelectorAll('tbody tr[data-days]').forEach(row => {
      const txt = row.textContent.toLowerCase();
      const rowStatut = row.dataset.statut || '';
      const d = Number(row.dataset.days || 0);
      let ok = true;
      if(q && !txt.includes(q)) ok = false;
      if(statut && rowStatut !== statut) ok = false;
      if(ok && op && daysVal !== null && Number.isFinite(daysVal)){
        if(op === '<') ok = d < daysVal;
        else if(op === '<=') ok = d <= daysVal;
        else if(op === '>') ok = d > daysVal;
        else if(op === '>=') ok = d >= daysVal;
        else if(op === '=') ok = d === daysVal;
      }
      row.style.display = ok ? '' : 'none';
      if(ok) visible++;
    });
    const count = document.getElementById('gfDashboardVisibleCount');
    if(count) count.textContent = String(visible);
  }
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.gf-dashboard-live-filters').forEach(form => {
      form.querySelectorAll('.gf-dashboard-live-input').forEach(input => {
        input.addEventListener('input', () => applyDashboardFilters(form));
        input.addEventListener('change', () => applyDashboardFilters(form));
      });
      form.addEventListener('keydown', e => {
        if(e.key === 'Enter' && e.target.matches('input')){
          e.preventDefault();
          applyDashboardFilters(form);
        }
      });
      applyDashboardFilters(form);
    });
  });
})();


// GO-FITNESS v33 - recherche instantanée robuste pour tableaux Admin/Gérant
(function(){
  function norm(v){return String(v||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim();}
  function scopeFor(input){
    if(input.dataset.liveTarget){
      const el=document.querySelector(input.dataset.liveTarget);
      if(el) return el;
    }
    const form=input.closest('form');
    if(form){
      let node=form.nextElementSibling;
      while(node){
        if(node.querySelector && node.querySelector('table tbody tr')) return node;
        node=node.nextElementSibling;
      }
    }
    return input.closest('.card') || document;
  }
  function apply(input){
    const scope=scopeFor(input);
    const q=norm(input.value);
    const rows=scope.querySelectorAll('table tbody tr');
    rows.forEach(row=>{
      if(row.children.length<=1) return;
      const hay=norm((row.dataset.filter||'')+' '+row.textContent);
      row.style.display=(!q || hay.includes(q))?'':'none';
    });
  }
  document.addEventListener('DOMContentLoaded',()=>{
    document.querySelectorAll('.gf-live-search,.gf-admin-instant-search').forEach(input=>{
      if(input.dataset.gfV33Bound==='1') return;
      input.dataset.gfV33Bound='1';
      input.addEventListener('input',()=>apply(input));
      input.addEventListener('search',()=>apply(input));
      apply(input);
    });
  });
})();
