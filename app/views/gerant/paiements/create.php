<?php $content=function()use($abonnes,$refs,$selected,$error,$csrf){
$m=fn($v)=>number_format((float)$v,0,',',' ');
$isReabonne=isset($_GET['reabonne']);
$tarifs=[]; foreach($refs['tarifs'] as $t){ $tarifs[$t['idtype'].'_'.$t['idmode']]=['montant'=>(float)$t['montant'],'mode'=>$t['mode_nom'],'type'=>$t['type_nom']]; }
?>
<?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<?php if(isset($_GET['reabonne'])): ?><div class="alert alert-info"><b>Réabonnement :</b> choisissez l’ancien abonné puis enregistrez son nouveau type/mode d’abonnement.</div><?php endif; ?>
<div class="card border-0 shadow-sm p-4">
<form method="post" id="paymentForm" data-unsaved-message="<?= $isReabonne ? 'Ce réabonnement n\'a pas encore été enregistré. Voulez-vous quitter cette page ?' : 'Cet abonnement n\'a pas encore été enregistré. Voulez-vous quitter cette page ?' ?>">
<input type="hidden" name="_csrf" value="<?=$csrf?>">
<input type="hidden" name="is_reabonne" value="<?=$isReabonne?1:0?>">
<div class="row g-3">
 <div class="col-md-6"><label class="form-label fw-semibold">Abonné</label><input type="text" class="form-control mb-2 gf-select-search" data-target="idabonne_select" placeholder="Tapez nom ou numéro abonné..."><select class="form-select" name="idabonne" id="idabonne_select" size="6" required><?php foreach($abonnes as $a): $jr=max(0,(int)($a['jours_restants']??0)); $isActive=$jr>0; $labelStat=$isActive?'Actif':'Expiré'; $activeTypes=(string)($a['active_type_ids']??''); ?><option value="<?=$a['idabonne']?>" data-active="<?=$isActive?1:0?>" data-active-types="<?=htmlspecialchars($activeTypes)?>" data-days="<?=$jr?>" data-fin="<?=htmlspecialchars($a['date_fin']??'')?>" <?=$selected===(int)$a['idabonne']?'selected':''?>><?=htmlspecialchars(($a['numero_abonne']??'').' - '.$a['nom'].' '.$a['prenom'].' - '.(($a['type_nom']??'')?:'Aucun type actif').' / '.(($a['mode_nom']??'')?:'Aucun mode actif').' - '.$labelStat.($isActive?' ('.$jr.' jour(s) restant(s))':''))?></option><?php endforeach; ?></select><div class="form-text">Le téléphone n’est pas affiché ici. Le statut, le type et le mode actifs sont visibles pour éviter un doublon.</div><div id="reabonneActiveNotice" class="alert alert-warning py-2 px-3 mt-2 d-none">Cet abonné est encore actif. Le nouvel abonnement sera préparé pour commencer après la fin de l’abonnement actuel.</div><div id="sameTypeNotice" class="alert alert-danger py-2 px-3 mt-2 d-none">Séance journalière refusée pour ce type : l’abonné possède déjà un abonnement actif du même type. Choisissez un autre type de séance.</div><div id="activeLongNotice" class="alert alert-danger py-2 px-3 mt-2 d-none">Cet abonné possède déjà un abonnement actif. Pour ajouter un abonnement 1 mois, 6 mois ou annuel, utilisez Réabonner afin de préparer la suite.</div></div>
 <div class="col-md-3"><label class="form-label fw-semibold">Type d’abonnement</label><select class="form-select" name="idtype" id="idtype"><?php foreach($refs['types'] as $t): ?><option value="<?=$t['idtype']?>"><?=htmlspecialchars($t['nom'])?></option><?php endforeach; ?></select></div>
 <div class="col-md-3"><label class="form-label fw-semibold">Mode / durée</label><select class="form-select" name="idmode" id="idmode"><?php foreach($refs['modes'] as $mde): ?><option value="<?=$mde['idmode']?>"><?=htmlspecialchars($mde['nom'])?></option><?php endforeach; ?></select></div>
 <div class="col-md-4"><label class="form-label fw-semibold">Type de paiement</label><select class="form-select" name="type_paiement" id="type_paiement"><option value="complet">Paiement complet</option><option value="moitie">Paiement moitié</option></select><div class="form-text">La moitié est interdite pour une séance par jour.</div></div>
 <div class="col-md-4"><label class="form-label fw-semibold">Date début</label><input type="date" class="form-control" name="date_debut" value="<?=date('Y-m-d')?>"></div>
 <div class="col-md-4"><label class="form-label fw-semibold">Mode paiement</label><select class="form-select" name="mode_paiement"><option>Espèces</option><option>Mobile Money</option><option>Carte</option><option>Virement</option></select></div>
 <div class="col-md-4"><label class="form-label fw-semibold">Montant total</label><input class="form-control bg-light" id="montant_total_display" readonly></div>
 <div class="col-md-4"><label class="form-label fw-semibold">Montant à payer</label><input class="form-control" name="montant_paye" id="montant_paye" required readonly></div>
 <div class="col-md-4"><label class="form-label fw-semibold">Reste à payer</label><input class="form-control bg-light" id="reste_display" readonly></div>
</div>
<div class="d-flex gap-2 mt-4"><button class="btn btn-danger"><?=isset($_GET['reabonne'])?'Enregistrer le réabonnement':'Enregistrer le paiement'?></button><a class="btn btn-outline-secondary gf-unsaved-close" href="<?=rtrim(BASE_URL,'/')?>/gerant/paiements">Fermer</a></div>
</form>
</div>
<div class="alert alert-warning mt-3 mb-0"><b>Règle :</b> séance par jour = paiement complet obligatoire. Mensuel, 6 mois et annuel = moitié acceptée seulement si elle atteint au moins 50% du tarif.</div>
<script>
const tarifs = <?=json_encode($tarifs,JSON_UNESCAPED_UNICODE)?>;
const isReabonne = <?=$isReabonne?'true':'false'?>;
function fmt(n){return new Intl.NumberFormat('fr-FR').format(n)+' Ar';}
function refreshAmount(){
 const type=document.getElementById('idtype').value;
 const mode=document.getElementById('idmode').value;
 const pay=document.getElementById('type_paiement');
 const ref=tarifs[type+'_'+mode]; if(!ref) return;
 const total=Number(ref.montant||0);
 if(mode==='1'){ pay.value='complet'; pay.querySelector('option[value="moitie"]').disabled=true; } else { pay.querySelector('option[value="moitie"]').disabled=false; }
 const paid = pay.value==='moitie' ? Math.ceil(total/2) : total;
 document.getElementById('montant_total_display').value=fmt(total);
 document.getElementById('montant_paye').value=paid;
 document.getElementById('reste_display').value=fmt(Math.max(0,total-paid));
}
['idtype','idmode','type_paiement'].forEach(id=>document.getElementById(id).addEventListener('change',()=>{refreshAmount();refreshSelectedAbonneStatus();}));
refreshAmount();

const abonneSelect=document.getElementById('idabonne_select');
const activeNotice=document.getElementById('reabonneActiveNotice');
function refreshSelectedAbonneStatus(){
 const opt=abonneSelect ? abonneSelect.selectedOptions[0] : null;
 const isActive=opt && opt.dataset.active==='1';
 if(activeNotice) activeNotice.classList.toggle('d-none', !(isActive && isReabonne));
 const activeTypes=(opt && opt.dataset.activeTypes ? opt.dataset.activeTypes.split(',').filter(Boolean) : []);
 const typeSelect=document.getElementById('idtype');
 const modeSelect=document.getElementById('idmode');
 const selectedMode=modeSelect ? String(modeSelect.value) : '';
 const sameTypeNotice=document.getElementById('sameTypeNotice');
 const activeLongNotice=document.getElementById('activeLongNotice');
 if(typeSelect){
   Array.from(typeSelect.options).forEach(o=>{
     // On bloque uniquement la séance journalière du même type que l’abonnement actif.
     // Exemple : actif Normal mensuel => séance Normal désactivée, séance Premium/VIP autorisée.
     const locked=!isReabonne && selectedMode==='1' && activeTypes.includes(String(o.value));
     o.disabled=locked;
   });
   if(typeSelect.selectedOptions[0] && typeSelect.selectedOptions[0].disabled){
     const next=Array.from(typeSelect.options).find(o=>!o.disabled);
     if(next) typeSelect.value=next.value;
   }
   const sameTypeDailyBlocked=!isReabonne && selectedMode==='1' && activeTypes.includes(String(typeSelect.value));
   if(sameTypeNotice){ sameTypeNotice.classList.toggle('d-none', !sameTypeDailyBlocked); }
 }
 const longModeBlocked=!isReabonne && isActive && selectedMode!=='1';
 if(activeLongNotice){ activeLongNotice.classList.toggle('d-none', !longModeBlocked); }
 refreshAmount();
}
if(abonneSelect){ abonneSelect.addEventListener('change', refreshSelectedAbonneStatus); refreshSelectedAbonneStatus(); }

document.querySelectorAll('.gf-select-search').forEach(input=>{
 const select=document.getElementById(input.dataset.target);
 if(!select) return;
 const options=Array.from(select.options);
 input.addEventListener('input',()=>{
   const q=input.value.toLowerCase().trim();
   options.forEach(opt=>{ opt.hidden = !!q && !opt.textContent.toLowerCase().includes(q); });
   const first=options.find(opt=>!opt.hidden);
   if(first) first.selected=true;
   refreshSelectedAbonneStatus();
 });
});
const paymentForm=document.getElementById('paymentForm');
if(paymentForm && abonneSelect){
 paymentForm.addEventListener('submit',(e)=>{
   const opt=abonneSelect.selectedOptions[0];
   const activeTypes=(opt && opt.dataset.activeTypes ? opt.dataset.activeTypes.split(',').filter(Boolean) : []);
   const type=document.getElementById('idtype').value;
   const mode=document.getElementById('idmode').value;
   if(!isReabonne && mode==='1' && activeTypes.includes(String(type))){
     e.preventDefault();
     alert('Séance journalière refusée : ce type est déjà actif pour cet abonné. Choisissez un autre type de séance.');
     return;
   }
   if(!isReabonne && mode!=='1' && opt && opt.dataset.active==='1'){
     e.preventDefault();
     alert('Cet abonné possède déjà un abonnement actif. Utilisez Réabonner pour préparer la suite après expiration.');
     return;
   }
   if(isReabonne && opt && opt.dataset.active==='1'){
     const ok=confirm('Cet abonné est encore actif. Voulez-vous préparer ce nouvel abonnement après la fin de son abonnement actuel ?');
     if(!ok) e.preventDefault();
   }
 });
}


// Confirmation de sortie si le paiement/réabonnement n'est pas enregistré.
(function(){
 const form=document.getElementById('paymentForm');
 if(!form) return;
 let saved=false;
 const closeLinks=document.querySelectorAll('.gf-unsaved-close');
 const watched=Array.from(form.querySelectorAll('input,select,textarea')).filter(el=>el.name !== '_csrf');
 const initial=watched.map(el=>`${el.name||el.id}=${el.value}`).join('&');
 const hasChanged=()=> watched.map(el=>`${el.name||el.id}=${el.value}`).join('&') !== initial;
 form.addEventListener('submit',()=>{ saved=true; });
 async function askQuit(e){
   if(saved || !hasChanged()) return;
   e.preventDefault();
   const url=this.href;
   const message=form.dataset.unsavedMessage || 'Les informations saisies ne sont pas enregistrées. Voulez-vous quitter cette page ?';
   if(typeof Swal !== 'undefined'){
     const res=await Swal.fire({
       icon:'warning',
       title:'Quitter sans enregistrer ?',
       text:message,
       showCancelButton:true,
       confirmButtonText:'Oui',
       cancelButtonText:'Non',
       reverseButtons:true
     });
     if(res.isConfirmed) window.location.href=url;
   } else {
     if(confirm(message)) window.location.href=url;
   }
 }
 closeLinks.forEach(a=>a.addEventListener('click', askQuit));
})();

</script>
<?php }; require __DIR__.'/../../layouts/app.php'; ?>
