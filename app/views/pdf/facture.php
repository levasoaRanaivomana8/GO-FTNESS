<?php
$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$m=fn($v)=>number_format((float)$v,0,',',' ').' Ar';
$logoPath=__DIR__.'/../../../public/assets/images/logo.png';
$logo='';
if(is_file($logoPath)){ $logo='data:image/png;base64,'.base64_encode(file_get_contents($logoPath)); }
$reste=(float)($f['reste_a_payer'] ?? 0);
$isPlanning=(($f['type_facture'] ?? '') === 'planning');
$periode=$isPlanning ? ($f['planning_date'] ?? '') : (($f['date_debut'] ?? '').' au '.($f['date_fin'] ?? ''));
$type=$isPlanning ? 'Planning' : ($f['type_nom'] ?? '');
$mode=$isPlanning ? ($f['planning_activite'] ?? 'Participation') : ($f['mode_nom'] ?? '');
$paymentSeq=(int)($f['paiement_sequence'] ?? 1);
$paymentCount=(int)($f['paiement_count'] ?? 1);
$paymentNote='';
if(!$isPlanning && $paymentCount>1){
  $paymentNote = $paymentSeq===1 ? 'Facture 1 : premier paiement 50%' : 'Facture '.$paymentSeq.' : paiement du reste / deuxième 50%';
} elseif(!$isPlanning && $reste>0){
  $paymentNote = 'Paiement partiel : premier 50%';
}
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;color:#111;font-size:13px}.top{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:4px solid #dc2626;padding-bottom:12px}.brandWrap{display:flex;gap:12px;align-items:center}.logo{width:72px;height:72px;object-fit:contain;border:1px solid #eee;padding:6px;background:#fff}.brand{font-size:27px;font-weight:bold;color:#111}.box{border:1px solid #ddd;padding:12px;margin:14px 0;border-radius:6px}.table{width:100%;border-collapse:collapse;margin-top:12px}.table td,.table th{border:1px solid #ddd;padding:9px}.table th{background:#f3f4f6}.right{text-align:right}.center{text-align:center}.sign{width:230px;text-align:center;padding-top:70px}.sign-line{border-top:1px solid #111;padding-top:8px}.muted{color:#555;font-size:11px}.danger{color:#dc2626;font-weight:bold}.total{font-size:15px;font-weight:bold;background:#fff7ed}.small{font-size:11px;color:#555}
</style></head><body>
<div class="top"><div class="brandWrap"><?php if($logo): ?><img class="logo" src="<?=$logo?>"><?php endif; ?><div><div class="brand">GO-FITNESS</div><div>Contact : 0385911846</div><div>Adresse : LOT III B 09 AMBOHIBAO</div></div></div><div class="right"><h1>FACTURE</h1><?php if($paymentNote): ?><div class="danger"><?= $e($paymentNote) ?></div><?php endif; ?><b>Numéro : <?= $e($f['numero']) ?></b><br>Date : <?= $e($f['date_facture']) ?><br>Statut : <?= $e($f['facture_statut'] ?? $f['statut'] ?? '') ?></div></div>
<div class="box"><h3>Informations du client</h3><p><b><?= $e(trim(($f['nom']??'').' '.($f['prenom']??''))) ?></b><br>Téléphone : <?= $e($f['tel']??'') ?><br>Adresse : <?= $e($f['adresse']??'') ?></p></div>
<table class="table">
 <tr><th>Type abonnement</th><th>Mode</th><th>Période</th><th class="right">Montant</th></tr>
 <tr><td><?= $e($type) ?></td><td><?= $e($mode) ?><?php if($isPlanning && !empty($f['planning_lieu'])): ?><br><span class="small">Lieu : <?= $e($f['planning_lieu']) ?></span><?php endif; ?></td><td><?= $e($periode) ?></td><td class="right"><?= $m($f['montant_total']) ?></td></tr>
 <tr><td colspan="3">Montant payé<?php if($paymentNote): ?> <span class="small">(<?= $e($paymentNote) ?>)</span><?php endif; ?></td><td class="right"><?= $m($f['montant_paye']) ?></td></tr>
 <?php if($reste > 0): ?>
 <tr class="total"><td colspan="3">Reste à payer<?php if(!empty($f['date_limite_reste'])): ?> — Date limite : <?= $e($f['date_limite_reste']) ?><?php endif; ?></td><td class="right danger"><?= $m($reste) ?></td></tr>
 <?php endif; ?>
</table>
<?php if($reste > 0): ?><div class="box"><b>Remarque :</b> Paiement partiel. Reste à payer : <span class="danger"><?= $m($reste) ?></span><?php if(!empty($f['date_limite_reste'])): ?> avant le <?= $e($f['date_limite_reste']) ?><?php endif; ?>.</div><?php endif; ?>
<p class="muted">Facture générée par GO-FITNESS. Le numéro de facture est unique et ne doit jamais être réutilisé, même en cas d’annulation.</p>
<div style="display:flex;justify-content:space-between;margin-top:80px"><div class="sign"><div class="sign-line">Signature du Gérant</div></div><div class="sign"><div class="sign-line">Signature du Client</div></div></div>
</body></html>
