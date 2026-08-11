<?php $content=function()use($stats,$abonnes,$abonnesPage,$baseUrl,$statutFiltre,$q,$daysOp,$daysValue,$restesDue){
$m=fn($v)=>number_format((float)$v,0,',',' ').' Ar';
$months=array_reverse($stats['monthly']??[]);
$statutFiltre=$statutFiltre??'en_cours';
$dayClass=function(int $j): string {
  if($j<=0) return 'gf-days-expired';
  if($j<=2) return 'gf-days-critical';
  if($j<=5) return 'gf-days-danger';
  if($j<=10) return 'gf-days-warning';
  if($j<=20) return 'gf-days-light-success';
  return 'gf-days-success';
};
$dayText=function(int $j): string { return $j<=0?'Expiré':'Fin dans '.$j.' jour(s)'; };
$qs=function(array $extra) use($statutFiltre,$q,$daysOp,$daysValue){
  return http_build_query(array_filter(array_merge(['statut'=>$statutFiltre,'q'=>$q,'days_op'=>$daysOp,'days'=>$daysValue],$extra),fn($v)=>$v!==''&&$v!==null));
};
$page=(int)($abonnesPage['page']??1); $pages=(int)($abonnesPage['pages']??1);
?>
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card border-0 shadow-sm p-3 gf-stat-card"><div class="text-muted small">Total abonnés</div><div class="fs-2 fw-bold"><?=htmlspecialchars((string)$stats['total_abonnes'])?></div></div></div>
  <div class="col-md-3"><div class="card border-0 shadow-sm p-3 gf-stat-card"><div class="text-muted small">Abonnements actifs</div><div class="fs-2 fw-bold text-success"><?=htmlspecialchars((string)$stats['actifs'])?></div></div></div>
  <div class="col-md-3"><div class="card border-0 shadow-sm p-3 gf-stat-card"><div class="text-muted small">Recettes du jour</div><div class="fs-4 fw-bold text-danger"><?=$m($stats['recette_jour'])?></div></div></div>
  <div class="col-md-3"><div class="card border-0 shadow-sm p-3 gf-stat-card"><div class="text-muted small">Recettes du mois</div><div class="fs-4 fw-bold text-primary"><?=$m($stats['recette_mois'])?></div></div></div>
</div>



<div class="card border-0 shadow-sm p-3 mb-4 gf-dashboard-list-card">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="h5 mb-0">Liste des abonnés</h2>
  </div>

  <form method="get" action="<?=$baseUrl?>/gerant/dashboard" class="gf-dashboard-filter-grid gf-search-form gf-dashboard-live-filters mb-3">
    <div class="gf-filter-cell gf-filter-num">
      <label>Statut</label>
      <select class="form-select form-select-sm gf-dashboard-live-input" name="statut" data-filter="statut">
        <option value="en_cours" <?=$statutFiltre==='en_cours'?'selected':''?>>En cours</option>
        <option value="expire" <?=$statutFiltre==='expire'?'selected':''?>>Expirés</option>
      </select>
    </div>
    <div class="gf-filter-cell gf-filter-search">
      <label>Recherche</label>
      <input class="form-control form-control-sm gf-dashboard-live-input" name="q" data-filter="q" value="<?=htmlspecialchars($q??'')?>" placeholder="Nom, téléphone, facture, type, mode">
    </div>
    <div class="gf-filter-cell">
      <label>Jours</label>
      <select class="form-select form-select-sm gf-dashboard-live-input" name="days_op" data-filter="days_op">
        <option value="">Tous</option>
        <?php foreach(['<','<=','>','>=','='] as $op): ?><option value="<?=$op?>" <?=$daysOp===$op?'selected':''?>><?=$op?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="gf-filter-cell">
      <label>Valeur</label>
      <input type="number" min="0" class="form-control form-control-sm gf-dashboard-live-input" name="days" data-filter="days" value="<?=htmlspecialchars((string)($daysValue??''))?>">
    </div>
    <div class="gf-filter-cell gf-filter-button">
      <label>&nbsp;</label>
      <button class="btn btn-sm btn-dark w-100">Rechercher</button>
    </div>
  </form>

  <div class="table-responsive"><table class="table table-striped table-hover align-middle gf-zebra-table gf-searchable-table"><thead><tr><th>N°</th><th>Nom</th><th>Téléphone</th><th>Type</th><th>Mode</th><th>Statut</th><th>Fin abonnement</th><th>Message</th></tr></thead><tbody>
  <?php if(empty($abonnes)): ?><tr><td colspan="8" class="text-center text-muted py-4">Aucun abonné trouvé.</td></tr><?php endif; ?>
  <?php foreach($abonnes as $a): $j=max(0,(int)($a['jours_restants']??0)); $cls=$dayClass($j); ?>
    <tr data-statut="<?= $j>0?'en_cours':'expire' ?>" data-days="<?=$j?>">
      <td><?=htmlspecialchars($a['numero_abonne']??'-')?></td>
      <td class="fw-semibold"><?=htmlspecialchars(($a['nom']??'').' '.($a['prenom']??''))?></td>
      <td><?=htmlspecialchars($a['tel']??'')?></td>
      <td><?=htmlspecialchars($a['type_nom']??'-')?></td>
      <td><?=htmlspecialchars($a['mode_nom']??'-')?></td>
      <td><span class="gf-days-pill <?=$cls?>"><?= $j>0?'En cours':'Expiré' ?></span></td>
      <td><?=htmlspecialchars($a['date_fin']??'-')?></td>
      <td><span class="gf-message-pill <?=$cls?>"><?=htmlspecialchars($dayText($j))?></span></td>
    </tr>
  <?php endforeach; ?></tbody></table></div>
  <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
    <div class="small text-muted">Résultat : <span id="gfDashboardVisibleCount"><?=htmlspecialchars((string)($abonnesPage['total']??count($abonnes)))?></span> abonné(s) — page <?=$page?> / <?=$pages?></div>
    <div class="d-flex gap-2">
      <?php if($page>1): ?><a class="btn btn-outline-dark btn-sm" href="<?=$baseUrl?>/gerant/dashboard?<?=$qs(['page'=>$page-1])?>">Précédent</a><?php endif; ?>
      <?php if($page<$pages): ?><a class="btn btn-outline-dark btn-sm" href="<?=$baseUrl?>/gerant/dashboard?<?=$qs(['page'=>$page+1])?>">Suivant</a><?php endif; ?>
    </div>
  </div>
</div>

<?php $restesDue=$restesDue??[]; ?>
<div class="card border-0 shadow-sm p-3 mb-4 gf-restes-card">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
      <h2 class="h5 mb-0">Liste des abonnés avec reste à payer</h2>
      <div class="small text-muted">Tous les paiements partiels non soldés, triés par date limite la plus proche.</div>
    </div>
    <span class="badge bg-warning text-dark"><?=count($restesDue)?> reste(s)</span>
  </div>
  <div class="row g-2 mb-3">
    <div class="col-md-8"><input type="search" id="resteDashboardSearch" class="form-control" autocomplete="off" placeholder="Recherche instantanée : nom, téléphone, type, facture"></div>
    <div class="col-md-4"><input type="date" id="resteDashboardDate" class="form-control" title="Filtrer par date limite"></div>
  </div>
  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle gf-zebra-table" id="resteDashboardTable">
      <thead><tr><th>Abonné</th><th>Abonnement</th><th>Reste</th><th>Date limite</th><th>Message</th></tr></thead>
      <tbody>
      <?php if(empty($restesDue)): ?><tr><td colspan="5" class="text-center text-muted py-4">Aucun reste à payer pour le moment.</td></tr><?php endif; ?>
      <?php foreach($restesDue as $r): $j=(int)($r['jours_avant_limite']??0); $msg=$j<=0?'Paiement attendu maintenant':'À payer dans '.$j.' jour(s)'; ?>
        <tr data-date="<?=htmlspecialchars((string)($r['date_limite_reste']??''))?>" data-filter="<?=htmlspecialchars(($r['numero_abonne']??'').' '.($r['nom']??'').' '.($r['prenom']??'').' '.($r['tel']??'').' '.($r['type_nom']??'').' '.($r['mode_nom']??'').' '.($r['numero']??''),ENT_QUOTES)?>">
          <td class="fw-semibold"><?=htmlspecialchars(($r['numero_abonne']??'').' - '.($r['nom']??'').' '.($r['prenom']??''))?><br><small class="text-muted"><?=htmlspecialchars($r['tel']??'')?></small></td>
          <td><?=htmlspecialchars(($r['type_nom']??'-').' / '.($r['mode_nom']??'-'))?></td>
          <td class="text-danger fw-bold"><?=$m($r['reste_a_payer']??0)?></td>
          <td><?=htmlspecialchars($r['date_limite_reste']??'-')?></td>
          <td><span class="gf-message-pill <?= $j<=0?'gf-days-critical':($j<=2?'gf-days-danger':'gf-days-warning') ?>"><?=htmlspecialchars($msg)?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-between align-items-center mt-2">
    <span class="small text-muted" id="resteDashboardCount"></span>
    <div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-outline-dark" id="restePrev">Précédent</button><button type="button" class="btn btn-sm btn-outline-dark" id="resteNext">Suivant</button></div>
  </div>
</div>
<script>
(function(){
 const rows=Array.from(document.querySelectorAll('#resteDashboardTable tbody tr'));
 const search=document.getElementById('resteDashboardSearch');
 const date=document.getElementById('resteDashboardDate');
 const count=document.getElementById('resteDashboardCount');
 const prev=document.getElementById('restePrev');
 const next=document.getElementById('resteNext');
 let page=1, per=5;
 function norm(v){return String(v||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim();}
 function filtered(){const q=norm(search&&search.value); const d=date&&date.value; return rows.filter(r=>(!q || norm(r.dataset.filter+' '+r.textContent).includes(q)) && (!d || r.dataset.date===d));}
 function render(){const list=filtered(); const pages=Math.max(1,Math.ceil(list.length/per)); if(page>pages) page=pages; rows.forEach(r=>r.style.display='none'); list.forEach((r,i)=>{r.style.display=(i>=(page-1)*per && i<page*per)?'':'none';}); if(count) count.textContent=list.length+' résultat(s) — page '+page+' / '+pages; if(prev) prev.style.display=page>1?'':'none'; if(next) next.style.display=page<pages?'':'none';}
 [search,date].forEach(el=>el&&el.addEventListener('input',()=>{page=1;render();})); if(prev) prev.onclick=()=>{if(page>1){page--;render();}}; if(next) next.onclick=()=>{page++;render();}; render();
})();
</script>



<div class="row g-4 mb-4">
  <div class="col-lg-6"><div class="card border-0 shadow-sm p-3"><h2 class="h5 mb-3">Graphe des recettes mensuelles</h2><div class="gf-chart-fixed"><canvas id="gerantRevenueChart"></canvas></div></div></div>
  <div class="col-lg-6"><div class="card border-0 shadow-sm p-3"><h2 class="h5 mb-3">État des abonnés</h2><div class="gf-chart-fixed"><canvas id="gerantAbonnesChart"></canvas></div></div></div>
</div>
<div class="d-flex gap-2 flex-wrap"><a class="btn btn-danger" href="<?=$baseUrl?>/gerant/abonnes/create">Créer un abonné</a><a class="btn btn-primary" href="<?=$baseUrl?>/gerant/paiements/create">Enregistrer abonnement/paiement</a><a class="btn btn-outline-dark" href="<?=$baseUrl?>/gerant/abonnements">Voir les abonnements et prix</a></div>
<script>
window.addEventListener('load',()=>{
 const monthly = <?=json_encode($months,JSON_UNESCAPED_UNICODE)?>;
 const ctx=document.getElementById('gerantRevenueChart');
 if(ctx && window.Chart){ new Chart(ctx,{type:'line',data:{labels:monthly.map(x=>x.mois),datasets:[{label:'Recettes',data:monthly.map(x=>Number(x.total||0)),tension:.35,fill:true}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}}}}); }
 const c2=document.getElementById('gerantAbonnesChart');
 if(c2 && window.Chart){ new Chart(c2,{type:'doughnut',data:{labels:['Actifs','Expirés','Nouveaux'],datasets:[{data:[<?=$stats['actifs']?>,<?=$stats['expires']?>,<?=$stats['nouveaux']?>]}]},options:{responsive:true,maintainAspectRatio:false}}); }
});
</script>
<?php }; require __DIR__.'/../layouts/app.php'; ?>
