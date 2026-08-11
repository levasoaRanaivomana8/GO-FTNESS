<?php $content=function()use($rows,$abonnes,$csrf,$baseUrl){
$m=fn($v)=>number_format((float)$v,0,',',' ').' Ar';
$newFacture=(int)($_GET['facture']??0);
?>
<?php if($newFacture>0): ?>
  <div class="alert alert-success d-flex justify-content-between align-items-center shadow-sm gf-temp-notice" data-auto-hide="3000">
    <div><b>Facture prête.</b> Participation encaissée.</div>
    <a class="btn btn-success" target="_blank" href="<?=$baseUrl?>/facture/pdf?id=<?=$newFacture?>">Ouvrir la facture</a>
  </div>
<?php endif; ?>

<div class="card p-3 border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle gf-zebra-table">
      <thead><tr><th>Date</th><th>Activité</th><th>Lieu</th><th>Prix</th><th>Places restantes</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($rows as $r): $rest=max(0,(int)$r['limite_participants']-(int)$r['inscrits']); ?>
        <tr>
          <td><?=htmlspecialchars($r['date_event'])?></td>
          <td><b><?=htmlspecialchars($r['activite'])?></b><br><small><?=htmlspecialchars($r['description'])?></small></td>
          <td><?=htmlspecialchars($r['lieu'])?></td>
          <td><?=$m($r['prix_participation'])?><br><small>Paiement avant <?=htmlspecialchars($r['date_limite_paiement'])?></small></td>
          <td><?=$rest?></td>
          <td>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#inscrire<?=$r['idplanning']?>">Inscrire</button>
            <button class="btn btn-sm btn-outline-dark btnParticipants" data-id="<?=$r['idplanning']?>" data-title="<?=htmlspecialchars($r['activite'])?>">Liste inscrits</button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php foreach($rows as $r): ?>
<div class="modal fade gf-inscription-modal" id="inscrire<?=$r['idplanning']?>" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title">Inscrire un abonné — <?=htmlspecialchars($r['activite'])?></h5>
          <div class="small text-muted">Prix participation : <b><?=$m($r['prix_participation'])?></b></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="search" class="form-control mb-3 gf-inscrire-search" value="" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" placeholder="Rechercher nom, prénom, téléphone, numéro, type ou mode">
        <div class="list-group gf-abonne-list">
          <?php foreach($abonnes as $a): $annual=(int)($a['has_annuel_actif']??0)===1; ?>
            <div class="list-group-item d-flex justify-content-between gap-3 align-items-center gf-planning-abonne-item" data-filter="<?=htmlspecialchars(($a['numero_abonne']??'').' '.($a['nom']??'').' '.($a['prenom']??'').' '.($a['tel']??'').' '.($a['type_nom']??'').' '.($a['mode_nom']??''),ENT_QUOTES)?>">
              <div>
                <b><?=htmlspecialchars(($a['numero_abonne']??'').' - '.$a['nom'].' '.$a['prenom'])?></b><br>
                <small><?=htmlspecialchars(($a['tel']??'').' | '.($a['type_nom']??'-').' / '.($a['mode_nom']??'-'))?></small>
              </div>
              <?php if($annual): ?>
                <form method="post" action="<?=$baseUrl?>/gerant/planning/participate" class="m-0">
                  <input type="hidden" name="_csrf" value="<?=$csrf?>">
                  <input type="hidden" name="idplanning" value="<?=$r['idplanning']?>">
                  <input type="hidden" name="idabonne" value="<?=$a['idabonne']?>">
                  <button class="btn btn-success btn-sm">S'inscrire</button>
                </form>
              <?php else: ?>
                <button type="button" class="btn btn-primary btn-sm btnOpenPlanningPayment"
                  data-parent="#inscrire<?=$r['idplanning']?>"
                  data-idplanning="<?=$r['idplanning']?>"
                  data-idabonne="<?=$a['idabonne']?>"
                  data-abonne="<?=htmlspecialchars($a['nom'].' '.$a['prenom'],ENT_QUOTES)?>"
                  data-activite="<?=htmlspecialchars($r['activite'],ENT_QUOTES)?>"
                  data-prix="<?=$m($r['prix_participation'])?>">
                  S'inscrire
                </button>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<div class="modal fade" id="planningPaymentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?=$baseUrl?>/gerant/planning/participate">
        <div class="modal-header"><h5 class="modal-title">Paiement participation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="_csrf" value="<?=$csrf?>">
          <input type="hidden" name="idplanning" id="payPlanningId">
          <input type="hidden" name="idabonne" id="payAbonneId">
          <div class="mb-2">Activité : <b id="payActivite">-</b></div>
          <div class="mb-2">Abonné : <b id="payAbonne">-</b></div>
          <div class="mb-3">Montant à encaisser : <b id="payMontant">-</b></div>
          <label class="form-label">Mode de paiement</label>
          <select class="form-select" name="mode_paiement"><option>Espèces</option><option>MVola</option><option>Carte bancaire</option><option>Orange Money</option><option>Airtel Money</option></select>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Valider paiement et inscrire</button></div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="participantsModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="participantsTitle">Liste des inscrits</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="search" id="participantsSearch" class="form-control mb-3" autocomplete="off" placeholder="Rechercher dans les inscrits"><div id="participantsBody">Chargement…</div></div></div></div>
</div>

<script>
(function(){
  function normalizeGF(v){
    return String(v || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g,'')
      .replace(/\s+/g,' ')
      .trim();
  }

  function getInscriptionModal(el){
    return el ? el.closest('.gf-inscription-modal') : null;
  }

  function resetInscriptionSearch(modal){
    if(!modal) return;
    const input = modal.querySelector('.gf-inscrire-search');
    if(input){
      input.value = '';
      input.defaultValue = '';
      input.setAttribute('value','');
      input.setAttribute('autocomplete','off');
    }
    modal.querySelectorAll('.gf-planning-abonne-item').forEach(row => {
      row.hidden = false;
      row.classList.remove('d-none');
      row.style.display = '';
    });
  }

  function applyInscriptionSearch(input){
    const modal = getInscriptionModal(input);
    if(!modal) return;
    const q = normalizeGF(input.value);
    modal.querySelectorAll('.gf-planning-abonne-item').forEach(row => {
      const hay = normalizeGF((row.dataset.filter || '') + ' ' + row.textContent);
      const match = !q || hay.indexOf(q) !== -1;
      row.hidden = !match;
      row.classList.toggle('d-none', !match);
      row.style.display = match ? '' : 'none';
    });
  }

  function bindInscriptionSearches(){
    document.querySelectorAll('.gf-inscription-modal').forEach(modal => {
      if(modal.dataset.gfSearchBound === '1') return;
      modal.dataset.gfSearchBound = '1';
      const input = modal.querySelector('.gf-inscrire-search');
      if(input){
        ['input','keyup','change','search','paste'].forEach(evt => {
          input.addEventListener(evt, () => setTimeout(() => applyInscriptionSearch(input), 0));
        });
        input.addEventListener('keydown', e => {
          if(e.key === 'Enter'){
            e.preventDefault();
            applyInscriptionSearch(input);
          }
        });
      }
      modal.addEventListener('show.bs.modal', () => resetInscriptionSearch(modal));
      modal.addEventListener('shown.bs.modal', () => {
        resetInscriptionSearch(modal);
        const inp = modal.querySelector('.gf-inscrire-search');
        if(inp) setTimeout(() => inp.focus(), 80);
      });
      modal.addEventListener('hidden.bs.modal', () => resetInscriptionSearch(modal));
    });
  }

  document.addEventListener('DOMContentLoaded', bindInscriptionSearches);
  bindInscriptionSearches();

  document.addEventListener('input', e => {
    if(e.target && e.target.matches('.gf-inscrire-search')) applyInscriptionSearch(e.target);
  }, true);

  document.addEventListener('click',(e)=>{
    const btn=e.target.closest('.btnOpenPlanningPayment');
    if(!btn) return;
    e.preventDefault();
    const payEl=document.getElementById('planningPaymentModal');
    if(!payEl || typeof bootstrap === 'undefined') return;
    document.getElementById('payPlanningId').value=btn.dataset.idplanning || '';
    document.getElementById('payAbonneId').value=btn.dataset.idabonne || '';
    document.getElementById('payActivite').textContent=btn.dataset.activite || '-';
    document.getElementById('payAbonne').textContent=btn.dataset.abonne || '-';
    document.getElementById('payMontant').textContent=btn.dataset.prix || '-';
    const parentSelector=btn.dataset.parent || '';
    const parentEl=parentSelector ? document.querySelector(parentSelector) : btn.closest('.modal');
    const showPay=()=>bootstrap.Modal.getOrCreateInstance(payEl).show();
    if(parentEl && parentEl.classList.contains('show')){
      parentEl.addEventListener('hidden.bs.modal', showPay, {once:true});
      bootstrap.Modal.getOrCreateInstance(parentEl).hide();
    }else{ showPay(); }
  });

  document.addEventListener('DOMContentLoaded',()=>{
    document.querySelectorAll('.btnParticipants').forEach(btn=>btn.addEventListener('click',async()=>{
      const body=document.getElementById('participantsBody'); const search=document.getElementById('participantsSearch');
      document.getElementById('participantsTitle').textContent='Liste des inscrits — '+btn.dataset.title;
      body.textContent='Chargement…'; if(search) search.value='';
      bootstrap.Modal.getOrCreateInstance(document.getElementById('participantsModal')).show();
      const data=await fetch(GF.baseUrl+'/gerant/planning/participants?id='+btn.dataset.id).then(r=>r.json()).catch(()=>[]);
      body.innerHTML=data.length?'<table class="table table-striped gf-zebra-table"><thead><tr><th>Abonné</th><th>Téléphone</th><th>Statut</th><th>Facture</th><th>Action</th></tr></thead><tbody>'+data.map(x=>`<tr><td>${x.numero_abonne||''} - ${x.nom||''} ${x.prenom||''}</td><td>${x.tel||''}</td><td>${Number(x.gratuit)?'Gratuit annuel':'Payé'}</td><td>${x.idfacture?`<a target="_blank" href="${GF.baseUrl}/facture/pdf?id=${x.idfacture}">Facture</a>`:'-'}</td><td>${Number(x.gratuit)?`<form method="post" action="${GF.baseUrl}/gerant/planning/participant/cancel" onsubmit="return confirm('Annuler cette inscription gratuite ?')"><input type="hidden" name="_csrf" value="${GF.csrf}"><input type="hidden" name="idparticipant" value="${x.idparticipant}"><button class="btn btn-sm btn-outline-danger">Annuler</button></form>`:'-'}</td></tr>`).join('')+'</tbody></table>':'Aucun inscrit.';
      if(search){search.oninput=()=>{const q=normalizeGF(search.value);body.querySelectorAll('tbody tr').forEach(tr=>tr.style.display=(!q || normalizeGF(tr.textContent).includes(q))?'':'none');};}
    }));
  });
})();
</script>
<?php }; require __DIR__.'/../../layouts/app.php'; ?>
