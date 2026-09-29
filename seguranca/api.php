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
        return "$context\n\n" .
        "Você é um Engenheiro de Segurança do Trabalho Sênior, com profundo domínio da Norma Regulamentadora NR-01 (Gerenciamento de Riscos Ocupacionais - GRO / PGR) e normas técnicas brasileiras.\n" .
        "Gere um PGR (Programa de Gerenciamento de Riscos) COMPLETO, EXTENSO, TÉCNICO E DE NÍVEL PROFISSIONAL EXECUTIVO para aprovação em auditorias e fiscalizações do Ministério do Trabalho.\n\n" .
        "DADOS DA EMPRESA E ESTABELECIMENTO:\n" .
        "- Razão Social: {$data['empresa']}\n" .
        "- CNPJ: {$data['cnpj']}\n" .
        "- Endereço: {$data['endereco']}\n" .
        "- Ramo de Atividade: {$data['ramo']}\n" .
        "- Efetivo de Funcionários: {$data['funcionarios']}\n" .
        "- Responsável Técnico: {$data['responsavel']}\n" .
        "- Registro CREA/Registro Profissional: {$data['crea']}\n" .
        "- Riscos Prioritários Identificados: {$riscos}\n" .
        "- Informações e Plano de Ação Específico: {$data['plano_acao']}\n\n" .
        "ESTRUTURA TÉCNICA OBRIGATÓRIA (Elabore todas as 12 seções com alto nível de detalhamento técnico):\n" .
        "1. IDENTIFICAÇÃO DA EMPRESA E DO ESTABELECIMENTO (Razão Social, CNPJ, CNAE, Grau de Risco conforme NR-04, Endereço, Efetivo, Turnos de Trabalho e Abrangência).\n" .
        "2. RESPONSABILIDADE TÉCNICA E GESTÃO DO GRO (Engenheiro Responsável Técnico, Registro CREA, Emissão de ART de SST, Atribuições do SESMT e CIPA/Designado NR-05).\n" .
        "3. OBJETIVOS E DIRETRIZES DA NR-01 (Compromisso com melhoria contínua, prevenção ativa de acidentes e doenças ocupacionais, e alinhamento à ISO 45001).\n" .
        "4. MAPEAMENTO DE PROCESSOS E GRUPOS HOMOGÊNEOS DE EXPOSIÇÃO (GHE) (Definição dos postos de trabalho, tarefas executadas, máquinas operadas e número de trabalhadores expostos por GHE).\n" .
        "5. METODOLOGIA DE AVALIAÇÃO DE RISCOS (Critérios de Severidade x Probabilidade em Matriz 5x5, Níveis de Risco Ocupacional: Baixo, Médio, Alto e Crítico conforme NR-01.5.4).\n" .
        "6. INVENTÁRIO DETALHADO DE RISCOS OCUPACIONAIS (Identificação de Perigos e Fontes Geradoras: Físicos como ruído, vibrações, calor; Químicos como poeiras, fumos e vapores; Biológicos; Ergonômicos como esforço físico e postura; Mecânicos e Acidentes como trabalho em altura, máquinas rotativas e choques elétricos).\n" .
        "7. MEDIDAS DE PREVENÇÃO E HIERARQUIA DE CONTROLES (Medidas de Eliminação do Perigo, Substituição, Proteções Coletivas EPCs, Controles Administrativos/Procedimentais e Equipamentos de Proteção Individual EPIs).\n" .
        "8. PLANO DE AÇÃO ESTRUTURADO NO MODELO 5W2H (Detalhamento de ações preventivas: O que fazer, Por que fazer, Quem é o responsável, Onde aplicar, Quando concluir e Como executar com metas e prazos).\n" .
        "9. MONITORAMENTO DAS EXPOSIÇÕES E INTERFACE COM O PCMSO (NR-07) (Critérios de medição quantitativa/qualitativa, exames admissionais, periódicos, de retorno e demissionais baseados no inventário).\n" .
        "10. PROGRAMA DE TREINAMENTOS E CAPACITAÇÃO EM SST (Treinamento de integração NR-01, treinamentos específicos conforme as NRs aplicáveis, DDS diário e registro formal de capacitação).\n" .
        "11. PLANO DE ATENDIMENTO A EMERGÊNCIAS (PAE) E REGISTRO DE ACIDENTES (Procedimentos para sinistros, rotas de fuga, kit de primeiros socorros, investigação de quase-acidentes e emissão de CAT).\n" .
        "12. DISPOSIÇÕES FINAIS, INDICADORES E REVISÃO BIENAL (Critérios de reavaliação a cada 2 anos ou em modificações de processos, guarda dos registros por 20 anos, e assinaturas formais).\n\n" .
        "Responda APENAS com o JSON estruturado abaixo (sem markdown, sem texto fora):\n" .
        "{\n" .
        "    \"titulo\": \"PROGRAMA DE GERENCIAMENTO DE RISCOS - PGR\",\n" .
        "    \"subtitulo\": \"Conforme Norma Regulamentadora NR-01 - {$data['empresa']}\",\n" .
        "    \"validade\": \"Validade: 2 anos a contar da data de emissão (Revisão periódica obrigatória)\",\n" .
        "    \"secoes\": [\n" .
        "        {\n" .
        "            \"numero\": \"1\",\n" .
        "            \"titulo\": \"IDENTIFICAÇÃO DA EMPRESA E DO ESTABELECIMENTO\",\n" .
        "            \"conteudo\": \"texto detalhado...\",\n" .
        "            \"itens\": [\"Item 1...\", \"Item 2...\"]\n" .
        "        }\n" .
        "    ]\n" .
        "}";
    }
    
    private function buildPCMATPrompt(array $data, string $context): string {
        $funcoes = implode(', ', $data['funcoes'] ?? []);
        return "$context\n\n" .
        "Você é um Engenheiro de Segurança do Trabalho e Engenheiro Civil Sênior no Brasil, especialista em NR-18 (Condições de Segurança e Saúde no Trabalho na Indústria da Construção) e NR-01.\n" .
        "Gere um PCMAT (Programa de Condições e Meio Ambiente de Trabalho) EXTREMAMENTE DETALHADO, TÉCNICO, COMPLETO E APROFUNDADO para fiscalização de canteiro de obras pelo Ministério do Trabalho.\n\n" .
        "DADOS DA OBRA:\n" .
        "- Nome da Obra: {$data['obra']}\n" .
        "- Contratante / Empresa: {$data['contratante']}\n" .
        "- Endereço da Obra: {$data['endereco']}\n" .
        "- Tipo de Obra: {$data['tipo_obra']}\n" .
        "- Efetivo de Trabalhadores: {$data['trabalhadores']} trabalhadores\n" .
        "- Período Previsto: {$data['data_inicio']} a {$data['data_fim']}\n" .
        "- Engenheiro Responsável: {$data['engenheiro']}\n" .
        "- Registro Profissional (CREA/CAU): {$data['crea']}\n" .
        "- Funções no Canteiro: {$funcoes}\n" .
        "- Especificações/Medidas Informadas: {$data['medidas']}\n\n" .
        "ESTRUTURA TÉCNICA OBRIGATÓRIA (Desenvolva detalhadamente todas as 12 seções técnicas sem simplificações):\n" .
        "1. IDENTIFICAÇÃO DO EMPREENDIMENTO E DADOS CADASTRAIS (Obra, Contratante, Endereço, CNAE 41.20-4, Grau de Risco 3/4 conforme NR-04, Área Construída estimada, Efetivo total, Turnos e Prazos).\n" .
        "2. RESPONSABILIDADE TÉCNICA E GESTÃO DE SST (Engenheiro Responsável, CREA, emissão de ART de Segurança, atribuições da CIPA/CIPAMIN e do SESMT na obra).\n" .
        "3. OBJETIVOS E DIRETRIZES LEGAIS (Fundamentação na Portaria SEPRT nº 3.733/2020 e NR-18 vigente, NR-01 GRO, metas de taxa de gravidade/frequência de acidentes).\n" .
        "4. CRONOGRAMA E FASES CONSTRUTIVAS DA OBRA (Detalhamento técnico de todas as fases: 1. Demolição e Terraplenagem; 2. Fundações e Contenções; 3. Estrutura de Concreto/Aço; 4. Alvenarias e Vedações; 5. Instalações Elétricas e Hidrossanitárias; 6. Revestimentos e Acabamentos; 7. Fachadas e Trabalho em Altura; 8. Desmobilização e Limpeza Final).\n" .
        "5. DIMENSIONAMENTO TÉCNICO DAS ÁREAS DE VIVÊNCIA (NR-18.4) (Cálculo quantitativo exato: instalações sanitárias com proporção de 1 bacia e 1 lavatório para cada 20 trabalhadores ou fração, 1 chuveiro com água quente para cada 10 trabalhadores; vestiários com armários individuais de dois compartimentos; refeitório com assentos para 100% dos trabalhadores no intervalo, bebedouro de água potável refrigerada a cada 25 trabalhadores; ambulatório/caixa de primeiros socorros).\n" .
        "6. INVENTÁRIO DE RISCOS OCUPACIONAIS NA CONSTRUÇÃO CIVIL (Análise por Grupo de Risco: Riscos Físicos como ruído contínuo/intermitente de marteletes/serras com limite 85dB(A), vibrações mão-braço, radiação solar UV; Riscos Químicos como poeiras de sílica livre cristalina no corte de alvenarias, dermatoses por cimento e cal cáusticos, fumos de solda; Riscos Ergonômicos no manuseio de sacos e blocos; Riscos de Acidentes como queda com diferença de nível >2m, choque elétrico em quadros provisórios, soterramento em valas, quedas de objetos e ferramentas de lajes).\n" .
        "7. MEDIDAS DE PROTEÇÃO COLETIVA (EPCs OBRIGATÓRIOS) (Sistemas de guarda-corpo e rodapé com 1,20m/0,70m/0,20m nas periferias e vãos; plataformas de proteção com bandeja principal na 1ª laje de 2,50m + 0,80m a 45º e bandejas secundárias a cada 3 lajes de 1,40m + 0,80m; telas fachadeiras 100%; fechamento de poços de elevador e aberturas de piso; linhas de vida em cabo de aço 8mm certificados para ancoragem; quadros elétricos blindados com disjuntor DR 30mA e aterramento SPDA).\n" .
        "8. EQUIPAMENTOS DE PROTEÇÃO INDIVIDUAL (EPIs) POR FUNÇÃO (Lista técnica com especificação de CA para todas as funções: Pedreiro, Servente, Eletricista, Carpinteiro, Armador, Pintor, Encanador, Operador de Máquinas, Engenheiro/Visitantes; exigência de capacete com jugular, botinas com biqueira e palmilha anti-perfuração, óculos de proteção UV, protetor auricular NRRsf, cinto paraquedista com duplo talabarte e absorvedor de energia NR-35, luvas adequadas a cada risco, máscara PFF2).\n" .
        "9. PROGRAMA DE CAPACITAÇÃO E TREINAMENTOS OBRIGATÓRIOS (Treinamento Admissional NR-18 de 4h antes de iniciar no canteiro; NR-35 Trabalho em Altura de 8h bienal; NR-10 Segurança em Eletricidade de 40h; NR-12 para operadores de betoneira e serra circular; Diálogo Diário de Segurança - DDS de 10 min matinal).\n" .
        "10. PLANO DE RESPOSTA A EMERGÊNCIAS, RESGATE EM ALTURA E PRIMEIROS SOCORROS (Fluxograma de emergência, procedimentos de resgate em altura em até 15 minutos para evitar trauma de suspensão inerte, kit de primeiros socorros, telefones de socorro SAMU 192, Bombeiros 193 e hospital de referência, emissão de CAT).\n" .
        "11. CRONOGRAMA FÍSICO DE IMPLEMENTAÇÃO E AUDITORIAS DE SST (Rotina diária de verificação de andaimes e ferramentas, semanal de proteções coletivas e mensal de extintores e instalações elétricas).\n" .
        "12. DISPOSIÇÕES FINAIS E TERMO DE ENCERRAMENTO (Obrigatoriedade de manutenção no canteiro, guarda do histórico por 20 anos, campo de assinatura do Responsável Técnico com CREA e do Representante Legal do Contratante).\n\n" .
        "IMPORTANTE: Todos os itens dentro de cada array \"itens\" devem ser STRINGS simples e detalhadas.\n" .
        "Responda APENAS com o JSON estruturado abaixo (sem markdown, sem texto fora das chaves):\n" .
        "{\n" .
        "    \"titulo\": \"PCMAT - PROGRAMA DE CONDIÇÕES E MEIO AMBIENTE DE TRABALHO\",\n" .
        "    \"subtitulo\": \"Conforme Norma Regulamentadora NR-18 - {$data['obra']}\",\n" .
        "    \"validade\": \"Vigência: Durante toda a execução da obra ({$data['data_inicio']} a {$data['data_fim']})\",\n" .
        "    \"secoes\": [\n" .
        "        {\n" .
        "            \"numero\": \"1\",\n" .
        "            \"titulo\": \"IDENTIFICAÇÃO DO EMPREENDIMENTO E DADOS CADASTRAIS\",\n" .
        "            \"conteudo\": \"texto detalhado...\",\n" .
        "            \"itens\": [\"Item 1...\", \"Item 2...\"]\n" .
        "        }\n" .
        "    ]\n" .
        "}";
    }
    
    private function buildAPRPrompt(array $data, string $context): string {
        $epis = implode(', ', $data['epis'] ?? []);
        $dataFormatada = date('d/m/Y', strtotime($data['data']));
        return "$context\n\n" .
        "Você é um Especialista em Gestão de Riscos Ocupacionais e Engenheiro de Segurança do Trabalho no Brasil.\n" .
        "Gere uma APR (Análise Preliminar de Risco) COMPLETA, DETALHADA E PROFISSIONAL para a atividade informada:\n\n" .
        "DADOS DA ATIVIDADE:\n" .
        "- Atividade: {$data['atividade']}\n" .
        "- Local de Execução: {$data['local']}\n" .
        "- Responsável Técnico / Supervisor: {$data['responsavel']}\n" .
        "- Data da Análise: {$dataFormatada}\n" .
        "- Tipo de Serviço: {$data['tipo_servico']}\n" .
        "- Nível de Risco Inicial Estimado: {$data['nivel_risco']}\n" .
        "- Riscos Específicos Descritos: {$data['riscos']}\n" .
        "- Medidas Preventivas Sugeridas: {$data['medidas']}\n" .
        "- EPIs Selecionados: {$epis}\n\n" .
        "ESTRUTURA TÉCNICA OBRIGATÓRIA (Elabore todas as 10 seções detalhadamente):\n" .
        "1. IDENTIFICAÇÃO DA ATIVIDADE, LOCAL E RESPONSABILIDADE (Atividade, Local, Data, Supervisor, Equipe envolvida e Classificação do Serviço).\n" .
        "2. ESCOPO, CONDIÇÕES AMBIENTAIS E PRÉ-REQUISITOS (Condições climáticas aceitáveis, velocidade máxima de vento para trabalho em altura, iluminação e isolamento prévio).\n" .
        "3. PASSO A PASSO SEQUENCIAL DA TAREFA (Detalhamento das etapas operacionais: Planejamento/Inspeção prévia, Transporte de materiais, Preparação do local, Execução da tarefa principal, Desmobilização e Limpeza).\n" .
        "4. ANÁLISE DE PERIGOS, CAUSAS E DANOS POTENCIAIS (Mapeamento de quedas, choques, prensamentos, cortes, projeção de estilhaços, inalação de poeiras/vapores e esforços ergonômicos).\n" .
        "5. AVALIAÇÃO DE RISCO INICIAL (Matriz de Severidade x Probabilidade com classificação do risco sem medidas de controle adicionais).\n" .
        "6. MEDIDAS DE CONTROLE E PROTEÇÃO COLETIVA (EPCs) (Isolamento com fita zebrada e cones, linha de vida, aterramento temporário, telas, ventilação e proteções de máquinas).\n" .
        "7. EQUIPAMENTOS DE PROTEÇÃO INDIVIDUAL (EPIs) OBRIGATÓRIOS (Especificação técnica completa com exigência de C.A. válido conforme NR-06).\n" .
        "8. REQUISITOS PARA PERMISSÃO DE TRABALHO (PT) (Condições para liberação da PT para trabalho a quente, em altura NR-35 ou espaço confinado NR-33).\n" .
        "9. AVALIAÇÃO DO RISCO RESIDUAL E CRITÉRIOS DE INTERRUPÇÃO (Garantia do Direito de Recusa do Trabalhador em caso de risco iminente).\n" .
        "10. PLANO DE EMERGÊNCIA, RESGATE E ASSINATURAS DA EQUIPE (Primeiros socorros, canais de comunicação com SAMU 192/Bombeiros 193 e campo de assinatura de toda a equipe executora).\n\n" .
        "Responda APENAS com o JSON estruturado (sem markdown, sem texto fora):\n" .
        "{\n" .
        "    \"titulo\": \"ANÁLISE PRELIMINAR DE RISCO - APR\",\n" .
        "    \"subtitulo\": \"{$data['atividade']} - {$data['local']}\",\n" .
        "    \"nivel_risco\": \"{$data['nivel_risco']}\",\n" .
        "    \"secoes\": [\n" .
        "        {\n" .
        "            \"numero\": \"1\",\n" .
        "            \"titulo\": \"IDENTIFICAÇÃO DA ATIVIDADE, LOCAL E RESPONSABILIDADE\",\n" .
        "            \"conteudo\": \"texto detalhado...\",\n" .
        "            \"itens\": [\"Item 1...\", \"Item 2...\"]\n" .
        "        }\n" .
        "    ]\n" .
        "}";
    }
    
    private function buildTermoEPIPrompt(array $data, string $context): string {
        return "$context\n\n" .
        "Gere um Termo de Responsabilidade e Guarda de Equipamento de Proteção Individual (EPI) COMPLETO, FORMAL E COM EMBASAMENTO JURÍDICO conforme CLT (Art. 158 e 166) e NR-06:\n\n" .
        "DADOS:\n" .
        "- Funcionário: {$data['funcionario']}\n" .
        "- CPF: {$data['cpf']}\n" .
        "- Tipo de EPI: {$data['tipo_epi']}\n" .
        "- Certificado de Aprovação (CA): {$data['ca']}\n" .
        "- Data de Entrega: {$data['data_entrega']}\n" .
        "- Validade Estimada: {$data['validade']}\n" .
        "- Observações Adicionais: {$data['observacoes']}\n\n" .
        "Responda APENAS com o JSON (sem markdown):\n" .
        "{\n" .
        "    \"titulo\": \"TERMO DE RESPONSABILIDADE E ENTREGA DE EPI\",\n" .
        "    \"subtitulo\": \"Conforme Norma Regulamentadora NR-06 e Artigos 158 e 166 da CLT\",\n" .
        "    \"termo_declaracao\": \"Eu, {$data['funcionario']}, inscrito(a) no CPF sob o nº {$data['cpf']}, declaro formalmente ter recebido da empresa empregadora o Equipamento de Proteção Individual (EPI) especificado neste termo, em perfeito estado de conservação e funcionamento, acompanhado de Certificado de Aprovação (CA nº {$data['ca']}) expedido pelo órgão competente, tendo recebido orientação e treinamento adequado sobre seu uso correto, higienização, guarda e conservação.\",\n" .
        "    \"obrigacoes_empregado\": [\n" .
        "        \"Usar o EPI fornecido exclusivamente para a finalidade a que se destina durante toda a jornada de trabalho\",\n" .
        "        \"Responsabilizar-se integralmente pela guarda, limpeza e conservação do equipamento sob sua custódia\",\n" .
        "        \"Comunicar imediatamente ao empregador ou SESMT qualquer alteração, desgaste ou dano que o torne impróprio para uso\",\n" .
        "        \"Cumprir rigorosamente as determinações do empregador sobre o uso adequado, ciente de que a recusa injustificada constitui ato faltoso passível de sanções disciplinares conforme Art. 158 da CLT\"\n" .
        "    ],\n" .
        "    \"obrigacoes_empregador\": [\n" .
        "        \"Adquirir e fornecer gratuitamente ao trabalhador o EPI adequado ao risco em perfeito estado de conservação\",\n" .
        "        \"Exigir seu uso contínuo e fiscalizar a correta utilização nas frentes de trabalho\",\n" .
        "        \"Fornecer ao trabalhador somente EPI aprovado pelo órgão nacional competente em SST com CA válido\",\n" .
        "        \"Substituir imediatamente o EPI quando danificado ou extraviado e promover treinamento sobre uso correto\"\n" .
        "    ],\n" .
        "    \"observacoes\": \"O descumprimento das normas aqui estipuladas sujeitará o empregado às sanções disciplinares previstas no artigo 482 da CLT, incluindo advertência por escrito, suspensão disciplinar e demissão por justa causa. O empregado autoriza o desconto em folha em caso de extravio ou dano doloso conforme CLT Art. 462 § 1º.\"\n" .
        "}";
    }
    
    private function buildCertificadoPrompt(array $data, string $context): string {
        preg_match('/NR-(\d+)/', $data['treinamento'], $matches);
        $nr = isset($matches[0]) ? $matches[0] : '';
        return "$context\n\n" .
        "Gere o conteúdo oficial para um Certificado de Conclusão de Treinamento de Segurança do Trabalho em estrita conformidade com a NR-01 (Anexo II) e a respectiva NR do curso:\n\n" .
        "DADOS:\n" .
        "- Funcionário / Participante: {$data['funcionario']}\n" .
        "- CPF: {$data['cpf']}\n" .
        "- Treinamento Realizado: {$data['treinamento']}\n" .
        "- Carga Horária: {$data['carga_horaria']} horas\n" .
        "- Data de Realização: {$data['data_realizacao']}\n" .
        "- Data de Validade: {$data['validade']}\n" .
        "- Instrutor e Entidade: {$data['instrutor']}\n\n" .
        "Responda APENAS com o JSON (sem markdown):\n" .
        "{\n" .
        "    \"titulo\": \"CERTIFICADO DE CAPACITAÇÃO PROFISSIONAL EM SEGURANÇA DO TRABALHO\",\n" .
        "    \"treinamento\": \"{$data['treinamento']}\",\n" .
        "    \"nr_referencia\": \"$nr\",\n" .
        "    \"texto_certificacao\": \"Certificamos que o(a) profissional {$data['funcionario']}, portador(a) do CPF {$data['cpf']}, concluiu com aproveitamento satisfatório o treinamento de {$data['treinamento']}, cumprindo integralmente o conteúdo programático teórico e prático e as diretrizes pedagógicas da Norma Regulamentadora NR-01.\",\n" .
        "    \"conteudo_programatico\": [\n" .
        "        \"Disposições legais e diretrizes normativas das NRs aplicáveis\",\n" .
        "        \"Identificação de perigos, fontes geradoras e avaliação de riscos ocupacionais\",\n" .
        "        \"Medidas de prevenção coletiva (EPC) e equipamentos de proteção individual (EPI)\",\n" .
        "        \"Procedimentos operacionais seguros e práticas de trabalho em canteiro\",\n" .
        "        \"Plano de emergência, procedimentos de evacuação e noções de primeiros socorros\"\n" .
        "    ],\n" .
        "    \"carga_horaria\": \"{$data['carga_horaria']} horas\",\n" .
        "    \"metodologia\": \"Presencial com aulas teóricas e práticas simuladas\",\n" .
        "    \"observacoes_legais\": \"Certificado emitido em conformidade com a Portaria SEPRT nº 6.730/2020 e NR-01 da Secretaria Especial de Previdência e Trabalho do Ministério da Economia. Válido em todo o território nacional.\"\n" .
        "}";
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
                    'content' => 'Você é um assistente sênior e perito em Engenharia de Segurança do Trabalho e Construção Civil. Responda SEMPRE em JSON válido estruturado conforme solicitado, com rico detalhamento normativo.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.5,
            'max_tokens' => 4096,
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
                        ['text' => $prompt . "\n\nIMPORTANTE: Responda APENAS com o JSON solicitado, sem blocos de código nem texto adicional."]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.5,
                'maxOutputTokens' => 8192
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
