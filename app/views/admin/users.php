<?php $content=function()use($rows,$csrf,$baseUrl){ ?>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card p-3">
      <h2 class="h5">Créer un compte Gérant</h2>
      <form method="post" action="<?=$baseUrl?>/admin/users/create">
        <input type="hidden" name="_csrf" value="<?=$csrf?>">
        <label class="form-label">Nom</label>
        <input class="form-control mb-2" name="nom" placeholder="Nom" required>
        <label class="form-label">Matricule</label>
        <input class="form-control mb-2" name="matricule" placeholder="Auto si vide, ex: GER-0002">
        <label class="form-label">Username</label>
        <input class="form-control mb-2" name="username" placeholder="Username" required>
        <label class="form-label">Email</label>
        <input class="form-control mb-2" type="email" name="email" placeholder="Email" required>
        <label class="form-label">Mot de passe</label>
        <input class="form-control mb-2" type="password" name="password" placeholder="Mot de passe" required>
        <label class="form-label">Statut</label>
        <select class="form-select mb-2" name="is_active"><option value="1">Actif</option><option value="0">Inactif</option></select>
        <div class="d-flex gap-2"><button class="btn btn-danger">Créer</button><button type="reset" class="btn btn-outline-secondary">Annuler</button></div>
      </form>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card p-3">
      <div class="table-responsive"><table class="table table-striped table-hover align-middle gf-zebra-table">
        <thead><tr><th>Matricule</th><th>Nom</th><th>Username</th><th>Email</th><th>Statut</th><th class="gf-action-header">Options</th></tr></thead>
        <tbody><?php foreach($rows as $r): ?>
          <tr class="gf-action-row">
            <td><span class="badge bg-dark"><?=htmlspecialchars($r['matricule']??'-')?></span></td>
            <td><?=htmlspecialchars($r['nom'])?></td>
            <td><?=htmlspecialchars($r['username'])?></td>
            <td><?=htmlspecialchars($r['email'])?></td>
            <td><?=((int)$r['is_active']===1?'Actif':'Inactif')?></td>
            <td class="gf-action-cell"><div class="gf-row-actions">
              <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editGerant<?=$r['iduser']?>">Modifier</button>
              <form class="d-inline" method="post" action="<?=$baseUrl?>/admin/users/delete" onsubmit="return confirm('Désactiver ce gérant ?')"><input type="hidden" name="_csrf" value="<?=$csrf?>"><input type="hidden" name="iduser" value="<?=$r['iduser']?>"><button class="btn btn-sm btn-outline-danger">Supprimer</button></form>
            </div>
            <div class="modal fade" id="editGerant<?=$r['iduser']?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post" action="<?=$baseUrl?>/admin/users/update"><div class="modal-header"><h5 class="modal-title">Modifier gérant</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="_csrf" value="<?=$csrf?>"><input type="hidden" name="iduser" value="<?=$r['iduser']?>"><label class="form-label">Nom</label><input class="form-control mb-2" name="nom" value="<?=htmlspecialchars($r['nom'])?>" required><label class="form-label">Matricule</label><input class="form-control mb-2" name="matricule" value="<?=htmlspecialchars($r['matricule']??'')?>" required><label class="form-label">Username</label><input class="form-control mb-2" name="username" value="<?=htmlspecialchars($r['username'])?>" required><label class="form-label">Email</label><input class="form-control mb-2" type="email" name="email" value="<?=htmlspecialchars($r['email'])?>" required><label class="form-label">Nouveau mot de passe</label><input class="form-control mb-2" type="password" name="password" placeholder="Laisser vide pour conserver"><label class="form-label">Statut</label><select class="form-select" name="is_active"><option value="1" <?=((int)$r['is_active']===1?'selected':'')?>>Actif</option><option value="0" <?=((int)$r['is_active']!==1?'selected':'')?>>Inactif</option></select></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button><button class="btn btn-danger">Enregistrer</button></div></form></div></div></div>
            </td>
          </tr>
        <?php endforeach; ?></tbody>
      </table></div>
    </div>
  </div>
</div>
<?php }; require __DIR__.'/../layouts/app.php'; ?>
