<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
ini_set('display_errors', '0');
ini_set('session.use_strict_mode', '1');
session_name('reunite_session');
$config = require __DIR__ . '/config.php';
$cookiePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/';
$usesHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !$config['local'] || $usesHttps,
    'path' => $cookiePath,
]);
session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(24));
const CATEGORIES = ['Electronics','Bags & wallets','Keys','Clothing','Documents','Other'];

function respond(array $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
function fail(string $message, int $status = 400): never {
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}
function clean_answer(string $answer): string {
    return mb_strtolower(preg_replace('/\s+/u', ' ', trim($answer)), 'UTF-8');
}
function field(array $input, string $key, int $min, int $max): string {
    $v = $input[$key] ?? '';
    if (!is_string($v)) fail('Please enter a valid ' . str_replace('_', ' ', $key) . '.');
    $v = trim($v);
    if (mb_strlen($v) < $min || mb_strlen($v) > $max) fail(ucfirst(str_replace('_', ' ', $key)) . " must be between $min and $max characters.");
    return $v;
}
function user(): ?array { return $_SESSION['user'] ?? null; }
function require_user(): array { return user() ?? fail('Please sign in to continue.', 401); }
function require_admin(): array { $u = require_user(); if ($u['role'] !== 'admin') fail('Administrator access required.', 403); return $u; }
function query(PDO $db, string $sql, array $args = []): PDOStatement { $stmt = $db->prepare($sql); $stmt->execute($args); return $stmt; }
function item_row(array $row): array {
    $row['id'] = (int)$row['id']; $row['user_id'] = (int)$row['user_id']; $row['is_demo'] = (bool)$row['is_demo'];
    unset($row['verification_hash']);
    return $row;
}
function public_select(): string {
    return 'SELECT i.id,i.user_id,i.title,i.description,i.type,i.category,i.location,i.event_date,i.status,i.image,i.verification_question,i.is_demo,i.created_at,u.name AS owner_name FROM items i JOIN users u ON u.id=i.user_id';
}
function get_item(PDO $db, int $id, bool $private = false): array {
    $row = query($db, public_select() . ' WHERE i.id=?', [$id])->fetch();
    if (!$row || ($row['status'] === 'hidden' && (!user() || (user()['role'] !== 'admin' && user()['id'] !== (int)$row['user_id'])))) fail('This item could not be found.', 404);
    return item_row($row);
}
function owns(array $item): void {
    $u = require_user();
    if ($u['id'] !== $item['user_id'] && $u['role'] !== 'admin') fail('Only the person who posted this item can manage it.', 403);
}
function rate_limit(PDO $db, string $action, int $limit, int $minutes): void {
    $bucket = hash('sha256', $action . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'local'));
    query($db, 'INSERT INTO rate_limits (bucket,attempts,reset_at) VALUES (?,1,DATE_ADD(NOW(), INTERVAL ' . $minutes . ' MINUTE)) ON DUPLICATE KEY UPDATE attempts=IF(reset_at<NOW(),1,attempts+1),reset_at=IF(reset_at<NOW(),DATE_ADD(NOW(),INTERVAL ' . $minutes . ' MINUTE),reset_at)', [$bucket]);
    if ((int)query($db, 'SELECT attempts FROM rate_limits WHERE bucket=?', [$bucket])->fetchColumn() > $limit) fail("Too many attempts. Please try again in $minutes minutes.", 429);
}
function claims_for(PDO $db, int $itemId): array {
    $rows = query($db, 'SELECT c.id,c.item_id,c.user_id,c.message,c.answer_verified,c.status,c.created_at,u.name AS claimant_name,CASE WHEN c.answer_verified=1 THEN u.email ELSE NULL END AS claimant_email FROM claims c JOIN users u ON c.user_id=u.id WHERE c.item_id=? ORDER BY c.created_at DESC', [$itemId])->fetchAll();
    foreach ($rows as &$r) { $r['id']=(int)$r['id']; $r['item_id']=(int)$r['item_id']; $r['user_id']=(int)$r['user_id']; $r['answer_verified']=(bool)$r['answer_verified']; }
    return $rows;
}
function list_items(PDO $db, array $filters): array {
    $sql = public_select() . " WHERE i.status != 'hidden'"; $args = [];
    $status = $filters['status'] ?? 'open';
    if (in_array($status, ['open','returned'], true)) { $sql .= ' AND i.status=?'; $args[]=$status; }
    if (!empty($filters['type']) && in_array($filters['type'], ['lost','found'], true)) { $sql .= ' AND i.type=?'; $args[]=$filters['type']; }
    if (!empty($filters['category']) && in_array($filters['category'], CATEGORIES, true)) { $sql .= ' AND i.category=?'; $args[]=$filters['category']; }
    $q = mb_substr(trim((string)($filters['q'] ?? '')), 0, 120);
    if ($q !== '') { $sql .= ' AND (i.title LIKE ? OR i.description LIKE ? OR i.location LIKE ?)'; array_push($args, "%$q%", "%$q%", "%$q%"); }
    $location = mb_substr(trim((string)($filters['location'] ?? '')),0,120);
    if ($location !== '') { $sql .= ' AND i.location LIKE ?'; $args[]="%$location%"; }
    return array_map('item_row', query($db,$sql.' ORDER BY i.is_demo ASC,i.created_at DESC,i.id DESC LIMIT 200',$args)->fetchAll());
}
function stats(PDO $db): array {
    $r = $db->query("SELECT COALESCE(SUM(type='lost' AND status='open'),0) lost, COALESCE(SUM(type='found' AND status='open'),0) found, COALESCE(SUM(status='returned'),0) returned FROM items")->fetch();
    return array_map('intval',$r);
}

try {
    $database = $config['database'];
    if ($config['host'] === '' || $config['username'] === '' ||
        !preg_match('/^[a-zA-Z0-9_-]+$/D', $database) ||
        (!$config['local'] && ($config['password'] === '' || strtolower($config['username']) === 'root'))) {
        throw new RuntimeException('Database settings are incomplete.');
    }
    $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
    if (!$config['local']) $dsn .= ';dbname=' . $database;
    $db = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    if ($config['local']) {
        $db->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $db->exec("USE `$database`");
    }
    $lock = fopen(__DIR__ . '/data/setup.lock','c');
    if (!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('Cannot initialize storage');
    try {
        $db->exec(file_get_contents(__DIR__.'/schema.sql'));
        if (!$db->query("SHOW COLUMNS FROM items LIKE 'previous_status'")->fetch()) {
            $db->exec("ALTER TABLE items ADD COLUMN previous_status ENUM('open','returned') DEFAULT NULL AFTER status");
        }
        if ((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
            $adminEmail = mb_strtolower(trim($config['admin_email']));
            $adminPassword = $config['admin_password'];
            if ($config['local'] && $adminPassword === '') {
                $adminPassword = bin2hex(random_bytes(18));
                $credentialsPath = $config['admin_credentials_file'];
                if (file_put_contents($credentialsPath, "Email: $adminEmail\nPassword: $adminPassword\n", LOCK_EX) === false) {
                    throw new RuntimeException('Cannot save local administrator credentials.');
                }
                chmod($credentialsPath, 0600);
            }
            if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPassword) < 12 || strlen($adminPassword) > 72) {
                throw new RuntimeException('Configure an administrator email and a 12–72 byte password before the first run.');
            }
            $db->beginTransaction();
            query($db,'INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)',['Reunite demo',$adminEmail,password_hash($adminPassword,PASSWORD_DEFAULT),'admin']);
            $adminId = (int)$db->lastInsertId();
            $demo = [
                ['Forest green backpack','A green backpack with a front pocket was left near the library entrance. This is a sample listing to show how Reunite works.','found','Bags & wallets','Central library',1,'What is attached to the zipper?','a star keychain'],
                ['Wireless headphones','Over-ear headphones in a dark case, last seen at the student cafe. This is a sample listing.','lost','Electronics','Student cafe',0,'What color is the case lining?','blue'],
                ['Keys with a yellow tag','A small set of keys with a yellow tag, found beside the sports court. This is a sample listing.','found','Keys','Sports court',2,'How many keys are on the ring?','three'],
                ['Blue reusable bottle','A blue water bottle with a silver lid, left after an afternoon class. This is a sample listing.','lost','Other','Science block',1,'What sticker is on the bottle?','a mountain'],
                ['Grey zip-up hoodie','A soft grey hoodie found on a bench in the courtyard. This is a sample listing.','found','Clothing','Main courtyard',3,'What size is the hoodie?','medium'],
                ['Notebook and study notes','A notebook and handwritten study notes successfully reunited in this sample story.','lost','Documents','Reading room',4,'What subject are the notes for?','mathematics'],
            ];
            foreach ($demo as $i=>$d) {
                query($db,'INSERT INTO items (user_id,title,description,type,category,location,event_date,status,verification_question,verification_hash,is_demo) VALUES (?,?,?,?,?,?,?,?,?,?,1)',[$adminId,$d[0],$d[1],$d[2],$d[3],$d[4],date('Y-m-d',strtotime('-'.$d[5].' days')),$i===5?'returned':'open',$d[6],password_hash(clean_answer($d[7]),PASSWORD_DEFAULT)]);
            }
            $db->commit();
        }
    } finally { flock($lock,LOCK_UN); fclose($lock); }

    $action = $_GET['action'] ?? 'bootstrap';
    $method = $_SERVER['REQUEST_METHOD'];
    $input = $_POST;
    if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) { $input = json_decode(file_get_contents('php://input'),true); if (!is_array($input)) fail('Invalid request.'); }
    $readActions = ['bootstrap','items','item','mine','admin'];
    if (in_array($action,$readActions,true)) { if ($method !== 'GET') fail('Use GET for this request.',405); }
    else {
        if ($method !== 'POST') fail('Use POST for this request.',405);
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf'] ?? '');
        if (!is_string($csrf) || !hash_equals($_SESSION['csrf'],$csrf)) fail('Your session changed. Refresh the page and try again.',403);
    }

    switch ($action) {
        case 'bootstrap': respond(['user'=>user(),'csrf'=>$_SESSION['csrf'],'categories'=>CATEGORIES,'stats'=>stats($db),'items'=>list_items($db,$_GET)]);
        case 'items': respond(['items'=>list_items($db,$_GET),'stats'=>stats($db)]);
        case 'item':
            $item = get_item($db,(int)($_GET['id']??0));
            $candidates = query($db,public_select()." WHERE i.id<>? AND i.type<>? AND i.category=? AND i.status='open' AND i.is_demo=? ORDER BY (i.location=?) DESC,ABS(DATEDIFF(i.event_date,?)) ASC,i.created_at DESC LIMIT 4",[$item['id'],$item['type'],$item['category'],(int)$item['is_demo'],$item['location'],$item['event_date']])->fetchAll();
            $canManage = user() && (user()['id']===$item['user_id'] || user()['role']==='admin');
            respond(['item'=>$item,'matches'=>array_map('item_row',$candidates),'claims'=>$canManage?claims_for($db,$item['id']):[]]);
        case 'register':
            rate_limit($db,'register',15,15);
            $name = field($input,'name',2,80); $email = mb_strtolower(field($input,'email',5,190)); $password = field($input,'password',8,72);
            if (!filter_var($email,FILTER_VALIDATE_EMAIL)) fail('Please enter a valid email address.');
            if (query($db,'SELECT id FROM users WHERE email=?',[$email])->fetch()) fail('That email already has an account. Please sign in.',409);
            query($db,'INSERT INTO users (name,email,password_hash) VALUES (?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['user']=['id'=>(int)$db->lastInsertId(),'name'=>$name,'email'=>$email,'role'=>'user'];
            respond(['user'=>user(),'csrf'=>$_SESSION['csrf'],'message'=>'Your account is ready.'],201);
        case 'login':
            rate_limit($db,'login',25,15);
            $email = mb_strtolower(field($input,'email',1,190)); $password = field($input,'password',1,72);
            $row = query($db,'SELECT * FROM users WHERE email=?',[$email])->fetch();
            if (!$row || !password_verify($password,$row['password_hash'])) fail('Email or password is incorrect.',401);
            session_regenerate_id(true);
            $_SESSION['user']=['id'=>(int)$row['id'],'name'=>$row['name'],'email'=>$row['email'],'role'=>$row['role']];
            respond(['user'=>user(),'csrf'=>$_SESSION['csrf'],'message'=>'Welcome back.']);
        case 'logout':
            $_SESSION=[]; session_regenerate_id(true); $_SESSION['csrf']=bin2hex(random_bytes(24)); respond(['user'=>null,'csrf'=>$_SESSION['csrf']]);
        case 'create':
            $u = require_user(); rate_limit($db,'create-'.$u['id'],30,60);
            $title=field($input,'title',3,120); $description=field($input,'description',10,2000); $location=field($input,'location',2,120);
            $type=field($input,'type',4,5); $category=field($input,'category',2,40); $eventDate=field($input,'event_date',10,10);
            $question=field($input,'verification_question',5,200); $answer=field($input,'verification_answer',1,100);
            if (!in_array($type,['lost','found'],true) || !in_array($category,CATEGORIES,true)) fail('Please choose a valid item type and category.');
            $date=DateTimeImmutable::createFromFormat('!Y-m-d',$eventDate);
            if (!$date || $date->format('Y-m-d')!==$eventDate || $eventDate>date('Y-m-d')) fail('Please choose a valid date that is today or earlier.');
            $image=null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error']!==UPLOAD_ERR_NO_FILE) {
                $file=$_FILES['photo'];
                if ($file['error']!==UPLOAD_ERR_OK || $file['size']>5*1024*1024) fail('Please upload an image smaller than 5 MB.');
                $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']); $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
                if (!isset($extensions[$mime]) || !getimagesize($file['tmp_name'])) fail('Please upload a JPG, PNG, or WebP image.');
                $image='uploads/'.bin2hex(random_bytes(16)).'.'.$extensions[$mime];
                if (!move_uploaded_file($file['tmp_name'],__DIR__.'/'.$image)) fail('The photo could not be saved. Please try again.',500);
            }
            query($db,'INSERT INTO items (user_id,title,description,type,category,location,event_date,image,verification_question,verification_hash) VALUES (?,?,?,?,?,?,?,?,?,?)',[$u['id'],$title,$description,$type,$category,$location,$eventDate,$image,$question,password_hash(clean_answer($answer),PASSWORD_DEFAULT)]);
            respond(['item'=>get_item($db,(int)$db->lastInsertId()),'message'=>'Your item has been posted.'],201);
        case 'claim':
            $u=require_user(); $item=get_item($db,(int)($input['item_id']??0));
            if ($item['status']!=='open') fail('This item is no longer accepting claims.',409);
            if ($item['user_id']===$u['id']) fail('You cannot claim your own post.');
            if ($item['is_demo']) fail('This is a sample listing. Create a real post to try the claim process.');
            rate_limit($db,'claim-'.$item['id'],12,15);
            $answer=field($input,'answer',1,100); $message=field($input,'message',10,1000);
            if (query($db,'SELECT id FROM claims WHERE item_id=? AND user_id=?',[$item['id'],$u['id']])->fetch()) fail('You have already sent a claim for this item.',409);
            $hash=query($db,'SELECT verification_hash FROM items WHERE id=?',[$item['id']])->fetchColumn();
            if (!password_verify(clean_answer($answer),$hash)) fail('That answer did not match. Check the identifying detail and try again.');
            query($db,'INSERT INTO claims (item_id,user_id,message,answer_verified) VALUES (?,?,?,1)',[$item['id'],$u['id'],$message]);
            respond(['message'=>'Your answer matched. Your claim has been sent to the person who posted this item.','claim_id'=>(int)$db->lastInsertId()],201);
        case 'return':
            $item=get_item($db,(int)($input['item_id']??0)); owns($item);
            if ($item['status']==='hidden') fail('Restore this listing before marking it returned.');
            $db->beginTransaction();
            query($db,"UPDATE items SET status='returned' WHERE id=?",[$item['id']]);
            query($db,"UPDATE claims SET status='closed' WHERE item_id=? AND status='pending'",[$item['id']]);
            $db->commit(); respond(['item'=>get_item($db,$item['id']),'message'=>'Marked as returned. A little less lost!']);
        case 'approve_claim':
            $claim=query($db,'SELECT * FROM claims WHERE id=?',[(int)($input['claim_id']??0)])->fetch();
            if (!$claim) fail('Claim not found.',404);
            $item=get_item($db,(int)$claim['item_id']); owns($item);
            if ($item['status']!=='open' || $claim['status']!=='pending') fail('This claim is no longer pending.',409);
            $db->beginTransaction();
            query($db,"UPDATE claims SET status=IF(id=?,'approved','closed') WHERE item_id=? AND status='pending'",[(int)$claim['id'],$item['id']]);
            query($db,"UPDATE items SET status='returned' WHERE id=?",[$item['id']]);
            $db->commit(); respond(['message'=>'Claim approved and item marked as returned.','item'=>get_item($db,$item['id'])]);
        case 'mine':
            $u=require_user();
            $items=array_map('item_row',query($db,public_select().' WHERE i.user_id=? ORDER BY i.created_at DESC',[$u['id']])->fetchAll());
            $claims=query($db,'SELECT c.id,c.item_id,c.message,c.status,c.created_at,i.title,i.type,i.image FROM claims c JOIN items i ON i.id=c.item_id WHERE c.user_id=? ORDER BY c.created_at DESC',[$u['id']])->fetchAll();
            respond(['items'=>$items,'claims'=>$claims]);
        case 'admin':
            require_admin();
            $items=array_map('item_row',query($db,public_select().' ORDER BY i.created_at DESC LIMIT 300')->fetchAll());
            $claims=query($db,'SELECT c.id,c.item_id,c.status,c.created_at,i.title,u.name AS claimant_name FROM claims c JOIN items i ON i.id=c.item_id JOIN users u ON u.id=c.user_id ORDER BY c.created_at DESC LIMIT 100')->fetchAll();
            respond(['items'=>$items,'claims'=>$claims,'stats'=>stats($db),'counts'=>['users'=>(int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn(),'items'=>(int)$db->query('SELECT COUNT(*) FROM items')->fetchColumn(),'claims'=>(int)$db->query('SELECT COUNT(*) FROM claims')->fetchColumn()]]);
        case 'moderate':
            require_admin(); $item=get_item($db,(int)($input['item_id']??0)); $status=field($input,'status',4,6);
            if (!in_array($status,['open','hidden'],true)) fail('Choose hide or restore.');
            if ($status==='hidden' && $item['status']!=='hidden') {
                query($db,"UPDATE items SET previous_status=status,status='hidden' WHERE id=? AND status<>'hidden'",[$item['id']]);
            } elseif ($status==='open' && $item['status']==='hidden') {
                query($db,"UPDATE items SET status=COALESCE(previous_status,'open'),previous_status=NULL WHERE id=? AND status='hidden'",[$item['id']]);
            }
            respond(['item'=>get_item($db,$item['id']),'message'=>$status==='hidden'?'Listing hidden.':'Listing restored.']);
        default: fail('Unknown request.',404);
    }
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('Reunite: '.$error->getMessage());
    fail('Reunite is temporarily unavailable. Please try again shortly.',503);
}
