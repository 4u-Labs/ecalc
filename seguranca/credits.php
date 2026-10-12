<?php
declare(strict_types=1);
/** Short SQLite transactions. External AI calls never run inside a transaction. */
final class SafeWorkCredits {
 public static function initialize(PDO $pdo): void {$pdo->exec('PRAGMA busy_timeout=5000');$pdo->exec("CREATE TABLE IF NOT EXISTS safework_requests (user_id INTEGER NOT NULL, request_id TEXT NOT NULL, input_hash TEXT NOT NULL, status TEXT NOT NULL, response TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(user_id,request_id))");}
 public static function reserve(PDO $pdo,int $uid,string $id,string $hash): ?array {
  self::initialize($pdo);$pdo->exec('BEGIN IMMEDIATE');$active=true;try {
   $q=$pdo->prepare('SELECT * FROM safework_requests WHERE user_id=? AND request_id=?');$q->execute([$uid,$id]);$existing=$q->fetch(PDO::FETCH_ASSOC);
   if($existing){if(!hash_equals($existing['input_hash'],$hash))throw new RuntimeException('Identificador já usado para outros dados',409);if($existing['status']==='pending')throw new RuntimeException('Esta geração está em andamento. Aguarde antes de tentar novamente.',409);$pdo->exec('COMMIT');$active=false;return json_decode($existing['response'],true,512,JSON_THROW_ON_ERROR);}
   $q=$pdo->prepare('UPDATE users SET credits=credits-1,updated_at=datetime("now") WHERE id=? AND credits>=1');$q->execute([$uid]);if($q->rowCount()!==1)throw new RuntimeException('Saldo de IA insuficiente',402);
   $pdo->prepare("INSERT INTO safework_requests(user_id,request_id,input_hash,status) VALUES(?,?,?,'pending')")->execute([$uid,$id,$hash]);
   self::ledger($pdo,$uid,$id,-1,'Reserva SafeWork Pro');$pdo->exec('COMMIT');$active=false;return null;
  }catch(Throwable $error){if($active)$pdo->exec('ROLLBACK');throw $error;}
 }
 private static function ledger(PDO $pdo,int $uid,string $id,int $delta,string $label):void{$pdo->prepare("INSERT INTO transactions(user_id,mp_payment_id,package_label,amount_brl,credits_added,status) VALUES(?,?,?,0,?,'approved')")->execute([$uid,'SWP-'.$uid.'-'.$id.($delta<0?'-reserve':'-refund'),$label,$delta]);}
 public static function finish(PDO $pdo,int $uid,string $id,array $result):array {
  $pdo->exec('BEGIN IMMEDIATE');$active=true;try{$q=$pdo->prepare("SELECT status FROM safework_requests WHERE user_id=? AND request_id=?");$q->execute([$uid,$id]);if($q->fetchColumn()!=='pending')throw new RuntimeException('Geração já finalizada',409);
   $q=$pdo->prepare('SELECT credits FROM users WHERE id=?');$q->execute([$uid]);$response=['success'=>true,'data'=>$result,'credits_remaining'=>(int)$q->fetchColumn(),'request_id'=>$id];
   $pdo->prepare("UPDATE safework_requests SET status='completed',response=?,updated_at=CURRENT_TIMESTAMP WHERE user_id=? AND request_id=? AND status='pending'")->execute([json_encode($response,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$uid,$id]);$pdo->exec('COMMIT');$active=false;return $response;
  }catch(Throwable $error){if($active)$pdo->exec('ROLLBACK');throw $error;}
 }
 public static function fail(PDO $pdo,int $uid,string $id):void {
  $pdo->exec('BEGIN IMMEDIATE');$active=true;try{$response=json_encode(['success'=>false,'error'=>'A geração falhou e o crédito foi devolvido. Tente novamente.']);$q=$pdo->prepare("UPDATE safework_requests SET status='failed',response=?,updated_at=CURRENT_TIMESTAMP WHERE user_id=? AND request_id=? AND status='pending'");$q->execute([$response,$uid,$id]);if($q->rowCount()===1){$pdo->prepare('UPDATE users SET credits=credits+1,updated_at=datetime("now") WHERE id=?')->execute([$uid]);self::ledger($pdo,$uid,$id,1,'Estorno SafeWork Pro');}$pdo->exec('COMMIT');$active=false;}catch(Throwable $error){if($active)$pdo->exec('ROLLBACK');throw $error;}
 }
}
