<?php
declare(strict_types=1);
namespace App\Models;
use Config\DB; use PDO;
final class App {
 public static function pdo(): PDO { return DB::pdo(); }
 public static function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
 public static function money($v): string { return number_format((float)$v,0,',',' ').' Ar'; }
 public static function log(?array $u,string $action,string $entity='',?string $id=null,$old=null,$new=null,?string $motif=null): void { $p=self::pdo(); $st=$p->prepare('INSERT INTO audit_logs(user_id,role,action,entity,entity_id,old_value,new_value,motif,ip_address) VALUES(?,?,?,?,?,?,?,?,?)'); $st->execute([$u['iduser']??null,$u['role']??null,$action,$entity,$id,json_encode($old,JSON_UNESCAPED_UNICODE),json_encode($new,JSON_UNESCAPED_UNICODE),$motif,$_SERVER['REMOTE_ADDR']??null]); }
 public static function notify(string $role,string $titre,string $msg,?int $uid=null): void { $st=self::pdo()->prepare('INSERT INTO notifications(role_cible,user_id,titre,message) VALUES(?,?,?,?)'); $st->execute([$role,$uid,$titre,$msg]); }
 public static function deleteNotification(int $id, array $u): void { $p=self::pdo(); $st=$p->prepare('SELECT * FROM notifications WHERE idnotification=?'); $st->execute([$id]); $before=$st->fetch(); if(!$before) return; $p->prepare('DELETE FROM notifications WHERE idnotification=?')->execute([$id]); self::log($u,'Suppression notification interne','notifications',(string)$id,$before,null,'Suppression par admin'); }
 public static function refs(): array { $p=self::pdo(); return ['types'=>$p->query('SELECT * FROM types_abonnement WHERE is_active=1 ORDER BY idtype')->fetchAll(), 'modes'=>$p->query('SELECT * FROM modes_abonnement ORDER BY idmode')->fetchAll(), 'tarifs'=>$p->query('SELECT t.*,ta.nom type_nom, m.nom mode_nom FROM tarifs t JOIN types_abonnement ta ON ta.idtype=t.idtype JOIN modes_abonnement m ON m.idmode=t.idmode ORDER BY ta.idtype,m.idmode')->fetchAll()]; }
 public static function updateExpired(): void { self::pdo()->exec("UPDATE abonnements SET statut='expire' WHERE statut='actif' AND date_fin < CURDATE()"); self::pdo()->exec("UPDATE abonnes a SET statut='expire' WHERE statut='actif' AND NOT EXISTS(SELECT 1 FROM abonnements ab WHERE ab.idabonne=a.idabonne AND ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE())"); }
 public static function abonneSearch(string $q='', string $type='', string $modeGroup=''): array {
  self::updateExpired();
  $p=self::pdo();
  $params=[];
  $where=["a.statut<>'desactive'"];
  if($q!==''){
    $where[]="(a.numero_abonne LIKE ? OR a.nom LIKE ? OR a.prenom LIKE ? OR a.tel LIKE ? OR f.numero LIKE ?)";
    $like='%'.$q.'%'; array_push($params,$like,$like,$like,$like,$like);
  }
  if($type!=='' && in_array($type,['Normal','Premium','VIP'],true)){
    $where[]="ta.nom = ?"; $params[]=$type;
  }
  if($modeGroup==='journalier'){
    $where[]="m.idmode = 1";
  } elseif($modeGroup==='abonnement'){
    $where[]="m.idmode <> 1";
  }
  $sql="SELECT a.*,
        SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE() THEN ta.nom END ORDER BY ab.date_fin DESC SEPARATOR '||'),'||',1) AS type_nom,
        SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE() THEN m.nom END ORDER BY ab.date_fin DESC SEPARATOR '||'),'||',1) AS mode_nom,
        SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE() THEN m.idmode END ORDER BY ab.date_fin DESC SEPARATOR '||'),'||',1) AS idmode,
        MAX(CASE WHEN ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE() THEN ab.date_fin END) date_fin,
        GREATEST(COALESCE(DATEDIFF(MAX(CASE WHEN ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE() THEN ab.date_fin END),CURDATE()),0),0) jours_restants,
        MAX(CASE WHEN ab.idmode=4 AND ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE() THEN 1 ELSE 0 END) has_annuel_actif,
        GROUP_CONCAT(DISTINCT CASE WHEN ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE() THEN ab.idtype END) AS active_type_ids
        FROM abonnes a
        LEFT JOIN abonnements ab ON ab.idabonne=a.idabonne AND ab.statut<>'annule'
        LEFT JOIN types_abonnement ta ON ta.idtype=ab.idtype
        LEFT JOIN modes_abonnement m ON m.idmode=ab.idmode
        LEFT JOIN paiements pmt ON pmt.idabonnement=ab.idabonnement
        LEFT JOIN factures f ON f.idpaiement=pmt.idpaiement
        WHERE ".implode(' AND ',$where)."
        GROUP BY a.idabonne
        ORDER BY jours_restants ASC, a.nom ASC, a.prenom ASC
        LIMIT 500";
  $st=$p->prepare($sql); $st->execute($params); return $st->fetchAll();
 }

 public static function dashboardAbonnes(string $statut='en_cours', string $q='', int $page=1, int $perPage=10, string $daysOp='', ?int $daysValue=null): array {
  self::updateExpired();
  $p=self::pdo();
  $statut=in_array($statut,['all','en_cours','expire'],true)?$statut:'en_cours';
  $page=max(1,$page); $perPage=max(5,min(500,$perPage)); $offset=($page-1)*$perPage;
  $params=[]; $where=["a.statut<>'desactive'"];
  if($q!==''){
    $where[]="(a.numero_abonne LIKE ? OR a.nom LIKE ? OR a.prenom LIKE ? OR a.tel LIKE ? OR f.numero LIKE ? OR ta.nom LIKE ? OR m.nom LIKE ?)";
    $like='%'.$q.'%'; array_push($params,$like,$like,$like,$like,$like,$like,$like);
  }

  // MariaDB/XAMPP ne supporte pas toujours l'utilisation d'un alias calculé
  // à partir d'une fonction d'agrégation dans HAVING/ORDER BY au même niveau.
  // On calcule donc les jours restants dans une sous-requête, puis on filtre dessus.
  $inner="SELECT
        a.idabonne, a.numero_abonne, a.nom, a.prenom, a.tel, a.adresse, a.sexe, a.age, a.statut, a.created_at,
        MAX(CASE WHEN ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE() THEN ab.date_fin END) AS date_fin,
        SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE() THEN ta.nom END ORDER BY ab.date_fin DESC SEPARATOR '||'),'||',1) AS type_nom,
        SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE() THEN m.nom END ORDER BY ab.date_fin DESC SEPARATOR '||'),'||',1) AS mode_nom,
        GREATEST(COALESCE(DATEDIFF(MAX(CASE WHEN ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE() THEN ab.date_fin END),CURDATE()),0),0) AS jours_restants
        FROM abonnes a
        LEFT JOIN abonnements ab ON ab.idabonne=a.idabonne AND ab.statut<>'annule'
        LEFT JOIN types_abonnement ta ON ta.idtype=ab.idtype
        LEFT JOIN modes_abonnement m ON m.idmode=ab.idmode
        LEFT JOIN paiements pmt ON pmt.idabonnement=ab.idabonnement
        LEFT JOIN factures f ON f.idpaiement=pmt.idpaiement
        WHERE ".implode(' AND ',$where)."
        GROUP BY a.idabonne";

  $outerWhere=[]; $outerParams=$params;
  if($statut==='expire') $outerWhere[]='x.jours_restants = 0';
  elseif($statut==='en_cours') $outerWhere[]='x.jours_restants > 0';
  if($daysValue!==null && in_array($daysOp,['<','<=','>','>=','='],true)){
    $outerWhere[]='x.jours_restants '.$daysOp.' ?'; $outerParams[]=$daysValue;
  }
  $outer=" FROM (".$inner.") x";
  if($outerWhere){ $outer .= " WHERE ".implode(' AND ',$outerWhere); }

  $countSql="SELECT COUNT(*)".$outer;
  $st=$p->prepare($countSql); $st->execute($outerParams); $total=(int)$st->fetchColumn();

  $sql="SELECT x.*".$outer." ORDER BY CASE WHEN x.jours_restants=0 THEN 1 ELSE 0 END ASC, x.jours_restants ASC, x.nom ASC, x.prenom ASC LIMIT ".(int)$perPage." OFFSET ".(int)$offset;
  $st=$p->prepare($sql); $st->execute($outerParams); $rows=$st->fetchAll();
  return ['rows'=>$rows,'total'=>$total,'page'=>$page,'perPage'=>$perPage,'pages'=>max(1,(int)ceil($total/$perPage))];
 }
 public static function findAbonne(int $id): ?array { $st=self::pdo()->prepare('SELECT * FROM abonnes WHERE idabonne=?'); $st->execute([$id]); return $st->fetch()?:null; }
 public static function createAbonne(array $d,array $u): int { $p=self::pdo(); $st=$p->prepare('INSERT INTO abonnes(numero_abonne,nom,prenom,tel,adresse,sexe,age,created_by) VALUES(NULL,?,?,?,?,?,?,?)'); $st->execute([$d['nom'],$d['prenom'],$d['tel'],$d['adresse'],$d['sexe'],(int)$d['age'],$u['iduser']]); $id=(int)$p->lastInsertId(); $num='GF-A'.str_pad((string)$id,6,'0',STR_PAD_LEFT); $p->prepare('UPDATE abonnes SET numero_abonne=? WHERE idabonne=?')->execute([$num,$id]); self::log($u,'Création abonné','abonnes',(string)$id,null,$d); return $id; }
 public static function updateAbonne(int $id,array $d,array $u): void { $old=self::findAbonne($id); self::pdo()->prepare('UPDATE abonnes SET nom=?,prenom=?,tel=?,adresse=?,sexe=?,age=?,statut=? WHERE idabonne=?')->execute([$d['nom'],$d['prenom'],$d['tel'],$d['adresse'],$d['sexe'],(int)$d['age'],$d['statut'],$id]); self::log($u,'Modification abonné','abonnes',(string)$id,$old,$d); }
 public static function deactivateAbonne(int $id,array $u,string $motif=''): void { self::pdo()->prepare("UPDATE abonnes SET statut='desactive' WHERE idabonne=?")->execute([$id]); self::log($u,'Désactivation abonné','abonnes',(string)$id,null,null,$motif); }

 public static function activeTypeConflict(int $idabonne, int $idtype): ?array {
  self::updateExpired();
  $p=self::pdo();
  $owner=$p->prepare('SELECT nom,prenom,tel FROM abonnes WHERE idabonne=? LIMIT 1');
  $owner->execute([$idabonne]);
  $a=$owner->fetch();
  if(!$a) return ['message'=>'Abonné introuvable.'];
  $sql="SELECT a.idabonne,a.numero_abonne,a.nom,a.prenom,a.tel,ab.idabonnement,ab.idtype,ab.idmode,ab.date_debut,ab.date_fin,ta.nom type_nom,m.nom mode_nom
        FROM abonnes a
        JOIN abonnements ab ON ab.idabonne=a.idabonne
        JOIN types_abonnement ta ON ta.idtype=ab.idtype
        JOIN modes_abonnement m ON m.idmode=ab.idmode
        WHERE LOWER(a.nom)=LOWER(?) AND LOWER(a.prenom)=LOWER(?) AND a.tel=?
          AND ab.idtype=? AND ab.statut='actif'
          AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE()
        ORDER BY ab.date_fin DESC LIMIT 1";
  $st=$p->prepare($sql); $st->execute([$a['nom'],$a['prenom'],$a['tel'],$idtype]);
  $row=$st->fetch();
  if(!$row) return null;
  return $row;
 }

 public static function activeSubscriptionConflict(int $idabonne): ?array {
  self::updateExpired();
  $p=self::pdo();
  $owner=$p->prepare('SELECT nom,prenom,tel FROM abonnes WHERE idabonne=? LIMIT 1');
  $owner->execute([$idabonne]);
  $a=$owner->fetch();
  if(!$a) return ['message'=>'Abonné introuvable.'];
  $sql="SELECT a.idabonne,a.numero_abonne,a.nom,a.prenom,a.tel,ab.idabonnement,ab.idtype,ab.idmode,ab.date_debut,ab.date_fin,ta.nom type_nom,m.nom mode_nom
        FROM abonnes a
        JOIN abonnements ab ON ab.idabonne=a.idabonne
        JOIN types_abonnement ta ON ta.idtype=ab.idtype
        JOIN modes_abonnement m ON m.idmode=ab.idmode
        WHERE LOWER(a.nom)=LOWER(?) AND LOWER(a.prenom)=LOWER(?) AND a.tel=?
          AND ab.statut='actif'
          AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE()
        ORDER BY ab.date_fin DESC LIMIT 1";
  $st=$p->prepare($sql); $st->execute([$a['nom'],$a['prenom'],$a['tel']]);
  $row=$st->fetch();
  return $row ?: null;
 }

 public static function createSubscriptionPaymentInvoice(array $d,array $u): array {
  $p=self::pdo();
  $type=(int)$d['idtype']; $mode=(int)$d['idmode']; $idab=(int)$d['idabonne'];
  $isReabonne=!empty($d['is_reabonne']);
  $st=$p->prepare('SELECT t.montant,m.duree_jours,m.allow_partial,m.nom mode_nom,ta.nom type_nom FROM tarifs t JOIN modes_abonnement m ON m.idmode=t.idmode JOIN types_abonnement ta ON ta.idtype=t.idtype WHERE t.idtype=? AND t.idmode=?');
  $st->execute([$type,$mode]); $ref=$st->fetch(); if(!$ref) throw new \RuntimeException('Tarif introuvable');
  $total=(float)$ref['montant'];
  $paye=(float)str_replace([' ', ','],['','.'],(string)$d['montant_paye']);
  if($mode===1 && $paye<$total) throw new \RuntimeException('La séance par jour exige un paiement complet.');
  if($paye<$total && (int)$ref['allow_partial']!==1) throw new \RuntimeException('Paiement partiel interdit pour ce mode.');
  if($paye<$total && $paye < ($total/2)) throw new \RuntimeException('Paiement partiel refusé : minimum la moitié du total.');
  if($paye>$total) throw new \RuntimeException('Montant payé supérieur au total.');

  // Règle métier GO-FITNESS :
  // - si l'abonné possède déjà un abonnement actif mensuel/6 mois/annuel, il ne peut pas créer un second abonnement long en parallèle ; il doit passer par Réabonner.
  // - il peut acheter une séance journalière seulement si le type choisi est différent du type déjà actif.
  //   Exemple : actif Normal mensuel => séance VIP/Premium autorisée, séance Normal refusée.
  if(!$isReabonne){
    if($mode===1){
      $sameTypeConflict=self::activeTypeConflict($idab,$type);
      if($sameTypeConflict){
        throw new \RuntimeException('Séance journalière refusée : cet abonné possède déjà un abonnement actif de type '.$sameTypeConflict['type_nom'].' jusqu\'au '.$sameTypeConflict['date_fin'].'. Choisissez une séance d’un autre type ou attendez l’expiration.');
      }
    } else {
      $activeConflict=self::activeSubscriptionConflict($idab);
      if($activeConflict){
        throw new \RuntimeException('Abonnement refusé : cet abonné possède déjà un abonnement actif '.$activeConflict['type_nom'].' / '.$activeConflict['mode_nom'].' jusqu\'au '.$activeConflict['date_fin'].'. Utilisez Réabonner pour préparer la suite après expiration.');
      }
    }
  }

  // Réabonnement/prolongation : si l'abonné est encore actif, le nouvel abonnement commence le lendemain de sa date fin actuelle.
  $cur=$p->prepare("SELECT MAX(date_fin) current_fin, GREATEST(COALESCE(DATEDIFF(MAX(date_fin),CURDATE()),0),0) jours_restants FROM abonnements WHERE idabonne=? AND statut='actif' AND date_debut<=CURDATE() AND date_fin>=CURDATE()");
  $cur->execute([$idab]); $current=$cur->fetch()?:[];
  $currentFin=$current['current_fin']??null;
  $hasActive=!empty($currentFin) && (int)($current['jours_restants']??0)>0;

  $requestedDebut=$d['date_debut']?:date('Y-m-d');
  $debut=$hasActive ? date('Y-m-d', strtotime($currentFin.' +1 day')) : $requestedDebut;
  $fin=date('Y-m-d', strtotime($debut.' +'.(((int)$ref['duree_jours'])-1).' days'));
  $reste=max(0,$total-$paye); $stat=$reste>0?'partiel':'paye';
  $limite=$reste>0?date('Y-m-d', strtotime($debut.' +'.floor(((int)$ref['duree_jours'])/2).' days')):null;
  $p->beginTransaction();
  $p->prepare('INSERT INTO abonnements(idabonne,idtype,idmode,date_debut,date_fin,created_by) VALUES(?,?,?,?,?,?)')->execute([$idab,$type,$mode,$debut,$fin,$u['iduser']]);
  $idsub=(int)$p->lastInsertId();
  $p->prepare('INSERT INTO paiements(idabonnement,montant_total,montant_paye,reste_a_payer,mode_paiement,statut,date_limite_reste,created_by) VALUES(?,?,?,?,?,?,?,?)')->execute([$idsub,$total,$paye,$reste,$d['mode_paiement']?:'Espèces',$stat,$limite,$u['iduser']]);
  $idpay=(int)$p->lastInsertId(); $numero='GoF-'.str_pad((string)$idpay,6,'0',STR_PAD_LEFT);
  $p->prepare('INSERT INTO factures(numero,idpaiement,type_facture,created_by) VALUES(?,?,?,?)')->execute([$numero,$idpay,'abonnement',$u['iduser']]);
  $idfac=(int)$p->lastInsertId(); $p->commit();
  self::log($u,$hasActive?'Réabonnement avec prolongation jours restants':'Création abonnement/paiement/facture','factures',(string)$idfac,null,array_merge($d,['date_debut_effective'=>$debut,'date_fin_effective'=>$fin]));
  if($reste>0) self::notify('gerant','Reste à payer','Paiement partiel : reste à payer '.self::money($reste).' avant le '.$limite);
  return ['idabonnement'=>$idsub,'idpaiement'=>$idpay,'idfacture'=>$idfac,'numero'=>$numero,'extended'=>$hasActive];
 }

 public static function partialDuePayments(bool $onlyDueSoon=false, string $q='', string $date=''): array {
  self::updateExpired();
  $p=self::pdo();
  $where=["pm.statut='partiel'", "pm.reste_a_payer>0", "pm.date_limite_reste IS NOT NULL"];
  $params=[];
  if($onlyDueSoon){ $where[]="pm.date_limite_reste <= DATE_ADD(CURDATE(), INTERVAL 5 DAY)"; }
  if($q!==''){
    $where[]="(a.numero_abonne LIKE ? OR a.nom LIKE ? OR a.prenom LIKE ? OR a.tel LIKE ? OR ta.nom LIKE ? OR m.nom LIKE ? OR f.numero LIKE ?)";
    $like='%'.$q.'%'; array_push($params,$like,$like,$like,$like,$like,$like,$like);
  }
  if($date!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)){
    $where[]='pm.date_limite_reste=?'; $params[]=$date;
  }
  $sql="SELECT pm.idpaiement,pm.idabonnement,pm.montant_total,pm.montant_paye,pm.reste_a_payer,pm.date_limite_reste,pm.mode_paiement,pm.date_paiement,
        f.idfacture,f.numero,
        a.idabonne,a.numero_abonne,a.nom,a.prenom,a.tel,ta.nom type_nom,m.nom mode_nom,ab.date_fin,
        GREATEST(COALESCE(DATEDIFF(pm.date_limite_reste,CURDATE()),0),0) jours_avant_limite
        FROM paiements pm
        JOIN abonnements ab ON ab.idabonnement=pm.idabonnement
        JOIN abonnes a ON a.idabonne=ab.idabonne
        LEFT JOIN types_abonnement ta ON ta.idtype=ab.idtype
        LEFT JOIN modes_abonnement m ON m.idmode=ab.idmode
        LEFT JOIN factures f ON f.idpaiement=pm.idpaiement
        WHERE ".implode(' AND ',$where)."
        ORDER BY pm.date_limite_reste ASC, a.nom ASC, a.prenom ASC
        LIMIT 500";
  $st=$p->prepare($sql); $st->execute($params); return $st->fetchAll();
 }

 public static function payRemainingBalance(int $idpaiement, string $modePaiement, array $u): array {
  $p=self::pdo();
  $st=$p->prepare("SELECT pm.*,ab.idabonne FROM paiements pm JOIN abonnements ab ON ab.idabonnement=pm.idabonnement WHERE pm.idpaiement=? AND pm.statut='partiel' AND pm.reste_a_payer>0 LIMIT 1");
  $st->execute([$idpaiement]); $old=$st->fetch();
  if(!$old) throw new \RuntimeException('Reste à payer introuvable ou déjà réglé.');
  $reste=(float)$old['reste_a_payer'];
  if($reste<=0) throw new \RuntimeException('Aucun reste à payer pour ce paiement.');
  $p->beginTransaction();
  try{
    // On clôture le paiement partiel initial pour ne plus afficher le reste dans les relances.
    $p->prepare("UPDATE paiements SET reste_a_payer=0, statut='paye' WHERE idpaiement=?")->execute([$idpaiement]);
    // On crée un nouveau paiement lié au même abonnement : cela garde une trace claire de la recette du jour.
    $p->prepare('INSERT INTO paiements(idabonnement,montant_total,montant_paye,reste_a_payer,mode_paiement,statut,created_by) VALUES(?,?,?,?,?,?,?)')->execute([(int)$old['idabonnement'],$reste,$reste,0,$modePaiement?:'Espèces','paye',$u['iduser']??null]);
    $newPay=(int)$p->lastInsertId();
    $numero='GoF-'.str_pad((string)$newPay,6,'0',STR_PAD_LEFT);
    $p->prepare('INSERT INTO factures(numero,idpaiement,type_facture,created_by) VALUES(?,?,?,?)')->execute([$numero,$newPay,'abonnement',$u['iduser']??null]);
    $idfac=(int)$p->lastInsertId();
    $p->commit();
  }catch(\Throwable $e){ $p->rollBack(); throw $e; }
  self::log($u,'Paiement du reste abonnement','paiements',(string)$newPay,$old,['reste_regle'=>$reste,'ancien_paiement'=>$idpaiement,'facture'=>$numero]);
  return ['idpaiement'=>$newPay,'idfacture'=>$idfac,'numero'=>$numero,'montant'=>$reste];
 }

 public static function cancelRemainingBalance(int $idpaiement, array $u, string $motif='Reste non payé - abonnement annulé'): void {
  $p=self::pdo();
  $st=$p->prepare("SELECT pm.*,ab.idabonnement,ab.idabonne FROM paiements pm JOIN abonnements ab ON ab.idabonnement=pm.idabonnement WHERE pm.idpaiement=? AND pm.statut='partiel' AND pm.reste_a_payer>0 LIMIT 1");
  $st->execute([$idpaiement]); $row=$st->fetch();
  if(!$row) throw new \RuntimeException('Reste à payer introuvable ou déjà annulé/réglé.');
  $p->beginTransaction();
  try{
    $p->prepare("UPDATE paiements SET reste_a_payer=0, statut='annule' WHERE idpaiement=?")->execute([$idpaiement]);
    $p->prepare("UPDATE abonnements SET statut='annule' WHERE idabonnement=?")->execute([(int)$row['idabonnement']]);
    $p->prepare("UPDATE abonnes a SET statut=CASE WHEN EXISTS(SELECT 1 FROM abonnements ab WHERE ab.idabonne=a.idabonne AND ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE()) THEN 'actif' ELSE 'expire' END WHERE idabonne=?")->execute([(int)$row['idabonne']]);
    $p->commit();
  }catch(\Throwable $e){ $p->rollBack(); throw $e; }
  self::log($u,'Annulation reste à payer','paiements',(string)$idpaiement,$row,['reste_a_payer'=>0,'statut'=>'annule'],$motif);
 }


 public static function facturesByAbonnements(): array {
  $p=self::pdo();
  $sql="SELECT ab.idabonnement,f.idfacture,f.numero,f.date_facture,pmt.idpaiement,pmt.montant_paye,pmt.reste_a_payer
        FROM factures f
        JOIN paiements pmt ON pmt.idpaiement=f.idpaiement
        JOIN abonnements ab ON ab.idabonnement=pmt.idabonnement
        WHERE f.statut<>'annule'
        ORDER BY ab.idabonnement ASC, f.idfacture ASC";
  $rows=$p->query($sql)->fetchAll();
  $out=[];
  foreach($rows as $r){ $out[(int)$r['idabonnement']][]=$r; }
  return $out;
 }

 public static function payments(string $q=''): array { self::updateExpired(); $p=self::pdo(); $sql="SELECT p.*, f.idfacture, f.numero, f.statut facture_statut, a.nom,a.prenom,a.tel, ta.nom type_nom,m.nom mode_nom, ab.date_fin FROM paiements p JOIN abonnements ab ON ab.idabonnement=p.idabonnement JOIN abonnes a ON a.idabonne=ab.idabonne JOIN types_abonnement ta ON ta.idtype=ab.idtype JOIN modes_abonnement m ON m.idmode=ab.idmode LEFT JOIN factures f ON f.idpaiement=p.idpaiement"; $params=[]; if($q!==''){ $sql.=" WHERE f.numero LIKE ? OR a.nom LIKE ? OR a.prenom LIKE ? OR a.tel LIKE ? OR ta.nom LIKE ? OR m.nom LIKE ?"; $like='%'.$q.'%'; $params=[$like,$like,$like,$like,$like,$like]; } $sql.=" ORDER BY p.idpaiement DESC LIMIT 500"; $st=$p->prepare($sql); $st->execute($params); return $st->fetchAll(); }
 public static function invoice(int $id): ?array {
  $sql="SELECT f.idfacture,f.numero,f.type_facture,f.date_facture,f.statut facture_statut,f.annule_motif,
  p.idpaiement,p.idabonnement,p.montant_total,p.montant_paye,p.reste_a_payer,p.date_limite_reste,p.mode_paiement,p.statut paiement_statut,p.date_paiement,
  (SELECT COUNT(*) FROM paiements px WHERE px.idabonnement=p.idabonnement AND px.idpaiement<=p.idpaiement) paiement_sequence,
  (SELECT COUNT(*) FROM paiements py WHERE py.idabonnement=p.idabonnement) paiement_count,
  COALESCE(a.idabonne,pa.idabonne) idabonne,COALESCE(a.nom,pa.nom) nom,COALESCE(a.prenom,pa.prenom) prenom,COALESCE(a.tel,pa.tel) tel,COALESCE(a.adresse,pa.adresse) adresse,
  ta.nom type_nom,m.nom mode_nom,ab.date_debut,ab.date_fin,pl.activite planning_activite,pl.date_event planning_date,pl.lieu planning_lieu,u.username gerant
  FROM factures f
  LEFT JOIN paiements p ON p.idpaiement=f.idpaiement
  LEFT JOIN abonnements ab ON ab.idabonnement=p.idabonnement
  LEFT JOIN abonnes a ON a.idabonne=ab.idabonne
  LEFT JOIN types_abonnement ta ON ta.idtype=ab.idtype
  LEFT JOIN modes_abonnement m ON m.idmode=ab.idmode
  LEFT JOIN planning_participants pp ON pp.idfacture=f.idfacture
  LEFT JOIN abonnes pa ON pa.idabonne=pp.idabonne
  LEFT JOIN planning pl ON pl.idplanning=pp.idplanning
  LEFT JOIN users u ON u.iduser=f.created_by";
  $p=self::pdo();
  $st=$p->prepare($sql." WHERE f.idfacture=? LIMIT 1"); $st->execute([$id]); $row=$st->fetch(); if($row) return $row;
  // Fallback utile pour les anciennes vues qui envoyaient par erreur idpaiement au lieu de idfacture.
  $st=$p->prepare($sql." WHERE f.idpaiement=? LIMIT 1"); $st->execute([$id]); $row=$st->fetch(); if($row) return $row;
  return null;
 }

 public static function dashboard(?array $u=null): array {
  self::updateExpired();
  $p=self::pdo();
  $where=''; $params=[];
  if($u && ($u['role']??'')==='gerant'){ $where=' AND p.created_by=?'; $params=[$u['iduser']]; }
  $today=$p->prepare("SELECT COALESCE(SUM(montant_paye),0) FROM paiements p WHERE p.statut<>'annule' AND DATE(p.date_paiement)=CURDATE()$where"); $today->execute($params);
  $month=$p->prepare("SELECT COALESCE(SUM(montant_paye),0) FROM paiements p WHERE p.statut<>'annule' AND DATE_FORMAT(p.date_paiement,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')$where"); $month->execute($params);
  $year=$p->prepare("SELECT COALESCE(SUM(montant_paye),0) FROM paiements p WHERE p.statut<>'annule' AND YEAR(p.date_paiement)=YEAR(CURDATE())$where"); $year->execute($params);
  $exp=$p->query("SELECT a.numero_abonne,a.nom,a.prenom,a.tel,ta.nom type_nom,m.nom mode_nom,ab.date_fin,GREATEST(DATEDIFF(ab.date_fin,CURDATE()),0) jours FROM abonnements ab JOIN abonnes a ON a.idabonne=ab.idabonne LEFT JOIN types_abonnement ta ON ta.idtype=ab.idtype LEFT JOIN modes_abonnement m ON m.idmode=ab.idmode WHERE ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE() AND DATEDIFF(ab.date_fin,CURDATE())<=10 ORDER BY jours ASC, a.nom ASC LIMIT 20")->fetchAll();
  $monthly=$p->query("SELECT DATE_FORMAT(date_paiement,'%Y-%m') mois, SUM(montant_paye) total FROM paiements WHERE statut<>'annule' GROUP BY mois ORDER BY mois DESC LIMIT 12")->fetchAll();
  return [
    'total_abonnes'=>(int)$p->query("SELECT COUNT(*) FROM abonnes WHERE statut<>'desactive'")->fetchColumn(),
    'actifs'=>(int)$p->query("SELECT COUNT(DISTINCT idabonne) FROM abonnements WHERE statut='actif' AND date_debut<=CURDATE() AND date_fin>=CURDATE()")->fetchColumn(),
    'expires'=>(int)$p->query("SELECT COUNT(*) FROM abonnes a WHERE a.statut<>'desactive' AND NOT EXISTS(SELECT 1 FROM abonnements ab WHERE ab.idabonne=a.idabonne AND ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE())")->fetchColumn(),
    'nouveaux'=>(int)$p->query('SELECT COUNT(*) FROM abonnes WHERE DATE(created_at)=CURDATE()')->fetchColumn(),
    'recette_jour'=>(float)$today->fetchColumn(),
    'recette_mois'=>(float)$month->fetchColumn(),
    'recette_annee'=>(float)$year->fetchColumn(),
    'expires_soon'=>$exp,
    'monthly'=>$monthly,
    'notifications'=>$p->query("SELECT n.*, u.username, u.matricule FROM notifications n LEFT JOIN users u ON u.iduser=n.user_id WHERE n.role_cible IN ('all','".($u['role']??'admin')."') ORDER BY n.idnotification DESC LIMIT 8")->fetchAll()
  ];
 }

 public static function factures(string $q='', string $dateFrom='', string $dateTo='', string $sort='date_facture', string $dir='desc'): array {
  $p=self::pdo();
  $allowed=['numero'=>'f.numero','date_facture'=>'f.date_facture','utilisateur'=>'u.username','abonne'=>'nom','montant_paye'=>'p.montant_paye','reste_a_payer'=>'p.reste_a_payer','statut'=>'f.statut'];
  $sortKey=$allowed[$sort]??'f.date_facture';
  $dir=strtolower($dir)==='asc'?'ASC':'DESC';
  $sql="SELECT f.idfacture,f.numero,f.statut facture_statut,f.type_facture,f.date_facture,p.montant_total,p.montant_paye,p.reste_a_payer,
        COALESCE(a.nom,pa.nom) nom,COALESCE(a.prenom,pa.prenom) prenom,COALESCE(a.tel,pa.tel) tel,
        ta.nom type_nom,m.nom mode_nom,pl.activite planning_activite,u.username utilisateur
        FROM factures f
        LEFT JOIN users u ON u.iduser=f.created_by
        LEFT JOIN paiements p ON p.idpaiement=f.idpaiement
        LEFT JOIN abonnements ab ON ab.idabonnement=p.idabonnement
        LEFT JOIN abonnes a ON a.idabonne=ab.idabonne
        LEFT JOIN types_abonnement ta ON ta.idtype=ab.idtype
        LEFT JOIN modes_abonnement m ON m.idmode=ab.idmode
        LEFT JOIN planning_participants pp ON pp.idfacture=f.idfacture
        LEFT JOIN abonnes pa ON pa.idabonne=pp.idabonne
        LEFT JOIN planning pl ON pl.idplanning=pp.idplanning";
  $where=[]; $params=[];
  if($q!==''){
    $where[]="(f.numero LIKE ? OR COALESCE(a.nom,pa.nom) LIKE ? OR COALESCE(a.prenom,pa.prenom) LIKE ? OR COALESCE(a.tel,pa.tel) LIKE ? OR pl.activite LIKE ? OR u.username LIKE ?)";
    $like='%'.$q.'%'; array_push($params,$like,$like,$like,$like,$like,$like);
  }
  if($dateFrom!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$dateFrom)){ $where[]='DATE(f.date_facture)>=?'; $params[]=$dateFrom; }
  if($dateTo!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$dateTo)){ $where[]='DATE(f.date_facture)<=?'; $params[]=$dateTo; }
  if($where){ $sql.=' WHERE '.implode(' AND ',$where); }
  $sql.=' ORDER BY '.$sortKey.' '.$dir.' LIMIT 500';
  $st=$p->prepare($sql); $st->execute($params); return $st->fetchAll();
 }

 public static function adminDashboard(array $filters=[]): array {
  self::updateExpired();
  $p=self::pdo();
  $today=date('Y-m-d');
  $day=$filters['day']??$today; if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$day) || $day>$today) $day=$today;
  $month=$filters['month']??date('Y-m'); if(!preg_match('/^\d{4}-\d{2}$/',$month) || $month>date('Y-m')) $month=date('Y-m');
  $year=$filters['year']??date('Y'); if(!preg_match('/^\d{4}$/',$year) || (int)$year>(int)date('Y')) $year=date('Y');
  $sum=function($sql,$params=[])use($p){$st=$p->prepare($sql);$st->execute($params);return (float)$st->fetchColumn();};
  $stats=self::dashboard(null);
  // Recettes strictement limitées à la période choisie : on utilise des bornes DATETIME
  // pour éviter que MariaDB/XAMPP réinterprète mal DATE_FORMAT selon les versions.
  $dayStart=$day.' 00:00:00'; $dayEnd=$day.' 23:59:59';
  $monthStart=$month.'-01 00:00:00'; $monthEnd=date('Y-m-t 23:59:59',strtotime($month.'-01'));
  $yearStart=$year.'-01-01 00:00:00'; $yearEnd=$year.'-12-31 23:59:59';
  $stats['recette_jour']=$sum("SELECT COALESCE(SUM(montant_paye),0) FROM paiements WHERE statut<>'annule' AND date_paiement BETWEEN ? AND ?",[$dayStart,$dayEnd]);
  $stats['recette_mois']=$sum("SELECT COALESCE(SUM(montant_paye),0) FROM paiements WHERE statut<>'annule' AND date_paiement BETWEEN ? AND ?",[$monthStart,$monthEnd]);
  $stats['recette_annee']=$sum("SELECT COALESCE(SUM(montant_paye),0) FROM paiements WHERE statut<>'annule' AND date_paiement BETWEEN ? AND ?",[$yearStart,$yearEnd]);
  // Totaux fixes de référence : aujourd'hui, mois courant, année courante.
  $todayStart=date('Y-m-d 00:00:00'); $todayEnd=date('Y-m-d 23:59:59');
  $currentMonthStart=date('Y-m-01 00:00:00'); $currentMonthEnd=date('Y-m-t 23:59:59');
  $currentYearStart=date('Y-01-01 00:00:00'); $currentYearEnd=date('Y-12-31 23:59:59');
  $stats['recette_today_total']=$sum("SELECT COALESCE(SUM(montant_paye),0) FROM paiements WHERE statut<>'annule' AND date_paiement BETWEEN ? AND ?",[$todayStart,$todayEnd]);
  $stats['recette_current_month_total']=$sum("SELECT COALESCE(SUM(montant_paye),0) FROM paiements WHERE statut<>'annule' AND date_paiement BETWEEN ? AND ?",[$currentMonthStart,$currentMonthEnd]);
  $stats['recette_current_year_total']=$sum("SELECT COALESCE(SUM(montant_paye),0) FROM paiements WHERE statut<>'annule' AND date_paiement BETWEEN ? AND ?",[$currentYearStart,$currentYearEnd]);
  $stats['period_labels']=['day'=>$day,'month'=>$month,'year'=>$year];
  $stats['filters']=['day'=>$day,'month'=>$month,'year'=>$year];
  $stats['by_type']=$p->query("SELECT ta.nom label, COALESCE(SUM(pm.montant_paye),0) total FROM types_abonnement ta LEFT JOIN abonnements ab ON ab.idtype=ta.idtype LEFT JOIN paiements pm ON pm.idabonnement=ab.idabonnement AND pm.statut<>'annule' GROUP BY ta.idtype ORDER BY ta.idtype")->fetchAll();
  $stats['by_mode']=$p->query("SELECT m.nom label, COALESCE(SUM(pm.montant_paye),0) total FROM modes_abonnement m LEFT JOIN abonnements ab ON ab.idmode=m.idmode LEFT JOIN paiements pm ON pm.idabonnement=ab.idabonnement AND pm.statut<>'annule' GROUP BY m.idmode ORDER BY m.idmode")->fetchAll();
  return $stats;
 }

 public static function adminAbonnes(string $q='', string $statut='en_cours', string $dateFrom='', string $dateTo='', string $daysOp='', ?int $daysValue=null): array {
  $base=self::dashboardAbonnes('all',$q,1,500,'',null)['rows'];
  $out=[];
  foreach($base as $r){
    $j=max(0,(int)($r['jours_restants']??0));
    if($statut==='en_cours' && $j<=0) continue;
    if($statut==='expire' && $j>0) continue;
    $created=substr((string)($r['created_at']??''),0,10);
    if($dateFrom!=='' && $created!=='' && $created<$dateFrom) continue;
    if($dateTo!=='' && $created!=='' && $created>$dateTo) continue;
    if($daysValue!==null && in_array($daysOp,['<','<=','>','>=','='],true)){
      $ok=match($daysOp){ '<'=>$j<$daysValue, '<='=>$j<=$daysValue, '>'=>$j>$daysValue, '>='=>$j>=$daysValue, '='=>$j===$daysValue, default=>true};
      if(!$ok) continue;
    }
    $out[]=$r;
  }
  usort($out,fn($a,$b)=>max(0,(int)($a['jours_restants']??0))<=>max(0,(int)($b['jours_restants']??0)) ?: strcmp((string)$a['nom'],(string)$b['nom']));
  return $out;
 }



 public static function tarifsUpdate(array $post,array $u): void {
  foreach(($post['tarif']??[]) as $id=>$m){
   $val=(float)str_replace([' ', ','],['','.'],$m);
   self::pdo()->prepare('UPDATE tarifs SET montant=?,updated_by=? WHERE idtarif=?')->execute([$val,$u['iduser']??null,(int)$id]);
  }
  foreach(($post['description']??[]) as $id=>$desc){
   self::pdo()->prepare('UPDATE types_abonnement SET description=? WHERE idtype=?')->execute([$desc,(int)$id]);
  }
  foreach(($post['type_nom']??[]) as $id=>$nom){
   $nom=trim((string)$nom);
   if($nom!=='') self::pdo()->prepare('UPDATE types_abonnement SET nom=? WHERE idtype=?')->execute([$nom,(int)$id]);
  }
  foreach(($post['mode_nom']??[]) as $id=>$nom){
   $nom=trim((string)$nom);
   if($nom!=='') self::pdo()->prepare('UPDATE modes_abonnement SET nom=? WHERE idmode=?')->execute([$nom,(int)$id]);
  }
  self::log($u,'Modification tarifs/types/modes','tarifs',null,null,$post);
 }

 public static function abonnementModeStats(): array {
  $p=self::pdo();
  $rows=$p->query("SELECT ta.nom type_nom,m.nom mode_nom,COUNT(ab.idabonnement) total
   FROM types_abonnement ta
   CROSS JOIN modes_abonnement m
   LEFT JOIN abonnements ab ON ab.idtype=ta.idtype AND ab.idmode=m.idmode AND ab.statut<>'annule'
   GROUP BY ta.idtype,m.idmode
   ORDER BY ta.idtype,m.idmode")->fetchAll();
  $out=[];
  foreach($rows as $r){ $out[$r['type_nom']][]=['mode'=>$r['mode_nom'],'total'=>(int)$r['total']]; }
  return $out;
 }

 public static function planningEligibleAbonnes(): array {
  self::updateExpired();
  $sql="SELECT a.*,
        SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE() THEN ta.nom END ORDER BY ab.date_fin DESC SEPARATOR '||'),'||',1) AS type_nom,
        SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE() THEN m.nom END ORDER BY ab.date_fin DESC SEPARATOR '||'),'||',1) AS mode_nom,
        SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE() THEN m.idmode END ORDER BY ab.date_fin DESC SEPARATOR '||'),'||',1) AS idmode,
        MAX(CASE WHEN ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE() THEN ab.date_fin END) date_fin,
        GREATEST(COALESCE(DATEDIFF(MAX(CASE WHEN ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE() THEN ab.date_fin END),CURDATE()),0),0) jours_restants,
        MAX(CASE WHEN ab.idmode=4 AND ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE() THEN 1 ELSE 0 END) has_annuel_actif
        FROM abonnes a
        INNER JOIN abonnements ab ON ab.idabonne=a.idabonne AND ab.statut='actif' AND ab.date_debut<=CURDATE() AND ab.date_fin>=CURDATE()
        LEFT JOIN types_abonnement ta ON ta.idtype=ab.idtype
        LEFT JOIN modes_abonnement m ON m.idmode=ab.idmode
        WHERE a.statut<>'desactive'
        GROUP BY a.idabonne
        ORDER BY a.nom ASC, a.prenom ASC
        LIMIT 1000";
  return self::pdo()->query($sql)->fetchAll();
 }

 public static function planning(): array {
  return self::pdo()->query("SELECT p.*, (SELECT COUNT(*) FROM planning_participants pp WHERE pp.idplanning=p.idplanning) inscrits FROM planning p ORDER BY p.date_event DESC LIMIT 300")->fetchAll();
 }

 public static function createPlanning(array $d,array $u): void {
  self::pdo()->prepare('INSERT INTO planning(date_event,activite,description,coach,prix_participation,limite_participants,lieu,date_limite_paiement,statut,created_by) VALUES(?,?,?,?,?,?,?,?,?,?)')->execute([$d['date_event'],$d['activite'],$d['description'],$d['coach'],(float)$d['prix_participation'],(int)$d['limite_participants'],$d['lieu'],$d['date_limite_paiement'],$d['statut'],$u['iduser']]);
  self::notify('gerant','Nouveau planning','Un nouvel évènement a été créé : '.$d['activite'].' le '.$d['date_event']);
  self::log($u,'Création planning','planning',(string)self::pdo()->lastInsertId(),null,$d);
 }

 public static function participate(int $idplanning,int $idabonne,array $u,string $modePaiement='Espèces'): array {
  $p=self::pdo();
  $pl=$p->prepare('SELECT * FROM planning WHERE idplanning=?'); $pl->execute([$idplanning]); $pl=$pl->fetch();
  if(!$pl) throw new \RuntimeException('Planning introuvable');
  $exists=$p->prepare('SELECT COUNT(*) FROM planning_participants WHERE idplanning=? AND idabonne=?'); $exists->execute([$idplanning,$idabonne]);
  if((int)$exists->fetchColumn()>0) throw new \RuntimeException('Cet abonné est déjà inscrit à ce planning.');
  $active=$p->prepare("SELECT COUNT(*) FROM abonnements WHERE idabonne=? AND statut='actif' AND date_debut<=CURDATE() AND date_fin>=CURDATE()"); $active->execute([$idabonne]);
  if((int)$active->fetchColumn()<=0) throw new \RuntimeException('Cet abonné n’a pas d’abonnement actif.');
  $annual=$p->prepare("SELECT COUNT(*) FROM abonnements WHERE idabonne=? AND idmode=4 AND statut='actif' AND date_debut<=CURDATE() AND date_fin>=CURDATE()"); $annual->execute([$idabonne]);
  $free=(int)$annual->fetchColumn()>0;
  $idpay=null; $idfac=null; $numero=null;
  $p->beginTransaction();
  try{
   if(!$free){
    $amount=(float)$pl['prix_participation'];
    $p->prepare('INSERT INTO paiements(montant_total,montant_paye,reste_a_payer,mode_paiement,statut,created_by) VALUES(?,?,?,?,?,?)')->execute([$amount,$amount,0,$modePaiement?:'Espèces','paye',$u['iduser']]);
    $idpay=(int)$p->lastInsertId();
    $numero='GoF-'.str_pad((string)$idpay,6,'0',STR_PAD_LEFT);
    $p->prepare('INSERT INTO factures(numero,idpaiement,type_facture,created_by) VALUES(?,?,?,?)')->execute([$numero,$idpay,'planning',$u['iduser']]);
    $idfac=(int)$p->lastInsertId();
   }
   $p->prepare('INSERT INTO planning_participants(idplanning,idabonne,gratuit,idpaiement,idfacture,created_by) VALUES(?,?,?,?,?,?)')->execute([$idplanning,$idabonne,$free?1:0,$idpay,$idfac,$u['iduser']]);
   $p->commit();
  } catch(\Throwable $e){ $p->rollBack(); throw $e; }
  self::log($u,'Inscription participant planning','planning_participants',(string)$idplanning,null,['idabonne'=>$idabonne,'gratuit'=>$free]);
  return ['gratuit'=>$free,'idfacture'=>$idfac,'numero'=>$numero];
 }

 public static function cancelFreePlanningParticipant(int $idparticipant,array $u): void {
  $p=self::pdo();
  $st=$p->prepare('SELECT * FROM planning_participants WHERE idparticipant=? LIMIT 1'); $st->execute([$idparticipant]);
  $row=$st->fetch();
  if(!$row) throw new \RuntimeException('Participant introuvable.');
  if((int)($row['gratuit']??0)!==1) throw new \RuntimeException('Annulation réservée aux participants gratuits annuels.');
  $p->prepare('DELETE FROM planning_participants WHERE idparticipant=? AND gratuit=1')->execute([$idparticipant]);
  self::log($u,'Annulation inscription gratuite planning','planning_participants',(string)$idparticipant,$row,null,'Annulation participant annuel actif');
 }

 public static function planningParticipants(int $idplanning): array {
  $st=self::pdo()->prepare('SELECT pp.*,a.numero_abonne,a.nom,a.prenom,a.tel,f.numero FROM planning_participants pp JOIN abonnes a ON a.idabonne=pp.idabonne LEFT JOIN factures f ON f.idfacture=pp.idfacture WHERE pp.idplanning=? ORDER BY pp.idparticipant DESC');
  $st->execute([$idplanning]); return $st->fetchAll();
 }

 public static function setAbonneJoursRestants(int $id,int $jours,array $u): void {
  $p=self::pdo(); $jours=max(0,$jours); $old=self::findAbonne($id);
  $st=$p->prepare("SELECT idabonnement,date_fin FROM abonnements WHERE idabonne=? AND statut<>'annule' ORDER BY date_fin DESC LIMIT 1");
  $st->execute([$id]); $ab=$st->fetch();
  $newDate=date('Y-m-d',strtotime('+'.$jours.' days'));
  if($ab){ $p->prepare('UPDATE abonnements SET date_fin=?, statut=? WHERE idabonnement=?')->execute([$newDate,$jours>0?'actif':'expire',(int)$ab['idabonnement']]); }
  else { $p->prepare('INSERT INTO abonnements(idabonne,idtype,idmode,date_debut,date_fin,statut,created_by) VALUES(?,1,1,CURDATE(),?,?,?)')->execute([$id,$newDate,$jours>0?'actif':'expire',$u['iduser']??null]); }
  $p->prepare('UPDATE abonnes SET statut=? WHERE idabonne=?')->execute([$jours>0?'actif':'expire',$id]);
  self::log($u,'Correction jours restants','abonnes',(string)$id,$old,['jours_restants'=>$jours,'date_fin'=>$newDate]);
 }

 public static function updatePlanning(int $id,array $d,array $u): void {
  $old=self::pdo()->prepare('SELECT * FROM planning WHERE idplanning=?'); $old->execute([$id]); $before=$old->fetch();
  self::pdo()->prepare('UPDATE planning SET date_event=?,activite=?,description=?,coach=?,prix_participation=?,limite_participants=?,lieu=?,date_limite_paiement=?,statut=? WHERE idplanning=?')->execute([$d['date_event'],$d['activite'],$d['description'],$d['coach'],(float)$d['prix_participation'],(int)$d['limite_participants'],$d['lieu'],$d['date_limite_paiement'],$d['statut'],$id]);
  self::log($u,'Modification planning','planning',(string)$id,$before,$d);
 }
 public static function cancelPlanning(int $id,array $u,string $motif=''): void {
  self::pdo()->prepare("UPDATE planning SET statut='annule' WHERE idplanning=?")->execute([$id]);
  self::log($u,'Annulation planning','planning',(string)$id,null,null,$motif);
 }

}
