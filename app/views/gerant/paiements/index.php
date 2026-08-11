<?php $content=function()use($rows,$q,$baseUrl,$restes,$csrf,$facturesByAbonnement){
$m=fn($v)=>number_format((float)$v,0,',',' ').' Ar'; $newFacture=(int)($_GET['facture']??0);
?>
<?php if($newFacture>0): ?>
<div class="alert alert-success d-flex justify-content-between align-items-center shadow-sm gf-temp-notice" data-auto-hide="3000">
  <div><b>Facture prête.</b> Cliquez pour l’ouvrir, l’imprimer ou la télécharger.</div>
  <a class="btn btn-success" target="_blank" href="<?=$baseUrl?>/facture/pdf?id=<?=$newFacture?>">Ouvrir la facture</a>
</div>
<?php endif; ?>
<div class="d-flex gap-2 flex-wrap mb-3">
  <a class="btn btn-danger" href="<?=$baseUrl?>/gerant/paiements/create">Nouveau paiement</a>
  <button class="btn btn-warning" type="button" data-bs-toggle="modal" data-bs-target="#restesPaiementModal">Voir les restes à payer</button>
</div>
<form class="row g-2 mb-3 gf-live-search-form" method="get" action="<?=$baseUrl?>/gerant/paiements"><div class="col-md-8"><input class="form-control gf-live-search" name="q" value="<?=htmlspecialchars($q)?>" placeholder="Nom, téléphone, facture"></div><div class="col-md-4"><button type="submit" class="btn btn-dark w-100"><i class="bi bi-search"></i> Rechercher</button></div></form>
<div class="card border-0 shadow-sm p-3"><div class="table-responsive"><table class="table table-striped table-hover align-middle gf-zebra-table"><thead><tr><th>Facture</th><th>Abonné</th><th>Abonnement</th><th>Total</th><th>Payé</th><th>Reste</th><th>Statut</th><th>Facture</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td class="fw-semibold"><?=htmlspecialchars($r['numero']??'-')?></td><td><?=htmlspecialchars(($r['nom']??'').' '.($r['prenom']??''))?></td><td><?=htmlspecialchars(($r['type_nom']??'').' / '.($r['mode_nom']??''))?></td><td><?=$m($r['montant_total'])?></td><td><?=$m($r['montant_paye'])?></td><td class="<?=$r['reste_a_payer']>0?'text-danger fw-bold':''?>"><?=$m($r['reste_a_payer'])?></td><td><?=htmlspecialchars($r['statut'])?></td><td><?php
  $fid=(int)($r['idfacture']??0);
  $abid=(int)($r['idabonnement']??0);
  $choices=$facturesByAbonnement[$abid]??[];
  if(count($choices)>1): ?>
    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#factureChoice<?=$abid?>">Factures</button>
  <?php elseif($fid>0): ?>
    <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?=$baseUrl?>/facture/pdf?id=<?=$fid?>">Facture</a>
  <?php else: ?><span class="text-muted">-</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></div>

<?php $printedFactureModals=[]; foreach($rows as $rr): $abid=(int)($rr['idabonnement']??0); $choices=$facturesByAbonnement[$abid]??[]; if($abid>0 && count($choices)>1 && empty($printedFactureModals[$abid])): $printedFactureModals[$abid]=true; ?>
<div class="modal fade" id="factureChoice<?=$abid?>" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Choisir la facture</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <p class="text-muted mb-3">Ce paiement partiel possède plusieurs factures. Ouvrez celle que vous voulez consulter.</p>
      <div class="list-group">
        <?php foreach($choices as $idx=>$fc): $label=($idx===0?'Facture 1 — Premier 50%':'Facture '.($idx+1).' — Deuxième 50% / reste payé'); ?>
          <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" target="_blank" href="<?=$baseUrl?>/facture/pdf?id=<?=(int)$fc['idfacture']?>">
            <span><b><?=htmlspecialchars($label)?></b><br><small><?=htmlspecialchars($fc['numero']??'')?> — <?=htmlspecialchars($fc['date_facture']??'')?></small></span>
            <span class="badge bg-primary rounded-pill">Ouvrir</span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button></div>
  </div></div>
</div>
<?php endif; endforeach; ?>

<div class="modal fade" id="restesPaiementModal" tabindex="-1">
  <div class="modal-dialog modal-xl"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Restes à payer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input type="search" id="restesSearch" class="form-control mb-3" autocomplete="off" placeholder="Recherche instantanée : nom, téléphone, type, facture, date limite">
      <div class="table-responsive"><table class="table table-striped table-hover align-middle gf-zebra-table"><thead><tr><th>Abonné</th><th>Abonnement</th><th>Facture initiale</th><th>Reste</th><th>Date limite</th><th class="gf-action-header">Action</th></tr></thead><tbody id="restesTableBody">
        <?php foreach($restes as $r): ?>
        <tr class="gf-action-row" data-filter="<?=htmlspecialchars(($r['numero_abonne']??'').' '.($r['nom']??'').' '.($r['prenom']??'').' '.($r['tel']??'').' '.($r['type_nom']??'').' '.($r['mode_nom']??'').' '.($r['numero']??'').' '.($r['date_limite_reste']??''),ENT_QUOTES)?>">
          <td class="fw-semibold"><?=htmlspecialchars(($r['numero_abonne']??'').' - '.($r['nom']??'').' '.($r['prenom']??''))?><br><small class="text-muted"><?=htmlspecialchars($r['tel']??'')?></small></td>
          <td><?=htmlspecialchars(($r['type_nom']??'-').' / '.($r['mode_nom']??'-'))?></td>
          <td><?=htmlspecialchars($r['numero']??'-')?></td>
          <td class="text-danger fw-bold"><?=$m($r['reste_a_payer']??0)?></td>
          <td><?=htmlspecialchars($r['date_limite_reste']??'-')?></td>
          <td class="gf-action-cell"><div class="gf-row-actions d-flex gap-1 flex-wrap"><button type="button" class="btn btn-sm btn-success btnPayReste" data-idpaiement="<?=$r['idpaiement']?>" data-abonne="<?=htmlspecialchars(($r['nom']??'').' '.($r['prenom']??''),ENT_QUOTES)?>" data-reste="<?=$m($r['reste_a_payer']??0)?>">Payer le reste</button><button type="button" class="btn btn-sm btn-outline-danger btnCancelReste" data-idpaiement="<?=$r['idpaiement']?>" data-abonne="<?=htmlspecialchars(($r['nom']??'').' '.($r['prenom']??''),ENT_QUOTES)?>">Annuler</button></div></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($restes)): ?><tr><td colspan="6" class="text-center text-muted py-4">Aucun reste à payer.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button></div>
  </div></div>
</div>

<div class="modal fade" id="payResteModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content"><form method="post" action="<?=$baseUrl?>/gerant/paiements/reste">
    <div class="modal-header"><h5 class="modal-title">Payer le reste</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input type="hidden" name="_csrf" value="<?=$csrf?>"><input type="hidden" name="idpaiement" id="payResteId">
      <div class="mb-2">Abonné : <b id="payResteAbonne">-</b></div>
      <div class="mb-3">Reste à payer : <b class="text-danger" id="payResteMontant">-</b></div>
      <label class="form-label">Mode de paiement</label><select class="form-select" name="mode_paiement"><option>Espèces</option><option>MVola</option><option>Orange Money</option><option>Airtel Money</option><option>Carte</option><option>Virement</option></select>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button><button class="btn btn-success">Valider le paiement du reste</button></div>
  </form></div></div>
</div>

<div class="modal fade" id="cancelResteModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content"><form method="post" action="<?=$baseUrl?>/gerant/paiements/reste/cancel">
    <div class="modal-header"><h5 class="modal-title">Annuler le reste à payer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input type="hidden" name="_csrf" value="<?=$csrf?>"><input type="hidden" name="idpaiement" id="cancelResteId">
      <p class="mb-2">Abonné : <b id="cancelResteAbonne">-</b></p>
      <div class="alert alert-warning small">Cette action retire l’abonné de la liste des restes à payer et annule l’abonnement concerné. À utiliser uniquement si le client ne règle plus le reste.</div>
      <label class="form-label">Motif</label><input class="form-control" name="motif" value="Reste non payé par le client" required>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button><button class="btn btn-outline-danger">Confirmer l’annulation</button></div>
  </form></div></div>
</div>

<script>
(function(){
 function norm(v){return String(v||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim();}
 const search=document.getElementById('restesSearch');
 if(search){search.addEventListener('input',()=>{const q=norm(search.value);document.querySelectorAll('#restesTableBody tr[data-filter]').forEach(tr=>{tr.style.display=(!q || norm(tr.dataset.filter+' '+tr.textContent).includes(q))?'':'none';});});}
 document.addEventListener('click',e=>{
   const btn=e.target.closest('.btnPayReste'); if(!btn) return;
   document.getElementById('payResteId').value=btn.dataset.idpaiement||'';
   document.getElementById('payResteAbonne').textContent=btn.dataset.abonne||'-';
   document.getElementById('payResteMontant').textContent=btn.dataset.reste||'-';
   bootstrap.Modal.getOrCreateInstance(document.getElementById('payResteModal')).show();
 });
 document.addEventListener('click',e=>{
   const btn=e.target.closest('.btnCancelReste'); if(!btn) return;
   document.getElementById('cancelResteId').value=btn.dataset.idpaiement||'';
   document.getElementById('cancelResteAbonne').textContent=btn.dataset.abonne||'-';
   bootstrap.Modal.getOrCreateInstance(document.getElementById('cancelResteModal')).show();
 });
})();
</script>
<?php }; require __DIR__.'/../../layouts/app.php'; ?>
