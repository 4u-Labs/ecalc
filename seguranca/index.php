<?php
session_start();
if (empty($_SESSION['api_token'])) {
    $_SESSION['api_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeWork Pro - Sistema de Segurança do Trabalho com IA</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .bg-slate-900/50 backdrop-blur-xl border-r border-white/10 { background: linear-gradient(135deg, #1e3a5f 0%, #0f2744 100%); }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.15); }
        .sidebar-item { transition: all 0.2s ease; }
        .sidebar-item:hover { background: rgba(255,255,255,0.1); }
        .sidebar-item.active { background: rgba(251,191,36,0.2); border-left: 4px solid #fbbf24; }
        .pulse-dot { animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        input:focus, select:focus, textarea:focus { outline: none; ring: 2px; ring-color: #fbbf24; }
        .risk-low { background: #22c55e; }
        .risk-medium { background: #f59e0b; }
        .risk-high { background: #ef4444; }
        .ai-badge { 
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            animation: glow 2s ease-in-out infinite alternate;
        }
        @keyframes glow {
            from { box-shadow: 0 0 5px #8b5cf6; }
            to { box-shadow: 0 0 20px #8b5cf6; }
        }
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #8b5cf6;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .generating-overlay {
            backdrop-filter: blur(5px);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-300 min-h-screen">
    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-black/80 backdrop-blur-sm generating-overlay hidden items-center justify-center z-[100]">
        <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-8 max-w-md w-full mx-4 text-center">
            <div class="flex justify-center mb-4">
                <div class="loading-spinner w-16 h-16 border-4"></div>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">🤖 IA Gerando Documento</h3>
            <p class="text-slate-400" id="loading-message">Analisando dados e criando conteúdo profissional...</p>
            <div class="mt-4 bg-slate-800/50 rounded-lg p-3">
                <p class="text-sm text-slate-400">Isso pode levar alguns segundos</p>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-slate-900/50 backdrop-blur-xl border-r border-white/10 text-white z-50 transform transition-transform duration-300">
        <div class="p-6 border-b border-white/10 border-white/10">
            <a href="#" id="logo-link" class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-400 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">🛡️</span>
                </div>
                <div>
                    <h1 class="font-bold text-lg">SafeWork Pro</h1>
                    <div class="flex items-center gap-1">
                        <span class="ai-badge text-xs px-2 py-0.5 rounded-full text-white">IA</span>
                        <p class="text-xs text-gray-300">Powered by AI</p>
                    </div>
                </div>
            </a>
        </div>
        
        <nav class="p-4 space-y-2">
            <button onclick="showSection('dashboard')" class="sidebar-item active w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left" data-section="dashboard">
                <span class="text-xl">📊</span>
                <span>Dashboard</span>
            </button>
            <button onclick="showSection('pgr')" class="sidebar-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left" data-section="pgr">
                <span class="text-xl">🦺</span>
                <span>Gerador de PGR</span>
            </button>
            <button onclick="showSection('pcmat')" class="sidebar-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left" data-section="pcmat">
                <span class="text-xl">🏗️</span>
                <span>Gerador de PCMAT</span>
            </button>
            <button onclick="showSection('apr')" class="sidebar-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left" data-section="apr">
                <span class="text-xl">⚠️</span>
                <span>APR Digital</span>
            </button>
            <button onclick="showSection('epi')" class="sidebar-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left" data-section="epi">
                <span class="text-xl">🧤</span>
                <span>Checklist de EPI</span>
            </button>
            <button onclick="showSection('treinamentos')" class="sidebar-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left" data-section="treinamentos">
                <span class="text-xl">🎓</span>
                <span>Treinamentos</span>
            </button>
        </nav>
        
        <!-- Badge de Créditos Unificados -->
        <div id="sidebar-credits-badge" class="hidden mx-4 my-2 p-3 bg-gradient-to-r from-purple-900/40 to-indigo-900/40 border border-purple-500/20 rounded-xl flex items-center justify-between cursor-pointer hover:border-purple-400/40 transition-all hover:shadow-[0_0_10px_rgba(139,92,246,0.15)]" onclick="openModal('pix-modal')">
            <div class="flex items-center gap-2">
                <span class="text-lg">💎</span>
                <div>
                    <p class="text-[10px] text-slate-400">Saldo de IA</p>
                    <p class="font-bold text-xs text-white" id="sidebar-credits-count">0 créditos</p>
                </div>
            </div>
            <span class="text-[10px] font-semibold text-purple-400 bg-purple-500/10 px-2 py-0.5 rounded-lg border border-purple-500/20">Recarregar</span>
        </div>
        
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white/10" id="sidebar-footer-container">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center">
                    <span class="text-sm">👷</span>
                </div>
                <div>
                    <p class="font-medium text-sm">Técnico de Segurança</p>
                    <p class="text-xs text-slate-500">Administrador</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 p-8">
        <!-- Dashboard Section -->
        <section id="dashboard-section" class="fade-in">
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-white">Dashboard</h2>
                <p class="text-slate-400">Visão geral do sistema de segurança com IA</p>
            </div>
            
            <!-- AI Status -->
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl p-6 mb-8 text-white" id="ai-status-banner">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-slate-900/40 border border-white/5/20 rounded-xl flex items-center justify-center">
                        <span class="text-4xl">🤖</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">Inteligência Artificial Ativa</h3>
                        <p class="text-purple-200">Documentos gerados automaticamente com análise inteligente</p>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-6 card-hover shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-amber-500/20 text-amber-400 rounded-xl flex items-center justify-center">
                            <span class="text-2xl">🦺</span>
                        </div>
                        <span class="text-green-500 text-sm font-medium">+12%</span>
                    </div>
                    <h3 class="text-2xl font-bold text-white" id="stat-pgr">0</h3>
                    <p class="text-slate-400 text-sm">PGRs Gerados</p>
                </div>
                
                <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-6 card-hover shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-cyan-500/20 text-cyan-400 rounded-xl flex items-center justify-center">
                            <span class="text-2xl">⚠️</span>
                        </div>
                        <span class="text-green-500 text-sm font-medium">+8%</span>
                    </div>
                    <h3 class="text-2xl font-bold text-white" id="stat-apr">0</h3>
                    <p class="text-slate-400 text-sm">APRs Ativas</p>
                </div>
                
                <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-6 card-hover shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-rose-500/20 text-rose-400 rounded-xl flex items-center justify-center">
                            <span class="text-2xl">🧤</span>
                        </div>
                        <span class="pulse-dot w-2 h-2 bg-red-500 rounded-full"></span>
                    </div>
                    <h3 class="text-2xl font-bold text-white" id="stat-epi-alert">0</h3>
                    <p class="text-slate-400 text-sm">EPIs Vencendo</p>
                </div>
                
                <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-6 card-hover shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-emerald-500/20 text-emerald-400 rounded-xl flex items-center justify-center">
                            <span class="text-2xl">🎓</span>
                        </div>
                        <span class="text-yellow-500 text-sm font-medium">5 pendentes</span>
                    </div>
                    <h3 class="text-2xl font-bold text-white" id="stat-train">0</h3>
                    <p class="text-slate-400 text-sm">Treinamentos</p>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-6 shadow-sm">
                    <h3 class="font-bold text-lg text-white mb-4">🚀 Ações Rápidas com IA</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <button onclick="showSection('pgr')" class="p-4 bg-gradient-to-r from-amber-600 to-amber-700 text-white border border-amber-500/30 rounded-xl hover:shadow-lg transition-all">
                            <span class="text-2xl block mb-2">🦺</span>
                            <span class="font-medium">Novo PGR</span>
                            <span class="block text-xs opacity-75">Gerado por IA</span>
                        </button>
                        <button onclick="showSection('apr')" class="p-4 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-xl hover:shadow-lg transition-all">
                            <span class="text-2xl block mb-2">⚠️</span>
                            <span class="font-medium">Nova APR</span>
                            <span class="block text-xs opacity-75">Gerado por IA</span>
                        </button>
                        <button onclick="showSection('epi')" class="p-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl hover:shadow-lg transition-all">
                            <span class="text-2xl block mb-2">🧤</span>
                            <span class="font-medium">Registrar EPI</span>
                            <span class="block text-xs opacity-75">Termo por IA</span>
                        </button>
                        <button onclick="showSection('treinamentos')" class="p-4 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl hover:shadow-lg transition-all">
                            <span class="text-2xl block mb-2">🎓</span>
                            <span class="font-medium">Treinamento</span>
                            <span class="block text-xs opacity-75">Certificado por IA</span>
                        </button>
                    </div>
                </div>
                
                <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-6 shadow-sm">
                    <h3 class="font-bold text-lg text-white mb-4">⚡ Alertas Importantes</h3>
                    <div class="space-y-3" id="alerts-container">
                        <div class="flex items-center gap-3 p-3 bg-slate-800/50 rounded-lg">
                            <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                            <p class="text-sm text-slate-400">Nenhum alerta no momento</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- PGR Section -->
        <section id="pgr-section" class="hidden fade-in">
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-bold text-white">🦺 Gerador de PGR</h2>
                    <p class="text-slate-400">Programa de Gerenciamento de Riscos - NR-01 <span class="ai-badge text-xs px-2 py-0.5 rounded-full text-white ml-2">IA</span></p>
                </div>
                <button onclick="checkAuthAndOpen('pgr-modal')" class="bg-amber-600 hover:bg-amber-500 shadow-[0_0_15px_rgba(245,158,11,0.5)] text-white px-6 py-3 rounded-xl font-medium transition-colors flex items-center gap-2">
                    <span>🤖</span> Gerar PGR com IA
                </button>
            </div>
            
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-6 shadow-sm mb-6">
                <h3 class="font-bold text-lg mb-4">PGRs Cadastrados</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-slate-400 text-sm border-b border-white/10">
                                <th class="pb-3 font-medium">Empresa</th>
                                <th class="pb-3 font-medium">CNPJ</th>
                                <th class="pb-3 font-medium">Riscos Identificados</th>
                                <th class="pb-3 font-medium">Validade</th>
                                <th class="pb-3 font-medium">Status</th>
                                <th class="pb-3 font-medium">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="pgr-table-body">
                            <tr class="text-slate-500 text-center">
                                <td colspan="6" class="py-8">Nenhum PGR cadastrado ainda</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- PCMAT Section -->
        <section id="pcmat-section" class="hidden fade-in">
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-bold text-white">🏗️ Gerador de PCMAT</h2>
                    <p class="text-slate-400">Programa de Condições e Meio Ambiente de Trabalho - NR-18 <span class="ai-badge text-xs px-2 py-0.5 rounded-full text-white ml-2">IA</span></p>
                </div>
                <button onclick="checkAuthAndOpen('pcmat-modal')" class="bg-cyan-600 hover:bg-cyan-500 shadow-[0_0_15px_rgba(6,182,212,0.5)] text-white px-6 py-3 rounded-xl font-medium transition-colors flex items-center gap-2">
                    <span>🤖</span> Gerar PCMAT com IA
                </button>
            </div>
            
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-6 shadow-sm">
                <h3 class="font-bold text-lg mb-4">PCMATs Cadastrados</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-slate-400 text-sm border-b border-white/10">
                                <th class="pb-3 font-medium">Obra</th>
                                <th class="pb-3 font-medium">Endereço</th>
                                <th class="pb-3 font-medium">Nº Trabalhadores</th>
                                <th class="pb-3 font-medium">Prazo Obra</th>
                                <th class="pb-3 font-medium">Status</th>
                                <th class="pb-3 font-medium">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="pcmat-table-body">
                            <tr class="text-slate-500 text-center">
                                <td colspan="6" class="py-8">Nenhum PCMAT cadastrado ainda</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- APR Section -->
        <section id="apr-section" class="hidden fade-in">
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-bold text-white">⚠️ APR Digital</h2>
                    <p class="text-slate-400">Análise Preliminar de Risco <span class="ai-badge text-xs px-2 py-0.5 rounded-full text-white ml-2">IA</span></p>
                </div>
                <button onclick="checkAuthAndOpen('apr-modal')" class="bg-yellow-600 hover:bg-yellow-500 shadow-[0_0_15px_rgba(234,179,8,0.5)] text-white px-6 py-3 rounded-xl font-medium transition-colors flex items-center gap-2">
                    <span>🤖</span> Gerar APR com IA
                </button>
            </div>
            
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-6 shadow-sm">
                <h3 class="font-bold text-lg mb-4">APRs Cadastradas</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-slate-400 text-sm border-b border-white/10">
                                <th class="pb-3 font-medium">Atividade</th>
                                <th class="pb-3 font-medium">Local</th>
                                <th class="pb-3 font-medium">Responsável</th>
                                <th class="pb-3 font-medium">Data</th>
                                <th class="pb-3 font-medium">Risco</th>
                                <th class="pb-3 font-medium">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="apr-table-body">
                            <tr class="text-slate-500 text-center">
                                <td colspan="6" class="py-8">Nenhuma APR cadastrada ainda</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- EPI Section -->
        <section id="epi-section" class="hidden fade-in">
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-bold text-white">🧤 Checklist de EPI</h2>
                    <p class="text-slate-400">Controle de Equipamentos de Proteção Individual <span class="ai-badge text-xs px-2 py-0.5 rounded-full text-white ml-2">IA</span></p>
                </div>
                <button onclick="checkAuthAndOpen('epi-modal')" class="bg-emerald-600 hover:bg-emerald-500 shadow-[0_0_15px_rgba(16,185,129,0.5)] text-white px-6 py-3 rounded-xl font-medium transition-colors flex items-center gap-2">
                    <span>🤖</span> Registrar EPI com Termo IA
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-slate-400">Total de EPIs</span>
                        <span class="text-2xl">🧤</span>
                    </div>
                    <h3 class="text-3xl font-bold text-white" id="epi-total">0</h3>
                </div>
                <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-6 shadow-sm border-l-4 border-green-500">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-slate-400">Válidos</span>
                        <span class="text-2xl">✅</span>
                    </div>
                    <h3 class="text-3xl font-bold text-green-600" id="epi-valid">0</h3>
                </div>
                <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-6 shadow-sm border-l-4 border-red-500">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-slate-400">Vencidos/Vencendo</span>
                        <span class="text-2xl">⚠️</span>
                    </div>
                    <h3 class="text-3xl font-bold text-red-600" id="epi-expired">0</h3>
                </div>
            </div>
            
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-6 shadow-sm">
                <h3 class="font-bold text-lg mb-4">Registro de EPIs</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-slate-400 text-sm border-b border-white/10">
                                <th class="pb-3 font-medium">Funcionário</th>
                                <th class="pb-3 font-medium">EPI</th>
                                <th class="pb-3 font-medium">CA</th>
                                <th class="pb-3 font-medium">Data Entrega</th>
                                <th class="pb-3 font-medium">Validade</th>
                                <th class="pb-3 font-medium">Status</th>
                                <th class="pb-3 font-medium">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="epi-table-body">
                            <tr class="text-slate-500 text-center">
                                <td colspan="7" class="py-8">Nenhum EPI registrado ainda</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Treinamentos Section -->
        <section id="treinamentos-section" class="hidden fade-in">
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-bold text-white">🎓 Controle de Treinamentos</h2>
                    <p class="text-slate-400">Gestão de treinamentos obrigatórios por NR <span class="ai-badge text-xs px-2 py-0.5 rounded-full text-white ml-2">IA</span></p>
                </div>
                <button onclick="checkAuthAndOpen('treinamento-modal')" class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-3 rounded-xl font-medium transition-colors flex items-center gap-2">
                    <span>🤖</span> Novo Treinamento com Certificado IA
                </button>
            </div>
            
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-6 shadow-sm">
                <h3 class="font-bold text-lg mb-4">Treinamentos Registrados</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-slate-400 text-sm border-b border-white/10">
                                <th class="pb-3 font-medium">Funcionário</th>
                                <th class="pb-3 font-medium">Treinamento</th>
                                <th class="pb-3 font-medium">NR</th>
                                <th class="pb-3 font-medium">Data Realização</th>
                                <th class="pb-3 font-medium">Validade</th>
                                <th class="pb-3 font-medium">Status</th>
                                <th class="pb-3 font-medium">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="treinamento-table-body">
                            <tr class="text-slate-500 text-center">
                                <td colspan="7" class="py-8">Nenhum treinamento registrado ainda</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Footer Padrão -->
        <footer class="mt-12 pt-6 border-t border-white/10 text-center pb-8">
            <p class="text-sm text-slate-400">
                &copy; <span id="ano"></span> 4U.IA.BR - SafeWork Pro. Todos os direitos reservados. Feito com amor por 
                <a href="https://4u.ia.br" target="_blank" class="text-purple-600 hover:text-orange-500 font-bold transition-colors">4u.ia.br</a>.
            </p>
        </footer>

    </main>

    <!-- Modal de Autenticação Unificada (4uLabs) -->
    <div id="auth-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 overflow-y-auto py-8">
        <div class="bg-slate-900/80 backdrop-blur-md border border-white/10 rounded-2xl p-8 max-w-md w-full mx-4 relative shadow-[0_0_50px_rgba(139,92,246,0.15)]">
            <button onclick="closeModal('auth-modal')" class="absolute top-4 right-4 text-slate-400 hover:text-white text-2xl transition-colors">&times;</button>
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-gradient-to-tr from-purple-600 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-[0_8px_30px_rgba(124,58,237,0.4)] border border-white/10">
                    <span class="text-3xl font-extrabold text-white">4U</span>
                </div>
                <h3 class="text-2xl font-bold text-white">Portal 4uLabs</h3>
                <p class="text-slate-400 text-sm mt-1" id="auth-modal-subtitle">Conecte sua conta unificada para gerenciar seus créditos de IA.</p>
            </div>
            
            <div class="flex border-b border-white/10 mb-6">
                <button id="tab-login" onclick="switchAuthMode('login')" class="flex-1 pb-3 text-center font-semibold text-sm border-b-2 border-purple-500 text-white transition-all">Entrar</button>
                <button id="tab-register" onclick="switchAuthMode('register')" class="flex-1 pb-3 text-center font-semibold text-sm border-b-2 border-transparent text-slate-400 hover:text-white transition-all">Cadastrar</button>
            </div>
            
            <form id="auth-modal-form" onsubmit="handleAuthSubmit(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">E-mail</label>
                    <input type="email" id="auth-modal-email" required placeholder="seuemail@exemplo.com" class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Senha</label>
                    <input type="password" id="auth-modal-password" required placeholder="••••••••" class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all">
                </div>
                
                <button type="submit" id="btn-auth-modal-submit" class="w-full py-3 mt-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-xl font-bold transition-all shadow-[0_4px_20px_rgba(124,58,237,0.3)]">
                    Entrar no Portal 4uLabs
                </button>
            </form>
        </div>
    </div>

    <!-- Modal de Recarga PIX (Mercado Pago) -->
    <div id="pix-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 overflow-y-auto py-8">
        <div class="bg-slate-900/80 backdrop-blur-md border border-white/10 rounded-2xl p-8 max-w-md w-full mx-4 relative shadow-[0_0_50px_rgba(6,182,212,0.15)]">
            <button onclick="closePixModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white text-2xl transition-colors">&times;</button>
            <div class="text-center mb-6">
                <h3 class="text-2xl font-bold text-white flex items-center justify-center gap-2">💎 Recarregar Créditos</h3>
                <span class="inline-flex items-center gap-1.5 text-[10px] text-cyan-400 bg-cyan-500/10 px-3 py-1 rounded-full border border-cyan-500/20 mt-2 font-semibold tracking-wider">PORTAL UNIFICADO 4ULABS</span>
                <p class="text-slate-400 text-sm mt-3 leading-relaxed">
                    Créditos compartilhados! Suas recargas ficam disponíveis para uso no **SafeWork Pro**, **Keep AI**, **TubeMind AI** e demais ferramentas.
                </p>
            </div>
            
            <!-- Packages -->
            <div class="space-y-3 mb-6" id="pix-packages-container">
                <div onclick="selectPixPackage(0)" id="pkg-0" class="p-4 border-2 border-purple-500 bg-purple-500/10 rounded-2xl cursor-pointer flex items-center justify-between transition-all hover:bg-purple-500/5">
                    <div>
                        <p class="font-bold text-white text-base">10 créditos</p>
                        <p class="text-[11px] text-slate-400">Pacote Bronze</p>
                    </div>
                    <span class="font-bold text-purple-400 text-base">R$ 4,90</span>
                </div>
                <div onclick="selectPixPackage(1)" id="pkg-1" class="p-4 border border-white/10 bg-slate-950/20 rounded-2xl cursor-pointer flex items-center justify-between transition-all hover:bg-white/5">
                    <div>
                        <p class="font-bold text-white text-base">50 créditos</p>
                        <p class="text-[11px] text-slate-400">Pacote Prata</p>
                    </div>
                    <span class="font-bold text-slate-300 text-base">R$ 19,90</span>
                </div>
                <div onclick="selectPixPackage(2)" id="pkg-2" class="p-4 border border-white/10 bg-slate-950/20 rounded-2xl cursor-pointer flex items-center justify-between transition-all hover:bg-white/5">
                    <div>
                        <p class="font-bold text-white text-base">100 créditos</p>
                        <p class="text-[11px] text-slate-400">Pacote Ouro</p>
                    </div>
                    <span class="font-bold text-slate-300 text-base">R$ 34,90</span>
                </div>
            </div>
            
            <!-- QR Area -->
            <div id="swp-qr-area" class="hidden flex flex-col items-center justify-center p-4 bg-slate-950/60 rounded-2xl border border-white/5 mb-6 text-center">
                <!-- QR Code e Copia e Cola -->
            </div>
            
            <button id="btn-swp-gerar-pix" onclick="generateSWPPix()" class="w-full py-3.5 bg-gradient-to-r from-cyan-500 to-purple-600 hover:from-cyan-400 hover:to-purple-500 text-white rounded-xl font-bold transition-all shadow-[0_4px_25px_rgba(6,182,212,0.3)]">
                Gerar QR Code PIX
            </button>
        </div>
    </div>

    <!-- Modal PGR -->
    <div id="pgr-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 overflow-y-auto py-8">
        <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-8 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-2xl font-bold text-white">🦺 Novo PGR</h3>
                    <p class="text-sm text-purple-600">Documento será gerado automaticamente pela IA</p>
                </div>
                <button onclick="closeModal('pgr-modal')" class="text-slate-500 hover:text-slate-400 text-2xl">&times;</button>
            </div>
            <form id="pgr-form" onsubmit="savePGR(event)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Empresa</label>
                        <input type="text" name="empresa" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">CNPJ</label>
                        <input type="text" name="cnpj" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Endereço</label>
                        <input type="text" name="endereco" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Ramo de Atividade</label>
                        <select name="ramo" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            <option value="">Selecione...</option>
                            <option value="Construção Civil">Construção Civil</option>
                            <option value="Indústria">Indústria</option>
                            <option value="Comércio">Comércio</option>
                            <option value="Serviços">Serviços</option>
                            <option value="Agropecuária">Agropecuária</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Nº de Funcionários</label>
                        <input type="number" name="funcionarios" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Responsável Técnico</label>
                        <input type="text" name="responsavel" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">CREA/Registro</label>
                        <input type="text" name="crea" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Riscos Identificados</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="riscos" value="Físico" class="w-4 h-4 text-orange-500">
                                <span class="text-sm">Físico</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="riscos" value="Químico" class="w-4 h-4 text-orange-500">
                                <span class="text-sm">Químico</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="riscos" value="Biológico" class="w-4 h-4 text-orange-500">
                                <span class="text-sm">Biológico</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="riscos" value="Ergonômico" class="w-4 h-4 text-orange-500">
                                <span class="text-sm">Ergonômico</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="riscos" value="Acidente" class="w-4 h-4 text-orange-500">
                                <span class="text-sm">Acidente</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="riscos" value="Altura" class="w-4 h-4 text-orange-500">
                                <span class="text-sm">Altura</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="riscos" value="Elétrico" class="w-4 h-4 text-orange-500">
                                <span class="text-sm">Elétrico</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="riscos" value="Mecânico" class="w-4 h-4 text-orange-500">
                                <span class="text-sm">Mecânico</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Informações Adicionais (opcional)</label>
                        <textarea name="plano_acao" rows="3" class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent" placeholder="Adicione informações específicas que a IA deve considerar..."></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end gap-4 mt-8">
                    <button type="button" onclick="closeModal('pgr-modal')" class="px-6 py-3 border border-white/10 rounded-xl text-slate-400 hover:bg-white/5">Cancelar</button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-orange-500 to-purple-600 text-white rounded-xl hover:shadow-lg font-medium flex items-center gap-2">
                        <span>🤖</span> Gerar PGR com IA
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal PCMAT -->
    <div id="pcmat-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 overflow-y-auto py-8">
        <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-8 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-2xl font-bold text-white">🏗️ Novo PCMAT</h3>
                    <p class="text-sm text-purple-600">Documento será gerado automaticamente pela IA</p>
                </div>
                <button onclick="closeModal('pcmat-modal')" class="text-slate-500 hover:text-slate-400 text-2xl">&times;</button>
            </div>
            <form id="pcmat-form" onsubmit="savePCMAT(event)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Nome da Obra</label>
                        <input type="text" name="obra" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Contratante</label>
                        <input type="text" name="contratante" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Endereço da Obra</label>
                        <input type="text" name="endereco" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Tipo de Obra</label>
                        <select name="tipo_obra" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                            <option value="">Selecione...</option>
                            <option value="Edificação">Edificação</option>
                            <option value="Reforma">Reforma</option>
                            <option value="Demolição">Demolição</option>
                            <option value="Infraestrutura">Infraestrutura</option>
                            <option value="Industrial">Industrial</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Nº de Trabalhadores</label>
                        <input type="number" name="trabalhadores" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Data Início</label>
                        <input type="date" name="data_inicio" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Previsão Término</label>
                        <input type="date" name="data_fim" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Engenheiro Responsável</label>
                        <input type="text" name="engenheiro" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">CREA</label>
                        <input type="text" name="crea" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Funções na Obra</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="funcoes" value="Pedreiro" class="w-4 h-4 text-blue-500">
                                <span class="text-sm">Pedreiro</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="funcoes" value="Eletricista" class="w-4 h-4 text-blue-500">
                                <span class="text-sm">Eletricista</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="funcoes" value="Carpinteiro" class="w-4 h-4 text-blue-500">
                                <span class="text-sm">Carpinteiro</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="funcoes" value="Armador" class="w-4 h-4 text-blue-500">
                                <span class="text-sm">Armador</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="funcoes" value="Pintor" class="w-4 h-4 text-blue-500">
                                <span class="text-sm">Pintor</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="funcoes" value="Servente" class="w-4 h-4 text-blue-500">
                                <span class="text-sm">Servente</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="funcoes" value="Encanador" class="w-4 h-4 text-blue-500">
                                <span class="text-sm">Encanador</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="funcoes" value="Operador" class="w-4 h-4 text-blue-500">
                                <span class="text-sm">Operador</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Informações Adicionais (opcional)</label>
                        <textarea name="medidas" rows="3" class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500" placeholder="Adicione informações específicas que a IA deve considerar..."></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end gap-4 mt-8">
                    <button type="button" onclick="closeModal('pcmat-modal')" class="px-6 py-3 border border-white/10 rounded-xl text-slate-400 hover:bg-white/5">Cancelar</button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl hover:shadow-lg font-medium flex items-center gap-2">
                        <span>🤖</span> Gerar PCMAT com IA
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal APR -->
    <div id="apr-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 overflow-y-auto py-8">
        <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-8 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-2xl font-bold text-white">⚠️ Nova APR</h3>
                    <p class="text-sm text-purple-600">Análise de riscos detalhada gerada pela IA</p>
                </div>
                <button onclick="closeModal('apr-modal')" class="text-slate-500 hover:text-slate-400 text-2xl">&times;</button>
            </div>
            <form id="apr-form" onsubmit="saveAPR(event)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Atividade</label>
                        <input type="text" name="atividade" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-yellow-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Local</label>
                        <input type="text" name="local" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-yellow-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Responsável</label>
                        <input type="text" name="responsavel" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-yellow-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Data</label>
                        <input type="date" name="data" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-yellow-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Tipo de Serviço</label>
                        <select name="tipo_servico" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-yellow-500">
                            <option value="">Selecione...</option>
                            <option value="Trabalho em Altura">Trabalho em Altura</option>
                            <option value="Espaço Confinado">Espaço Confinado</option>
                            <option value="Trabalho a Quente">Trabalho a Quente</option>
                            <option value="Elétrica">Elétrica</option>
                            <option value="Escavação">Escavação</option>
                            <option value="Movimentação de Carga">Movimentação de Carga</option>
                            <option value="Outros">Outros</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Nível de Risco Estimado</label>
                        <select name="nivel_risco" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-yellow-500">
                            <option value="">Selecione...</option>
                            <option value="Baixo">Baixo</option>
                            <option value="Médio">Médio</option>
                            <option value="Alto">Alto</option>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Descrição dos Riscos (a IA irá detalhar)</label>
                        <textarea name="riscos" rows="2" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-yellow-500" placeholder="Descreva brevemente os riscos da atividade..."></textarea>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Medidas de Controle Sugeridas (a IA irá complementar)</label>
                        <textarea name="medidas" rows="2" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-yellow-500" placeholder="Descreva as medidas de controle iniciais..."></textarea>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-300 mb-2">EPIs Obrigatórios</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="epis" value="Capacete" class="w-4 h-4 text-yellow-500">
                                <span class="text-sm">Capacete</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="epis" value="Óculos" class="w-4 h-4 text-yellow-500">
                                <span class="text-sm">Óculos</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="epis" value="Luvas" class="w-4 h-4 text-yellow-500">
                                <span class="text-sm">Luvas</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="epis" value="Botina" class="w-4 h-4 text-yellow-500">
                                <span class="text-sm">Botina</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="epis" value="Cinto" class="w-4 h-4 text-yellow-500">
                                <span class="text-sm">Cinto Segurança</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="epis" value="Protetor Auricular" class="w-4 h-4 text-yellow-500">
                                <span class="text-sm">Protetor Auricular</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="epis" value="Máscara" class="w-4 h-4 text-yellow-500">
                                <span class="text-sm">Máscara</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="epis" value="Uniforme" class="w-4 h-4 text-yellow-500">
                                <span class="text-sm">Uniforme</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Assinatura do Responsável</label>
                        <input type="text" name="assinatura" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-yellow-500" placeholder="Nome completo">
                    </div>
                </div>
                
                <div class="flex justify-end gap-4 mt-8">
                    <button type="button" onclick="closeModal('apr-modal')" class="px-6 py-3 border border-white/10 rounded-xl text-slate-400 hover:bg-white/5">Cancelar</button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-yellow-500 to-purple-600 text-white rounded-xl hover:shadow-lg font-medium flex items-center gap-2">
                        <span>🤖</span> Gerar APR com IA
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal EPI -->
    <div id="epi-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 overflow-y-auto py-8">
        <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-8 max-w-2xl w-full mx-4">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-2xl font-bold text-white">🧤 Registrar EPI</h3>
                    <p class="text-sm text-purple-600">Termo de responsabilidade gerado pela IA</p>
                </div>
                <button onclick="closeModal('epi-modal')" class="text-slate-500 hover:text-slate-400 text-2xl">&times;</button>
            </div>
            <form id="epi-form" onsubmit="saveEPI(event)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Funcionário</label>
                        <input type="text" name="funcionario" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">CPF</label>
                        <input type="text" name="cpf" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Tipo de EPI</label>
                        <select name="tipo_epi" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-green-500">
                            <option value="">Selecione...</option>
                            <option value="Capacete">Capacete</option>
                            <option value="Óculos de Proteção">Óculos de Proteção</option>
                            <option value="Protetor Auricular">Protetor Auricular</option>
                            <option value="Máscara Respiratória">Máscara Respiratória</option>
                            <option value="Luvas">Luvas</option>
                            <option value="Botina de Segurança">Botina de Segurança</option>
                            <option value="Cinto de Segurança">Cinto de Segurança</option>
                            <option value="Uniforme">Uniforme</option>
                            <option value="Avental">Avental</option>
                            <option value="Mangote">Mangote</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Número do CA</label>
                        <input type="text" name="ca" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-green-500" placeholder="Certificado de Aprovação">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Data de Entrega</label>
                        <input type="date" name="data_entrega" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Data de Validade</label>
                        <input type="date" name="validade" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-green-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Observações</label>
                        <textarea name="observacoes" rows="2" class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-green-500"></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end gap-4 mt-8">
                    <button type="button" onclick="closeModal('epi-modal')" class="px-6 py-3 border border-white/10 rounded-xl text-slate-400 hover:bg-white/5">Cancelar</button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-500 to-purple-600 text-white rounded-xl hover:shadow-lg font-medium flex items-center gap-2">
                        <span>🤖</span> Registrar com Termo IA
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Treinamento -->
    <div id="treinamento-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 overflow-y-auto py-8">
        <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-2xl p-8 max-w-2xl w-full mx-4">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-2xl font-bold text-white">🎓 Novo Treinamento</h3>
                    <p class="text-sm text-purple-600">Certificado com conteúdo programático gerado pela IA</p>
                </div>
                <button onclick="closeModal('treinamento-modal')" class="text-slate-500 hover:text-slate-400 text-2xl">&times;</button>
            </div>
            <form id="treinamento-form" onsubmit="saveTreinamento(event)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Funcionário</label>
                        <input type="text" name="funcionario" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">CPF</label>
                        <input type="text" name="cpf" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Treinamento</label>
                        <select name="treinamento" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-purple-500">
                            <option value="">Selecione...</option>
                            <option value="NR-06 - EPI">NR-06 - EPI</option>
                            <option value="NR-10 - Segurança em Eletricidade">NR-10 - Segurança em Eletricidade</option>
                            <option value="NR-11 - Transporte e Movimentação">NR-11 - Transporte e Movimentação</option>
                            <option value="NR-12 - Máquinas e Equipamentos">NR-12 - Máquinas e Equipamentos</option>
                            <option value="NR-18 - Construção Civil">NR-18 - Construção Civil</option>
                            <option value="NR-33 - Espaço Confinado">NR-33 - Espaço Confinado</option>
                            <option value="NR-35 - Trabalho em Altura">NR-35 - Trabalho em Altura</option>
                            <option value="CIPA">CIPA</option>
                            <option value="Primeiros Socorros">Primeiros Socorros</option>
                            <option value="Brigada de Incêndio">Brigada de Incêndio</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Carga Horária</label>
                        <input type="number" name="carga_horaria" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-purple-500" placeholder="Horas">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Data de Realização</label>
                        <input type="date" name="data_realizacao" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Data de Validade</label>
                        <input type="date" name="validade" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Instrutor/Empresa</label>
                        <input type="text" name="instrutor" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>
                
                <div class="flex justify-end gap-4 mt-8">
                    <button type="button" onclick="closeModal('treinamento-modal')" class="px-6 py-3 border border-white/10 rounded-xl text-slate-400 hover:bg-white/5">Cancelar</button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl hover:shadow-lg font-medium flex items-center gap-2">
                        <span>🤖</span> Registrar com Certificado IA
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // API Configuration
        const API_URL = 'api.php';
        const API_TOKEN = '<?= $_SESSION['api_token'] ?>';
        
        // --- 4ULABS ECOSYSTEM ACCOUNT & CREDIT INTEGRATION ---
        let userToken = localStorage.getItem('keepai_token') || null;
        let userData = null;
        let userCredits = 0;
        
        async function syncUser() {
            if (!userToken) {
                userData = null;
                userCredits = 0;
                updateAuthUI();
                return;
            }
            
            try {
                const response = await fetch('/app/keepai/api/auth.php', {
                    headers: { 'Authorization': `Bearer ${userToken}` }
                });
                
                if (!response.ok) {
                    throw new Error('Token expirado');
                }
                
                const result = await response.json();
                userData = result.user;
                userCredits = parseInt(result.user.credits || 0);
                updateCreditsUI();
                updateAuthUI();
            } catch (err) {
                console.error('Erro de sincronização de conta:', err);
                // Token inválido, limpa local
                userToken = null;
                localStorage.removeItem('keepai_token');
                userData = null;
                userCredits = 0;
                updateAuthUI();
            }
        }
        
        function updateCreditsUI() {
            // Atualiza sidebar
            const sidebarCredits = document.getElementById('sidebar-credits-badge');
            const sidebarCount = document.getElementById('sidebar-credits-count');
            if (sidebarCredits && sidebarCount) {
                if (userToken) {
                    sidebarCredits.classList.remove('hidden');
                    sidebarCount.textContent = `${userCredits} créditos`;
                } else {
                    sidebarCredits.classList.add('hidden');
                }
            }
            
            // Atualiza o dashboard AI Status
            const aiStatusBanner = document.getElementById('ai-status-banner');
            if (aiStatusBanner) {
                if (userToken) {
                    aiStatusBanner.innerHTML = `
                        <div class="flex items-center justify-between flex-wrap gap-4 w-full">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 bg-slate-900/40 border border-white/20 rounded-xl flex items-center justify-center">
                                    <span class="text-4xl">🤖</span>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold">Inteligência Artificial Ativa</h3>
                                    <p class="text-purple-200">Saldo unificado: <strong class="text-white">${userCredits} créditos</strong></p>
                                </div>
                            </div>
                            <button onclick="openModal('pix-modal')" class="bg-white/10 hover:bg-white/20 border border-white/20 px-4 py-2 rounded-xl text-sm font-semibold transition-all">
                                ⚡ Recarregar Créditos
                            </button>
                        </div>
                    `;
                } else {
                    aiStatusBanner.innerHTML = `
                        <div class="flex items-center justify-between flex-wrap gap-4 w-full">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 bg-slate-900/40 border border-white/20 rounded-xl flex items-center justify-center">
                                    <span class="text-4xl">🔒</span>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold">IA Protegida por Créditos</h3>
                                    <p class="text-purple-200">Faça login para utilizar o PGR, PCMAT e APR com Inteligência Artificial.</p>
                                </div>
                            </div>
                            <button onclick="openModal('auth-modal')" class="bg-yellow-400 text-slate-950 font-bold px-6 py-2.5 rounded-xl text-sm hover:bg-yellow-350 transition-all shadow-[0_0_15px_rgba(250,204,21,0.4)]">
                                🔑 Conectar Conta
                            </button>
                        </div>
                    `;
                }
            }
        }
        
        function updateAuthUI() {
            const footerContainer = document.getElementById('sidebar-footer-container');
            if (!footerContainer) return;
            
            if (userToken && userData) {
                const name = userData.display_name || userData.email || 'SW';
                const initials = name.substring(0, 2).toUpperCase();
                footerContainer.innerHTML = `
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-tr from-purple-500 to-indigo-500 rounded-full flex items-center justify-center font-bold text-white shadow-[0_0_10px_rgba(168,85,247,0.4)]">
                                ${initials}
                            </div>
                            <div>
                                <p class="font-medium text-sm text-white max-w-[120px] truncate" title="${name}">${name}</p>
                                <p class="text-[10px] text-slate-400">Portal 4uLabs</p>
                            </div>
                        </div>
                        <button onclick="handleLogout()" class="text-slate-400 hover:text-red-400 transition-colors p-1" title="Sair da Conta">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        </button>
                    </div>
                `;
            } else {
                footerContainer.innerHTML = `
                    <div class="flex flex-col gap-2 w-full">
                        <p class="text-[10px] text-slate-400 text-center">Entre para gerar documentos com IA</p>
                        <button onclick="openModal('auth-modal')" class="w-full py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-xl font-semibold text-sm transition-all hover:shadow-[0_0_15px_rgba(124,58,237,0.5)]">
                            🔑 Conectar Conta
                        </button>
                    </div>
                `;
            }
            updateCreditsUI();
        }
        
        function handleLogout() {
            if (confirm('Deseja desconectar sua conta do Portal 4uLabs?')) {
                userToken = null;
                localStorage.removeItem('keepai_token');
                userData = null;
                userCredits = 0;
                updateAuthUI();
                alert('Conta desconectada com sucesso.');
            }
        }
        
        function checkAuthAndOpen(modalId) {
            if (!userToken) {
                alert('🔑 Você precisa conectar sua conta do Portal 4uLabs para gerar documentos com IA.');
                openModal('auth-modal');
                return;
            }
            if (userCredits < 1) {
                alert('❌ Saldo de créditos de IA insuficiente.');
                openModal('pix-modal');
                return;
            }
            openModal(modalId);
        }

        // --- AUTHENTICATION MODAL LOGIC ---
        let authModalMode = 'login';
        
        function switchAuthMode(mode) {
            authModalMode = mode;
            const tabLogin = document.getElementById('tab-login');
            const tabReg = document.getElementById('tab-register');
            const btnSubmit = document.getElementById('btn-auth-modal-submit');
            const subtitle = document.getElementById('auth-modal-subtitle');
            
            if (mode === 'login') {
                tabLogin.className = 'flex-1 pb-3 text-center font-semibold text-sm border-b-2 border-purple-500 text-white transition-all';
                tabReg.className = 'flex-1 pb-3 text-center font-semibold text-sm border-b-2 border-transparent text-slate-400 hover:text-white transition-all';
                btnSubmit.textContent = 'Entrar no Portal 4uLabs';
                subtitle.textContent = 'Conecte sua conta unificada para gerenciar seus créditos de IA.';
            } else {
                tabReg.className = 'flex-1 pb-3 text-center font-semibold text-sm border-b-2 border-purple-500 text-white transition-all';
                tabLogin.className = 'flex-1 pb-3 text-center font-semibold text-sm border-b-2 border-transparent text-slate-400 hover:text-white transition-all';
                btnSubmit.textContent = 'Criar Conta Unificada';
                subtitle.textContent = 'Crie sua conta unificada. Seus créditos e saldo serão compartilhados em todo o portal.';
            }
        }
        
        async function handleAuthSubmit(e) {
            e.preventDefault();
            const email = document.getElementById('auth-modal-email').value.trim();
            const password = document.getElementById('auth-modal-password').value.trim();
            const btnSubmit = document.getElementById('btn-auth-modal-submit');
            
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="loading-spinner border-2 w-4 h-4 inline-block mr-2 align-middle"></span> Processando...';
            
            try {
                const response = await fetch(`/app/keepai/api/auth.php?action=${authModalMode}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.error || 'Erro na autenticação');
                }
                
                userToken = result.token;
                userData = result.user;
                userCredits = parseInt(result.user.credits || 0);
                localStorage.setItem('keepai_token', result.token);
                
                updateAuthUI();
                closeModal('auth-modal');
                alert(authModalMode === 'login' ? '🔑 Bem-vindo ao Portal 4uLabs!' : '🎉 Conta unificada criada com sucesso!');
            } catch (err) {
                alert('❌ Erro: ' + err.message);
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.textContent = authModalMode === 'login' ? 'Entrar no Portal 4uLabs' : 'Criar Conta Unificada';
            }
        }

        // --- PIX RECHARGE MODAL LOGIC ---
        let swpSelectedPackage = 0;
        let swpPixPollingInterval = null;
        
        function selectPixPackage(index) {
            swpSelectedPackage = index;
            document.querySelectorAll('#pix-packages-container > div').forEach((el, idx) => {
                if (idx === index) {
                    el.className = 'p-4 border-2 border-purple-500 bg-purple-500/10 rounded-2xl cursor-pointer flex items-center justify-between transition-all hover:bg-purple-500/5';
                } else {
                    el.className = 'p-4 border border-white/10 bg-slate-950/20 rounded-2xl cursor-pointer flex items-center justify-between transition-all hover:bg-white/5';
                }
            });
        }
        
        async function generateSWPPix() {
            const btn = document.getElementById('btn-swp-gerar-pix');
            const qrArea = document.getElementById('swp-qr-area');
            
            btn.disabled = true;
            btn.innerHTML = '<span class="loading-spinner border-2 w-4 h-4 inline-block mr-2 align-middle"></span> Gerando PIX...';
            qrArea.classList.add('hidden');
            qrArea.innerHTML = '';
            
            try {
                const response = await fetch('/app/keepai/api/mp_create.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${userToken}`
                    },
                    body: JSON.stringify({ package_index: swpSelectedPackage })
                });
                
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.error || 'Erro ao gerar PIX');
                }
                
                renderSWPQR(result);
                startSWPPixPolling(result.payment_id);
            } catch (err) {
                alert('❌ Erro ao gerar PIX: ' + err.message);
                btn.disabled = false;
                btn.innerHTML = 'Gerar QR Code PIX';
            }
        }
        
        function renderSWPQR(data) {
            const qrArea = document.getElementById('swp-qr-area');
            const btn = document.getElementById('btn-swp-gerar-pix');
            
            const imgSrc = data.qr_code_base64
                ? `data:image/png;base64,${data.qr_code_base64}`
                : `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(data.qr_code)}`;
                
            qrArea.innerHTML = `
                <img src="${imgSrc}" class="w-48 h-48 mx-auto mb-4 rounded-xl border border-white/10" alt="PIX QR Code">
                <p class="text-[11px] text-slate-400 mb-2">Clique no código abaixo para copiar o copia e cola:</p>
                <div class="bg-slate-900 border border-white/10 px-3 py-2 rounded-xl text-[10px] font-mono text-slate-300 max-w-full truncate cursor-pointer hover:bg-slate-800 transition-all mb-4 text-center" onclick="copySWPPixCode('${data.qr_code}')">
                    ${data.qr_code.substring(0, 45)}...
                </div>
                <div class="flex items-center justify-center gap-2 text-cyan-400 text-xs font-medium pulse-dot">
                    <span class="loading-spinner border-2 border-cyan-400 w-3 h-3"></span>
                    Aguardando confirmação do PIX...
                </div>
            `;
            qrArea.classList.remove('hidden');
            btn.classList.add('hidden');
        }
        
        function copySWPPixCode(code) {
            navigator.clipboard.writeText(code).then(() => {
                alert('📋 Código PIX Copia e Cola copiado com sucesso!');
            });
        }
        
        function startSWPPixPolling(paymentId) {
            stopSWPPixPolling();
            const startCredits = userCredits;
            swpPixPollingInterval = setInterval(async () => {
                try {
                    const response = await fetch('/app/keepai/api/credits.php', {
                        headers: { 'Authorization': `Bearer ${userToken}` }
                    });
                    
                    if (!response.ok) return;
                    
                    const result = await response.json();
                    if (result.credits > startCredits) {
                        userCredits = result.credits;
                        updateCreditsUI();
                        updateAuthUI();
                        stopSWPPixPolling();
                        closePixModal();
                        alert(`🎉 PIX confirmado com sucesso! Seu saldo agora é de ${userCredits} créditos de IA.`);
                    }
                } catch (e) {
                    console.error('Erro de polling do PIX:', e);
                }
            }, 5000);
        }
        
        function stopSWPPixPolling() {
            if (swpPixPollingInterval) {
                clearInterval(swpPixPollingInterval);
                swpPixPollingInterval = null;
            }
        }
        
        function closePixModal() {
            stopSWPPixPolling();
            document.getElementById('btn-swp-gerar-pix').classList.remove('hidden');
            document.getElementById('swp-qr-area').classList.add('hidden');
            document.getElementById('swp-qr-area').innerHTML = '';
            closeModal('pix-modal');
        }

        // Data Storage
        let data = {
            pgr: JSON.parse(localStorage.getItem('pgr') || '[]'),
            pcmat: JSON.parse(localStorage.getItem('pcmat') || '[]'),
            apr: JSON.parse(localStorage.getItem('apr') || '[]'),
            epi: JSON.parse(localStorage.getItem('epi') || '[]'),
            treinamentos: JSON.parse(localStorage.getItem('treinamentos') || '[]')
        };

        // Loading Functions
        function showLoading(message = 'Analisando dados e criando conteúdo profissional...') {
            document.getElementById('loading-message').textContent = message;
            document.getElementById('loading-overlay').classList.remove('hidden');
            document.getElementById('loading-overlay').classList.add('flex');
        }

        function hideLoading() {
            document.getElementById('loading-overlay').classList.add('hidden');
            document.getElementById('loading-overlay').classList.remove('flex');
        }

        // API Functions
        async function callAPI(action, data) {
            const token = localStorage.getItem('keepai_token') || '';
            const response = await fetch(`${API_URL}?action=${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-TOKEN': API_TOKEN,
                    'Authorization': token ? `Bearer ${token}` : ''
                },
                body: JSON.stringify(data)
            });
            
            if (response.status === 402) {
                // Payment Required
                hideLoading();
                const errResult = await response.json();
                alert('❌ ' + (errResult.error || 'Saldo insuficiente.'));
                openModal('pix-modal');
                throw new Error('credits_required');
            }
            
            if (response.status === 401 || response.status === 403) {
                hideLoading();
                alert('🔑 Você precisa estar logado no Portal 4uLabs para usar a IA.');
                openModal('auth-modal');
                throw new Error('auth_required');
            }
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'Erro desconhecido');
            }
            
            // Se o saldo veio atualizado na resposta, sincroniza a tela
            if (result.credits_remaining !== undefined) {
                userCredits = result.credits_remaining;
                updateCreditsUI();
                updateAuthUI();
            }
            
            return result.data;
        }

        // Navigation
        function showSection(section) {
            document.querySelectorAll('main > section').forEach(s => s.classList.add('hidden'));
            document.getElementById(`${section}-section`).classList.remove('hidden');
            
            document.querySelectorAll('.sidebar-item').forEach(item => {
                item.classList.remove('active');
                if (item.dataset.section === section) {
                    item.classList.add('active');
                }
            });
            
            updateStats();
        }

        // Modal Functions
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
            document.getElementById(id).classList.add('flex');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
            document.getElementById(id).classList.remove('flex');
        }

        // Save Functions with AI
        async function savePGR(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            
            const riscos = [];
            form.querySelectorAll('input[name="riscos"]:checked').forEach(cb => riscos.push(cb.value));
            
            const pgrData = {
                empresa: formData.get('empresa'),
                cnpj: formData.get('cnpj'),
                endereco: formData.get('endereco'),
                ramo: formData.get('ramo'),
                funcionarios: formData.get('funcionarios'),
                responsavel: formData.get('responsavel'),
                crea: formData.get('crea'),
                riscos: riscos,
                plano_acao: formData.get('plano_acao')
            };
            
            closeModal('pgr-modal');
            showLoading('Gerando PGR completo com análise de riscos...');
            
            try {
                const aiContent = await callAPI('generate-pgr', pgrData);
                
                const pgr = {
                    id: Date.now(),
                    ...pgrData,
                    aiContent: aiContent,
                    validade: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                    criado_em: new Date().toISOString()
                };
                
                data.pgr.push(pgr);
                localStorage.setItem('pgr', JSON.stringify(data.pgr));
                
                hideLoading();
                form.reset();
                updatePGRTable();
                updateStats();
                
                // Generate PDF with AI content
                generateAIPDF('PGR', pgr);
                
                alert('✅ PGR gerado com sucesso pela IA!');
                
            } catch (error) {
                hideLoading();
                alert('❌ Erro ao gerar PGR: ' + error.message);
            }
        }

        async function savePCMAT(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            
            const funcoes = [];
            form.querySelectorAll('input[name="funcoes"]:checked').forEach(cb => funcoes.push(cb.value));
            
            const pcmatData = {
                obra: formData.get('obra'),
                contratante: formData.get('contratante'),
                endereco: formData.get('endereco'),
                tipo_obra: formData.get('tipo_obra'),
                trabalhadores: formData.get('trabalhadores'),
                data_inicio: formData.get('data_inicio'),
                data_fim: formData.get('data_fim'),
                engenheiro: formData.get('engenheiro'),
                crea: formData.get('crea'),
                funcoes: funcoes,
                medidas: formData.get('medidas')
            };
            
            closeModal('pcmat-modal');
            showLoading('Gerando PCMAT completo conforme NR-18...');
            
            try {
                const aiContent = await callAPI('generate-pcmat', pcmatData);
                
                const pcmat = {
                    id: Date.now(),
                    ...pcmatData,
                    aiContent: aiContent,
                    criado_em: new Date().toISOString()
                };
                
                data.pcmat.push(pcmat);
                localStorage.setItem('pcmat', JSON.stringify(data.pcmat));
                
                hideLoading();
                form.reset();
                updatePCMATTable();
                updateStats();
                
                generateAIPDF('PCMAT', pcmat);
                
                alert('✅ PCMAT gerado com sucesso pela IA!');
                
            } catch (error) {
                hideLoading();
                alert('❌ Erro ao gerar PCMAT: ' + error.message);
            }
        }

        async function saveAPR(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            
            const epis = [];
            form.querySelectorAll('input[name="epis"]:checked').forEach(cb => epis.push(cb.value));
            
            const aprData = {
                atividade: formData.get('atividade'),
                local: formData.get('local'),
                responsavel: formData.get('responsavel'),
                data: formData.get('data'),
                tipo_servico: formData.get('tipo_servico'),
                nivel_risco: formData.get('nivel_risco'),
                riscos: formData.get('riscos'),
                medidas: formData.get('medidas'),
                epis: epis,
                assinatura: formData.get('assinatura')
            };
            
            closeModal('apr-modal');
            showLoading('Gerando APR com análise detalhada de riscos...');
            
            try {
                const aiContent = await callAPI('generate-apr', aprData);
                
                const apr = {
                    id: Date.now(),
                    ...aprData,
                    aiContent: aiContent,
                    criado_em: new Date().toISOString()
                };
                
                data.apr.push(apr);
                localStorage.setItem('apr', JSON.stringify(data.apr));
                
                hideLoading();
                form.reset();
                updateAPRTable();
                updateStats();
                
                generateAIPDF('APR', apr);
                
                alert('✅ APR gerada com sucesso pela IA!');
                
            } catch (error) {
                hideLoading();
                alert('❌ Erro ao gerar APR: ' + error.message);
            }
        }

        async function saveEPI(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            
            const epiData = {
                funcionario: formData.get('funcionario'),
                cpf: formData.get('cpf'),
                tipo_epi: formData.get('tipo_epi'),
                ca: formData.get('ca'),
                data_entrega: formData.get('data_entrega'),
                validade: formData.get('validade'),
                observacoes: formData.get('observacoes')
            };
            
            closeModal('epi-modal');
            showLoading('Gerando Termo de Responsabilidade de EPI...');
            
            try {
                const aiContent = await callAPI('generate-termo-epi', epiData);
                
                const epi = {
                    id: Date.now(),
                    ...epiData,
                    aiContent: aiContent,
                    criado_em: new Date().toISOString()
                };
                
                data.epi.push(epi);
                localStorage.setItem('epi', JSON.stringify(data.epi));
                
                hideLoading();
                form.reset();
                updateEPITable();
                updateStats();
                
                generateTermoEPIAI(epi);
                
                alert('✅ EPI registrado com Termo gerado pela IA!');
                
            } catch (error) {
                hideLoading();
                // Save without AI content
                const epi = {
                    id: Date.now(),
                    ...epiData,
                    criado_em: new Date().toISOString()
                };
                
                data.epi.push(epi);
                localStorage.setItem('epi', JSON.stringify(data.epi));
                
                form.reset();
                updateEPITable();
                updateStats();
                
                alert('⚠️ EPI registrado, mas erro ao gerar termo com IA: ' + error.message);
            }
        }

        async function saveTreinamento(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            
            const treinamentoData = {
                funcionario: formData.get('funcionario'),
                cpf: formData.get('cpf'),
                treinamento: formData.get('treinamento'),
                carga_horaria: formData.get('carga_horaria'),
                data_realizacao: formData.get('data_realizacao'),
                validade: formData.get('validade'),
                instrutor: formData.get('instrutor')
            };
            
            closeModal('treinamento-modal');
            showLoading('Gerando Certificado com conteúdo programático...');
            
            try {
                const aiContent = await callAPI('generate-certificado', treinamentoData);
                
                const treinamento = {
                    id: Date.now(),
                    ...treinamentoData,
                    aiContent: aiContent,
                    criado_em: new Date().toISOString()
                };
                
                data.treinamentos.push(treinamento);
                localStorage.setItem('treinamentos', JSON.stringify(data.treinamentos));
                
                hideLoading();
                form.reset();
                updateTreinamentoTable();
                updateStats();
                
                generateCertificadoAI(treinamento);
                
                alert('✅ Treinamento registrado com Certificado gerado pela IA!');
                
            } catch (error) {
                hideLoading();
                // Save without AI content
                const treinamento = {
                    id: Date.now(),
                    ...treinamentoData,
                    criado_em: new Date().toISOString()
                };
                
                data.treinamentos.push(treinamento);
                localStorage.setItem('treinamentos', JSON.stringify(data.treinamentos));
                
                form.reset();
                updateTreinamentoTable();
                updateStats();
                
                alert('⚠️ Treinamento registrado, mas erro ao gerar certificado com IA: ' + error.message);
            }
        }

        // Delete Functions
        function deleteItem(type, id) {
            if (confirm('Tem certeza que deseja excluir este item?')) {
                data[type] = data[type].filter(item => item.id !== id);
                localStorage.setItem(type, JSON.stringify(data[type]));
                
                switch(type) {
                    case 'pgr': updatePGRTable(); break;
                    case 'pcmat': updatePCMATTable(); break;
                    case 'apr': updateAPRTable(); break;
                    case 'epi': updateEPITable(); break;
                    case 'treinamentos': updateTreinamentoTable(); break;
                }
                updateStats();
            }
        }

        // Update Tables
        function updatePGRTable() {
            const tbody = document.getElementById('pgr-table-body');
            if (data.pgr.length === 0) {
                tbody.innerHTML = '<tr class="text-slate-500 text-center"><td colspan="6" class="py-8">Nenhum PGR cadastrado ainda</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.pgr.map(pgr => `
                <tr class="border-b border-white/10 hover:bg-white/5">
                    <td class="py-4 font-medium">${pgr.empresa}</td>
                    <td class="py-4 text-slate-400">${pgr.cnpj}</td>
                    <td class="py-4"><span class="px-2 py-1 bg-amber-500/20 text-amber-400 text-orange-700 rounded-full text-xs">${pgr.riscos.length} riscos</span></td>
                    <td class="py-4 text-slate-400">${formatDate(pgr.validade)}</td>
                    <td class="py-4">
                        <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 text-green-700 rounded-full text-sm">Ativo</span>
                    </td>
                    <td class="py-4">
                        <button onclick="regeneratePDF('pgr', ${pgr.id})" class="text-blue-500 hover:text-blue-700 mr-2">📄 PDF</button>
                        <button onclick="deleteItem('pgr', ${pgr.id})" class="text-red-500 hover:text-red-700">🗑️</button>
                    </td>
                </tr>
            `).join('');
        }

        function updatePCMATTable() {
            const tbody = document.getElementById('pcmat-table-body');
            if (data.pcmat.length === 0) {
                tbody.innerHTML = '<tr class="text-slate-500 text-center"><td colspan="6" class="py-8">Nenhum PCMAT cadastrado ainda</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.pcmat.map(pcmat => `
                <tr class="border-b border-white/10 hover:bg-white/5">
                    <td class="py-4 font-medium">${pcmat.obra}</td>
                    <td class="py-4 text-slate-400">${pcmat.endereco}</td>
                    <td class="py-4">${pcmat.trabalhadores}</td>
                    <td class="py-4 text-slate-400">${formatDate(pcmat.data_fim)}</td>
                    <td class="py-4">
                        <span class="px-3 py-1 bg-cyan-500/20 text-cyan-400 text-blue-700 rounded-full text-sm">Em andamento</span>
                    </td>
                    <td class="py-4">
                        <button onclick="regeneratePDF('pcmat', ${pcmat.id})" class="text-blue-500 hover:text-blue-700 mr-2">📄 PDF</button>
                        <button onclick="deleteItem('pcmat', ${pcmat.id})" class="text-red-500 hover:text-red-700">🗑️</button>
                    </td>
                </tr>
            `).join('');
        }

        function updateAPRTable() {
            const tbody = document.getElementById('apr-table-body');
            if (data.apr.length === 0) {
                tbody.innerHTML = '<tr class="text-slate-500 text-center"><td colspan="6" class="py-8">Nenhuma APR cadastrada ainda</td></tr>';
                return;
            }
            
            const riskColors = { 'Baixo': 'green', 'Médio': 'yellow', 'Alto': 'red' };
            
            tbody.innerHTML = data.apr.map(apr => `
                <tr class="border-b border-white/10 hover:bg-white/5">
                    <td class="py-4 font-medium">${apr.atividade}</td>
                    <td class="py-4 text-slate-400">${apr.local}</td>
                    <td class="py-4">${apr.responsavel}</td>
                    <td class="py-4 text-slate-400">${formatDate(apr.data)}</td>
                    <td class="py-4">
                        <span class="px-3 py-1 bg-${riskColors[apr.nivel_risco]}-100 text-${riskColors[apr.nivel_risco]}-700 rounded-full text-sm">${apr.nivel_risco}</span>
                    </td>
                    <td class="py-4">
                        <button onclick="regeneratePDF('apr', ${apr.id})" class="text-blue-500 hover:text-blue-700 mr-2">📄 PDF</button>
                        <button onclick="deleteItem('apr', ${apr.id})" class="text-red-500 hover:text-red-700">🗑️</button>
                    </td>
                </tr>
            `).join('');
        }

        function updateEPITable() {
            const tbody = document.getElementById('epi-table-body');
            if (data.epi.length === 0) {
                tbody.innerHTML = '<tr class="text-slate-500 text-center"><td colspan="7" class="py-8">Nenhum EPI registrado ainda</td></tr>';
                return;
            }
            
            const today = new Date();
            
            tbody.innerHTML = data.epi.map(epi => {
                const validade = new Date(epi.validade);
                const diff = Math.ceil((validade - today) / (1000 * 60 * 60 * 24));
                let status, statusColor;
                
                if (diff < 0) {
                    status = 'Vencido';
                    statusColor = 'red';
                } else if (diff <= 30) {
                    status = 'Vencendo';
                    statusColor = 'yellow';
                } else {
                    status = 'Válido';
                    statusColor = 'green';
                }
                
                return `
                    <tr class="border-b border-white/10 hover:bg-white/5">
                        <td class="py-4 font-medium">${epi.funcionario}</td>
                        <td class="py-4">${epi.tipo_epi}</td>
                        <td class="py-4 text-slate-400">${epi.ca}</td>
                        <td class="py-4 text-slate-400">${formatDate(epi.data_entrega)}</td>
                        <td class="py-4 text-slate-400">${formatDate(epi.validade)}</td>
                        <td class="py-4">
                            <span class="px-3 py-1 bg-${statusColor}-100 text-${statusColor}-700 rounded-full text-sm">${status}</span>
                            ${epi.aiContent ? '<span class="ml-1 px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs">IA</span>' : ''}
                        </td>
                        <td class="py-4">
                            <button onclick="regeneratePDF('epi', ${epi.id})" class="text-blue-500 hover:text-blue-700 mr-2">📄 Termo</button>
                            <button onclick="deleteItem('epi', ${epi.id})" class="text-red-500 hover:text-red-700">🗑️</button>
                        </td>
                    </tr>
                `;
            }).join('');
            
            // Update EPI stats
            const valid = data.epi.filter(epi => {
                const diff = Math.ceil((new Date(epi.validade) - today) / (1000 * 60 * 60 * 24));
                return diff > 30;
            }).length;
            
            const expired = data.epi.length - valid;
            
            document.getElementById('epi-total').textContent = data.epi.length;
            document.getElementById('epi-valid').textContent = valid;
            document.getElementById('epi-expired').textContent = expired;
        }

        function updateTreinamentoTable() {
            const tbody = document.getElementById('treinamento-table-body');
            if (data.treinamentos.length === 0) {
                tbody.innerHTML = '<tr class="text-slate-500 text-center"><td colspan="7" class="py-8">Nenhum treinamento registrado ainda</td></tr>';
                return;
            }
            
            const today = new Date();
            
            tbody.innerHTML = data.treinamentos.map(t => {
                const validade = new Date(t.validade);
                const diff = Math.ceil((validade - today) / (1000 * 60 * 60 * 24));
                let status, statusColor;
                
                if (diff < 0) {
                    status = 'Vencido';
                    statusColor = 'red';
                } else if (diff <= 30) {
                    status = 'Vencendo';
                    statusColor = 'yellow';
                } else {
                    status = 'Válido';
                    statusColor = 'green';
                }
                
                const nrMatch = t.treinamento.match(/NR-\d+/);
                const nr = nrMatch ? nrMatch[0] : '-';
                
                return `
                    <tr class="border-b border-white/10 hover:bg-white/5">
                        <td class="py-4 font-medium">${t.funcionario}</td>
                        <td class="py-4">${t.treinamento}</td>
                        <td class="py-4"><span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs">${nr}</span></td>
                        <td class="py-4 text-slate-400">${formatDate(t.data_realizacao)}</td>
                        <td class="py-4 text-slate-400">${formatDate(t.validade)}</td>
                        <td class="py-4">
                            <span class="px-3 py-1 bg-${statusColor}-100 text-${statusColor}-700 rounded-full text-sm">${status}</span>
                            ${t.aiContent ? '<span class="ml-1 px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs">IA</span>' : ''}
                        </td>
                        <td class="py-4">
                            <button onclick="regeneratePDF('treinamentos', ${t.id})" class="text-blue-500 hover:text-blue-700 mr-2">📄 Certificado</button>
                            <button onclick="deleteItem('treinamentos', ${t.id})" class="text-red-500 hover:text-red-700">🗑️</button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Update Stats
        function updateStats() {
            document.getElementById('stat-pgr').textContent = data.pgr.length;
            document.getElementById('stat-apr').textContent = data.apr.length;
            document.getElementById('stat-train').textContent = data.treinamentos.length;
            
            const today = new Date();
            const expiringEPIs = data.epi.filter(epi => {
                const diff = Math.ceil((new Date(epi.validade) - today) / (1000 * 60 * 60 * 24));
                return diff <= 30;
            }).length;
            document.getElementById('stat-epi-alert').textContent = expiringEPIs;
            
            // Atualiza alertas
            updateAlerts();
        }
        
        // Gerar Alertas Reais
        function updateAlerts() {
            const today = new Date();
            const alerts = [];
            
            // Verificar EPIs vencendo em 7 dias
            const episVencendo7dias = data.epi.filter(epi => {
                const diff = Math.ceil((new Date(epi.validade) - today) / (1000 * 60 * 60 * 24));
                return diff >= 0 && diff <= 7;
            });
            
            if (episVencendo7dias.length > 0) {
                alerts.push({
                    type: 'red',
                    pulse: true,
                    message: `${episVencendo7dias.length} EPI${episVencendo7dias.length > 1 ? 's' : ''} vence${episVencendo7dias.length > 1 ? 'm' : ''} nos próximos 7 dias`
                });
            }
            
            // Verificar EPIs vencidos
            const episVencidos = data.epi.filter(epi => {
                const diff = Math.ceil((new Date(epi.validade) - today) / (1000 * 60 * 60 * 24));
                return diff < 0;
            });
            
            if (episVencidos.length > 0) {
                alerts.push({
                    type: 'red',
                    pulse: true,
                    message: `${episVencidos.length} EPI${episVencidos.length > 1 ? 's' : ''} vencido${episVencidos.length > 1 ? 's' : ''} - Substituição urgente!`
                });
            }
            
            // Verificar Treinamentos vencendo em 30 dias
            const treinamentosVencendo = data.treinamentos.filter(t => {
                const diff = Math.ceil((new Date(t.validade) - today) / (1000 * 60 * 60 * 24));
                return diff >= 0 && diff <= 30;
            });
            
            if (treinamentosVencendo.length > 0) {
                alerts.push({
                    type: 'yellow',
                    pulse: false,
                    message: `${treinamentosVencendo.length} treinamento${treinamentosVencendo.length > 1 ? 's' : ''} vence${treinamentosVencendo.length > 1 ? 'm' : ''} nos próximos 30 dias`
                });
            }
            
            // Verificar Treinamentos vencidos
            const treinamentosVencidos = data.treinamentos.filter(t => {
                const diff = Math.ceil((new Date(t.validade) - today) / (1000 * 60 * 60 * 24));
                return diff < 0;
            });
            
            if (treinamentosVencidos.length > 0) {
                alerts.push({
                    type: 'red',
                    pulse: true,
                    message: `${treinamentosVencidos.length} treinamento${treinamentosVencidos.length > 1 ? 's' : ''} vencido${treinamentosVencidos.length > 1 ? 's' : ''} - Reciclagem necessária!`
                });
            }
            
            // Verificar PGRs vencendo em 60 dias
            const pgrsVencendo = data.pgr.filter(pgr => {
                const diff = Math.ceil((new Date(pgr.validade) - today) / (1000 * 60 * 60 * 24));
                return diff >= 0 && diff <= 60;
            });
            
            if (pgrsVencendo.length > 0) {
                pgrsVencendo.forEach(pgr => {
                    const diff = Math.ceil((new Date(pgr.validade) - today) / (1000 * 60 * 60 * 24));
                    alerts.push({
                        type: 'blue',
                        pulse: false,
                        message: `PGR de "${pgr.empresa}" expira em ${diff} dias`
                    });
                });
            }
            
            // Verificar PGRs vencidos
            const pgrsVencidos = data.pgr.filter(pgr => {
                const diff = Math.ceil((new Date(pgr.validade) - today) / (1000 * 60 * 60 * 24));
                return diff < 0;
            });
            
            if (pgrsVencidos.length > 0) {
                alerts.push({
                    type: 'red',
                    pulse: true,
                    message: `${pgrsVencidos.length} PGR${pgrsVencidos.length > 1 ? 's' : ''} vencido${pgrsVencidos.length > 1 ? 's' : ''} - Renovação urgente!`
                });
            }
            
            // Verificar PCMATs com obra próxima do fim
            const pcmatsFinalizando = data.pcmat.filter(pcmat => {
                const diff = Math.ceil((new Date(pcmat.data_fim) - today) / (1000 * 60 * 60 * 24));
                return diff >= 0 && diff <= 30;
            });
            
            if (pcmatsFinalizando.length > 0) {
                pcmatsFinalizando.forEach(pcmat => {
                    const diff = Math.ceil((new Date(pcmat.data_fim) - today) / (1000 * 60 * 60 * 24));
                    alerts.push({
                        type: 'blue',
                        pulse: false,
                        message: `Obra "${pcmat.obra}" finaliza em ${diff} dias`
                    });
                });
            }
            
            // Renderizar alertas
            const container = document.getElementById('alerts-container');
            
            if (alerts.length === 0) {
                container.innerHTML = `
                    <div class="flex items-center gap-3 p-3 bg-green-50 rounded-lg">
                        <span class="text-lg">✅</span>
                        <p class="text-sm text-green-700 font-medium">Tudo em dia! Nenhum alerta no momento.</p>
                    </div>
                `;
            } else {
                container.innerHTML = alerts.slice(0, 5).map(alert => `
                    <div class="flex items-center gap-3 p-3 bg-${alert.type}-50 rounded-lg">
                        <span class="w-2 h-2 bg-${alert.type}-500 rounded-full ${alert.pulse ? 'pulse-dot' : ''}"></span>
                        <p class="text-sm text-slate-300">${alert.message}</p>
                    </div>
                `).join('');
                
                // Se tiver mais de 5 alertas, mostra contador
                if (alerts.length > 5) {
                    container.innerHTML += `
                        <div class="flex items-center gap-3 p-3 bg-slate-800/50 rounded-lg">
                            <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                            <p class="text-sm text-slate-400">+ ${alerts.length - 5} outros alertas</p>
                        </div>
                    `;
                }
            }
        }

        // Format Date
        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('pt-BR');
        }

        // Regenerate PDF from saved data
        function regeneratePDF(type, id) {
            const item = data[type].find(i => i.id === id);
            if (!item) return;
            
            switch(type) {
                case 'pgr':
                    generateAIPDF('PGR', item);
                    break;
                case 'pcmat':
                    generateAIPDF('PCMAT', item);
                    break;
                case 'apr':
                    generateAIPDF('APR', item);
                    break;
                case 'epi':
                    generateTermoEPIAI(item);
                    break;
                case 'treinamentos':
                    generateCertificadoAI(item);
                    break;
            }
        }

        // Helper function to convert object to readable text
        function objectToText(obj) {
            if (typeof obj === 'string') return obj;
            if (typeof obj !== 'object' || obj === null) return String(obj);
            
            // Handle arrays
            if (Array.isArray(obj)) {
                return obj.map(item => objectToText(item)).join(', ');
            }
            
            // Handle objects - extract meaningful text
            const parts = [];
            for (const [key, value] of Object.entries(obj)) {
                if (value && typeof value === 'string') {
                    // Format key nicely
                    const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    parts.push(`${formattedKey}: ${value}`);
                } else if (value && typeof value !== 'object') {
                    const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    parts.push(`${formattedKey}: ${value}`);
                }
            }
            return parts.join(' | ');
        }

        // PDF Generation with AI Content
        function generateAIPDF(type, item) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            const aiContent = item.aiContent;
            
            // Header
            doc.setFillColor(30, 58, 95);
            doc.rect(0, 0, 210, 40, 'F');
            
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(20);
            doc.setFont('helvetica', 'bold');
            
            if (aiContent && aiContent.titulo) {
                const titleLines = doc.splitTextToSize(aiContent.titulo, 170);
                doc.text(titleLines, 105, 18, { align: 'center' });
                if (aiContent.subtitulo) {
                    doc.setFontSize(10);
                    doc.text(aiContent.subtitulo, 105, 35, { align: 'center' });
                }
            } else {
                doc.text(type, 105, 25, { align: 'center' });
            }
            
            doc.setTextColor(0, 0, 0);
            let y = 55;
            
            // Content from AI
            if (aiContent && aiContent.secoes) {
                aiContent.secoes.forEach(secao => {
                    if (y > 250) {
                        doc.addPage();
                        y = 20;
                    }
                    
                    // Section title
                    doc.setFontSize(12);
                    doc.setFont('helvetica', 'bold');
                    doc.setTextColor(30, 58, 95);
                    const tituloSecao = secao.numero ? `${secao.numero}. ${secao.titulo}` : secao.titulo;
                    doc.text(tituloSecao, 20, y);
                    y += 8;
                    
                    doc.setFontSize(10);
                    doc.setFont('helvetica', 'normal');
                    doc.setTextColor(0, 0, 0);
                    
                    // Section content
                    if (secao.conteudo) {
                        const conteudoText = objectToText(secao.conteudo);
                        const lines = doc.splitTextToSize(conteudoText, 170);
                        lines.forEach(line => {
                            if (y > 280) {
                                doc.addPage();
                                y = 20;
                            }
                            doc.text(line, 20, y);
                            y += 5;
                        });
                        y += 3;
                    }
                    
                    // Section items (bullets)
                    if (secao.itens && secao.itens.length > 0) {
                        secao.itens.forEach(itemData => {
                            if (y > 275) {
                                doc.addPage();
                                y = 20;
                            }
                            const itemText = objectToText(itemData);
                            const itemLines = doc.splitTextToSize(`• ${itemText}`, 165);
                            itemLines.forEach(line => {
                                if (y > 280) {
                                    doc.addPage();
                                    y = 20;
                                }
                                doc.text(line, 25, y);
                                y += 5;
                            });
                        });
                        y += 3;
                    }
                    
                    // Handle tables if present
                    if (secao.tabela && secao.tabela.cabecalho && secao.tabela.linhas) {
                        if (y > 240) {
                            doc.addPage();
                            y = 20;
                        }
                        
                        const headers = secao.tabela.cabecalho;
                        const colWidth = 170 / headers.length;
                        
                        // Table header
                        doc.setFillColor(240, 240, 240);
                        doc.rect(20, y - 4, 170, 8, 'F');
                        doc.setFont('helvetica', 'bold');
                        doc.setFontSize(8);
                        headers.forEach((header, idx) => {
                            doc.text(String(header).substring(0, 15), 22 + (idx * colWidth), y);
                        });
                        y += 8;
                        
                        // Table rows
                        doc.setFont('helvetica', 'normal');
                        secao.tabela.linhas.forEach(row => {
                            if (y > 280) {
                                doc.addPage();
                                y = 20;
                            }
                            if (Array.isArray(row)) {
                                row.forEach((cell, idx) => {
                                    const cellText = objectToText(cell).substring(0, 20);
                                    doc.text(cellText, 22 + (idx * colWidth), y);
                                });
                            }
                            y += 6;
                        });
                        y += 5;
                    }
                    
                    // Handle subsections
                    if (secao.subsecoes && Array.isArray(secao.subsecoes)) {
                        secao.subsecoes.forEach(sub => {
                            if (y > 270) {
                                doc.addPage();
                                y = 20;
                            }
                            doc.setFont('helvetica', 'bold');
                            doc.setFontSize(10);
                            doc.text(`  ${sub.titulo || ''}`, 20, y);
                            y += 6;
                            
                            doc.setFont('helvetica', 'normal');
                            doc.setFontSize(9);
                            if (sub.conteudo) {
                                const subLines = doc.splitTextToSize(objectToText(sub.conteudo), 165);
                                subLines.forEach(line => {
                                    if (y > 280) {
                                        doc.addPage();
                                        y = 20;
                                    }
                                    doc.text(line, 25, y);
                                    y += 5;
                                });
                            }
                            y += 3;
                        });
                    }
                    
                    y += 5;
                });
            } else {
                // Fallback: basic info
                doc.setFontSize(11);
                if (type === 'PGR') {
                    doc.text(`Empresa: ${item.empresa}`, 20, y); y += 7;
                    doc.text(`CNPJ: ${item.cnpj}`, 20, y); y += 7;
                    doc.text(`Endereço: ${item.endereco}`, 20, y); y += 7;
                    doc.text(`Responsável: ${item.responsavel}`, 20, y); y += 7;
                    doc.text(`Riscos: ${item.riscos.join(', ')}`, 20, y);
                } else if (type === 'PCMAT') {
                    doc.text(`Obra: ${item.obra}`, 20, y); y += 7;
                    doc.text(`Contratante: ${item.contratante}`, 20, y); y += 7;
                    doc.text(`Endereço: ${item.endereco}`, 20, y); y += 7;
                    doc.text(`Engenheiro: ${item.engenheiro}`, 20, y);
                } else if (type === 'APR') {
                    doc.text(`Atividade: ${item.atividade}`, 20, y); y += 7;
                    doc.text(`Local: ${item.local}`, 20, y); y += 7;
                    doc.text(`Responsável: ${item.responsavel}`, 20, y); y += 7;
                    doc.text(`Nível de Risco: ${item.nivel_risco}`, 20, y);
                }
            }
            
            // Signature area
            y = Math.max(y + 20, 240);
            if (y > 260) {
                doc.addPage();
                y = 40;
            }
            
            doc.setDrawColor(0);
            doc.setLineWidth(0.5);
            doc.line(20, y, 90, y);
            doc.line(120, y, 190, y);
            
            doc.setFontSize(9);
            doc.setTextColor(0, 0, 0);
            doc.text('Responsável Técnico', 55, y + 6, { align: 'center' });
            doc.text('Representante da Empresa', 155, y + 6, { align: 'center' });
            
            // Footer on all pages
            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.setTextColor(128, 128, 128);
                doc.text(`Página ${i} de ${pageCount}`, 105, 290, { align: 'center' });
                doc.text(`Documento gerado em ${new Date().toLocaleDateString('pt-BR')} - SafeWork Pro`, 105, 295, { align: 'center' });
            }
            
            doc.save(`${type}_${Date.now()}.pdf`);
        }

        function generateTermoEPIAI(epi) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            const aiContent = epi.aiContent;
            
            // Header
            doc.setFillColor(34, 197, 94);
            doc.rect(0, 0, 210, 35, 'F');
            
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(18);
            doc.setFont('helvetica', 'bold');
            doc.text('TERMO DE RESPONSABILIDADE', 105, 15, { align: 'center' });
            doc.setFontSize(12);
            doc.text('EQUIPAMENTO DE PROTEÇÃO INDIVIDUAL - EPI', 105, 25, { align: 'center' });
            doc.setFontSize(9);
            doc.text('Conforme NR-06', 105, 32, { align: 'center' });
            
            doc.setTextColor(0, 0, 0);
            let y = 50;
            
            doc.setFontSize(11);
            doc.setFont('helvetica', 'normal');
            
            // Declaration text
            const texto = aiContent && aiContent.termo_declaracao 
                ? aiContent.termo_declaracao.replace('{nome}', epi.funcionario).replace('{cpf}', epi.cpf)
                : `Eu, ${epi.funcionario}, portador(a) do CPF ${epi.cpf}, declaro ter recebido o Equipamento de Proteção Individual (EPI) abaixo discriminado, comprometendo-me a utilizá-lo adequadamente durante toda a jornada de trabalho, conforme as orientações recebidas.`;
            
            const textoLines = doc.splitTextToSize(texto, 170);
            doc.text(textoLines, 20, y);
            y += textoLines.length * 6 + 15;
            
            // EPI Details Box
            doc.setFillColor(245, 245, 245);
            doc.rect(20, y - 5, 170, 35, 'F');
            
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(30, 58, 95);
            doc.text('DADOS DO EPI', 25, y + 3);
            y += 12;
            
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(0, 0, 0);
            doc.text(`Tipo: ${epi.tipo_epi}`, 25, y); 
            doc.text(`CA: ${epi.ca}`, 120, y); 
            y += 7;
            doc.text(`Data de Entrega: ${formatDate(epi.data_entrega)}`, 25, y); 
            doc.text(`Validade: ${formatDate(epi.validade)}`, 120, y); 
            y += 20;
            
            // Employee Obligations
            doc.setFontSize(11);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(30, 58, 95);
            doc.text('OBRIGAÇÕES DO EMPREGADO (NR-06.7.1)', 20, y);
            y += 8;
            
            doc.setFontSize(9);
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(0, 0, 0);
            
            const obrigacoes = aiContent && aiContent.obrigacoes_empregado 
                ? aiContent.obrigacoes_empregado 
                : [
                    'Usar o EPI apenas para a finalidade a que se destina',
                    'Responsabilizar-se pela guarda e conservação',
                    'Comunicar ao empregador qualquer alteração que o torne impróprio para uso',
                    'Cumprir as determinações do empregador sobre o uso adequado'
                ];
            
            obrigacoes.forEach(obr => {
                const obrText = typeof obr === 'string' ? obr : objectToText(obr);
                const lines = doc.splitTextToSize(`• ${obrText}`, 165);
                lines.forEach(line => {
                    doc.text(line, 25, y);
                    y += 5;
                });
            });
            y += 10;
            
            // Employer Obligations
            if (aiContent && aiContent.obrigacoes_empregador) {
                doc.setFontSize(11);
                doc.setFont('helvetica', 'bold');
                doc.setTextColor(30, 58, 95);
                doc.text('OBRIGAÇÕES DO EMPREGADOR (NR-06.6.1)', 20, y);
                y += 8;
                
                doc.setFontSize(9);
                doc.setFont('helvetica', 'normal');
                doc.setTextColor(0, 0, 0);
                
                aiContent.obrigacoes_empregador.slice(0, 4).forEach(obr => {
                    const obrText = typeof obr === 'string' ? obr : objectToText(obr);
                    const lines = doc.splitTextToSize(`• ${obrText}`, 165);
                    lines.forEach(line => {
                        doc.text(line, 25, y);
                        y += 5;
                    });
                });
                y += 10;
            }
            
            // Observations
            if (epi.observacoes) {
                doc.setFontSize(10);
                doc.setFont('helvetica', 'bold');
                doc.text('Observações:', 20, y);
                y += 6;
                doc.setFont('helvetica', 'normal');
                const obsLines = doc.splitTextToSize(epi.observacoes, 170);
                doc.text(obsLines, 20, y);
                y += obsLines.length * 5 + 10;
            }
            
            // Signatures
            y = Math.max(y + 10, 230);
            if (y > 250) y = 250;
            
            doc.setDrawColor(0);
            doc.setLineWidth(0.5);
            doc.line(20, y, 90, y);
            doc.line(120, y, 190, y);
            
            doc.setFontSize(9);
            doc.text('Assinatura do Funcionário', 55, y + 6, { align: 'center' });
            doc.text('Responsável pela Entrega', 155, y + 6, { align: 'center' });
            
            y += 18;
            doc.text(`Local e Data: _________________________________________, ${new Date().toLocaleDateString('pt-BR')}`, 20, y);
            
            // Footer
            doc.setFontSize(8);
            doc.setTextColor(128, 128, 128);
            doc.text('SafeWork Pro - Sistema de Segurança do Trabalho', 105, 290, { align: 'center' });
            
            doc.save(`Termo_EPI_${epi.funcionario.replace(/\s+/g, '_')}_${Date.now()}.pdf`);
        }

        function generateCertificadoAI(t) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('landscape');
            
            const aiContent = t.aiContent;
            
            // Decorative border
            doc.setDrawColor(147, 51, 234);
            doc.setLineWidth(3);
            doc.rect(10, 10, 277, 190, 'S');
            
            // Inner decorative border
            doc.setLineWidth(0.5);
            doc.rect(15, 15, 267, 180, 'S');
            
            // Header background
            doc.setFillColor(147, 51, 234);
            doc.rect(15, 15, 267, 45, 'F');
            
            // Decorative corners
            doc.setFillColor(251, 191, 36);
            doc.circle(15, 15, 5, 'F');
            doc.circle(282, 15, 5, 'F');
            doc.circle(15, 195, 5, 'F');
            doc.circle(282, 195, 5, 'F');
            
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(32);
            doc.setFont('helvetica', 'bold');
            doc.text('CERTIFICADO', 148.5, 38, { align: 'center' });
            doc.setFontSize(12);
            doc.text('DE CONCLUSÃO DE TREINAMENTO', 148.5, 52, { align: 'center' });
            
            doc.setTextColor(0, 0, 0);
            
            // Certification text
            doc.setFontSize(12);
            doc.setFont('helvetica', 'normal');
            doc.text('Certificamos que', 148.5, 75, { align: 'center' });
            
            // Name with decorative line
            doc.setFontSize(24);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(30, 58, 95);
            doc.text(t.funcionario.toUpperCase(), 148.5, 90, { align: 'center' });
            
            // Decorative line under name
            doc.setDrawColor(251, 191, 36);
            doc.setLineWidth(1);
            const nameWidth = doc.getTextWidth(t.funcionario.toUpperCase());
            doc.line(148.5 - nameWidth/2 - 10, 94, 148.5 + nameWidth/2 + 10, 94);
            
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text(`CPF: ${t.cpf}`, 148.5, 102, { align: 'center' });
            
            doc.setFontSize(12);
            doc.text('concluiu com êxito o treinamento de', 148.5, 114, { align: 'center' });
            
            // Training name
            doc.setFontSize(18);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(147, 51, 234);
            doc.text(t.treinamento, 148.5, 128, { align: 'center' });
            
            // Training details
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text(`Carga Horária: ${t.carga_horaria} horas  |  Realizado em: ${formatDate(t.data_realizacao)}  |  Válido até: ${formatDate(t.validade)}`, 148.5, 140, { align: 'center' });
            
            // Conteúdo programático
            if (aiContent && aiContent.conteudo_programatico && aiContent.conteudo_programatico.length > 0) {
                doc.setFontSize(8);
                doc.setFont('helvetica', 'bold');
                doc.text('Conteúdo Programático:', 25, 152);
                doc.setFont('helvetica', 'normal');
                const conteudoItems = aiContent.conteudo_programatico.slice(0, 5).map(c => 
                    typeof c === 'string' ? c : objectToText(c)
                );
                doc.text(conteudoItems.join('  •  ').substring(0, 120), 25, 158);
            }
            
            // NR Reference
            if (aiContent && aiContent.nr_referencia) {
                doc.setFontSize(8);
                doc.setTextColor(100, 100, 100);
                doc.text(`Referência: ${aiContent.nr_referencia}`, 148.5, 165, { align: 'center' });
            }
            
            // Signatures
            doc.setTextColor(0, 0, 0);
            doc.setDrawColor(0, 0, 0);
            doc.setLineWidth(0.5);
            
            doc.line(55, 182, 135, 182);
            doc.setFontSize(9);
            doc.text(t.instrutor, 95, 178, { align: 'center' });
            doc.text('Instrutor/Empresa Responsável', 95, 188, { align: 'center' });
            
            doc.line(162, 182, 242, 182);
            doc.text('Responsável Técnico', 202, 188, { align: 'center' });
            
            doc.save(`Certificado_${t.funcionario.replace(/\s+/g, '_')}_${Date.now()}.pdf`);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            updatePGRTable();
            updatePCMATTable();
            updateAPRTable();
            updateEPITable();
            updateTreinamentoTable();
            updateStats();
            
            // Sincroniza usuário e créditos
            syncUser();
            
            // Set current year in footer
            document.getElementById('ano').textContent = new Date().getFullYear();
        });

        // --- EASTER EGG LOGO (5 CLIQUES PARA O LOGIN ADMIN) ---
        const logoLink = document.getElementById('logo-link');
        if (logoLink) {
            logoLink.addEventListener('click', function(e) {
                const now = Date.now();
                let clicks = parseInt(localStorage.getItem('logo_clicks') || '0');
                let lastClick = parseInt(localStorage.getItem('logo_last_click') || '0');

                // Incrementa se o clique anterior ocorreu em até 2 segundos
                if (now - lastClick < 2000) {
                    clicks++;
                } else {
                    clicks = 1;
                }

                localStorage.setItem('logo_clicks', clicks);
                localStorage.setItem('logo_last_click', now);

                if (clicks >= 5) {
                    e.preventDefault();
                    localStorage.removeItem('logo_clicks');
                    localStorage.removeItem('logo_last_click');
                    window.location.href = '/admin/login';
                    return;
                }

                // Previne salto pra topo e mantém navegação fluida
                e.preventDefault();
            });
        }
    </script>
</body>
</html>
