<?php
declare(strict_types=1);
namespace App\Controllers; use Core\Controller; use Core\Auth; use App\Models\App;
final class GerantController extends Controller {
 public function dashboard(): void {
  Auth::requireRole('gerant');
  $statut=trim((string)($_GET['statut']??'en_cours'));
  if(!in_array($statut,['en_cours','expire'],true)){$statut='en_cours';}
  $q=trim((string)($_GET['q']??''));
  $page=max(1,(int)($_GET['page']??1));
  $daysOp=trim((string)($_GET['days_op']??''));
  $daysRaw=trim((string)($_GET['days']??''));
  $daysValue=$daysRaw!==''?(int)$daysRaw:null;
  // On charge les deux statuts pour que le filtre instantané En cours/Expirés fonctionne sans rechargement.
  $abonnesPage=App::dashboardAbonnes('all',$q,$page,500,$daysOp,$daysValue);
  $this->render('gerant/dashboard',['title'=>'Dashboard Gérant','active'=>'gerant_dashboard','user'=>Auth::user(),'baseUrl'=>rtrim(BASE_URL,'/'),'csrf'=>$this->csrfToken(),'flash'=>$this->pullFlash(),'stats'=>App::dashboard(Auth::user()),'refs'=>App::refs(),'abonnesPage'=>$abonnesPage,'abonnes'=>$abonnesPage['rows'],'restesDue'=>App::partialDuePayments(false),'statutFiltre'=>$statut,'q'=>$q,'daysOp'=>$daysOp,'daysValue'=>$daysRaw]);
 }
 public function abonnements(): void { Auth::requireRole('gerant'); $this->render('gerant/abonnements/index',['title'=>'Abonnements et prix','active'=>'gerant_abonnements','user'=>Auth::user(),'baseUrl'=>rtrim(BASE_URL,'/'),'csrf'=>$this->csrfToken(),'flash'=>$this->pullFlash(),'refs'=>App::refs(),'modeStats'=>App::abonnementModeStats()]); }
}
