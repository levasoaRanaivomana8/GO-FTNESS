<?php
declare(strict_types=1);
namespace App\Controllers; use Core\Controller; use Core\Auth; use App\Models\App; use Config\DB; use Dompdf\Dompdf;
final class GerantPaiementController extends Controller {
 private function view(string $v,array $d=[]): void { $this->render($v,array_merge(['user'=>Auth::user(),'baseUrl'=>rtrim(BASE_URL,'/'),'csrf'=>$this->csrfToken(),'flash'=>$this->pullFlash()],$d)); }
 public function index(): void { Auth::requireRole('gerant'); $q=trim((string)($_GET['q']??'')); $this->view('gerant/paiements/index',['title'=>'Paiements / factures','active'=>'gerant_paiements','rows'=>App::payments($q),'facturesByAbonnement'=>App::facturesByAbonnements(),'q'=>$q,'restes'=>App::partialDuePayments(false)]); }
 public function create(): void { Auth::requireRole('gerant'); $this->view('gerant/paiements/create',['title'=>'Enregistrer abonnement','active'=>'gerant_paiements','abonnes'=>App::abonneSearch(''),'refs'=>App::refs(),'selected'=>(int)($_GET['idabonne']??0),'error'=>null]); }
 public function store(): void { Auth::requireRole('gerant'); $this->requireCsrf(); try{ $r=App::createSubscriptionPaymentInvoice($_POST,Auth::user()); $this->flash('success','Paiement enregistré. Facture '.$r['numero'].' générée. Cliquez sur la notification pour ouvrir/imprimer le PDF.'); $this->redirect('/gerant/paiements?facture='.$r['idfacture']); }catch(\Throwable $e){ $this->view('gerant/paiements/create',['title'=>'Enregistrer abonnement','active'=>'gerant_paiements','abonnes'=>App::abonneSearch(''),'refs'=>App::refs(),'selected'=>(int)($_POST['idabonne']??0),'error'=>$e->getMessage()]); } }
 public function payReste(): void { Auth::requireRole('gerant'); $this->requireCsrf(); try{ $r=App::payRemainingBalance((int)($_POST['idpaiement']??0),trim((string)($_POST['mode_paiement']??'Espèces')),Auth::user()); $this->flash('success','Reste payé. Facture générée. Cliquez sur la notification pour ouvrir la facture.'); $this->redirect('/gerant/paiements?facture='.(int)$r['idfacture']); }catch(\Throwable $e){ $this->flash('danger',$e->getMessage()); $this->redirect('/gerant/paiements'); } }


 public function cancelReste(): void { Auth::requireRole('gerant'); $this->requireCsrf(); try{ App::cancelRemainingBalance((int)($_POST['idpaiement']??0),Auth::user(),trim((string)($_POST['motif']??'Reste non payé'))); $this->flash('warning','Reste à payer annulé et abonnement concerné annulé.'); }catch(\Throwable $e){ $this->flash('danger',$e->getMessage()); } $this->redirect('/gerant/paiements'); }

 public function pdf(): void {
  Auth::requireLogin();
  $id=(int)($_GET['id']??0);
  $f=$id>0?App::invoice($id):null;
  if(!$f){
    http_response_code(404);
    echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Facture introuvable</title><style>body{font-family:Arial;padding:35px;background:#f8fafc}.box{max-width:720px;background:white;border:1px solid #e5e7eb;border-radius:14px;padding:24px;margin:auto}.btn{display:inline-block;background:#111827;color:#fff;padding:10px 14px;border-radius:8px;text-decoration:none}</style></head><body><div class="box"><h2>Facture introuvable</h2><p>La facture demandée n&#39;existe pas ou le lien utilisé est incomplet.</p><p>Retournez dans la liste des paiements ou des factures, puis cliquez sur le bouton <b>Facture</b>.</p><a class="btn" href="'.htmlspecialchars(rtrim(BASE_URL,'/')).'/gerant/paiements">Retour aux paiements</a></div></body></html>';
    return;
  }
  ob_start(); include __DIR__.'/../views/pdf/facture.php'; $html=ob_get_clean();
  try{
    $dompdf=new Dompdf(); $dompdf->loadHtml($html,'UTF-8'); $dompdf->setPaper('A4','portrait'); $dompdf->render(); $dompdf->stream(($f['numero']??'facture').'.pdf',['Attachment'=>false]);
  }catch(\Throwable $e){
    header('Content-Type: text/html; charset=utf-8'); echo $html;
  }
 }
}
