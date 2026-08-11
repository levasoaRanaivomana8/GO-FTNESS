<?php $content=function()use($rows,$journalierRows,$q,$qJournalier,$baseUrl,$typeFiltre,$statutFiltre){
$dayClass=function(int $j): string { if($j<=0)return 'gf-days-expired'; if($j<=2)return 'gf-days-critical'; if($j<=5)return 'gf-days-danger'; if($j<=10)return 'gf-days-warning'; if($j<=20)return 'gf-days-light-success'; return 'gf-days-success'; };
$renderTable=function(array $items,string $empty) use($dayClass){ ?>
<div class="table-responsive"><table class="table table-striped table-hover align-middle gf-zebra-table gf-searchable-table"><thead><tr><th>N°</th><th>Nom</th><th>Téléphone</th><th>Type d’abonnement</th><th>Mode</th><th>Statut</th><th>Fin</th><th>Message</th></tr></thead><tbody>
<?php if(empty($items)): ?><tr><td colspan="8" class="text-center text-muted py-4"><?=$empty?></td></tr><?php endif; ?>
<?php foreach($items as $r): $j=max(0,(int)($r['jours_restants']??0)); $cls=$dayClass($j); ?>
<tr>
<td><?=htmlspecialchars($r['numero_abonne']??$r['idabonne'])?></td>
<td class="fw-semibold"><?=htmlspecialchars(($r['nom']??'').' '.($r['prenom']??''))?></td>
<td><?=htmlspecialchars($r['tel']??'')?></td>
<td><?=htmlspecialchars($r['type_nom']??'-')?></td>
<td><?=htmlspecialchars($r['mode_nom']??'-')?></td>
<td><span class="gf-days-pill <?=$cls?>"><?=$j>0?'En cours':'Expiré'?></span></td>
<td><?=htmlspecialchars($r['date_fin']??'-')?></td>
<td><span class="gf-message-pill <?=$cls?>"><?=$j<=0?'Expiré':'Fin dans '.$j.' jour(s)'?></span></td>
</tr>
<?php endforeach; ?></tbody></table></div>
<?php };
?>
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
  <a class="btn btn-danger gf-top-action" href="<?=$baseUrl?>/gerant/abonnes/create">Nouvel abonné</a>
  <a class="btn btn-primary gf-top-action" href="<?=$baseUrl?>/gerant/paiements/create?reabonne=1">Réabonner</a>
</div>
<form class="row g-2 mb-3 gf-search-form" method="get" action="<?=$baseUrl?>/gerant/abonnes">
  <div class="col-md-3"><select class="form-select" name="statut"><option value="en_cours" <?=$statutFiltre==='en_cours'?'selected':''?>>En cours</option><option value="expire" <?=$statutFiltre==='expire'?'selected':''?>>Expirés</option></select></div>
  <div class="col-md-3"><select class="form-select" name="type"><option value="">Tous les types</option><?php foreach(['Normal','Premium','VIP'] as $t): ?><option value="<?=$t?>" <?=$typeFiltre===$t?'selected':''?>><?=$t?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><button type="submit" class="btn btn-dark w-100"><i class="bi bi-funnel"></i> Appliquer le tri</button></div>
</form>
<div class="card p-3 mb-4 border-0 shadow-sm">
  <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
    <h2 class="h5 mb-0">Abonnements mensuels, 6 mois et annuels</h2>
    <form class="d-flex gap-2 gf-search-form" method="get" action="<?=$baseUrl?>/gerant/abonnes">
      <input type="hidden" name="statut" value="<?=htmlspecialchars($statutFiltre)?>"><input type="hidden" name="type" value="<?=htmlspecialchars($typeFiltre)?>">
      <input class="form-control gf-client-search" name="q" value="<?=htmlspecialchars($q)?>" placeholder="Recherche mensuel, 6 mois, annuel">
      <input type="hidden" name="q_journalier" value="<?=htmlspecialchars($qJournalier)?>">
      <button class="btn btn-dark"><i class="bi bi-search"></i></button>
    </form>
  </div>
  <?php $renderTable($rows,'Aucun abonnement mensuel, 6 mois ou annuel trouvé.'); ?>
</div>
<div class="card p-3 border-0 shadow-sm">
  <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
    <h2 class="h5 mb-0">Séances journalières</h2>
    <form class="d-flex gap-2 gf-search-form" method="get" action="<?=$baseUrl?>/gerant/abonnes">
      <input type="hidden" name="statut" value="<?=htmlspecialchars($statutFiltre)?>"><input type="hidden" name="type" value="<?=htmlspecialchars($typeFiltre)?>">
      <input type="hidden" name="q" value="<?=htmlspecialchars($q)?>">
      <input class="form-control gf-client-search" name="q_journalier" value="<?=htmlspecialchars($qJournalier)?>" placeholder="Recherche séance journalière">
      <button class="btn btn-dark"><i class="bi bi-search"></i></button>
    </form>
  </div>
  <?php $renderTable($journalierRows??[],'Aucune séance journalière trouvée.'); ?>
</div>
<?php }; require __DIR__.'/../../layouts/app.php'; ?>
