<?php
$refs=$refs??[]; $modeStats=$modeStats??[];
$content=function()use($refs,$modeStats){
$m=fn($v)=>number_format((float)$v,0,',',' ').' Ar';
$colors=['normal'=>'danger','premium'=>'primary','prenium'=>'primary','vip'=>'warning'];
$group=[]; foreach($refs['tarifs'] as $t){ $group[$t['type_nom']][]=$t; }
$desc=[]; foreach($refs['types'] as $t){ $desc[$t['nom']]=$t['description']??''; }
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h4 mb-1">Abonnements et tarifs actuels</h2>
    <p class="text-muted mb-0">Prix consultables par le Gérant. Modification réservée à l’Admin.</p>
  </div>
</div>
<div class="row g-4">
<?php foreach($group as $type=>$rows): $key=strtolower($type); $c=$colors[$key]??'success'; $stats=$modeStats[$type]??[]; $canvasId='chartType'.preg_replace('/[^a-zA-Z0-9]/','',$type); ?>
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100 overflow-hidden gf-type-card gf-type-<?=$c?>">
      <div class="p-3 bg-<?=$c?> text-white">
        <div class="d-flex justify-content-between align-items-center">
          <h3 class="h4 mb-0"><?=htmlspecialchars($type)?></h3>
          <span class="badge bg-light text-dark">GO-FITNESS</span>
        </div>
        <div class="small mt-2 text-white"><?=nl2br(htmlspecialchars($desc[$type] ?: 'Description non renseignée par l’Admin.'))?></div>
      </div>
      <div class="p-3">
        <?php foreach($rows as $r): ?>
          <div class="d-flex justify-content-between align-items-center border-bottom py-2">
            <span class="fw-semibold"><?=htmlspecialchars($r['mode_nom'])?></span>
            <span class="fs-5 fw-bold"><?=$m($r['montant'])?></span>
          </div>
        <?php endforeach; ?>
        <div class="mt-3">
          <div class="fw-semibold mb-2">Répartition des abonnements</div>
          <div class="gf-mini-chart"><canvas id="<?=$canvasId?>"></canvas></div>
        </div>
      </div>
    </div>
  </div>
  <script type="application/json" id="<?=$canvasId?>Data"><?=json_encode($stats,JSON_UNESCAPED_UNICODE)?></script>
<?php endforeach; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('[id$="Data"][type="application/json"]').forEach(tag=>{
    const id=tag.id.replace('Data',''); const canvas=document.getElementById(id); if(!canvas || typeof Chart==='undefined') return;
    let rows=[]; try{ rows=JSON.parse(tag.textContent||'[]'); }catch(e){}
    new Chart(canvas,{type:'bar',data:{labels:rows.map(r=>r.mode),datasets:[{label:'Abonnés',data:rows.map(r=>Number(r.total||0)),borderWidth:1}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});
  });
});
</script>
<?php }; require __DIR__.'/../../layouts/app.php'; ?>
