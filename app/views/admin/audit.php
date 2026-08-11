<?php
$rows=$rows??[]; $filters=$filters??['date_from'=>'','date_to'=>'','action'=>'','user_id'=>0]; $users=$users??[]; $baseUrl=$baseUrl??'';
$content=function()use($rows,$filters,$users,$baseUrl){
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h4 mb-1">Historique / audit</h2>
    <div class="text-muted small">Recherche par date, période, action et utilisateur.</div>
  </div>
</div>
<form class="card p-3 mb-3 gf-live-search-form" method="get" action="<?=htmlspecialchars($baseUrl)?>/admin/audit">
  <div class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label">Date début</label><input type="date" class="form-control" name="date_from" value="<?=htmlspecialchars($filters['date_from']??'')?>"></div>
    <div class="col-md-3"><label class="form-label">Date fin</label><input type="date" class="form-control" name="date_to" value="<?=htmlspecialchars($filters['date_to']??'')?>"></div>
    <div class="col-md-3"><label class="form-label">Action</label><input class="form-control gf-live-search" name="action" value="<?=htmlspecialchars($filters['action']??'')?>" placeholder="Ex: facture, paiement, login"></div>
    <div class="col-md-2"><label class="form-label">Utilisateur</label><select class="form-select" name="user_id"><option value="0">Tous</option><?php foreach($users as $u): ?><option value="<?=(int)$u['iduser']?>" <?=((int)($filters['user_id']??0)===(int)$u['iduser']?'selected':'')?>><?=htmlspecialchars($u['username'].' — '.$u['role'])?></option><?php endforeach; ?></select></div>
    <div class="col-md-1 d-grid"><button class="btn btn-dark"><i class="bi bi-search"></i></button></div>
  </div>
</form>
<div class="card p-3">
  <div class="table-responsive"><table class="table table-sm table-striped table-hover align-middle">
    <thead><tr><th>Date</th><th>Utilisateur</th><th>Rôle</th><th>Action</th><th>Élément</th><th>Motif</th></tr></thead><tbody>
    <?php foreach($rows as $r): $element=trim((string)($r['entity']??'')); ?>
      <tr><td><?=htmlspecialchars($r['created_at'])?></td><td><?=htmlspecialchars($r['username']??'-')?></td><td><?=htmlspecialchars($r['role']??'')?></td><td><?=htmlspecialchars($r['action'])?></td><td><?=htmlspecialchars($element!==''?$element:'-')?></td><td><?=htmlspecialchars($r['motif']??'')?></td></tr>
    <?php endforeach; ?>
    <?php if(!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">Aucun historique trouvé.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>
<?php }; require __DIR__.'/../layouts/app.php'; ?>
