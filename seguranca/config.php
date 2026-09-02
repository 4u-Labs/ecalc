<?php
/**
 * Carrega variáveis de ambiente do arquivo .env
 */

function loadEnv($path = '.env') {
    if (!file_exists($path)) {
        throw new Exception("Arquivo .env não encontrado!");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Ignora comentários
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse da linha
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Remove aspas se houver
            $value = trim($value, '"\'');
            
            // Define a variável de ambiente
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

// Carrega as variáveis
loadEnv();

// Configurações da aplicação
if (!defined('AI_PROVIDER')) {
    define('AI_PROVIDER', getenv('AI_PROVIDER') ?: 'openai');
}
if (!defined('OPENAI_API_KEY')) {
    define('OPENAI_API_KEY', getenv('OPENAI_API_KEY'));
}
if (!defined('OPENAI_MODEL')) {
    define('OPENAI_MODEL', getenv('OPENAI_MODEL') ?: 'gpt-4o-mini');
}
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', getenv('GEMINI_API_KEY'));
}
if (!defined('GEMINI_MODEL')) {
    define('GEMINI_MODEL', getenv('GEMINI_MODEL') ?: 'gemini-1.5-flash');
}
