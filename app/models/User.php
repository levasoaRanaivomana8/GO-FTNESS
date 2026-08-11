<?php
declare(strict_types=1);
namespace App\Models;
use Config\DB; use PDO;

final class User {
 private static function nextMatricule(string $role): string {
  $prefix = strtolower($role)==='admin' ? 'ADM' : 'GER';
  $st=DB::pdo()->prepare("SELECT matricule FROM users WHERE matricule LIKE ? ORDER BY iduser DESC LIMIT 1");
  $st->execute([$prefix.'-%']);
  $last=(string)($st->fetchColumn() ?: '');
  $num=1;
  if(preg_match('/^(ADM|GER)-(\d+)$/',$last,$m)){ $num=((int)$m[2])+1; }
  return $prefix.'-'.str_pad((string)$num,4,'0',STR_PAD_LEFT);
 }
 private static function cleanMatricule(?string $v,string $role): string {
  $v=strtoupper(trim((string)$v));
  if($v==='') return self::nextMatricule($role);
  $v=preg_replace('/[^A-Z0-9\-]/','',$v) ?: '';
  return $v!=='' ? $v : self::nextMatricule($role);
 }
 public static function findByLogin(string $login): ?array {
  $st=DB::pdo()->prepare('SELECT iduser,nom,matricule,username,email,password_hash,role,is_active,failed_attempts FROM users WHERE username=? OR email=? OR matricule=? LIMIT 1');
  $st->execute([$login,$login,$login]);
  return $st->fetch(PDO::FETCH_ASSOC)?:null;
 }
 public static function fail(string $login): void {
  $p=DB::pdo();
  $st=$p->prepare('UPDATE users SET failed_attempts=failed_attempts+1,last_failed_at=NOW() WHERE username=? OR email=? OR matricule=?');
  $st->execute([$login,$login,$login]);
  $u=self::findByLogin($login);
  if($u && (int)$u['failed_attempts']>=3){
   App::notify('admin','Tentatives de connexion échouées','Compte concerné : '.(($u['matricule']??'')?:$login).' — Nombre de tentatives : '.(int)$u['failed_attempts'].'. Dernière tentative : '.date('d/m/Y H:i'));
   App::log($u,'Tentatives de connexion échouées','users',(string)$u['iduser'],null,null,'Nombre de tentatives : '.(int)$u['failed_attempts']);
  }
 }
 public static function resetFails(int $id): void { DB::pdo()->prepare('UPDATE users SET failed_attempts=0 WHERE iduser=?')->execute([$id]); }
 public static function all(): array { return DB::pdo()->query("SELECT * FROM users WHERE role='gerant' ORDER BY iduser DESC")->fetchAll(); }
 public static function createGerant(array $d,array $u): void {
  $matricule=self::cleanMatricule($d['matricule']??null,'gerant');
  DB::pdo()->prepare("INSERT INTO users(nom,matricule,username,email,password_hash,role,is_active) VALUES(?,?,?,?,?,'gerant',?)")
   ->execute([$d['nom'],$matricule,$d['username'],$d['email'],password_hash($d['password'],PASSWORD_BCRYPT),(int)($d['is_active']??1)]);
  App::log($u,'Création utilisateur Gérant','users',(string)DB::pdo()->lastInsertId(),null,['nom'=>$d['nom'],'matricule'=>$matricule,'username'=>$d['username'],'email'=>$d['email'],'is_active'=>$d['is_active']??1]);
 }
 public static function updateGerant(int $id,array $d,array $u): void {
  $old=DB::pdo()->prepare("SELECT * FROM users WHERE iduser=? AND role='gerant'"); $old->execute([$id]); $before=$old->fetch();
  if(!$before) throw new \RuntimeException('Gérant introuvable');
  $matricule=self::cleanMatricule($d['matricule']??($before['matricule']??''),'gerant');
  if(trim((string)($d['password']??''))!==''){
   DB::pdo()->prepare("UPDATE users SET nom=?,matricule=?,username=?,email=?,password_hash=?,is_active=? WHERE iduser=? AND role='gerant'")
    ->execute([$d['nom'],$matricule,$d['username'],$d['email'],password_hash($d['password'],PASSWORD_BCRYPT),(int)($d['is_active']??1),$id]);
  } else {
   DB::pdo()->prepare("UPDATE users SET nom=?,matricule=?,username=?,email=?,is_active=? WHERE iduser=? AND role='gerant'")
    ->execute([$d['nom'],$matricule,$d['username'],$d['email'],(int)($d['is_active']??1),$id]);
  }
  App::log($u,'Modification utilisateur Gérant','users',(string)$id,$before,['nom'=>$d['nom'],'matricule'=>$matricule,'username'=>$d['username'],'email'=>$d['email'],'is_active'=>$d['is_active']??1]);
 }
 public static function deleteGerant(int $id,array $u): void { DB::pdo()->prepare("UPDATE users SET is_active=0 WHERE iduser=? AND role='gerant'")->execute([$id]); App::log($u,'Désactivation utilisateur Gérant','users',(string)$id,null,null,'Désactivation admin'); }
}
