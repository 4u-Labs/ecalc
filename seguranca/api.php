<?php
declare(strict_types=1);

/**
 * SafeWork Pro - API Backend (Ecosystem Integrated)
 * Geração de documentos de segurança do trabalho com IA e Controle de Créditos
 */

// Permite preflight OPTIONS (CORS já tratado pelo config.php do Keep AI, mas por segurança reforçamos)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-TOKEN');
    http_response_code(200);
    exit(0);
}

// 1. Integra com a base de dados SQLite unificada do Keep AI (4uLabs)
require_once __DIR__ . '/../../keepai/api/database.php';

// 2. Carrega as variáveis de ambiente locais do SafeWork Pro (.env)
require_once __DIR__ . '/config.php';

// 3. Validação de Segurança e Créditos (Executada apenas se a ação não for "test")
$action = $_GET['action'] ?? '';
$user = null;

if ($action !== 'test') {
    // Valida o Bearer Token no header Authorization
    $user = verifyAuthToken();
    $uid = (int) $user['id'];
    
    // Verifica se possui créditos suficientes para uma geração de IA (1 crédito)
    $credits = (int) $user['credits'];
    if ($credits < 1) {
        http_response_code(402); // Payment Required
        echo json_encode([
            'success' => false,
            'error' => 'Saldo de IA insuficiente. Recarregue clicando no saldo de créditos no topo da tela.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

class AIDocumentGenerator {
    private $provider;
    
    public function __construct() {
        $this->provider = AI_PROVIDER;
    }
    
    /**
     * Gera documento usando a API de IA configurada
     */
    public function generate(string $type, array $data): array {
        $prompt = $this->buildPrompt($type, $data);
        
        if ($this->provider === 'gemini') {
            return $this->callGemini($prompt);
        } else {
            return $this->callOpenAI($prompt);
        }
    }
    
    /**
     * Constrói o prompt baseado no tipo de documento
     */
    private function buildPrompt(string $type, array $data): string {
        $baseContext = "Você é um especialista em Segurança do Trabalho no Brasil, com profundo conhecimento das Normas Regulamentadoras (NRs). Gere documentos técnicos, completos e profissionais.";
        
        switch ($type) {
            case 'PGR':
                return $this->buildPGRPrompt($data, $baseContext);
            case 'PCMAT':
                return $this->buildPCMATPrompt($data, $baseContext);
            case 'APR':
                return $this->buildAPRPrompt($data, $baseContext);
            case 'TERMO_EPI':
                return $this->buildTermoEPIPrompt($data, $baseContext);
            case 'CERTIFICADO':
                return $this->buildCertificadoPrompt($data, $baseContext);
            default:
                throw new Exception("Tipo de documento não suportado: $type");
        }
    }
    
    private function buildPGRPrompt(array $data, string $context): string {
        $riscos = implode(', ', $data['riscos'] ?? []);
        return "$context\n\nGere um PGR (Programa de Gerenciamento de Riscos) COMPLETO e PROFISSIONAL conforme a NR-01 para:\n\nDADOS DA EMPRESA:\n- Razão Social: {$data['empresa']}\n- CNPJ: {$data['cnpj']}\n- Endereço: {$data['endereco']}\n- Ramo de Atividade: {$data['ramo']}\n- Número de Funcionários: {$data['funcionarios']}\n- Responsável Técnico: {$data['responsavel']}\n- CREA/Registro: {$data['crea']}\n- Riscos Identificados: {$riscos}\n- Informações Adicionais: {$data['plano_acao']}\n\nIMPORTANTE: Gere conteúdo REAL e DETALHADO, não use placeholders. Os itens devem ser STRINGS de texto, não objetos.\n\nResponda APENAS com o JSON abaixo (sem markdown):\n{\n    \"titulo\": \"PROGRAMA DE GERENCIAMENTO DE RISCOS - PGR\",\n    \"subtitulo\": \"Conforme NR-01 - {$data['empresa']}\",\n    \"secoes\": [\n        {\n            \"numero\": \"1\",\n            \"titulo\": \"IDENTIFICAÇÃO DA EMPRESA\",\n            \"conteudo\": \"Razão Social: {$data['empresa']}. CNPJ: {$data['cnpj']}. Endereço: {$data['endereco']}. Ramo: {$data['ramo']}. Funcionários: {$data['funcionarios']}.\",\n            \"itens\": []\n        },\n        {\n            \"numero\": \"2\",\n            \"titulo\": \"OBJETIVO\",\n            \"conteudo\": \"Este PGR tem por objetivo estabelecer diretrizes e requisitos para o gerenciamento de riscos ocupacionais...\",\n            \"itens\": [\"Identificar perigos e avaliar riscos\", \"Implementar medidas de prevenção\"]\n        },\n        {\n            \"numero\": \"3\",\n            \"titulo\": \"RESPONSÁVEL TÉCNICO\",\n            \"conteudo\": \"Nome: {$data['responsavel']}. Registro: {$data['crea']}.\",\n            \"itens\": []\n        },\n        {\n            \"numero\": \"4\",\n            \"titulo\": \"INVENTÁRIO DE RISCOS\",\n            \"conteudo\": \"Foram identificados os seguintes riscos ocupacionais:\",\n            \"itens\": [\"RISCO FÍSICO: Ruído - Fonte: máquinas e equipamentos - Medida: uso de protetor auricular\", \"RISCO QUÍMICO: Poeiras - Fonte: processos produtivos - Medida: uso de máscara PFF2\", \"inclua todos os riscos selecionados: $riscos com descrição detalhada como STRING\"]\n        },\n        {\n            \"numero\": \"5\",\n            \"titulo\": \"PLANO DE AÇÃO\",\n            \"conteudo\": \"Medidas de prevenção e controle a serem implementadas:\",\n            \"itens\": [\"Implementar programa de manutenção preventiva - Prazo: 30 dias - Responsável: Manutenção\", \"Realizar treinamentos de segurança - Prazo: 15 dias - Responsável: RH\"]\n        },\n        {\n            \"numero\": \"6\",\n            \"titulo\": \"MEDIDAS DE CONTROLE\",\n            \"conteudo\": \"Hierarquia de controles aplicada:\",\n            \"itens\": [\"Eliminação: remover fontes de risco quando possível\", \"Substituição: trocar processos perigosos por mais seguros\", \"Controle de Engenharia: instalação de proteções coletivas\", \"Controle Administrativo: procedimentos e sinalizações\", \"EPIs: fornecimento e uso obrigatório\"]\n        }\n    ],\n    \"validade\": \"2 anos a partir da data de emissão\"\n}\n\nREGRA CRÍTICA: Todos os \"itens\" devem ser STRINGS simples, nunca objetos. Gere conteúdo real baseado nos dados fornecidos.";
    }
    
    private function buildPCMATPrompt(array $data, string $context): string {
        $funcoes = implode(', ', $data['funcoes'] ?? []);
        return "$context\n\nGere um PCMAT COMPLETO e PROFISSIONAL conforme NR-18 para:\n\nDADOS DA OBRA:\n- Nome da Obra: {$data['obra']}\n- Contratante: {$data['contratante']}\n- Endereço: {$data['endereco']}\n- Tipo de Obra: {$data['tipo_obra']}\n- Número de Trabalhadores: {$data['trabalhadores']}\n- Data de Início: {$data['data_inicio']}\n- Previsão de Término: {$data['data_fim']}\n- Engenheiro Responsável: {$data['engenheiro']}\n- CREA: {$data['crea']}\n- Funções na Obra: {$funcoes}\n- Informações Adicionais: {$data['medidas']}\n\nIMPORTANTE: Gere conteúdo REAL. Todos os itens devem ser STRINGS simples.\n\nResponda APENAS com o JSON (sem markdown):\n{\n    \"titulo\": \"PCMAT - PROGRAMA DE CONDIÇÕES E MEIO AMBIENTE DE TRABALHO\",\n    \"subtitulo\": \"Conforme NR-18 - {$data['obra']}\",\n    \"secoes\": [\n        {\n            \"numero\": \"1\",\n            \"titulo\": \"IDENTIFICAÇÃO DA OBRA\",\n            \"conteudo\": \"Obra: {$data['obra']}. Contratante: {$data['contratante']}. Endereço: {$data['endereco']}. Tipo: {$data['tipo_obra']}. Trabalhadores: {$data['trabalhadores']}. Período: {$data['data_inicio']} a {$data['data_fim']}.\",\n            \"itens\": []\n        },\n        {\n            \"numero\": \"2\",\n            \"titulo\": \"RESPONSÁVEL TÉCNICO\",\n            \"conteudo\": \"Engenheiro: {$data['engenheiro']}. CREA: {$data['crea']}.\",\n            \"itens\": []\n        },\n        {\n            \"numero\": \"3\",\n            \"titulo\": \"OBJETIVO\",\n            \"conteudo\": \"Estabelecer diretrizes de segurança e saúde no trabalho para a obra, em conformidade com a NR-18.\",\n            \"itens\": [\"Garantir condições seguras de trabalho\", \"Prevenir acidentes e doenças ocupacionais\", \"Atender à legislação vigente\"]\n        },\n        {\n            \"numero\": \"4\",\n            \"titulo\": \"ÁREAS DE VIVÊNCIA\",\n            \"conteudo\": \"O canteiro de obras disporá das seguintes instalações:\",\n            \"itens\": [\"Instalações sanitárias e vestiários estruturados\", \"Refeitório com capacidade adequada\"]\n        },\n        {\n            \"numero\": \"5\",\n            \"titulo\": \"FUNÇÕES E EPIs OBRIGATÓRIOS\",\n            \"conteudo\": \"EPIs por função de trabalho:\",\n            \"itens\": [\"Pedreiro: capacete, óculos, luvas, botina com biqueira\", \"Eletricista: capacete, óculos, luvas isolantes, calçado isolante\", \"Todas as funções na obra: {$funcoes}\"]\n        }\n    ]\n}\n\nREGRA CRÍTICA: Todos os itens devem ser STRINGS simples.";
    }
    
    private function buildAPRPrompt(array $data, string $context): string {
        $epis = implode(', ', $data['epis'] ?? []);
        $dataFormatada = date('d/m/Y', strtotime($data['data']));
        return "$context\n\nGere uma APR (Análise Preliminar de Risco) COMPLETA e PROFISSIONAL para:\n\nDADOS DA ATIVIDADE:\n- Atividade: {$data['atividade']}\n- Local: {$data['local']}\n- Responsável: {$data['responsavel']}\n- Data: {$dataFormatada}\n- Tipo de Serviço: {$data['tipo_servico']}\n- Nível de Risco Estimado: {$data['nivel_risco']}\n- Riscos Descritos: {$data['riscos']}\n- Medidas Sugeridas: {$data['medidas']}\n- EPIs: {$epis}\n\nIMPORTANTE: Gere conteúdo REAL e DETALHADO. Todos os itens devem ser STRINGS, não objetos.\n\nResponda APENAS com o JSON (sem markdown):\n{\n    \"titulo\": \"ANÁLISE PRELIMINAR DE RISCO - APR\",\n    \"subtitulo\": \"{$data['atividade']} - {$data['local']}\",\n    \"secoes\": [\n        {\n            \"numero\": \"1\",\n            \"titulo\": \"IDENTIFICAÇÃO DA ATIVIDADE\",\n            \"conteudo\": \"Atividade: {$data['atividade']}. Local: {$data['local']}. Data: {$dataFormatada}. Tipo: {$data['tipo_servico']}. Responsável: {$data['responsavel']}.\",\n            \"itens\": []\n        },\n        {\n            \"numero\": \"2\",\n            \"titulo\": \"DESCRIÇÃO DA ATIVIDADE\",\n            \"conteudo\": \"Descreva detalhadamente o passo a passo da atividade, incluindo preparação, execução e finalização.\",\n            \"itens\": [\"Etapa 1: Preparação do local e isolamento da área\", \"Etapa 2: Verificação de condições de segurança\", \"Etapa 3: Execução da atividade principal\"]\n        },\n        {\n            \"numero\": \"3\",\n            \"titulo\": \"RISCOS IDENTIFICADOS E MEDIDAS DE CONTROLE\",\n            \"conteudo\": \"Análise dos riscos presentes na atividade:\",\n            \"itens\": [\"RISCO: Queda de altura - CAUSA: Trabalho em nível elevado - CONSEQUÊNCIA: Lesões graves - MEDIDA: Uso de cinto e linha de vida\", \"Adicione os riscos fornecidos: {$data['riscos']}\"]\n        },\n        {\n            \"numero\": \"4\",\n            \"titulo\": \"EQUIPAMENTOS DE PROTEÇÃO INDIVIDUAL\",\n            \"conteudo\": \"EPIs obrigatórios para execução da atividade:\",\n            \"itens\": [\"Capacete de segurança com jugular\", \"Óculos de proteção contra impactos\", \"Inclua: {$epis}\"]\n        }\n    ],\n    \"nivel_risco\": \"{$data['nivel_risco']}\"\n}\n\nREGRA: Todos os itens devem ser STRINGS simples.";
    }
    
    private function buildTermoEPIPrompt(array $data, string $context): string {
        return "$context\n\nGere um Termo de Responsabilidade de EPI PROFISSIONAL conforme NR-06 para:\n\nDADOS:\n- Funcionário: {$data['funcionario']}\n- CPF: {$data['cpf']}\n- Tipo de EPI: {$data['tipo_epi']}\n- Número do CA: {$data['ca']}\n- Data de Entrega: {$data['data_entrega']}\n- Data de Validade: {$data['validade']}\n- Observações: {$data['observacoes']}\n\nIMPORTANTE: Todas as obrigações devem ser STRINGS simples, não objetos.\n\nResponda APENAS com o JSON (sem markdown):\n{\n    \"titulo\": \"TERMO DE RESPONSABILIDADE DE EPI\",\n    \"subtitulo\": \"Conforme NR-06\",\n    \"termo_declaracao\": \"Eu, {$data['funcionario']}, portador(a) do CPF {$data['cpf']}, declaro ter recebido o Equipamento de Proteção Individual (EPI) especificado neste documento, comprometendo-me a utilizá-lo exclusivamente para a finalidade a que se destina...\",\n    \"obrigacoes_empregado\": [\n        \"Utilizar o EPI apenas para a finalidade a que se destina\",\n        \"Responsabilizar-se pela guarda e conservação do equipamento\",\n        \"Comunicar ao empregador qualquer alteração que torne o EPI impróprio para uso\"\n    ],\n    \"obrigacoes_empregador\": [\n        \"Adquirir o EPI adequado ao risco de cada atividade\",\n        \"Exigir o uso correto do equipamento\",\n        \"Fornecer ao trabalhador somente EPI aprovado pelo MTE com CA válido\"\n    ],\n    \"observacoes\": \"O não cumprimento das obrigações acima poderá acarretar em advertência, suspensão ou demissão por justa causa, conforme artigo 158 da CLT e NR-06.\"\n}";
    }
    
    private function buildCertificadoPrompt(array $data, string $context): string {
        preg_match('/NR-(\d+)/', $data['treinamento'], $matches);
        $nr = isset($matches[0]) ? $matches[0] : '';
        return "$context\n\nGere o conteúdo para um Certificado de Treinamento PROFISSIONAL:\n\nDADOS:\n- Funcionário: {$data['funcionario']}\n- CPF: {$data['cpf']}\n- Treinamento: {$data['treinamento']}\n- Carga Horária: {$data['carga_horaria']} horas\n- Data de Realização: {$data['data_realizacao']}\n- Validade: {$data['validade']}\n- Instrutor/Empresa: {$data['instrutor']}\n\nIMPORTANTE: O conteúdo programático deve ser uma lista de STRINGS simples.\n\nResponda APENAS com o JSON (sem markdown):\n{\n    \"titulo\": \"CERTIFICADO DE CONCLUSÃO DE TREINAMENTO\",\n    \"treinamento\": \"{$data['treinamento']}\",\n    \"nr_referencia\": \"$nr\",\n    \"texto_certificacao\": \"Certificamos que o(a) profissional acima qualificado(a) concluiu com aproveitamento satisfatório o treinamento especificado.\",\n    \"conteudo_programatico\": [\n        \"Conceitos e definições básicas\",\n        \"Legislação aplicável e responsabilidades\",\n        \"Identificação e análise de riscos\",\n        \"Práticas seguras de trabalho\"\n    ],\n    \"carga_horaria\": \"{$data['carga_horaria']} horas\",\n    \"metodologia\": \"Teórico e Prático\",\n    \"observacoes_legais\": \"Este certificado atende aos requisitos legais estabelecidos na legislação trabalhista vigente.\"\n}";
    }
    
    private function callOpenAI(string $prompt): array {
        $apiKey = OPENAI_API_KEY;
        if (empty($apiKey) || $apiKey === 'sua_chave_openai_aqui') {
            throw new Exception("API Key da OpenAI não configurada. Configure no arquivo .env");
        }
        
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => OPENAI_MODEL,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Você é um assistente especializado em Segurança do Trabalho. Sempre responda em JSON válido conforme solicitado.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 4000,
            'response_format' => ['type' => 'json_object']
        ];
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 120
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Erro de conexão com OpenAI: $error");
        }
        
        $result = json_decode($response, true);
        if ($httpCode !== 200) {
            $errorMsg = $result['error']['message'] ?? 'Erro desconhecido';
            throw new Exception("Erro na API OpenAI ($httpCode): $errorMsg");
        }
        
        $content = $result['choices'][0]['message']['content'] ?? '';
        return json_decode($content, true);
    }
    
    private function callGemini(string $prompt): array {
        $apiKey = GEMINI_API_KEY;
        if (empty($apiKey) || $apiKey === 'sua_chave_gemini_aqui') {
            throw new Exception("API Key do Gemini não configurada. Configure no arquivo .env");
        }
        
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent?key=' . $apiKey;
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt . "\n\nIMPORTANTE: Responda APENAS com o JSON solicitado, sem texto adicional."]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 4000
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 120
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Erro de conexão com Gemini: $error");
        }
        
        $result = json_decode($response, true);
        if ($httpCode !== 200) {
            $errorMsg = $result['error']['message'] ?? 'Erro desconhecido';
            throw new Exception("Erro na API Gemini ($httpCode): $errorMsg");
        }
        
        $content = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*/', '', $content);
        $content = trim($content);
        
        $parsed = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Erro ao parsear resposta da IA: " . json_last_error_msg());
        }
        
        return $parsed;
    }
}

// Roteamento da API
try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method !== 'POST') {
        throw new Exception("Método não permitido. Use POST.");
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if ($action !== 'test' && !$input) {
        throw new Exception("Dados de entrada inválidos.");
    }
    
    $generator = new AIDocumentGenerator();
    $result = null;
    
    switch ($action) {
        case 'generate-pgr':
            $result = $generator->generate('PGR', $input);
            break;
            
        case 'generate-pcmat':
            $result = $generator->generate('PCMAT', $input);
            break;
            
        case 'generate-apr':
            $result = $generator->generate('APR', $input);
            break;
            
        case 'generate-termo-epi':
            $result = $generator->generate('TERMO_EPI', $input);
            break;
            
        case 'generate-certificado':
            $result = $generator->generate('CERTIFICADO', $input);
            break;
            
        case 'test':
            $result = [
                'status' => 'ok',
                'provider' => AI_PROVIDER,
                'message' => 'API do SafeWork Pro funcionando corretamente'
            ];
            break;
            
        default:
            throw new Exception("Ação não reconhecida: $action");
    }
    
    // Se a chamada gerou documento com sucesso, deduzir créditos do usuário
    if ($action !== 'test' && $user !== null) {
        $uid = (int) $user['id'];
        $pdo = Database::get();
        
        // Deduz 1 crédito
        $pdo->prepare('UPDATE users SET credits = credits - 1, updated_at = datetime("now") WHERE id = ?')->execute([$uid]);
        
        // Registra transação
        $pdo->prepare('
            INSERT INTO transactions (user_id, mp_payment_id, package_label, amount_brl, credits_added, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ')->execute([$uid, 'SWP-' . time(), 'Consumo SafeWork Pro', 0.0, -1, 'approved']);
        
        // Retorna o resultado com sucesso e o saldo atualizado
        echo json_encode([
            'success' => true,
            'data' => $result,
            'credits_remaining' => $credits - 1
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        // Apenas para o endpoint de teste
        echo json_encode([
            'success' => true,
            'data' => $result
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
