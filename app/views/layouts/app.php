<?php
// Variables attendues:
// $title (string), $content (callable), $active (string), $user (array|null)
// $baseUrl (string), $flash (array|null), $csrf (string)
$baseUrl = $baseUrl ?? rtrim((string)(defined('BASE_URL') ? BASE_URL : ''), '/');
$userRole = strtolower(trim((string)($user['role'] ?? '')));
$extraCss = is_array($extraCss ?? null) ? $extraCss : [];
$extraJs  = is_array($extraJs  ?? null) ? $extraJs  : [];
if (!isset($mainClass) || trim((string)$mainClass) === '') {
    $mainClass = 'gf-main-white';
}
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'GO-FITNESS') ?></title>

    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/vendor/icons-1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/css/app.css?v=29">
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/css/style.css?v=35">
    <style>
        .gf-brand-logo-box{
            width:56px;height:56px;border-radius:14px;background:#fff;
            display:flex;align-items:center;justify-content:center;padding:5px;
            box-shadow:0 6px 18px rgba(0,0,0,.22);
            flex:0 0 auto;
        }
        .gf-brand-logo-box img{max-width:100%;max-height:100%;object-fit:contain;display:block;}
        .gf-sidebar.is-collapsed .gf-brand-logo-box{width:44px;height:44px;border-radius:12px;}
        .gf-brand-title{color:#fff!important;text-shadow:none!important;}
        .gf-nav a .gf-ico i{font-size:18px;}
    </style>
    <?php foreach ($extraCss as $href): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl . $href) ?>">
    <?php endforeach; ?>

    <style>
      /* GO-FITNESS v9: boutons Modifier/Supprimer cachés dès le chargement */
      .gf-row-actions{display:none!important;opacity:0!important;visibility:hidden!important;pointer-events:none!important;transform:translateY(-2px)!important;transition:opacity .18s ease,transform .18s ease,visibility .18s ease!important;}
      .gf-row-actions.is-visible{display:inline-flex!important;opacity:1!important;visibility:visible!important;pointer-events:auto!important;transform:translateY(0)!important;}
      .gf-action-row{cursor:pointer;}
      .gf-action-row.gf-action-open>*{background-color:#fff7ed!important;}
    </style>

    <style>
      /* GO-FITNESS v10: actions sans ⋮ et sans mouvement de ligne */
      .gf-options-btn{display:none!important;}
      .gf-action-header,.gf-action-cell{width:210px!important;min-width:210px!important;max-width:210px!important;white-space:nowrap!important;}
      .gf-row-actions{display:inline-flex!important;gap:.35rem!important;align-items:center!important;min-width:190px!important;min-height:32px!important;opacity:0!important;visibility:hidden!important;pointer-events:none!important;transform:none!important;transition:opacity .18s ease,visibility .18s ease!important;margin-top:0!important;}
      .gf-row-actions.is-visible{opacity:1!important;visibility:visible!important;pointer-events:auto!important;}
      .gf-row-actions .btn{min-width:78px!important;}
    </style>
</head>

<body>
    <div class="gf-loader" id="gfLoader">
        <div class="text-center">
            <div class="gf-spinner mx-auto mb-3"></div>
            <div class="small gf-muted">Chargement…</div>
        </div>
    </div>

    <div class="gf-app">
        <aside class="gf-sidebar" id="gfSidebar">
            <div class="gf-brand">
                <div class="gf-brand-logo-box">
                    <img src="<?= htmlspecialchars($baseUrl) ?>/assets/images/logo.png" alt="GO-FITNESS">
                </div>
                <div>
                    <div class="gf-brand-title gf-text"><?= $userRole === 'admin' ? 'Admin' : 'Gérant' ?></div>
                    <div class="gf-brand-sub"><?= $userRole === 'admin' ? 'Admin Dashboard' : 'Espace Gérant' ?></div>
                </div>
            </div>

            <nav class="gf-nav">
                <?php if ($userRole === 'admin'): ?>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/dashboard" class="<?= ($active ?? '') === 'admin_dashboard' ? 'active' : '' ?>">
                        <span class="gf-ico"><i class="bi bi-speedometer2"></i></span>
                        <span class="gf-text">Dashboard</span>
                    </a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/abonnes" class="<?= ($active ?? '') === 'admin_abonnes' ? 'active' : '' ?>">
                        <span class="gf-ico"><i class="bi bi-people"></i></span>
                        <span class="gf-text">Abonnés</span>
                    </a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/factures" class="<?= ($active ?? '') === 'admin_factures' ? 'active' : '' ?>">
                        <span class="gf-ico"><i class="bi bi-receipt-cutoff"></i></span>
                        <span class="gf-text">Factures</span>
                    </a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/tarifs" class="<?= ($active ?? '') === 'admin_tarifs' ? 'active' : '' ?>">
                        <span class="gf-ico"><i class="bi bi-tags"></i></span>
                        <span class="gf-text">Tarifs</span>
                    </a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/planning" class="<?= ($active ?? '') === 'admin_planning' ? 'active' : '' ?>">
                        <span class="gf-ico"><i class="bi bi-calendar3"></i></span>
                        <span class="gf-text">Planning</span>
                    </a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/users" class="<?= ($active ?? '') === 'admin_users' ? 'active' : '' ?>">
                        <span class="gf-ico"><i class="bi bi-person-gear"></i></span>
                        <span class="gf-text">Gérants</span>
                    </a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/audit" class="<?= ($active ?? '') === 'admin_audit' ? 'active' : '' ?>">
                        <span class="gf-ico"><i class="bi bi-clipboard-data"></i></span>
                        <span class="gf-text">Audit</span>
                    </a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/gerant/dashboard" class="<?= ($active ?? '') === 'gerant_dashboard' ? 'active' : '' ?>">
                        <span class="gf-ico"><i class="bi bi-speedometer"></i></span>
                        <span class="gf-text">Tableau de bord</span>
                    </a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/gerant/abonnements" class="<?= ($active ?? '') === 'gerant_abonnements' ? 'active' : '' ?>">
                        <span class="gf-ico"><i class="bi bi-card-list"></i></span>
                        <span class="gf-text">Abonnements</span>
                    </a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/gerant/abonnes" class="<?= ($active ?? '') === 'gerant_abonnes' ? 'active' : '' ?>">
                        <span class="gf-ico"><i class="bi bi-person-plus"></i></span>
                        <span class="gf-text">Gérer abonnés</span>
                    </a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/gerant/paiements" class="<?= ($active ?? '') === 'gerant_paiements' ? 'active' : '' ?>">
                        <span class="gf-ico"><i class="bi bi-cash-stack"></i></span>
                        <span class="gf-text">Enregistrer paiement</span>
                    </a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/gerant/planning" class="<?= ($active ?? '') === 'gerant_planning' ? 'active' : '' ?>">
                        <span class="gf-ico"><i class="bi bi-calendar-event"></i></span>
                        <span class="gf-text">Planning</span>
                    </a>
                <?php endif; ?>

                <a href="<?= htmlspecialchars($baseUrl) ?>/logout">
                    <span class="gf-ico"><i class="bi bi-box-arrow-right"></i></span>
                    <span class="gf-text">Déconnexion</span>
                </a>
            </nav>
        </aside>

        <main class="gf-main <?= htmlspecialchars((string)($mainClass ?? '')) ?>" id="gfMain">
            <div class="gf-topbar">
                <div class="gf-topbar-inner">
                    <div class="d-flex align-items-center gap-2">
                        <button class="gf-btn-icon d-none d-lg-inline-flex" id="btnSidebarToggle" type="button" title="Réduire/étendre">☰</button>
                        <button class="gf-btn-icon d-lg-none" id="btnSidebarMobile" type="button" title="Menu">☰</button>
                        <div class="ms-2">
                            <div class="fw-semibold"><?= htmlspecialchars($title ?? 'GO-FITNESS') ?></div>
                            <div class="small gf-topbar-sub">Bienvenue, <?= htmlspecialchars($user['username'] ?? 'Utilisateur') ?></div>
                        </div>
                    </div>
                    <div class="small gf-topbar-sub">
                        Rôle: <span class="gf-topbar-role"><?= htmlspecialchars($user['role'] ?? '-') ?></span>
                    </div>
                </div>
            </div>

            <div class="container py-4">
                <?php if (!empty($flash['message'])): ?>
                    <?php $flashType = (string)($flash['type'] ?? 'info'); $flashAuto = in_array($flashType, ['success','warning'], true) ? ' gf-temp-notice' : ''; ?>
                    <div class="alert alert-<?= htmlspecialchars($flashType) ?> gf-anim<?= $flashAuto ?>" role="alert"<?= $flashAuto ? ' data-auto-hide="3000"' : '' ?>>
                        <?= htmlspecialchars($flash['message']) ?>
                    </div>
                <?php endif; ?>
                <?php $content(); ?>
            </div>
        </main>
    </div>

    <script src="<?= htmlspecialchars($baseUrl) ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= htmlspecialchars($baseUrl) ?>/assets/vendor/chart.umd.min.js"></script>
    <script>
        window.GF = window.GF || {};
        GF.baseUrl = <?= json_encode($baseUrl) ?>;
        GF.csrf = <?= json_encode($csrf ?? '') ?>;
    </script>
    <script src="<?= htmlspecialchars($baseUrl) ?>/assets/js/app.js?v=35"></script>
    <script>
    // Sécurité v7: même si le fichier app.js est en cache, les notifications disparaissent après 3 secondes.
    document.addEventListener('DOMContentLoaded',function(){
      document.querySelectorAll('.gf-temp-notice,[data-auto-hide]').forEach(function(n){
        if(n.dataset.gfInlineHide==='1') return;
        n.dataset.gfInlineHide='1';
        setTimeout(function(){ n.classList.add('gf-hide'); setTimeout(function(){ if(n.parentNode) n.parentNode.removeChild(n); },350); }, parseInt(n.dataset.autoHide||'3000',10));
      });
    });
    </script>


    <script>
    // GO-FITNESS v9: correction robuste des boutons Modifier/Supprimer + auto-hide 3 secondes.
    document.addEventListener('DOMContentLoaded', function(){
      function hideRow(row){
        if(!row) return;
        var actions = row.querySelector('.gf-row-actions');
        if(actions) actions.classList.remove('is-visible');
        row.classList.remove('gf-action-open');
      }
      function hideAllExcept(current){
        document.querySelectorAll('.gf-action-row').forEach(function(r){ if(r !== current) hideRow(r); });
      }
      document.querySelectorAll('.gf-action-row').forEach(function(row){
        var actions = row.querySelector('.gf-row-actions');
        if(!actions) return;
        var optionsBtn = row.querySelector('.gf-options-btn');
        var timer = null;
        actions.classList.remove('is-visible');
        row.classList.remove('gf-action-open');
        function showActions(e){
          if(e && e.target && e.target.closest('.gf-row-actions a,.gf-row-actions button,.gf-row-actions input,.gf-row-actions form')) return;
          hideAllExcept(row);
          actions.classList.add('is-visible');
          row.classList.add('gf-action-open');
          clearTimeout(timer);
          timer = setTimeout(function(){ hideRow(row); }, 3000);
        }
        row.addEventListener('mouseenter', showActions);
        row.addEventListener('click', showActions);
        if(optionsBtn){
          optionsBtn.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); showActions(e); });
        }
        actions.addEventListener('mouseenter', function(){ clearTimeout(timer); timer = setTimeout(function(){ hideRow(row); }, 3000); });
        actions.addEventListener('click', function(){ clearTimeout(timer); timer = setTimeout(function(){ hideRow(row); }, 3000); });
      });
    });
    </script>


    <script>
    // GO-FITNESS v29: pagination légère et générale des tableaux/listes à plus de 8 lignes.
    document.addEventListener('DOMContentLoaded', function(){
      document.querySelectorAll('table').forEach(function(table){
        if(table.dataset.gfPaginate === 'off') return;
        if(table.id === 'resteDashboardTable') return;
        var tbody = table.tBodies && table.tBodies[0];
        if(!tbody) return;
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'))
          .filter(function(r){ return !r.querySelector('td[colspan]'); });
        if(rows.length <= 8) return;
        var per = 8, page = 1;
        var nav = document.createElement('div');
        nav.className = 'd-flex justify-content-end align-items-center gap-2 mt-2 gf-table-pager';
        var info = document.createElement('span'); info.className = 'small text-muted me-auto';
        var prev = document.createElement('button'); prev.type='button'; prev.className='btn btn-sm btn-outline-dark'; prev.textContent='Précédent';
        var next = document.createElement('button'); next.type='button'; next.className='btn btn-sm btn-outline-dark'; next.textContent='Suivant';
        nav.appendChild(info); nav.appendChild(prev); nav.appendChild(next);
        var wrap = table.closest('.table-responsive') || table;
        wrap.insertAdjacentElement('afterend', nav);
        function textVisibleRow(r){ return r.dataset.gfSearchHidden !== '1'; }
        function visibleRows(){ return rows.filter(textVisibleRow); }
        function render(){
          var list = visibleRows();
          var pages = Math.max(1, Math.ceil(list.length/per));
          if(page > pages) page = pages;
          rows.forEach(function(r){ r.style.display='none'; });
          list.forEach(function(r,i){ r.style.display = (i >= (page-1)*per && i < page*per) ? '' : 'none'; });
          info.textContent = list.length + ' ligne(s) — page ' + page + ' / ' + pages;
          prev.style.display = page > 1 ? '' : 'none';
          next.style.display = page < pages ? '' : 'none';
        }
        prev.addEventListener('click', function(){ if(page>1){ page--; render(); }});
        next.addEventListener('click', function(){ page++; render(); });
        // Si un champ de recherche existe dans la même carte/modal, on marque les lignes filtrées sans perdre la pagination.
        var container = table.closest('.card,.modal-content,main') || document;
        container.querySelectorAll('input[type="search"], input.gf-live-search, input.gf-client-search, input[id*="Search"]').forEach(function(input){
          input.addEventListener('input', function(){
            var q = String(input.value||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim();
            rows.forEach(function(r){
              var hay = String((r.dataset.filter||'') + ' ' + r.textContent).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
              r.dataset.gfSearchHidden = (q && hay.indexOf(q) === -1) ? '1' : '0';
            });
            page=1; render();
          });
        });
        render();
      });
    });
    </script>

    <?php foreach ($extraJs as $src): ?>
        <script src="<?= htmlspecialchars($baseUrl . $src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
