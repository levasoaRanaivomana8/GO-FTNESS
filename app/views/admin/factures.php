<?php
$rows = $rows ?? [];
$q = $q ?? '';
$dateFrom = $dateFrom ?? '';
$dateTo = $dateTo ?? '';
$sort = $sort ?? 'date_facture';
$dir = strtolower($dir ?? 'desc') === 'asc' ? 'asc' : 'desc';
$baseUrl = $baseUrl ?? '';
$csrf = $csrf ?? '';
$content=function()use($rows,$q,$dateFrom,$dateTo,$sort,$dir,$baseUrl,$csrf){
$m=fn($v)=>number_format((float)$v,0,',',' ').' Ar';
$nextDir=fn($k)=>($sort===$k && $dir==='asc')?'desc':'asc';
$sortUrl=function($k)use($baseUrl,$q,$dateFrom,$dateTo,$nextDir){ return htmlspecialchars($baseUrl.'/admin/factures?'.http_build_query(['q'=>$q,'date_from'=>$dateFrom,'date_to'=>$dateTo,'sort'=>$k,'dir'=>$nextDir($k)])); };
$th=function($label,$key)use($sort,$dir,$sortUrl){ $ico=$sort===$key?($dir==='asc'?' ↑':' ↓'):''; return '<a class="text-decoration-none text-dark" href="'.$sortUrl($key).'">'.htmlspecialchars($label).$ico.'</a>'; };
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h4 mb-1">Factures</h2>
    <div class="text-muted small">Recherche par nom, téléphone, utilisateur, numéro ou période.</div>
  </div>
</div>
<form class="card border-0 shadow-sm p-3 mb-3 gf-live-search-form" method="get" action="<?=htmlspecialchars($baseUrl)?>/admin/factures">
  <div class="row g-2 align-items-end">
    <div class="col-md-4"><label class="small fw-bold">Recherche</label><input class="form-control gf-live-search" name="q" value="<?=htmlspecialchars($q)?>" placeholder="Nom, téléphone, numéro facture, gérant" autocomplete="off"></div>
    <div class="col-md-3"><label class="small fw-bold">Date facture du</label><input type="date" class="form-control" name="date_from" value="<?=htmlspecialchars($dateFrom)?>"></div>
    <div class="col-md-3"><label class="small fw-bold">Au</label><input type="date" class="form-control" name="date_to" value="<?=htmlspecialchars($dateTo)?>"></div>
    <div class="col-md-2"><button type="submit" class="btn btn-dark w-100"><i class="bi bi-search"></i> Rechercher</button></div>
  </div>
</form>
<div class="card p-3 border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle gf-zebra-table">
      <thead><tr><th><?=$th('Facture','numero')?></th><th><?=$th('Date','date_facture')?></th><th><?=$th('Utilisateur','utilisateur')?></th><th><?=$th('Abonné','abonne')?></th><th><?=$th('Payé','montant_paye')?></th><th><?=$th('Reste','reste_a_payer')?></th><th><?=$th('Statut','statut')?></th><th style="min-width:360px">Action</th></tr></thead>
      <tbody>
      <?php foreach($rows as $r): $fid=(int)($r['idfacture']??0); ?>
        <tr>
          <td class="fw-semibold"><?=htmlspecialchars($r['numero']??'-')?></td>
          <td><?=htmlspecialchars(substr((string)($r['date_facture']??''),0,16))?></td>
          <td><?=htmlspecialchars($r['utilisateur']??'-')?></td>
          <td><?=htmlspecialchars(($r['nom']??'').' '.($r['prenom']??''))?></td>
          <td><?=$m($r['montant_paye']??0)?></td>
          <td><?=$m($r['reste_a_payer']??0)?></td>
          <td><?=htmlspecialchars($r['facture_statut']??'')?></td>
          <td>
            <?php if($fid>0): ?>
              <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?=htmlspecialchars($baseUrl)?>/facture/pdf?id=<?=$fid?>">Facture</a>
            <?php else: ?>
              <span class="badge text-bg-warning">Facture non liée</span>
            <?php endif; ?>
            <?php if(($r['facture_statut']??'')==='valide' && $fid>0): ?>
              <form class="d-inline-flex align-items-center gap-2 ms-2" method="post" action="<?=htmlspecialchars($baseUrl)?>/admin/factures/cancel">
                <input type="hidden" name="_csrf" value="<?=htmlspecialchars($csrf)?>">
                <input type="hidden" name="idfacture" value="<?=$fid?>">
                <input name="motif" required placeholder="Motif d’annulation" class="form-control form-control-sm gf-motif-input">
                <button class="btn btn-sm btn-outline-danger">Annuler</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if(!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">Aucune facture trouvée.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php }; require __DIR__.'/../layouts/app.php'; ?>
