<?php
$refs=$refs??[]; $csrf=$csrf??'';
$content=function()use($refs,$csrf){
$m=fn($v)=>number_format((float)$v,0,',',' ');
$group=[]; foreach(($refs['tarifs']??[]) as $t){ $group[$t['type_nom']][]=$t; } $desc=[]; foreach(($refs['types']??[]) as $tp){ $desc[(int)$tp['idtype']]=$tp['description']??''; }
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div><h2 class="h4 mb-1">Tarifs</h2><div class="text-muted small">Modification des types, modes, prix et descriptions.</div></div>
</div>
<form method="post">
<input type="hidden" name="_csrf" value="<?=htmlspecialchars($csrf)?>">
<div class="row g-4 align-items-stretch">
<?php foreach($group as $type=>$items): $typeId=(int)$items[0]['idtype']; ?>
  <div class="col-lg-4">
    <div class="card gf-tarif-card h-100 p-3">
      <label class="form-label fw-bold">Type d’abonnement</label>
      <input class="form-control fw-bold mb-3" name="type_nom[<?=$typeId?>]" value="<?=htmlspecialchars($type)?>">
      <label class="form-label">Description du type</label>
      <textarea class="form-control mb-3" name="description[<?=$typeId?>]" rows="3"><?=htmlspecialchars($desc[$typeId]??'')?></textarea>
      <div class="vstack gap-3">
        <?php foreach($items as $t): ?>
          <div class="gf-tarif-line">
            <label class="form-label small text-muted mb-1">Mode</label>
            <input class="form-control mb-2" name="mode_nom[<?=(int)$t['idmode']?>]" value="<?=htmlspecialchars($t['mode_nom'])?>">
            <label class="form-label small text-muted mb-1">Montant Ar</label>
            <input class="form-control" name="tarif[<?=(int)$t['idtarif']?>]" value="<?=$m($t['montant'])?>">
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>
<button class="btn btn-danger mt-4">Enregistrer les modifications</button>
</form>
<?php }; require __DIR__.'/../layouts/app.php'; ?>
