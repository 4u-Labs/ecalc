<?php
/**
 * API de Geração de Contratos com IA
 * Padrão 4U.IA.BR Premium — Integrada ao Keep AI
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// CARREGA VARIÁVEIS DO ARQUIVO .ENV
// ═══════════════════════════════════════════════════════════════════════════

function loadEnv($path) {
    if (!file_exists($path)) return false;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        $value = trim($value, '"\'');
        if (!empty($name)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
    return true;
}

$envPaths = [
    __DIR__ . '/../.env',
    __DIR__ . '/.env',
];
foreach ($envPaths as $path) {
    if (loadEnv($path)) break;
}

$config = [
    'provider' => getenv('API_PROVIDER') ?: 'openai',
    'openai_api_key' => getenv('OPENAI_API_KEY') ?: '',
    'anthropic_api_key' => getenv('ANTHROPIC_API_KEY') ?: '',
    'gemini_api_key' => getenv('GEMINI_API_KEY') ?: '',
    'openai_model' => getenv('OPENAI_MODEL') ?: 'gpt-4o-mini',
    'anthropic_model' => getenv('ANTHROPIC_MODEL') ?: 'claude-3-haiku-20240307',
    'gemini_model' => getenv('GEMINI_MODEL') ?: 'gemini-pro',
    'max_tokens' => (int)(getenv('OPENAI_MAX_TOKENS') ?: 4000),
    'temperature' => (float)(getenv('OPENAI_TEMPERATURE') ?: 0.5),
];

// ═══════════════════════════════════════════════════════════════════════════
// AUTENTICAÇÃO E CONEXÃO KEEPAI
// ═══════════════════════════════════════════════════════════════════════════

$keepaiUser = null;
$bearerToken = null;

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/Bearer\s+(.+)/i', $authHeader, $m)) {
    $bearerToken = trim($m[1]);
}
if (!$bearerToken) {
    $bearerToken = $_GET['keepai_token'] ?? $_POST['keepai_token'] ?? null;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];
if (!$bearerToken && isset($body['keepai_token'])) {
    $bearerToken = $body['keepai_token'];
}

$keepaiDb = __DIR__ . '/../../keepai/database/keepai.db';
$kpdo = null;

if ($bearerToken) {
    if (file_exists($keepaiDb)) {
        try {
            $kpdo = new PDO("sqlite:$keepaiDb");
            $kpdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $kpdo->prepare("SELECT * FROM users WHERE token = ?");
            $stmt->execute([$bearerToken]);
            $keepaiUser = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Ignore connection errors
        }
    }
}

$action = $body['action'] ?? $_GET['action'] ?? $_POST['action'] ?? null;
if ($action === null && isset($body['promptSistema'])) {
    $action = 'generate_contract';
}

// Para ações que não exigem login
if ($action === 'check_status' && !$keepaiUser) {
    echo json_encode(['status' => 'success', 'data' => ['credits' => 0, 'email' => ''], 'mode' => 'guest']);
    exit;
}

// Webhook não exige login na requisição
if ($action === 'create_pix_webhook') {
    // Processa direto na rota do webhook abaixo
} else {
    // Demais ações requerem autenticação
    if (!$keepaiUser) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'error' => 'Acesso não autorizado. Por favor, conecte sua conta Keep AI.']);
        exit;
    }
}

switch ($action) {
    case 'check_status':
        echo json_encode([
            'status' => 'success',
            'data' => [
                'credits' => (int)$keepaiUser['credits'],
                'email' => $keepaiUser['email']
            ],
            'mode' => 'keepai'
        ]);
        break;

    case 'generate_contract':
        if ($keepaiUser['credits'] < 1) {
            http_response_code(402);
            echo json_encode(['status' => 'error', 'error' => 'Créditos insuficientes. Recarregue seu saldo Keep AI!']);
            exit;
        }

        $promptSistema = $body['promptSistema'] ?? '';
        $promptUsuario = $body['promptUsuario'] ?? '';
        $model = $body['model'] ?? null;

        if (empty($promptSistema) || empty($promptUsuario)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'error' => 'promptSistema e promptUsuario são obrigatórios.']);
            exit;
        }

        try {
            $textoContrato = '';
            switch ($config['provider']) {
                case 'openai':
                    $textoContrato = chamarOpenAI($config, $promptSistema, $promptUsuario, $model);
                    break;
                case 'anthropic':
                    $textoContrato = chamarAnthropic($config, $promptSistema, $promptUsuario, $model);
                    break;
                case 'gemini':
                    $textoContrato = chamarGemini($config, $promptSistema, $promptUsuario, $model);
                    break;
                default:
                    throw new Exception('Provedor de IA não configurado: ' . $config['provider']);
            }

            // --- Débito de Crédito ---
            $kpdo->beginTransaction();
            $stmt = $kpdo->prepare("UPDATE users SET credits = MAX(0, credits - 1), updated_at = datetime('now') WHERE id = ?");
            $stmt->execute([$keepaiUser['id']]);

            $stmt = $kpdo->prepare("INSERT INTO transactions (user_id, mp_payment_id, package_label, amount_brl, credits_added, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $keepaiUser['id'],
                'CONTRATO-' . time() . '-' . rand(1000, 9999),
                'Geração de Contrato IA',
                0.00,
                -1,
                'approved'
            ]);
            $kpdo->commit();

            // Busca saldo atualizado
            $stmt = $kpdo->prepare("SELECT credits FROM users WHERE id = ?");
            $stmt->execute([$keepaiUser['id']]);
            $newCredits = (int)$stmt->fetchColumn();

            echo json_encode([
                'success' => true,
                'textoContrato' => $textoContrato,
                'provider' => $config['provider'],
                'model' => $model ?? $config[$config['provider'] . '_model'],
                'credits_remaining' => $newCredits
            ]);

        } catch (Exception $e) {
            if ($kpdo && $kpdo->inTransaction()) $kpdo->rollBack();
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'error' => $e->getMessage(),
                'provider' => $config['provider']
            ]);
        }
        break;



    case 'activate_bonus':
        $pass = $body['password'] ?? $_POST['password'] ?? '';
        if ($pass === 'Fbr4g4@') {
            $kpdo->prepare("UPDATE users SET credits = credits + 50, updated_at = datetime('now') WHERE id = ?")
                 ->execute([$keepaiUser['id']]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'error' => 'Senha master incorreta.']);
        }
        break;

    case 'create_pix_webhook':
        // Webhook Mercado Pago
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (isset($data['type']) && $data['type'] === 'payment') {
            $payment_id = $data['data']['id'];

            // Recupera token do MP
            $mpToken = '';
            $keepaiConfig = __DIR__ . '/../../keepai/api/config.php';
            if (file_exists($keepaiConfig)) {
                $configContent = file_get_contents($keepaiConfig);
                if (preg_match("/define\('MP_ACCESS_TOKEN',\s*'([^']+)'\)/", $configContent, $matches)) {
                    $mpToken = $matches[1];
                }
            }

            if ($mpToken) {
                $ch = curl_init("https://api.mercadopago.com/v1/payments/$payment_id");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $mpToken"]);
                $response = curl_exec($ch);
                $payment_info = json_decode($response, true);
                curl_close($ch);

                if (isset($payment_info['status']) && $payment_info['status'] === 'approved') {
                    $ext_ref = $payment_info['external_reference'] ?? '';
                    $parts = explode(':', $ext_ref);

                    if (count($parts) >= 3 && $parts[0] === 'keepai') {
                        $identifier = $parts[1];
                        $packageIndex = (int)$parts[2];

                        $packages = [
                            0 => ['credits' => 10,  'label' => 'Bronze — 10 créditos'],
                            1 => ['credits' => 50,  'label' => 'Prata — 50 créditos'],
                            2 => ['credits' => 100, 'label' => 'Ouro — 100 créditos'],
                        ];

                        $credits = isset($packages[$packageIndex]) ? $packages[$packageIndex]['credits'] : 0;
                        $label = isset($packages[$packageIndex]) ? $packages[$packageIndex]['label'] : 'Recarga PIX';
                        $amountBRL = (float)($payment_info['transaction_amount'] ?? 0);

                        $keepaiDb = __DIR__ . '/../../keepai/database/keepai.db';
                        if (file_exists($keepaiDb)) {
                            try {
                                $kpdo = new PDO("sqlite:$keepaiDb");
                                $kpdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                
                                $kpdo->beginTransaction();
                                
                                $checkStmt = $kpdo->prepare('SELECT status FROM transactions WHERE mp_payment_id = ?');
                                $checkStmt->execute([(string)$payment_id]);
                                $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($existing && $existing['status'] === 'approved') {
                                    $kpdo->rollBack();
                                } else {
                                    // Adiciona créditos
                                    $stmt = $kpdo->prepare('UPDATE users SET credits = credits + ?, updated_at = datetime("now") WHERE id = ?');
                                    $stmt->execute([$credits, $identifier]);
                                    
                                    // Salva transação
                                    if ($existing) {
                                        $stmt = $kpdo->prepare('UPDATE transactions SET status = "approved", credits_added = ?, amount_brl = ? WHERE mp_payment_id = ?');
                                        $stmt->execute([$credits, $amountBRL, (string)$payment_id]);
                                    } else {
                                        $stmt = $kpdo->prepare('INSERT INTO transactions (user_id, mp_payment_id, package_label, amount_brl, credits_added, status) VALUES (?, ?, ?, ?, ?, "approved")');
                                        $stmt->execute([$identifier, (string)$payment_id, $label, $amountBRL, $credits]);
                                    }
                                    $kpdo->commit();
                                }
                            } catch (Exception $e) {
                                if (isset($kpdo) && $kpdo->inTransaction()) $kpdo->rollBack();
                            }
                        }
                    }
                }
            }
        }
        http_response_code(200);
        echo "OK";
        break;

    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'error' => 'Ação inválida.']);
        break;
}

// ═══════════════════════════════════════════════════════════════════════════
// FUNÇÕES DE CHAMADA PARA CADA PROVEDOR
// ═══════════════════════════════════════════════════════════════════════════

function chamarOpenAI($config, $promptSistema, $promptUsuario, $model = null) {
    $apiKey = $config['openai_api_key'];
    $modelToUse = $model ?? $config['openai_model'];
    if (empty($apiKey)) throw new Exception('Chave API da OpenAI não configurada.');
    $data = [
        'model' => $modelToUse,
        'messages' => [
            ['role' => 'system', 'content' => $promptSistema],
            ['role' => 'user', 'content' => $promptUsuario]
        ],
        'temperature' => $config['temperature'],
        'max_tokens' => $config['max_tokens']
    ];
    $response = fazerRequisicaoHTTP('https://api.openai.com/v1/chat/completions', $data, ['Authorization: Bearer ' . $apiKey]);
    if (isset($response['error'])) {
        throw new Exception('Erro OpenAI: ' . ($response['error']['message'] ?? json_encode($response['error'])));
    }
    return $response['choices'][0]['message']['content'] ?? '';
}

function chamarAnthropic($config, $promptSistema, $promptUsuario, $model = null) {
    $apiKey = $config['anthropic_api_key'];
    $modelToUse = $model ?? $config['anthropic_model'];
    if (empty($apiKey)) throw new Exception('Chave API da Anthropic não configurada.');
    $data = [
        'model' => $modelToUse,
        'max_tokens' => $config['max_tokens'],
        'system' => $promptSistema,
        'messages' => [
            ['role' => 'user', 'content' => $promptUsuario]
        ]
    ];
    $response = fazerRequisicaoHTTP('https://api.anthropic.com/v1/messages', $data, ['x-api-key: ' . $apiKey, 'anthropic-version: 2023-06-01']);
    if (isset($response['error'])) {
        throw new Exception('Erro Anthropic: ' . ($response['error']['message'] ?? json_encode($response['error'])));
    }
    return $response['content'][0]['text'] ?? '';
}

function chamarGemini($config, $promptSistema, $promptUsuario, $model = null) {
    $apiKey = $config['gemini_api_key'];
    $modelToUse = $model ?? $config['gemini_model'];
    if (empty($apiKey)) throw new Exception('Chave API do Gemini não configurada.');
    $data = [
        'contents' => [['parts' => [['text' => $promptSistema . "\n\n" . $promptUsuario]]]],
        'generationConfig' => ['temperature' => $config['temperature'], 'maxOutputTokens' => $config['max_tokens']]
    ];
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelToUse}:generateContent?key={$apiKey}";
    $response = fazerRequisicaoHTTP($url, $data, []);
    if (isset($response['error'])) {
        throw new Exception('Erro Gemini: ' . ($response['error']['message'] ?? json_encode($response['error'])));
    }
    return $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
}

function fazerRequisicaoHTTP($url, $data, $headers = []) {
    $ch = curl_init($url);
    $defaultHeaders = ['Content-Type: application/json'];
    $allHeaders = array_merge($defaultHeaders, $headers);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => $allHeaders,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) throw new Exception('Erro de conexão: ' . $error);
    $decoded = json_decode($response, true);
    if ($httpCode >= 400) {
        if ($decoded && isset($decoded['error'])) {
            throw new Exception($decoded['error']['message'] ?? 'Erro HTTP ' . $httpCode);
        }
        throw new Exception('Erro HTTP ' . $httpCode . ': ' . $response);
    }
    return $decoded;
}
