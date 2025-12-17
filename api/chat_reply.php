<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/dbcon.php'; 

// ---------------- 1) Debug Helper ----------------
function logger(string $msg, $data = null): void {
    $logEntry = "[" . date('Y-m-d H:i:s') . "] " . $msg;
    if ($data !== null) {
        $logEntry .= " | DATA: " . (is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    file_put_contents(__DIR__ . '/debug.log', $logEntry . PHP_EOL, FILE_APPEND);
}

// ---------------- 2) Helpers ----------------
function json_out($arr, int $code = 200): void {
    http_response_code($code);
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

function get_json_input(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function maybe_json_to_text(string $s): string {
    $t = trim($s);

    if (preg_match('/^```json\s*(.*?)\s*```$/si', $t, $m)) {
        $t = trim($m[1]);
    }

    $j = json_decode($t, true);
    if (!is_array($j)) return $s;

    $lines = [];

    if (!empty($j['summary'])) {
        $lines[] = (string)$j['summary'];
    }

    if (!empty($j['insights']) && is_array($j['insights'])) {
        foreach ($j['insights'] as $ins) {
            if (is_string($ins)) $lines[] = "• " . $ins;
            elseif (is_array($ins) && isset($ins['insight'])) $lines[] = "• " . (string)$ins['insight'];
        }
    }

    return implode("\n", $lines) ?: $s;
}


function escape_for_prompt(string $s): string {
    return str_replace(["\r","\n"], [' ',' '], $s);
}

function sanitize_rows(array $rows, bool $isAdmin): array {
    $out = [];
    foreach ($rows as $r) {

        $looksLikeUserRow = isset($r['email']) && isset($r['role']) && !isset($r['title']);

        if ($looksLikeUserRow) {
            if (!$isAdmin) continue;
            $out[] = [
                'id' => $r['id'] ?? null,
                'name' => $r['name'] ?? null,
                'email' => $r['email'] ?? null,
                'role' => $r['role'] ?? null,
            ];
            continue;
        }

        if ($isAdmin) {
            $out[] = [
                'id' => $r['id'] ?? null,
                'title' => $r['title'] ?? null,
                'author' => $r['author'] ?? null,
                'genre' => $r['genre'] ?? null,
                'status' => $r['status'] ?? null,
                'added_at' => $r['added_at'] ?? null,
                'user' => [
                    'id' => $r['user_id'] ?? null, 
                    'name' => $r['name'] ?? null,
                    'email' => $r['email'] ?? null,
                    'role' => $r['role'] ?? null,
                ],
            ];
        } else {
            $out[] = [
                'id' => $r['id'] ?? null,
                'title' => $r['title'] ?? null,
                'author' => $r['author'] ?? null,
                'genre' => $r['genre'] ?? null,
                'status' => $r['status'] ?? null,
                'added_at' => $r['added_at'] ?? null,
            ];
        }
    }
    return $out;
}

// ---------------- 3) Auth ----------------
if (!isset($_SESSION['auth']) || empty($_SESSION['auth_user']['id'])) {
    json_out(['error' => 'Unauthorized'], 401);
}

$userId  = (int)($_SESSION['auth_user']['id'] ?? 0);
$role    = (string)($_SESSION['auth_user']['role'] ?? 'client');
$isAdmin = ($role === 'admin');

$scopeUserId = $isAdmin ? null : $userId;

// ---------------- 4) Configuration ----------------
$OLLAMA_BASE = 'http://127.0.0.1:11434';
$CHAT_MODEL  = 'gemma3:latest';
$EMBED_MODEL = 'embeddinggemma:latest';

// ---------------- 5) Ollama Helpers ----------------
function ollama_post(string $base, string $path, array $payload): array {
    $ch = curl_init(rtrim($base,'/') . $path);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $res  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($res === false) {
        logger("CURL Error", $err);
        throw new RuntimeException("Ollama Connection Error");
    }
    if ($code >= 400) {
        logger("Ollama API Error ($code)", $res);
        throw new RuntimeException("Ollama API Error");
    }

    return json_decode($res, true) ?? [];
}

function ollama_chat(string $base, string $model, array $messages, bool $jsonMode = false): string {
    $payload = [
        'model' => $model,
        'stream' => false,
        'messages' => $messages,
        'options' => [
            'num_predict' => 450,
            'temperature' => $jsonMode ? 0.1 : 0.7,
            'top_p' => 0.9,
        ],
    ];
    if ($jsonMode) $payload['format'] = 'json';

    $out = ollama_post($base, '/api/chat', $payload);
    return $out['message']['content'] ?? '';
}

function ollama_embed(string $base, string $model, string $text): array {
    $out = ollama_post($base, '/api/embed', [
        'model' => $model,
        'input' => $text,
    ]);
    return $out['embeddings'][0] ?? [];
}

function dot(array $a, array $b): float {
    $n = min(count($a), count($b));
    $s = 0.0;
    for ($i=0; $i<$n; $i++) $s += $a[$i] * $b[$i];
    return $s;
}

// ---------------- 6) Intent detectors ----------------
function is_structured_question(string $q): bool {
    return (bool)preg_match('/\b(recommend|suggest|how many|count|list|show|latest|planned|reading|completed|status|author|genre|added|date|find|users|user|role|admin|client|email|name|who|which)\b/i', $q);
}

function is_recommend_question(string $q): bool {
    return (bool)preg_match('/\b(recommend|suggest)\b/i', $q);
}

function is_insights_question(string $q): bool {
    return (bool)preg_match('/\b(insight|insights|summary|summarize|habits|habit|statistics|stats|dashboard|trends|trend)\b/i', $q);
}

// ---------------- 7) Admin: resolve user by name/email ----------------
function find_user_id_by_name_or_email(mysqli $con, string $needle): ?int {
    $needle = trim($needle);
    if ($needle === '') return null;

    if (filter_var($needle, FILTER_VALIDATE_EMAIL)) {
        $stmt = mysqli_prepare($con, "SELECT id FROM users WHERE email=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $needle);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        return $row ? (int)$row['id'] : null;
    }

    $stmt = mysqli_prepare($con, "SELECT id FROM users WHERE LOWER(name) LIKE LOWER(?) LIMIT 1");
    $like = '%' . $needle . '%';
    mysqli_stmt_bind_param($stmt, "s", $like);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    return $row ? (int)$row['id'] : null;
}

function extract_target_user_text(string $msg): ?string {
    $m = trim($msg);

    if (preg_match('/\b(?:for|about)?\s*user\s*["\']?([^"\']{1,80})["\']?/i', $m, $x)) {
        return trim($x[1]);
    }
    if (preg_match('/\buser\s*:\s*([a-z0-9@\.\s_-]{1,80})/i', $m, $x)) {
        return trim($x[1]);
    }
    return null;
}

// ---------------- 8) Library Insights (genre + majority status) ----------------
function library_insights(mysqli $con, ?int $scopeUserId, bool $isAdmin): array {

    if ($scopeUserId === null) {
        $stmt = mysqli_prepare($con, "SELECT status, COUNT(*) c FROM books GROUP BY status");
    } else {
        $stmt = mysqli_prepare($con, "SELECT status, COUNT(*) c FROM books WHERE user_id=? GROUP BY status");
        mysqli_stmt_bind_param($stmt, "i", $scopeUserId);
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $status = ['planned'=>0,'reading'=>0,'completed'=>0];
    $totalBooks = 0;

    while ($row = mysqli_fetch_assoc($res)) {
        $st = strtolower((string)($row['status'] ?? ''));
        $cnt = (int)($row['c'] ?? 0);
        if ($st === '') continue;
        if (!isset($status[$st])) $status[$st] = 0;
        $status[$st] += $cnt;
        $totalBooks += $cnt;
    }
    mysqli_stmt_close($stmt);

    if ($scopeUserId === null) {
        $sql = "SELECT TRIM(genre) AS genre, COUNT(*) c
                FROM books
                WHERE genre IS NOT NULL AND TRIM(genre) <> '' AND status IN ('reading','completed')
                GROUP BY TRIM(genre)
                ORDER BY c DESC
                LIMIT 5";
        $stmt = mysqli_prepare($con, $sql);
    } else {
        $sql = "SELECT TRIM(genre) AS genre, COUNT(*) c
                FROM books
                WHERE user_id=? AND genre IS NOT NULL AND TRIM(genre) <> '' AND status IN ('reading','completed')
                GROUP BY TRIM(genre)
                ORDER BY c DESC
                LIMIT 5";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "i", $scopeUserId);
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $topGenres = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $g = (string)($row['genre'] ?? '');
        if (trim($g) === '') continue;
        $topGenres[] = ['genre'=>$g, 'count'=>(int)($row['c'] ?? 0)];
    }
    mysqli_stmt_close($stmt);

    $winnerStatus = 'none';
    $winnerCount  = 0;
    foreach ($status as $st => $cnt) {
        if ($cnt > $winnerCount) { $winnerCount = $cnt; $winnerStatus = $st; }
    }

    $topUsers = null;
    if ($isAdmin && $scopeUserId === null) {
        $sql = "SELECT users.id AS user_id, users.name, users.email, COUNT(*) c
                FROM books
                JOIN users ON users.id = books.user_id
                WHERE books.status='completed'
                GROUP BY users.id, users.name, users.email
                ORDER BY c DESC
                LIMIT 5";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $topUsers = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $topUsers[] = [
                'user' => [
                    'id'=>(int)($row['user_id'] ?? 0),
                    'name'=>$row['name'] ?? null,
                    'email'=>$row['email'] ?? null
                ],
                'completed' => (int)($row['c'] ?? 0)
            ];
        }
        mysqli_stmt_close($stmt);
    }

    return [
        'scope' => ($scopeUserId === null ? 'all_users' : 'single_user'),
        'total_books' => $totalBooks,
        'status_breakdown' => $status,
        'majority_status' => [
            'status' => $winnerStatus,
            'count'  => $winnerCount,
        ],
        'top_genres' => $topGenres,
        'top_users_by_completed' => $topUsers,
    ];
}

// ---------------- 9) SQL via LLM (role-aware) ----------------
function build_sql_with_llm(string $base, string $chatModel, string $question, bool $isAdmin): array {

    $rulesClient = <<<TXT
    You are a MySQL expert for a personal library app.
    Schema: books(id, user_id, title, author, genre, status, added_at)
    Status: 'planned', 'reading', 'completed'

    You MUST output ONLY valid JSON. No markdown. No code fences.

    Choose EXACTLY ONE intent:

    A) intent="count"
    Return:
    {"intent":"count","sql":"SELECT COUNT(*) AS c FROM books WHERE user_id=? ..."}
    Rules for count:
    - MUST select COUNT(*) AS c (or COUNT(DISTINCT ...) AS c)
    - MUST include user_id=? filter ALWAYS
    - Always return ONE row only (COUNT does that)

    B) intent="sql"
    Return:
    {"intent":"sql","sql":"SELECT ... LIMIT 50"}
    Rules for sql:
    - SELECT only
    - Always filter by user_id=? (mandatory)
    - Use placeholder ? ONLY for user_id
    - Always add LIMIT 50
    - Default ORDER BY added_at DESC

    C) intent="answer"
    Return:
    {"intent":"answer","answer":"..."}
    Use only if DB not needed.

    IMPORTANT:
    - If question contains: how many, count, total, number of -> use intent="count".
    TXT;

    $rulesAdmin = <<<TXT
    You are a MySQL expert for a library app.

    Schema:
    books(id, user_id, title, author, genre, status, added_at)
    users(id, name, email, role)

    You MUST output ONLY valid JSON. No markdown. No code fences.

    Choose EXACTLY ONE intent:

    A) intent="count"
    Return:
    {"intent":"count","sql":"SELECT COUNT(*) AS c FROM ..."}
    Rules:
    - Must select COUNT(*) AS c (or COUNT(DISTINCT ...) AS c)
    - SELECT only
    - You may count users/books/clients/genres
    - Placeholders:
    - If counting for a specific user, you MAY use user_id=? (only one ?).
    - For global admin counts, use NO placeholders.

    B) intent="sql"
    Return:
    {"intent":"sql","sql":"SELECT ... LIMIT 50"}
    Rules:
    - SELECT only
    - No sensitive user fields. Users: only id,name,email,role
    - If showing user info for books: JOIN users and alias users.id AS user_id
    - Placeholders:
    - Only allow ? for books.user_id if filtering a specific user (max 1)
    - Always add LIMIT 50
    - Default sorting: books ORDER BY books.added_at DESC, users ORDER BY users.id ASC

    C) intent="answer"
    Return:
    {"intent":"answer","answer":"..."}
    Use only if DB not needed.

    IMPORTANT:
    - If question contains: how many, count, total, number of -> use intent="count".
    TXT;

    $rules = $isAdmin ? $rulesAdmin : $rulesClient;

    $response = ollama_chat($base, $chatModel, [
        ['role'=>'system','content'=>$rules],
        ['role'=>'user','content'=>"Question: $question"],
    ], true);

    logger("SQL LLM Raw Response", $response);

    $raw = trim($response);
    if (preg_match('/^```json\s*(.*?)\s*```$/si', $raw, $m)) $raw = trim($m[1]);

    $json = json_decode($raw, true);
    if (!is_array($json) || empty($json['intent'])) {
        $retry = ollama_chat($base, $chatModel, [
            ['role'=>'system','content'=>$rules . "\nReturn JSON with keys: intent and (sql or answer). Do not return anything else."],
            ['role'=>'user','content'=>"Return ONLY JSON. Question: $question"],
        ], true);

        logger("SQL LLM Retry Response", $retry);

        $raw2 = trim($retry);
        if (preg_match('/^```json\s*(.*?)\s*```$/si', $raw2, $m2)) $raw2 = trim($m2[1]);
        $json = json_decode($raw2, true);
    }

    if (!is_array($json) || empty($json['intent'])) {
        throw new RuntimeException("LLM returned invalid JSON.");
    }

    $intent = (string)$json['intent'];

    if ($intent === 'answer') {
        return ['intent'=>'answer', 'answer'=>(string)($json['answer'] ?? '')];
    }

    if ($intent === 'count' || $intent === 'sql') {
        if (empty($json['sql']) || !is_string($json['sql'])) {
            throw new RuntimeException("LLM intent '$intent' missing sql.");
        }
        return ['intent'=>$intent, 'sql'=>$json['sql']];
    }

    throw new RuntimeException("Unknown LLM intent.");
}


function run_sql(mysqli $con, string $sql, ?int $scopeUserId, bool $isAdmin): array {
    $q = trim($sql);
    $low = strtolower($q);

    if (!str_starts_with($low, 'select')) throw new Exception("Only SELECT allowed");
    if (str_contains($q, ';')) throw new Exception("Multiple statements not allowed");
    if (preg_match('/\b(information_schema|mysql\.|performance_schema)\b/i', $q)) throw new Exception("Forbidden schema");
    if (preg_match('/\binto\s+outfile\b/i', $q)) throw new Exception("Forbidden clause");

    $hasBooksFrom = (bool)preg_match('/\bfrom\s+books\b/i', $q);
    $hasUsersFrom = (bool)preg_match('/\bfrom\s+users\b/i', $q);
    $hasUsersJoin = (bool)preg_match('/\bjoin\s+users\b/i', $q);

    if (!$hasBooksFrom && !$hasUsersFrom) throw new Exception("Only books/users queries allowed");

    if (!$isAdmin) {
        if ($hasUsersFrom || $hasUsersJoin) throw new Exception("Users table forbidden for client");
        if (!$hasBooksFrom) throw new Exception("Only books queries allowed");
    }

    $hasUserPlaceholder = (bool)preg_match('/\buser_id\s*=\s*\?/i', $q);

    if (!$isAdmin && !$hasUserPlaceholder) {
        if (preg_match('/\bwhere\b/i', $q)) $q .= " AND user_id = ?";
        else $q .= " WHERE user_id = ?";
        $hasUserPlaceholder = true;
    }

    if ($isAdmin && $scopeUserId === null && $hasUserPlaceholder) {
        $q = preg_replace('/\s+AND\s+user_id\s*=\s*\?/i', '', $q);
        $q = preg_replace('/\bWHERE\s+user_id\s*=\s*\?/i', 'WHERE 1=1', $q);
        $hasUserPlaceholder = (bool)preg_match('/\buser_id\s*=\s*\?/i', $q);
    }

    $placeholderCount = substr_count($q, '?');

    if ($isAdmin && $hasUsersFrom && $placeholderCount > 0) {
        throw new Exception("Placeholders not allowed in users queries");
    }

    if ($hasBooksFrom && $placeholderCount > 1) {
        throw new Exception("Too many placeholders");
    }

    $stmt = mysqli_prepare($con, $q);
    if (!$stmt) {
        logger("SQL Prepare Fail", mysqli_error($con));
        throw new RuntimeException("Database error.");
    }

    if ($hasUserPlaceholder) {
        if ($scopeUserId === null) {
            mysqli_stmt_close($stmt);
            throw new RuntimeException("Target user missing.");
        }
        mysqli_stmt_bind_param($stmt, "i", $scopeUserId);
    }

    if (!mysqli_stmt_execute($stmt)) {
        logger("SQL Execute Fail", mysqli_stmt_error($stmt));
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new RuntimeException($err);
    }

    $res = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($res && ($row = mysqli_fetch_assoc($res))) $rows[] = $row;
    mysqli_stmt_close($stmt);

    return $rows;
}

// ---------------- 10) RAG (role-aware) ----------------
function rag_top_books(mysqli $con, ?int $scopeUserId, string $question, string $base, string $embedModel, bool $isAdmin): array {
    $qEmb = ollama_embed($base, $embedModel, $question);
    if (empty($qEmb)) return [];

    if ($scopeUserId === null) {
        if (!$isAdmin) return [];
        $stmt = mysqli_prepare($con, "
          SELECT books.id, books.title, books.author, books.genre, books.status, books.added_at,
                 users.id AS user_id, users.name, users.email, users.role,
                 books.embedding_json
          FROM books
          JOIN users ON users.id = books.user_id
          LIMIT 200
        ");
    } else {
        $stmt = mysqli_prepare($con, "
          SELECT id, user_id, title, author, genre, status, added_at, embedding_json
          FROM books
          WHERE user_id=? LIMIT 200
        ");
        mysqli_stmt_bind_param($stmt, "i", $scopeUserId);
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $books = [];
    while ($row = mysqli_fetch_assoc($res)) {
        if (empty($row['embedding_json'])) {
            $textForEmb = "{$row['title']} by {$row['author']} ({$row['genre']}) - {$row['status']}";
            $newEmb = ollama_embed($base, $embedModel, $textForEmb);
            if ($newEmb) {
                $upd = mysqli_prepare($con, "UPDATE books SET embedding_json=? WHERE id=?");
                $jsonEmb = json_encode($newEmb, JSON_UNESCAPED_UNICODE);
                mysqli_stmt_bind_param($upd, "si", $jsonEmb, $row['id']);
                mysqli_stmt_execute($upd);
                mysqli_stmt_close($upd);
                $row['embedding_json'] = $jsonEmb;
            }
        }

        $emb = json_decode($row['embedding_json'] ?? '[]', true);
        if (is_array($emb) && count($emb) > 0) {
            $row['score'] = dot($qEmb, $emb);
            unset($row['embedding_json']);
            $books[] = $row;
        }
    }
    mysqli_stmt_close($stmt);

    usort($books, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));
    return array_slice($books, 0, 5);
}

// ---------------- 11) Recommendation Engine (Bonus) ----------------
function recommend_books(mysqli $con, int $userId, bool $isAdmin): array {
    $stmt = mysqli_prepare($con, "
        SELECT TRIM(genre) AS genre, COUNT(*) c
        FROM books
        WHERE user_id=? AND status IN ('reading','completed') AND genre IS NOT NULL AND TRIM(genre) <> ''
        GROUP BY TRIM(genre)
        ORDER BY c DESC
        LIMIT 2
    ");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $genres = [];
    while ($row = mysqli_fetch_assoc($res)) $genres[] = (string)$row['genre'];
    mysqli_stmt_close($stmt);

    if (empty($genres)) return [];

    $placeholders = implode(',', array_fill(0, count($genres), '?'));
    $types = str_repeat('s', count($genres)) . 'i';

    $sql = "
      SELECT id, title, author, genre, status, added_at
      FROM books
      WHERE status='planned' AND TRIM(genre) IN ($placeholders) AND user_id=?
      ORDER BY added_at DESC
      LIMIT 5
    ";
    $stmt = mysqli_prepare($con, $sql);

    $params = array_merge($genres, [$userId]);
    $bind = [];
    $bind[] = $types;
    foreach ($params as $k => $v) $bind[] = &$params[$k];
    call_user_func_array([$stmt, 'bind_param'], $bind);

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $recs = [];
    while ($row = mysqli_fetch_assoc($res)) $recs[] = $row;
    mysqli_stmt_close($stmt);

    if (empty($recs) && $isAdmin) {
        $sql2 = "
          SELECT books.id, books.title, books.author, books.genre, books.status, books.added_at,
                 users.id AS user_id, users.name, users.email, users.role
          FROM books
          JOIN users ON users.id=books.user_id
          WHERE TRIM(books.genre) IN ($placeholders)
          ORDER BY books.added_at DESC
          LIMIT 5
        ";
        $stmt = mysqli_prepare($con, $sql2);

        $params2 = $genres;
        $types2 = str_repeat('s', count($genres));
        $bind2 = [];
        $bind2[] = $types2;
        foreach ($params2 as $k => $v) $bind2[] = &$params2[$k];
        call_user_func_array([$stmt, 'bind_param'], $bind2);

        mysqli_stmt_execute($stmt);
        $res2 = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res2)) $recs[] = $row;
        mysqli_stmt_close($stmt);
    }

    return $recs;
}

// ---------------- 12) Main ----------------
try {
    $input = get_json_input();
    $message = trim((string)($input['message'] ?? ($_POST['message'] ?? '')));
    if ($message === '') json_out(['error' => 'Empty message'], 400);

    $targetUserId = isset($input['target_user_id']) ? (int)$input['target_user_id'] : null;
    if ($isAdmin && $targetUserId && $targetUserId > 0) {
        $scopeUserId = $targetUserId;
    }

    $targetUserText = null;
    if ($isAdmin) {
        $targetUserText = extract_target_user_text($message);
        if ($targetUserText) {
            $resolved = find_user_id_by_name_or_email($con, $targetUserText);
            if ($resolved) $scopeUserId = $resolved;
        }
    }

    logger("New Request", [
        'role' => $role,
        'userId' => $userId,
        'scopeUserId' => $scopeUserId,
        'targetUserText' => $targetUserText,
        'message' => $message
    ]);

    if (is_insights_question($message)) {
        $ins = library_insights($con, $scopeUserId, $isAdmin);

        $sysPrompt = "You are a librarian analyst. Create a short 'Library Insights' summary.
        Use ONLY INSIGHTS JSON. Provide:
        - 3-6 bullet insights
        - A short summary sentence telling whether most books are completed, reading, or planned (use majority_status).
        - Mention the most read genre (top_genres[0]) if available.
        Rules:
        - Do not mention SQL, database, IDs, or user_id.
        - If total_books is 0, say there isn't enough data yet.";

        $finalAnswer = ollama_chat($OLLAMA_BASE, $CHAT_MODEL, [
            ['role'=>'system', 'content'=>$sysPrompt],
            ['role'=>'user', 'content'=>"User Question: " . escape_for_prompt($message) . "\nINSIGHTS: " . json_encode($ins, JSON_UNESCAPED_UNICODE)]
        ]);
        $finalAnswer = maybe_json_to_text($finalAnswer);


        if (!$isAdmin) {
            json_out([
                'success' => true,
                'mode' => 'insights',
                'role' => $role,
                'answer' => $finalAnswer,
                'data' => null
            ]);
        }

        json_out([
            'success' => true,
            'mode' => 'insights',
            'role' => $role,
            'answer' => $finalAnswer,
            'data' => $ins
        ]);
    }

    // --- Recommendation mode ---
    if (is_recommend_question($message)) {
        $target = ($isAdmin && $targetUserId && $targetUserId > 0) ? $targetUserId : ($scopeUserId ?? $userId);

        if (!$isAdmin && $target !== $userId) $target = $userId;

        $recs = recommend_books($con, $target, $isAdmin);
        $recs = sanitize_rows($recs, $isAdmin);

        $finalAnswer = ollama_chat($OLLAMA_BASE, $CHAT_MODEL, [
            ['role'=>'system', 'content'=>"You are a friendly librarian assistant. Recommend books based on RECOMMENDATIONS. Do not mention any IDs or user_id. Keep it short and helpful."],
            ['role'=>'user', 'content'=>"User Question: " . escape_for_prompt($message) . "\nRECOMMENDATIONS: " . json_encode($recs, JSON_UNESCAPED_UNICODE)]
        ]);

        json_out([
            'success' => true,
            'mode' => 'recommend',
            'role' => $role,
            'answer' => $finalAnswer,
            'data' => $isAdmin ? $recs : null
        ]);
    }

    $mode = is_structured_question($message) ? 'sql' : 'rag';

    $finalAnswer = "";
    $data = [];

    if ($mode === 'sql') {
    logger("Mode: SQL");

    $q = build_sql_with_llm($OLLAMA_BASE, $CHAT_MODEL, $message, $isAdmin);

    if (($q['intent'] ?? '') === 'answer') {
        $finalAnswer = (string)($q['answer'] ?? '');
        if ($finalAnswer === '') $finalAnswer = "I can help, but I need a bit more detail.";

        if (!$isAdmin) {
            json_out(['success'=>true,'mode'=>'answer','role'=>$role,'answer'=>$finalAnswer,'data'=>null]);
        }
        json_out(['success'=>true,'mode'=>'answer','role'=>$role,'answer'=>$finalAnswer,'data'=>null]);
    }

    $sql = (string)($q['sql'] ?? '');
    logger("Generated SQL", $sql);

    $data = run_sql($con, $sql, $scopeUserId, $isAdmin);

    if (($q['intent'] ?? '') === 'count') {
        $c = 0;
        if (!empty($data) && isset($data[0]['c'])) $c = (int)$data[0]['c'];

        $finalAnswer = ollama_chat($OLLAMA_BASE, $CHAT_MODEL, [
            ['role'=>'system', 'content'=>"You are a librarian assistant. Reply in plain text only. Use COUNT to answer. Do not mention SQL/DB/IDs."],
            ['role'=>'user', 'content'=>"Question: " . escape_for_prompt($message) . "\nCOUNT: " . $c]
        ]);

        if (!$isAdmin) {
            json_out(['success'=>true,'mode'=>'count','role'=>$role,'answer'=>$finalAnswer,'data'=>null]);
        }
        json_out(['success'=>true,'mode'=>'count','role'=>$role,'answer'=>$finalAnswer,'data'=>['count'=>$c]]);
    }

    $data = sanitize_rows($data, $isAdmin);

    $sysPrompt = "You are a professional library assistant.
    Use ONLY the provided DATA to answer.

    Write a helpful, longer response with this structure:
    1) A 1-sentence direct answer.
    2) A short breakdown (2-5 bullet points) summarizing what you found.
    3) If applicable, include extra context: top genre/status patterns or notable titles/authors.
    4) If DATA is empty, explain what might be missing and suggest 2 alternative queries the user can try.

    Rules:
    - Do NOT mention SQL, database, embeddings, IDs, or any user_id.
    - Be clear and professional (not too casual).
    - Keep it readable, not overly long (about 8–15 lines).";

        $finalAnswer = ollama_chat($OLLAMA_BASE, $CHAT_MODEL, [
            ['role'=>'system', 'content'=>$sysPrompt],
            ['role'=>'user', 'content'=>"User Question: " . escape_for_prompt($message) . "\nDATA: " . json_encode($data, JSON_UNESCAPED_UNICODE)]
        ]);

    } else {
        logger("Mode: RAG");

        $data = rag_top_books($con, $scopeUserId, $message, $OLLAMA_BASE, $EMBED_MODEL, $isAdmin);
        $data = sanitize_rows($data, $isAdmin);

        $context = "";
        foreach ($data as $b) {
            $title  = escape_for_prompt((string)($b['title'] ?? ''));
            $author = escape_for_prompt((string)($b['author'] ?? ''));
            $genre  = escape_for_prompt((string)($b['genre'] ?? ''));
            $status = escape_for_prompt((string)($b['status'] ?? ''));

            if ($isAdmin && isset($b['user']) && is_array($b['user'])) {
                $uname = escape_for_prompt((string)($b['user']['name'] ?? ''));
                $uemail = escape_for_prompt((string)($b['user']['email'] ?? ''));
                $context .= "- Title: {$title}, Author: {$author}, Genre: {$genre}, Status: {$status}, User: {$uname} ({$uemail})\n";
            } else {
                $context .= "- Title: {$title}, Author: {$author}, Genre: {$genre}, Status: {$status}\n";
            }
        }

        $sysPrompt = "You are a professional library assistant.
        Use ONLY the provided DATA to answer.

        Write a helpful, longer response with this structure:
        1) A 1-sentence direct answer.
        2) A short breakdown (2-5 bullet points) summarizing what you found.
        3) If applicable, include extra context: top genre/status patterns or notable titles/authors.
        4) If DATA is empty, explain what might be missing and suggest 2 alternative queries the user can try.

        Rules:
        - Do NOT mention SQL, database, embeddings, IDs, or any user_id.
        - Be clear and professional (not too casual).
        - Keep it readable, not overly long (about 8–15 lines).";

        $finalAnswer = ollama_chat($OLLAMA_BASE, $CHAT_MODEL, [
            ['role'=>'system', 'content'=>$sysPrompt],
            ['role'=>'user', 'content'=>"CONTEXT:\n{$context}\nUser Question: " . escape_for_prompt($message)]
        ]);
    }

    if (!$isAdmin) {
        json_out([
            'success' => true,
            'mode' => $mode,
            'role' => $role,
            'answer' => $finalAnswer,
            'data' => null
        ]);
    }

    json_out([
        'success' => true,
        'mode' => $mode,
        'role' => $role,
        'answer' => $finalAnswer,
        'data' => $data
    ]);

} catch (Throwable $e) {
    logger("FATAL ERROR", $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    json_out(['error' => 'Something went wrong. Please try again later.'], 500);
}
