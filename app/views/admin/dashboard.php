<?php $content=function()use($stats,$baseUrl,$adminAbonnes,$abonneFilters,$csrf){
$m=fn($v)=>number_format((float)$v,0,',',' ').' Ar';
$months=array_reverse($stats['monthly']??[]);
$filters=$stats['filters']??['day'=>date('Y-m-d'),'month'=>date('Y-m'),'year'=>date('Y')];
$abonneFilters=$abonneFilters??['q'=>'','statut'=>'en_cours','days_op'=>'','days_value'=>null];
$dayClass=function(int $j): string { if($j<=0)return 'gf-days-expired'; if($j<=2)return 'gf-days-critical'; if($j<=5)return 'gf-days-danger'; if($j<=10)return 'gf-days-warning'; if($j<=20)return 'gf-days-light-success'; return 'gf-days-success'; };
?>
<div class="row g-3 mb-4">
<?php foreach([
 ['Total abonnés',$stats['total_abonnes'],'bi-people','danger'],['Actifs',$stats['actifs'],'bi-check-circle','success'],['Expirés',$stats['expires'],'bi-clock-history','secondary'],['Nouveaux',$stats['nouveaux'],'bi-person-plus','primary']
] as $c): ?>
 <div class="col-md-3"><div class="card border-0 shadow-sm p-3 gf-admin-stat gf-admin-stat-<?=$c[3]?>"><div class="d-flex justify-content-between"><div><div class="small text-muted"><?=$c[0]?></div><div class="fs-2 fw-bold"><?=htmlspecialchars((string)$c[1])?></div></div><i class="bi <?=$c[2]?> fs-2"></i></div></div></div>
<?php endforeach; ?>
</div>

<form class="row g-3 mb-3" method="get" action="<?=$baseUrl?>/admin/dashboard">
 <input type="hidden" name="q" value="<?=htmlspecialchars((string)($abonneFilters['q']??''))?>">
 <input type="hidden" name="statut" value="<?=htmlspecialchars((string)($abonneFilters['statut']??'en_cours'))?>">
 <input type="hidden" name="days_op" value="<?=htmlspecialchars((string)($abonneFilters['days_op']??''))?>">
 <input type="hidden" name="days_value" value="<?=htmlspecialchars((string)($abonneFilters['days_value']??''))?>">
 <div class="col-md-4"><div class="card border-0 shadow-sm p-3 gf-revenue-card"><label class="small fw-bold">Recette du jour choisi</label><input max="<?=date('Y-m-d')?>" type="date" class="form-control my-2" name="day" value="<?=htmlspecialchars($filters['day'])?>" onchange="this.form.submit()"><div class="fs-4 fw-bold text-success"><?=$m($stats['recette_jour'])?></div><div class="small text-muted mt-2">Référence aujourd’hui : <b><?=$m($stats['recette_today_total']??0)?></b></div></div></div>
 <div class="col-md-4"><div class="card border-0 shadow-sm p-3 gf-revenue-card"><label class="small fw-bold">Recette du mois choisi</label><input max="<?=date('Y-m')?>" type="month" class="form-control my-2" name="month" value="<?=htmlspecialchars($filters['month'])?>" onchange="this.form.submit()"><div class="fs-4 fw-bold text-primary"><?=$m($stats['recette_mois'])?></div><div class="small text-muted mt-2">Référence mois courant : <b><?=$m($stats['recette_current_month_total']??0)?></b></div></div></div>
 <div class="col-md-4"><div class="card border-0 shadow-sm p-3 gf-revenue-card"><label class="small fw-bold">Recette de l’année choisie</label><input max="<?=date('Y')?>" type="number" min="2000" class="form-control my-2" name="year" value="<?=htmlspecialchars($filters['year'])?>" onchange="this.form.submit()"><div class="fs-4 fw-bold text-danger"><?=$m($stats['recette_annee'])?></div><div class="small text-muted mt-2">Référence année courante : <b><?=$m($stats['recette_current_year_total']??0)?></b></div></div></div>
</form>

<div class="row g-4 mb-4">
 <div class="col-12"><div class="card border-0 shadow-sm p-3 gf-admin-list-card">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h2 class="h5 mb-0">Liste des abonnés</h2></div>
  <form class="row g-2 gf-admin-abonne-filter mb-3" method="get" action="<?=$baseUrl?>/admin/dashboard">
   <input type="hidden" name="day" value="<?=htmlspecialchars($filters['day'])?>"><input type="hidden" name="month" value="<?=htmlspecialchars($filters['month'])?>"><input type="hidden" name="year" value="<?=htmlspecialchars($filters['year'])?>">
   <div class="col-md-4"><label class="form-label small">Recherche</label><input class="form-control gf-client-search" name="q" value="<?=htmlspecialchars((string)($abonneFilters['q']??''))?>" placeholder="Nom, téléphone, type..."></div>
   <div class="col-md-3"><label class="form-label small">Statut</label><select class="form-select" name="statut" onchange="this.form.submit()"><option value="en_cours" <?=($abonneFilters['statut']??'en_cours')==='en_cours'?'selected':''?>>Actifs / en cours</option><option value="expire" <?=($abonneFilters['statut']??'')==='expire'?'selected':''?>>Expirés</option><option value="all" <?=($abonneFilters['statut']??'')==='all'?'selected':''?>>Tous</option></select></div>
   <div class="col-md-2"><label class="form-label small">Jours</label><select class="form-select" name="days_op"><option value="">Tous</option><?php foreach(['<','<=','>','>=','='] as $op): ?><option value="<?=$op?>" <?=($abonneFilters['days_op']??'')===$op?'selected':''?>><?=$op?></option><?php endforeach; ?></select></div>
   <div class="col-md-2"><label class="form-label small">Valeur</label><input type="number" min="0" class="form-control" name="days_value" value="<?=htmlspecialchars((string)($abonneFilters['days_value']??''))?>"></div>
   <div class="col-md-1 d-flex align-items-end"><button class="btn btn-primary w-100"><i class="bi bi-search"></i></button></div>
  </form>
  <div class="table-responsive"><table class="table table-striped table-hover align-middle gf-zebra-table"><thead><tr><th>N°</th><th>Abonné</th><th>Téléphone</th><th>Type</th><th>Mode</th><th>Statut</th><th>Date fin</th><th>Message</th></tr></thead><tbody><?php foreach($adminAbonnes as $r): $j=max(0,(int)($r['jours_restants']??0)); $cls=$dayClass($j); ?><tr><td><?=htmlspecialchars($r['numero_abonne']??'-')?></td><td><?=htmlspecialchars(($r['nom']??'').' '.($r['prenom']??''))?></td><td><?=htmlspecialchars($r['tel']??'-')?></td><td><?=htmlspecialchars($r['type_nom']??'-')?></td><td><?=htmlspecialchars($r['mode_nom']??'-')?></td><td><span class="gf-days-pill <?=$cls?>"><?=$j>0?'Actif':'Expiré'?></span></td><td><?=htmlspecialchars($r['date_fin']??'-')?></td><td><span class="gf-message-pill <?=$cls?>"><?=$j<=0?'Expiré':'Fin dans '.$j.' jour(s)'?></span></td></tr><?php endforeach; ?></tbody></table></div>
 </div></div>
</div>
<div class="card border-0 shadow-sm p-3 mb-4">
 <h2 class="h5 mb-3">Notifications internes</h2>
 <?php if(empty($stats['notifications'])): ?>
  <div class="text-muted text-center py-3">Aucune notification interne.</div>
 <?php else: ?>
  <div class="row g-2">
   <?php foreach($stats['notifications'] as $n): ?>
    <div class="col-md-6 col-xl-3">
     <div class="alert alert-light border h-100 mb-0 gf-action-row gf-notification-card">
      <div class="d-flex justify-content-between gap-2">
       <div><b><?=htmlspecialchars($n['titre'])?></b><br><span class="small"><?=htmlspecialchars($n['message'])?></span></div>
      </div>
      <div class="gf-row-actions mt-2">
       <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#notifDetail<?=$n['idnotification']?>">Détail</button>
       <form method="post" action="<?=$baseUrl?>/admin/notifications/delete" class="d-inline" onsubmit="return confirm('Supprimer cette notification ?')">
        <input type="hidden" name="_csrf" value="<?=$csrf?>"><input type="hidden" name="idnotification" value="<?=$n['idnotification']?>">
        <button class="btn btn-sm btn-outline-danger">Supprimer</button>
       </form>
      </div>
      <div class="modal fade" id="notifDetail<?=$n['idnotification']?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><?=htmlspecialchars($n['titre'])?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p><?=nl2br(htmlspecialchars($n['message']))?></p><hr><div class="small text-muted"><div><b>Date et heure :</b> <?=htmlspecialchars($n['created_at']??'-')?></div><div><b>Rôle cible :</b> <?=htmlspecialchars($n['role_cible']??'-')?></div><div><b>Utilisateur :</b> <?=htmlspecialchars(trim(($n['matricule']??'').' '.($n['username']??''))?:'-')?></div></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button></div></div></div></div>
     </div>
    </div>
   <?php endforeach; ?>
  </div>
 <?php endif; ?>
</div>

<div class="row g-4 mb-4">
 <div class="col-lg-4"><div class="card border-0 shadow-sm p-3"><h2 class="h6">Recettes par type</h2><div class="gf-chart-fixed"><canvas id="adminTypeChart"></canvas></div></div></div>
 <div class="col-lg-4"><div class="card border-0 shadow-sm p-3"><h2 class="h6">Recettes par mode</h2><div class="gf-chart-fixed"><canvas id="adminModeChart"></canvas></div></div></div>
 <div class="col-lg-4"><div class="card border-0 shadow-sm p-3"><h2 class="h6">Recettes mensuelles</h2><div class="gf-chart-fixed"><canvas id="adminMonthlyChart"></canvas></div></div></div>
</div>

<script>
window.addEventListener('load',()=>{
 const byType=<?=json_encode($stats['by_type']??[],JSON_UNESCAPED_UNICODE)?>, byMode=<?=json_encode($stats['by_mode']??[],JSON_UNESCAPED_UNICODE)?>, monthly=<?=json_encode($months,JSON_UNESCAPED_UNICODE)?>;
 const mk=(id,rows,type='doughnut')=>{const el=document.getElementById(id); if(el&&window.Chart)new Chart(el,{type,data:{labels:rows.map(x=>x.label||x.mois),datasets:[{data:rows.map(x=>Number(x.total||0))}]},options:{responsive:true,maintainAspectRatio:false}})};
 mk('adminTypeChart',byType); mk('adminModeChart',byMode); mk('adminMonthlyChart',monthly,'line');
});
</script>
<?php }; require __DIR__.'/../layouts/app.php'; ?>
