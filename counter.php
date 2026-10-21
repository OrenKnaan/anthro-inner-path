<?php
/**
 * Visitor counter for the anthro-inner-path page.
 *
 * POST {"vid": "..."} records a page view and answers the totals. GET answers
 * the totals only; the page polls it so the numbers grow while it is open.
 *
 * A visitor is one browser: a random id the page keeps in localStorage. A
 * visit is a run of page views from one visitor with no gap longer than
 * VISIT_GAP, so a reload or a quick return does not count twice. Nothing else
 * is stored about anyone: no address, no user agent, no page history.
 *
 * The database lives outside public_html, as /clarity/'s does. This host
 * ignores .htaccess, so anything under the document root is a public download.
 */

const VISIT_GAP          = 1800; // seconds away before the next view is a new visit
const NEW_PER_IP_PER_DAY = 10;   // new visitor ids one address may add per day

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

/** A writable directory no URL can reach, or null rather than a public one. */
function counter_dir(): ?string {
    // __DIR__ is <root>/public_html/anthro-inner-path, so two levels up clears
    // the document root. ANTHRO_COUNTER_DIR overrides it for a local test run.
    $env = getenv('ANTHRO_COUNTER_DIR');
    $dir = ($env !== false && $env !== '') ? $env : dirname(__DIR__, 2) . '/anthro-inner-path-private';

    if (!is_dir($dir) && !@mkdir($dir, 0700, true)) {
        return null;
    }
    $real = realpath($dir);
    $docroot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    if ($real === false || !is_writable($real)) {
        return null;
    }
    // Refuse a layout where "outside" turned out to be inside the web root.
    if ($docroot !== false && strpos($real . '/', rtrim($docroot, '/') . '/') === 0) {
        return null;
    }
    return $real;
}

function counter_db(string $dir): PDO {
    $pdo = new PDO('sqlite:' . $dir . '/counter.sqlite', null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec('CREATE TABLE IF NOT EXISTS visitors (
        vid        TEXT PRIMARY KEY,
        first_seen INTEGER NOT NULL,
        last_seen  INTEGER NOT NULL,
        visits     INTEGER NOT NULL DEFAULT 1
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS new_by_ip (
        ip_hash TEXT NOT NULL,
        day     TEXT NOT NULL,
        n       INTEGER NOT NULL,
        PRIMARY KEY (ip_hash, day)
    )');
    return $pdo;
}

/** Same order of trust as clarity_client_ip(). Only feeds the rate limit. */
function counter_client_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_TRUE_CLIENT_IP'] as $key) {
        if (!empty($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
            return $_SERVER[$key];
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

/** Pseudonymous and per-install: the salt is made on first run and never leaves the private dir. */
function counter_ip_hash(string $dir, string $ip): string {
    $file = $dir . '/salt';
    $salt = is_file($file) ? trim((string) file_get_contents($file)) : '';
    if ($salt === '') {
        $salt = bin2hex(random_bytes(16));
        file_put_contents($file, $salt, LOCK_EX);
        @chmod($file, 0600);
    }
    return substr(hash('sha256', $salt . '|' . $ip), 0, 32);
}

function counter_totals(PDO $pdo): array {
    $row = $pdo->query('SELECT COUNT(*) AS visitors, COALESCE(SUM(visits), 0) AS visits FROM visitors')->fetch();
    return ['visitors' => (int) $row['visitors'], 'visits' => (int) $row['visits']];
}

function counter_record(PDO $pdo, string $dir, string $vid): void {
    // Crawlers that run scripts would otherwise count as readers.
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '' || preg_match('/bot|crawl|spider|slurp|headless|lighthouse|preview|fetch|monitor/i', $ua)) {
        return;
    }

    $now = time();
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $q = $pdo->prepare('SELECT last_seen FROM visitors WHERE vid = ?');
        $q->execute([$vid]);
        $last = $q->fetchColumn();

        if ($last !== false) {
            $newVisit = ($now - (int) $last) > VISIT_GAP ? 1 : 0;
            $pdo->prepare('UPDATE visitors SET last_seen = ?, visits = visits + ? WHERE vid = ?')
                ->execute([$now, $newVisit, $vid]);
        } else {
            // A fresh id costs nothing to make, so one address may only add a few a day.
            $day = gmdate('Y-m-d', $now);
            $ip  = counter_ip_hash($dir, counter_client_ip());
            $q = $pdo->prepare('SELECT n FROM new_by_ip WHERE ip_hash = ? AND day = ?');
            $q->execute([$ip, $day]);
            $n = (int) $q->fetchColumn();

            if ($n < NEW_PER_IP_PER_DAY) {
                $pdo->prepare('INSERT INTO visitors (vid, first_seen, last_seen, visits) VALUES (?, ?, ?, 1)')
                    ->execute([$vid, $now, $now]);
                $pdo->prepare('INSERT OR REPLACE INTO new_by_ip (ip_hash, day, n) VALUES (?, ?, ?)')
                    ->execute([$ip, $day, $n + 1]);
                $pdo->prepare('DELETE FROM new_by_ip WHERE day < ?')->execute([$day]);
            }
        }
        $pdo->exec('COMMIT');
    } catch (Throwable $e) {
        $pdo->exec('ROLLBACK');
        throw $e;
    }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET' && $method !== 'POST') {
    http_response_code(405);
    exit;
}

$dir = counter_dir();
if ($dir === null) {
    error_log('[anthro-inner-path counter] no private directory outside the document root');
    http_response_code(503);
    echo '{"error":"unavailable"}';
    exit;
}

try {
    $pdo = counter_db($dir);

    if ($method === 'POST') {
        $raw = file_get_contents('php://input', false, null, 0, 1024);
        $in  = json_decode((string) $raw, true);
        $vid = is_array($in) && is_string($in['vid'] ?? null) ? $in['vid'] : '';
        if (!preg_match('/^[a-f0-9]{32}$/', $vid)) {
            http_response_code(400);
            echo '{"error":"bad_vid"}';
            exit;
        }
        counter_record($pdo, $dir, $vid);
    }

    echo json_encode(counter_totals($pdo));
} catch (Throwable $e) {
    error_log('[anthro-inner-path counter] ' . $e->getMessage());
    http_response_code(503);
    echo '{"error":"unavailable"}';
}
