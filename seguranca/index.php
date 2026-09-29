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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>SafeWork Pro - Sistema de Segurança do Trabalho com IA</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/icon-ecalc-32.png">
    <link rel="apple-touch-icon" sizes="192x192" href="../assets/icon-ecalc-192.png">
    <link rel="stylesheet" id="ecalc-suite-css" href="../assets/suite/suite-nav.css?v=20260929_3">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; box-sizing: border-box; }
        html, body {
            max-width: 100vw;
            overflow-x: hidden;
        }
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

        /* ==========================================================================
           DESKTOP & MOBILE RESPONSIVE ENGINE (Sem dependência de runtime Tailwind)
           ========================================================================== */
        @media (min-width: 1024px) {
            #sidebar {
                position: fixed !important;
                top: 52px !important;
                bottom: 0 !important;
                left: 0 !important;
                width: 260px !important;
                height: calc(100vh - 52px) !important;
                transform: none !important;
                z-index: 40 !important;
                border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
                background: rgba(15, 23, 42, 0.96) !important;
                box-shadow: 4px 0 24px rgba(0, 0, 0, 0.3) !important;
            }
            main {
                margin-left: 260px !important;
                width: calc(100% - 260px) !important;
                max-width: calc(100% - 260px) !important;
                min-height: calc(100vh - 52px) !important;
                padding: 28px 36px !important;
                box-sizing: border-box !important;
            }
            .mobile-header-bar {
                display: none !important;
            }
        }
        @media (max-width: 1023px) {
            #sidebar {
                position: fixed !important;
                top: 0 !important;
                bottom: 0 !important;
                left: 0 !important;
                width: 280px !important;
                max-width: 85vw !important;
                height: 100vh !important;
                z-index: 10001 !important;
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }
            #sidebar.-translate-x-full {
                transform: translateX(-100%) !important;
            }
            #sidebar:not(.-translate-x-full) {
                transform: translateX(0) !important;
            }
            main {
                margin-left: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                padding: 16px !important;
            }
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

    <!-- Sidebar Backdrop Overlay (Mobile) -->
    <div id="sidebar-backdrop" onclick="toggleSidebar(false)" class="fixed inset-0 bg-black/75 backdrop-blur-xs z-[10000] hidden lg:hidden transition-opacity duration-300"></div>

    <!-- Mobile Top Bar (visível apenas em telas menores que lg) -->
    <header class="mobile-header-bar lg:hidden sticky top-[46px] z-30 bg-slate-900/95 backdrop-blur-md border-b border-white/10 px-4 py-2.5 flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-2.5">
            <button type="button" onclick="toggleSidebar(true)" class="p-2 rounded-xl bg-slate-800 text-slate-200 hover:text-white border border-white/10 focus:outline-none focus:ring-2 focus:ring-amber-400 active:scale-95 transition-all" aria-label="Abrir Menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <div class="flex items-center gap-2">
                <span class="text-xl">🛡️</span>
                <div>
                    <h1 class="font-bold text-sm text-white leading-tight">SafeWork Pro</h1>
                    <span id="mobile-current-section" class="text-[11px] text-amber-400 font-medium">Dashboard</span>
                </div>
            </div>
        </div>
        <div id="mobile-credits-container" class="flex items-center gap-2">
            <button type="button" onclick="openModal('pix-modal')" class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-purple-500/20 text-purple-300 border border-purple-500/30 text-xs font-semibold hover:bg-purple-500/30 transition-colors">
                <span>💎</span>
                <span id="mobile-credits-count">0 cr</span>
            </button>
        </div>
    </header>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-0 lg:top-[46px] h-full lg:h-[calc(100vh-46px)] w-72 sm:w-80 lg:w-64 bg-slate-900/95 lg:bg-slate-900/70 backdrop-blur-xl border-r border-white/10 text-white z-[10001] lg:z-30 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col justify-between overflow-y-auto">
        <div>
            <div class="p-5 border-b border-white/10 flex items-center justify-between">
                <a href="#" id="logo-link" class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-yellow-400 rounded-lg flex items-center justify-center shrink-0">
                        <span class="text-2xl">🛡️</span>
                    </div>
                    <div>
                        <h1 class="font-bold text-lg text-white">SafeWork Pro</h1>
                        <div class="flex items-center gap-1">
                            <span class="ai-badge text-xs px-2 py-0.5 rounded-full text-white">IA</span>
                            <p class="text-xs text-gray-300">Powered by AI</p>
                        </div>
                    </div>
                </a>
                <button type="button" onclick="toggleSidebar(false)" class="lg:hidden p-2 text-slate-400 hover:text-white rounded-lg hover:bg-white/10 active:scale-95 transition-all" title="Fechar Menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
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
        </div>
        
        <div class="p-4 border-t border-white/10" id="sidebar-footer-container">
            <div class="flex flex-col gap-2 w-full">
                <p class="text-[11px] text-slate-400 text-center font-medium">Entre para usar IA</p>
                <button type="button" onclick="conectarGoogle()" class="w-full py-2.5 px-3 bg-white hover:bg-slate-100 active:scale-[0.98] text-slate-800 rounded-xl font-bold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-lg transition-all cursor-pointer">
                    <svg viewBox="0 0 24 24" width="18" height="18" class="shrink-0" style="display:inline-block;vertical-align:middle;"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>
                    <span>Entrar com Google</span>
                </button>
                <button type="button" onclick="openModal('auth-modal')" class="text-[11px] text-purple-300 hover:text-white text-center transition-colors">
                    ou usar e-mail
                </button>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="lg:ml-64 min-h-screen min-w-0 p-4 sm:p-6 lg:p-8 box-border">
        <div class="max-w-6xl mx-auto w-full">
        <!-- Dashboard Section -->
        <section id="dashboard-section" class="fade-in">
            <div class="mb-6 sm:mb-8 flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Dashboard</h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">Visão geral do sistema de segurança com IA</p>
                </div>
                <div id="desktop-header-auth" class="hidden sm:flex items-center gap-2.5"></div>
            </div>
            
            <!-- AI Status -->
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl p-4 sm:p-6 mb-6 sm:mb-8 text-white shadow-lg" id="ai-status-banner">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5 w-full">
                    <div class="flex items-start sm:items-center gap-4">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 bg-slate-900/50 border border-white/20 rounded-2xl flex items-center justify-center shrink-0 shadow-lg">
                            <span class="text-3xl sm:text-4xl">🤖</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-lg sm:text-xl font-bold text-white">Inteligência Artificial Ativa</h3>
                                <span class="bg-amber-400/20 text-amber-300 text-[11px] font-semibold px-2.5 py-0.5 rounded-full border border-amber-400/30 uppercase tracking-wide">PGR • PCMAT • APR</span>
                            </div>
                            <p class="text-xs sm:text-sm text-purple-100 mt-1 max-w-xl">
                                Crie documentos técnicos completos de Engenharia e Segurança do Trabalho com IA. Conecte sua conta Google para começar.
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                        <button type="button" onclick="conectarGoogle()" class="px-5 py-3 bg-white hover:bg-slate-100 active:scale-[0.98] text-slate-800 rounded-xl font-bold text-sm flex items-center justify-center gap-2.5 shadow-xl transition-all cursor-pointer border border-white/40">
                            <svg viewBox="0 0 24 24" width="20" height="20" class="shrink-0" style="display:inline-block;vertical-align:middle;"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>
                            <span>Entrar com Conta Google</span>
                        </button>
                        <button type="button" onclick="openModal('auth-modal')" class="px-4 py-3 bg-purple-900/40 hover:bg-purple-900/60 text-purple-200 hover:text-white rounded-xl text-xs sm:text-sm font-semibold border border-purple-400/30 transition-all text-center">
                            ou usar e-mail
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6 mb-6 sm:mb-8">
                <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-xl sm:rounded-2xl p-4 sm:p-6 card-hover shadow-sm">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-amber-500/20 text-amber-400 rounded-xl flex items-center justify-center">
                            <span class="text-xl sm:text-2xl">🦺</span>
                        </div>
                        <span class="text-green-500 text-xs sm:text-sm font-medium">+12%</span>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-bold text-white" id="stat-pgr">0</h3>
                    <p class="text-slate-400 text-xs sm:text-sm">PGRs Gerados</p>
                </div>
                
                <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-xl sm:rounded-2xl p-4 sm:p-6 card-hover shadow-sm">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-cyan-500/20 text-cyan-400 rounded-xl flex items-center justify-center">
                            <span class="text-xl sm:text-2xl">⚠️</span>
                        </div>
                        <span class="text-green-500 text-xs sm:text-sm font-medium">+8%</span>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-bold text-white" id="stat-apr">0</h3>
                    <p class="text-slate-400 text-xs sm:text-sm">APRs Ativas</p>
                </div>
                
                <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-xl sm:rounded-2xl p-4 sm:p-6 card-hover shadow-sm">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-rose-500/20 text-rose-400 rounded-xl flex items-center justify-center">
                            <span class="text-xl sm:text-2xl">🧤</span>
                        </div>
                        <span class="pulse-dot w-2 h-2 bg-red-500 rounded-full"></span>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-bold text-white" id="stat-epi-alert">0</h3>
                    <p class="text-slate-400 text-xs sm:text-sm">EPIs Vencendo</p>
                </div>
                
                <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-xl sm:rounded-2xl p-4 sm:p-6 card-hover shadow-sm">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-emerald-500/20 text-emerald-400 rounded-xl flex items-center justify-center">
                            <span class="text-xl sm:text-2xl">🎓</span>
                        </div>
                        <span class="text-yellow-500 text-[10px] sm:text-sm font-medium">5 pendentes</span>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-bold text-white" id="stat-train">0</h3>
                    <p class="text-slate-400 text-xs sm:text-sm">Treinamentos</p>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
                <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
                    <h3 class="font-bold text-base sm:text-lg text-white mb-4">🚀 Ações Rápidas com IA</h3>
                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                        <button onclick="showSection('pgr')" class="p-3 sm:p-4 bg-gradient-to-r from-amber-600 to-amber-700 text-white border border-amber-500/30 rounded-xl hover:shadow-lg transition-all text-left">
                            <span class="text-xl sm:text-2xl block mb-1">🦺</span>
                            <span class="font-medium text-xs sm:text-sm block">Novo PGR</span>
                            <span class="block text-[10px] sm:text-xs opacity-75">Gerado por IA</span>
                        </button>
                        <button onclick="showSection('apr')" class="p-3 sm:p-4 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-xl hover:shadow-lg transition-all text-left">
                            <span class="text-xl sm:text-2xl block mb-1">⚠️</span>
                            <span class="font-medium text-xs sm:text-sm block">Nova APR</span>
                            <span class="block text-[10px] sm:text-xs opacity-75">Gerado por IA</span>
                        </button>
                        <button onclick="showSection('epi')" class="p-3 sm:p-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl hover:shadow-lg transition-all text-left">
                            <span class="text-xl sm:text-2xl block mb-1">🧤</span>
                            <span class="font-medium text-xs sm:text-sm block">Registrar EPI</span>
                            <span class="block text-[10px] sm:text-xs opacity-75">Termo por IA</span>
                        </button>
                        <button onclick="showSection('treinamentos')" class="p-3 sm:p-4 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl hover:shadow-lg transition-all text-left">
                            <span class="text-xl sm:text-2xl block mb-1">🎓</span>
                            <span class="font-medium text-xs sm:text-sm block">Treinamento</span>
                            <span class="block text-[10px] sm:text-xs opacity-75">Certificado por IA</span>
                        </button>
                    </div>
                </div>
                
                <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
                    <h3 class="font-bold text-base sm:text-lg text-white mb-4">⚡ Alertas Importantes</h3>
                    <div class="space-y-3" id="alerts-container">
                        <div class="flex items-center gap-3 p-3 bg-slate-800/50 rounded-lg">
                            <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                            <p class="text-xs sm:text-sm text-slate-400">Nenhum alerta no momento</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- PGR Section -->
        <section id="pgr-section" class="hidden fade-in">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-2">
                        <span>🦺</span> Gerador de PGR
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">Programa de Gerenciamento de Riscos - NR-01 <span class="ai-badge text-xs px-2 py-0.5 rounded-full text-white ml-1">IA</span></p>
                </div>
                <button onclick="checkAuthAndOpen('pgr-modal')" class="w-full sm:w-auto shrink-0 bg-amber-600 hover:bg-amber-500 shadow-[0_0_15px_rgba(245,158,11,0.5)] text-white px-5 py-3 rounded-xl font-semibold transition-colors flex items-center justify-center gap-2">
                    <span>🤖</span> Gerar PGR com IA
                </button>
            </div>
            
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm mb-6">
                <h3 class="font-bold text-base sm:text-lg mb-4 text-white">PGRs Cadastrados</h3>
                <div class="overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
                    <table class="w-full min-w-[640px] text-sm">
                        <thead>
                            <tr class="text-left text-slate-400 text-xs sm:text-sm border-b border-white/10">
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
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-2">
                        <span>🏗️</span> Gerador de PCMAT
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">Programa de Condições e Meio Ambiente de Trabalho - NR-18 <span class="ai-badge text-xs px-2 py-0.5 rounded-full text-white ml-1">IA</span></p>
                </div>
                <button onclick="checkAuthAndOpen('pcmat-modal')" class="w-full sm:w-auto shrink-0 bg-cyan-600 hover:bg-cyan-500 shadow-[0_0_15px_rgba(6,182,212,0.5)] text-white px-5 py-3 rounded-xl font-semibold transition-colors flex items-center justify-center gap-2">
                    <span>🤖</span> Gerar PCMAT com IA
                </button>
            </div>
            
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
                <h3 class="font-bold text-base sm:text-lg mb-4 text-white">PCMATs Cadastrados</h3>
                <div class="overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
                    <table class="w-full min-w-[640px] text-sm">
                        <thead>
                            <tr class="text-left text-slate-400 text-xs sm:text-sm border-b border-white/10">
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
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-2">
                        <span>⚠️</span> APR Digital
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">Análise Preliminar de Risco <span class="ai-badge text-xs px-2 py-0.5 rounded-full text-white ml-1">IA</span></p>
                </div>
                <button onclick="checkAuthAndOpen('apr-modal')" class="w-full sm:w-auto shrink-0 bg-yellow-600 hover:bg-yellow-500 shadow-[0_0_15px_rgba(234,179,8,0.5)] text-white px-5 py-3 rounded-xl font-semibold transition-colors flex items-center justify-center gap-2">
                    <span>🤖</span> Gerar APR com IA
                </button>
            </div>
            
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
                <h3 class="font-bold text-base sm:text-lg mb-4 text-white">APRs Cadastradas</h3>
                <div class="overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
                    <table class="w-full min-w-[640px] text-sm">
                        <thead>
                            <tr class="text-left text-slate-400 text-xs sm:text-sm border-b border-white/10">
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
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-2">
                        <span>🧤</span> Checklist de EPI
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">Controle de Equipamentos de Proteção Individual <span class="ai-badge text-xs px-2 py-0.5 rounded-full text-white ml-1">IA</span></p>
                </div>
                <button onclick="checkAuthAndOpen('epi-modal')" class="w-full sm:w-auto shrink-0 bg-emerald-600 hover:bg-emerald-500 shadow-[0_0_15px_rgba(16,185,129,0.5)] text-white px-5 py-3 rounded-xl font-semibold transition-colors flex items-center justify-center gap-2">
                    <span>🤖</span> Registrar EPI com Termo IA
                </button>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-6 mb-6">
                <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-slate-400 text-xs sm:text-sm">Total de EPIs</span>
                        <span class="text-xl sm:text-2xl">🧤</span>
                    </div>
                    <h3 class="text-2xl sm:text-3xl font-bold text-white" id="epi-total">0</h3>
                </div>
                <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm border-l-4 border-green-500">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-slate-400 text-xs sm:text-sm">Válidos</span>
                        <span class="text-xl sm:text-2xl">✅</span>
                    </div>
                    <h3 class="text-2xl sm:text-3xl font-bold text-green-500" id="epi-valid">0</h3>
                </div>
                <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm border-l-4 border-red-500">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-slate-400 text-xs sm:text-sm">Vencidos/Vencendo</span>
                        <span class="text-xl sm:text-2xl">⚠️</span>
                    </div>
                    <h3 class="text-2xl sm:text-3xl font-bold text-red-500" id="epi-expired">0</h3>
                </div>
            </div>
            
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
                <h3 class="font-bold text-base sm:text-lg mb-4 text-white">Registro de EPIs</h3>
                <div class="overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
                    <table class="w-full min-w-[640px] text-sm">
                        <thead>
                            <tr class="text-left text-slate-400 text-xs sm:text-sm border-b border-white/10">
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
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-2">
                        <span>🎓</span> Controle de Treinamentos
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">Gestão de treinamentos obrigatórios por NR <span class="ai-badge text-xs px-2 py-0.5 rounded-full text-white ml-1">IA</span></p>
                </div>
                <button onclick="checkAuthAndOpen('treinamento-modal')" class="w-full sm:w-auto shrink-0 bg-purple-500 hover:bg-purple-600 text-white px-5 py-3 rounded-xl font-semibold transition-colors flex items-center justify-center gap-2">
                    <span>🤖</span> Novo Treinamento com Certificado IA
                </button>
            </div>
            
            <div class="bg-slate-900/40 backdrop-blur-md border border-white/5 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
                <h3 class="font-bold text-base sm:text-lg mb-4 text-white">Treinamentos Registrados</h3>
                <div class="overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
                    <table class="w-full min-w-[640px] text-sm">
                        <thead>
                            <tr class="text-left text-slate-400 text-xs sm:text-sm border-b border-white/10">
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

        </div>
    </main>

    <!-- Modal de Autenticação Unificada (Portal 4uLabs / Google) -->
    <div id="auth-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 overflow-y-auto p-3 sm:p-6">
        <div class="bg-slate-900/95 backdrop-blur-md border border-white/10 rounded-2xl p-5 sm:p-8 max-w-md w-full mx-auto relative shadow-[0_0_50px_rgba(139,92,246,0.2)] my-auto max-h-[92vh] overflow-y-auto">
            <button onclick="closeModal('auth-modal')" class="absolute top-4 right-4 text-slate-400 hover:text-white text-2xl transition-colors cursor-pointer">&times;</button>
            <div class="text-center mb-5">
                <div class="w-14 h-14 sm:w-16 sm:h-16 bg-gradient-to-tr from-purple-600 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-3.5 shadow-[0_8px_30px_rgba(124,58,237,0.4)] border border-white/10">
                    <span class="text-2xl sm:text-3xl font-extrabold text-white">4U</span>
                </div>
                <h3 class="text-xl sm:text-2xl font-bold text-white">Portal 4uLabs</h3>
                <p class="text-slate-400 text-xs sm:text-sm mt-1" id="auth-modal-subtitle">Conecte sua conta para gerenciar créditos e gerar documentos com IA.</p>
            </div>

            <!-- Botão Oficial Google OAuth 2.0 (Design Padronizado 4U) -->
            <div class="mb-4">
                <button type="button" id="googleLoginBtn" onclick="conectarGoogle()" class="w-full py-3.5 px-4 bg-white hover:bg-slate-100 active:scale-[0.99] text-slate-800 rounded-xl font-bold flex items-center justify-center gap-3 transition-all shadow-md hover:shadow-lg cursor-pointer border border-slate-200">
                    <svg viewBox="0 0 24 24" width="22" height="22" class="shrink-0">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span class="text-sm font-semibold text-slate-800">Entrar com Conta Google</span>
                </button>
            </div>

            <!-- Divisor -->
            <div class="relative flex py-2 items-center mb-4">
                <div class="flex-grow border-t border-white/10"></div>
                <span class="flex-shrink mx-3 text-[11px] text-slate-500 uppercase tracking-wider font-semibold">ou com e-mail</span>
                <div class="flex-grow border-t border-white/10"></div>
            </div>
            
            <div class="flex border-b border-white/10 mb-4">
                <button type="button" id="tab-login" onclick="switchAuthMode('login')" class="flex-1 pb-2.5 text-center font-semibold text-xs sm:text-sm border-b-2 border-purple-500 text-white transition-all cursor-pointer">Entrar</button>
                <button type="button" id="tab-register" onclick="switchAuthMode('register')" class="flex-1 pb-2.5 text-center font-semibold text-xs sm:text-sm border-b-2 border-transparent text-slate-400 hover:text-white transition-all cursor-pointer">Cadastrar</button>
            </div>
            
            <form id="auth-modal-form" onsubmit="handleAuthSubmit(event)" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">E-mail</label>
                    <input type="email" id="auth-modal-email" required placeholder="seuemail@exemplo.com" class="w-full px-3.5 py-2.5 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Senha</label>
                    <input type="password" id="auth-modal-password" required placeholder="••••••••" class="w-full px-3.5 py-2.5 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all text-sm">
                </div>
                
                <button type="submit" id="btn-auth-modal-submit" class="w-full py-3 mt-1 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-xl font-bold transition-all shadow-[0_4px_20px_rgba(124,58,237,0.3)] text-sm cursor-pointer">
                    Entrar no Portal 4uLabs
                </button>
            </form>
        </div>
    </div>

    <!-- Modal de Recarga PIX (Mercado Pago) -->
    <div id="pix-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 overflow-y-auto p-3 sm:p-6">
        <div class="bg-slate-900/90 backdrop-blur-md border border-white/10 rounded-2xl p-5 sm:p-8 max-w-md w-full mx-auto relative shadow-[0_0_50px_rgba(6,182,212,0.15)] my-auto max-h-[92vh] overflow-y-auto">
            <button onclick="closePixModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white text-2xl transition-colors">&times;</button>
            <div class="text-center mb-6">
                <h3 class="text-xl sm:text-2xl font-bold text-white flex items-center justify-center gap-2">💎 Recarregar Créditos</h3>
                <span class="inline-flex items-center gap-1.5 text-[10px] text-cyan-400 bg-cyan-500/10 px-3 py-1 rounded-full border border-cyan-500/20 mt-2 font-semibold tracking-wider">PORTAL UNIFICADO 4ULABS</span>
                <p class="text-slate-400 text-xs sm:text-sm mt-3 leading-relaxed">
                    Créditos compartilhados! Suas recargas ficam disponíveis para uso no <strong>SafeWork Pro</strong>, <strong>Keep AI</strong>, <strong>TubeMind AI</strong> e demais ferramentas.
                </p>
            </div>
            
            <!-- Packages -->
            <div class="space-y-3 mb-6" id="pix-packages-container">
                <div onclick="selectPixPackage(0)" id="pkg-0" class="p-3 sm:p-4 border-2 border-purple-500 bg-purple-500/10 rounded-2xl cursor-pointer flex items-center justify-between transition-all hover:bg-purple-500/5">
                    <div>
                        <p class="font-bold text-white text-sm sm:text-base">10 créditos</p>
                        <p class="text-[11px] text-slate-400">Pacote Bronze</p>
                    </div>
                    <span class="font-bold text-purple-400 text-sm sm:text-base">R$ 4,90</span>
                </div>
                <div onclick="selectPixPackage(1)" id="pkg-1" class="p-3 sm:p-4 border border-white/10 bg-slate-950/20 rounded-2xl cursor-pointer flex items-center justify-between transition-all hover:bg-white/5">
                    <div>
                        <p class="font-bold text-white text-sm sm:text-base">50 créditos</p>
                        <p class="text-[11px] text-slate-400">Pacote Prata</p>
                    </div>
                    <span class="font-bold text-slate-300 text-sm sm:text-base">R$ 19,90</span>
                </div>
                <div onclick="selectPixPackage(2)" id="pkg-2" class="p-3 sm:p-4 border border-white/10 bg-slate-950/20 rounded-2xl cursor-pointer flex items-center justify-between transition-all hover:bg-white/5">
                    <div>
                        <p class="font-bold text-white text-sm sm:text-base">100 créditos</p>
                        <p class="text-[11px] text-slate-400">Pacote Ouro</p>
                    </div>
                    <span class="font-bold text-slate-300 text-sm sm:text-base">R$ 34,90</span>
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
    <div id="pgr-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 overflow-y-auto p-2 sm:p-6">
        <div class="bg-slate-900/95 backdrop-blur-md border border-white/10 rounded-2xl p-4 sm:p-6 lg:p-8 max-w-4xl w-full mx-auto max-h-[92vh] overflow-y-auto my-auto shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-xl sm:text-2xl font-bold text-white flex items-center gap-2">
                        <span>🦺</span> Novo PGR
                    </h3>
                    <p class="text-xs sm:text-sm text-purple-400 mt-0.5">Documento será gerado automaticamente pela IA</p>
                </div>
                <button onclick="closeModal('pgr-modal')" class="text-slate-400 hover:text-white text-2xl transition-colors">&times;</button>
            </div>
            <form id="pgr-form" onsubmit="savePGR(event)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Empresa</label>
                        <input type="text" name="empresa" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">CNPJ</label>
                        <input type="text" name="cnpj" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Endereço</label>
                        <input type="text" name="endereco" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Ramo de Atividade</label>
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
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Nº de Funcionários</label>
                        <input type="number" name="funcionarios" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Responsável Técnico</label>
                        <input type="text" name="responsavel" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">CREA/Registro</label>
                        <input type="text" name="crea" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Riscos Identificados</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3">
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="riscos" value="Físico" class="w-4 h-4 text-orange-500">
                                <span class="text-xs sm:text-sm">Físico</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="riscos" value="Químico" class="w-4 h-4 text-orange-500">
                                <span class="text-xs sm:text-sm">Químico</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="riscos" value="Biológico" class="w-4 h-4 text-orange-500">
                                <span class="text-xs sm:text-sm">Biológico</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="riscos" value="Ergonômico" class="w-4 h-4 text-orange-500">
                                <span class="text-xs sm:text-sm">Ergonômico</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="riscos" value="Acidente" class="w-4 h-4 text-orange-500">
                                <span class="text-xs sm:text-sm">Acidente</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="riscos" value="Altura" class="w-4 h-4 text-orange-500">
                                <span class="text-xs sm:text-sm">Altura</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="riscos" value="Elétrico" class="w-4 h-4 text-orange-500">
                                <span class="text-xs sm:text-sm">Elétrico</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="riscos" value="Mecânico" class="w-4 h-4 text-orange-500">
                                <span class="text-xs sm:text-sm">Mecânico</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Informações Adicionais (opcional)</label>
                        <textarea name="plano_acao" rows="3" class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent" placeholder="Adicione informações específicas que a IA deve considerar..."></textarea>
                    </div>
                </div>
                
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 mt-6 sm:mt-8">
                    <button type="button" onclick="closeModal('pgr-modal')" class="w-full sm:w-auto px-6 py-3 border border-white/10 rounded-xl text-slate-400 hover:bg-white/5 text-center">Cancelar</button>
                    <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-orange-500 to-purple-600 text-white rounded-xl hover:shadow-lg font-medium flex items-center justify-center gap-2">
                        <span>🤖</span> Gerar PGR com IA
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal PCMAT -->
    <div id="pcmat-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 overflow-y-auto p-2 sm:p-6">
        <div class="bg-slate-900/95 backdrop-blur-md border border-white/10 rounded-2xl p-4 sm:p-6 lg:p-8 max-w-4xl w-full mx-auto max-h-[92vh] overflow-y-auto my-auto shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-xl sm:text-2xl font-bold text-white flex items-center gap-2">
                        <span>🏗️</span> Novo PCMAT
                    </h3>
                    <p class="text-xs sm:text-sm text-purple-400 mt-0.5">Documento será gerado automaticamente pela IA</p>
                </div>
                <button onclick="closeModal('pcmat-modal')" class="text-slate-400 hover:text-white text-2xl transition-colors">&times;</button>
            </div>
            <form id="pcmat-form" onsubmit="savePCMAT(event)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Nome da Obra</label>
                        <input type="text" name="obra" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Contratante</label>
                        <input type="text" name="contratante" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Endereço da Obra</label>
                        <input type="text" name="endereco" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Tipo de Obra</label>
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
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Nº de Trabalhadores</label>
                        <input type="number" name="trabalhadores" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Data Início</label>
                        <input type="date" name="data_inicio" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Previsão Término</label>
                        <input type="date" name="data_fim" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Engenheiro Responsável</label>
                        <input type="text" name="engenheiro" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">CREA</label>
                        <input type="text" name="crea" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Funções na Obra</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3">
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="funcoes" value="Pedreiro" class="w-4 h-4 text-blue-500">
                                <span class="text-xs sm:text-sm">Pedreiro</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="funcoes" value="Eletricista" class="w-4 h-4 text-blue-500">
                                <span class="text-xs sm:text-sm">Eletricista</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="funcoes" value="Carpinteiro" class="w-4 h-4 text-blue-500">
                                <span class="text-xs sm:text-sm">Carpinteiro</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="funcoes" value="Armador" class="w-4 h-4 text-blue-500">
                                <span class="text-xs sm:text-sm">Armador</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="funcoes" value="Pintor" class="w-4 h-4 text-blue-500">
                                <span class="text-xs sm:text-sm">Pintor</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="funcoes" value="Servente" class="w-4 h-4 text-blue-500">
                                <span class="text-xs sm:text-sm">Servente</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="funcoes" value="Encanador" class="w-4 h-4 text-blue-500">
                                <span class="text-xs sm:text-sm">Encanador</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="funcoes" value="Operador" class="w-4 h-4 text-blue-500">
                                <span class="text-xs sm:text-sm">Operador</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Informações Adicionais (opcional)</label>
                        <textarea name="medidas" rows="3" class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-blue-500" placeholder="Adicione informações específicas que a IA deve considerar..."></textarea>
                    </div>
                </div>
                
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 mt-6 sm:mt-8">
                    <button type="button" onclick="closeModal('pcmat-modal')" class="w-full sm:w-auto px-6 py-3 border border-white/10 rounded-xl text-slate-400 hover:bg-white/5 text-center">Cancelar</button>
                    <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl hover:shadow-lg font-medium flex items-center justify-center gap-2">
                        <span>🤖</span> Gerar PCMAT com IA
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal APR -->
    <div id="apr-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 overflow-y-auto p-2 sm:p-6">
        <div class="bg-slate-900/95 backdrop-blur-md border border-white/10 rounded-2xl p-4 sm:p-6 lg:p-8 max-w-4xl w-full mx-auto max-h-[92vh] overflow-y-auto my-auto shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-xl sm:text-2xl font-bold text-white flex items-center gap-2">
                        <span>⚠️</span> Nova APR
                    </h3>
                    <p class="text-xs sm:text-sm text-purple-400 mt-0.5">Análise de riscos detalhada gerada pela IA</p>
                </div>
                <button onclick="closeModal('apr-modal')" class="text-slate-400 hover:text-white text-2xl transition-colors">&times;</button>
            </div>
            <form id="apr-form" onsubmit="saveAPR(event)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Atividade</label>
                        <input type="text" name="atividade" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-yellow-500">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Local</label>
                        <input type="text" name="local" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-yellow-500">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Responsável</label>
                        <input type="text" name="responsavel" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-yellow-500">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Data</label>
                        <input type="date" name="data" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-yellow-500">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Tipo de Serviço</label>
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
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Nível de Risco Estimado</label>
                        <select name="nivel_risco" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-yellow-500">
                            <option value="">Selecione...</option>
                            <option value="Baixo">Baixo</option>
                            <option value="Médio">Médio</option>
                            <option value="Alto">Alto</option>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Descrição dos Riscos (a IA irá detalhar)</label>
                        <textarea name="riscos" rows="2" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-yellow-500" placeholder="Descreva brevemente os riscos da atividade..."></textarea>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Medidas de Controle Sugeridas (a IA irá complementar)</label>
                        <textarea name="medidas" rows="2" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-yellow-500" placeholder="Descreva as medidas de controle iniciais..."></textarea>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">EPIs Obrigatórios</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3">
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="epis" value="Capacete" class="w-4 h-4 text-yellow-500">
                                <span class="text-xs sm:text-sm">Capacete</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="epis" value="Óculos" class="w-4 h-4 text-yellow-500">
                                <span class="text-xs sm:text-sm">Óculos</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="epis" value="Luvas" class="w-4 h-4 text-yellow-500">
                                <span class="text-xs sm:text-sm">Luvas</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="epis" value="Botina" class="w-4 h-4 text-yellow-500">
                                <span class="text-xs sm:text-sm">Botina</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="epis" value="Cinto" class="w-4 h-4 text-yellow-500">
                                <span class="text-xs sm:text-sm">Cinto Segurança</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="epis" value="Protetor Auricular" class="w-4 h-4 text-yellow-500">
                                <span class="text-xs sm:text-sm">Protetor Auricular</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="epis" value="Máscara" class="w-4 h-4 text-yellow-500">
                                <span class="text-xs sm:text-sm">Máscara</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5">
                                <input type="checkbox" name="epis" value="Uniforme" class="w-4 h-4 text-yellow-500">
                                <span class="text-xs sm:text-sm">Uniforme</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Assinatura do Responsável</label>
                        <input type="text" name="assinatura" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-yellow-500" placeholder="Nome completo">
                    </div>
                </div>
                
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 mt-6 sm:mt-8">
                    <button type="button" onclick="closeModal('apr-modal')" class="w-full sm:w-auto px-6 py-3 border border-white/10 rounded-xl text-slate-400 hover:bg-white/5 text-center">Cancelar</button>
                    <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-yellow-500 to-purple-600 text-white rounded-xl hover:shadow-lg font-medium flex items-center justify-center gap-2">
                        <span>🤖</span> Gerar APR com IA
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal EPI -->
    <div id="epi-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 overflow-y-auto p-2 sm:p-6">
        <div class="bg-slate-900/95 backdrop-blur-md border border-white/10 rounded-2xl p-4 sm:p-6 lg:p-8 max-w-2xl w-full mx-auto max-h-[92vh] overflow-y-auto my-auto shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-xl sm:text-2xl font-bold text-white flex items-center gap-2">
                        <span>🧤</span> Registrar EPI
                    </h3>
                    <p class="text-xs sm:text-sm text-purple-400 mt-0.5">Termo de responsabilidade gerado pela IA</p>
                </div>
                <button onclick="closeModal('epi-modal')" class="text-slate-400 hover:text-white text-2xl transition-colors">&times;</button>
            </div>
            <form id="epi-form" onsubmit="saveEPI(event)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Funcionário</label>
                        <input type="text" name="funcionario" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">CPF</label>
                        <input type="text" name="cpf" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Tipo de EPI</label>
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
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Número do CA</label>
                        <input type="text" name="ca" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-green-500" placeholder="Certificado de Aprovação">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Data de Entrega</label>
                        <input type="date" name="data_entrega" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Data de Validade</label>
                        <input type="date" name="validade" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-green-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Observações</label>
                        <textarea name="observacoes" rows="2" class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-green-500"></textarea>
                    </div>
                </div>
                
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 mt-6 sm:mt-8">
                    <button type="button" onclick="closeModal('epi-modal')" class="w-full sm:w-auto px-6 py-3 border border-white/10 rounded-xl text-slate-400 hover:bg-white/5 text-center">Cancelar</button>
                    <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-green-500 to-purple-600 text-white rounded-xl hover:shadow-lg font-medium flex items-center justify-center gap-2">
                        <span>🤖</span> Registrar com Termo IA
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Treinamento -->
    <div id="treinamento-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 overflow-y-auto p-2 sm:p-6">
        <div class="bg-slate-900/95 backdrop-blur-md border border-white/10 rounded-2xl p-4 sm:p-6 lg:p-8 max-w-2xl w-full mx-auto max-h-[92vh] overflow-y-auto my-auto shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-xl sm:text-2xl font-bold text-white flex items-center gap-2">
                        <span>🎓</span> Novo Treinamento
                    </h3>
                    <p class="text-xs sm:text-sm text-purple-400 mt-0.5">Certificado com conteúdo programático gerado pela IA</p>
                </div>
                <button onclick="closeModal('treinamento-modal')" class="text-slate-400 hover:text-white text-2xl transition-colors">&times;</button>
            </div>
            <form id="treinamento-form" onsubmit="saveTreinamento(event)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Funcionário</label>
                        <input type="text" name="funcionario" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">CPF</label>
                        <input type="text" name="cpf" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Treinamento</label>
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
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Carga Horária</label>
                        <input type="number" name="carga_horaria" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-purple-500" placeholder="Horas">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Data de Realização</label>
                        <input type="date" name="data_realizacao" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Data de Validade</label>
                        <input type="date" name="validade" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs sm:text-sm font-medium text-slate-300 mb-2">Instrutor/Empresa</label>
                        <input type="text" name="instrutor" required class="w-full px-4 py-3 border border-white/10 bg-slate-950/50 text-white rounded-xl focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>
                
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 mt-6 sm:mt-8">
                    <button type="button" onclick="closeModal('treinamento-modal')" class="w-full sm:w-auto px-6 py-3 border border-white/10 rounded-xl text-slate-400 hover:bg-white/5 text-center">Cancelar</button>
                    <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl hover:shadow-lg font-medium flex items-center justify-center gap-2">
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

        try {
            const cachedProfile = localStorage.getItem('user_profile');
            if (cachedProfile && userToken) {
                userData = JSON.parse(cachedProfile);
                userCredits = parseInt(localStorage.getItem('user_credits') || '0');
            }
        } catch (e) {}

        const GOOGLE_ICON_SVG = '<svg viewBox="0 0 24 24" width="18" height="18" class="shrink-0" style="display:inline-block;vertical-align:middle;"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>';
        
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
                localStorage.setItem('user_profile', JSON.stringify(result.user));
                localStorage.setItem('user_credits', String(userCredits));
                updateCreditsUI();
                updateAuthUI();
            } catch (err) {
                console.error('Erro de sincronização de conta:', err);
                // Token inválido, limpa local
                userToken = null;
                localStorage.removeItem('keepai_token');
                localStorage.removeItem('user_profile');
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
            
            // Atualiza badge mobile
            const mobileCount = document.getElementById('mobile-credits-count');
            if (mobileCount) {
                mobileCount.textContent = `${userCredits} cr`;
            }
            
            // Atualiza o dashboard AI Status
            const aiStatusBanner = document.getElementById('ai-status-banner');
            if (aiStatusBanner) {
                if (userToken) {
                    const userName = (userData && (userData.display_name || userData.name || userData.email)) || 'Usuário';
                    aiStatusBanner.innerHTML = `
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 w-full">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 sm:w-16 sm:h-16 bg-slate-900/50 border border-white/20 rounded-2xl flex items-center justify-center shrink-0 shadow-lg">
                                    <span class="text-3xl sm:text-4xl">🤖</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-lg sm:text-xl font-bold text-white">Inteligência Artificial Pronta</h3>
                                        <span class="bg-emerald-500/20 text-emerald-300 text-[11px] font-semibold px-2.5 py-0.5 rounded-full border border-emerald-500/30">ONLINE</span>
                                    </div>
                                    <p class="text-xs sm:text-sm text-purple-100 mt-1">
                                        Conectado como <strong class="text-white">${userName}</strong> • Saldo unificado: <strong class="text-amber-300 font-bold">${userCredits} créditos</strong>
                                    </p>
                                </div>
                            </div>
                            <button type="button" onclick="openModal('pix-modal')" class="px-4 py-2.5 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-bold rounded-xl text-sm transition-all shadow-lg flex items-center justify-center gap-2 cursor-pointer shrink-0">
                                <span>💎</span>
                                <span>Recarregar Créditos</span>
                            </button>
                        </div>
                    `;
                } else {
                    aiStatusBanner.innerHTML = `
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5 w-full">
                            <div class="flex items-start sm:items-center gap-4">
                                <div class="w-14 h-14 sm:w-16 sm:h-16 bg-slate-900/50 border border-white/20 rounded-2xl flex items-center justify-center shrink-0 shadow-lg">
                                    <span class="text-3xl sm:text-4xl">🤖</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-lg sm:text-xl font-bold text-white">Inteligência Artificial Ativa</h3>
                                        <span class="bg-amber-400/20 text-amber-300 text-[11px] font-semibold px-2.5 py-0.5 rounded-full border border-amber-400/30 uppercase tracking-wide">PGR • PCMAT • APR</span>
                                    </div>
                                    <p class="text-xs sm:text-sm text-purple-100 mt-1 max-w-xl">
                                        Crie documentos técnicos completos de Engenharia e Segurança do Trabalho com IA. Conecte sua conta Google para começar.
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                                <button type="button" onclick="conectarGoogle()" class="px-5 py-3 bg-white hover:bg-slate-100 active:scale-[0.98] text-slate-800 rounded-xl font-bold text-sm flex items-center justify-center gap-2.5 shadow-xl transition-all cursor-pointer border border-white/40">
                                    ${GOOGLE_ICON_SVG}
                                    <span>Entrar com Conta Google</span>
                                </button>
                                <button type="button" onclick="openModal('auth-modal')" class="px-4 py-3 bg-purple-900/40 hover:bg-purple-900/60 text-purple-200 hover:text-white rounded-xl text-xs sm:text-sm font-semibold border border-purple-400/30 transition-all text-center">
                                    ou usar e-mail
                                </button>
                            </div>
                        </div>
                    `;
                }
            }
        }
        
        function updateAuthUI() {
            const footerContainer = document.getElementById('sidebar-footer-container');
            const desktopHeaderAuth = document.getElementById('desktop-header-auth');
            
            if (userToken && userData) {
                const name = userData.display_name || userData.name || userData.email || 'Usuário';
                const initials = name.substring(0, 2).toUpperCase();
                const avatarHtml = userData.photo_url 
                    ? `<img src="${userData.photo_url}" class="w-10 h-10 rounded-full object-cover border border-purple-500/40 shrink-0 shadow-[0_0_10px_rgba(168,85,247,0.3)]" alt="${name}">`
                    : `<div class="w-10 h-10 bg-gradient-to-tr from-purple-500 to-indigo-500 rounded-full flex items-center justify-center font-bold text-white shadow-[0_0_10px_rgba(168,85,247,0.4)] shrink-0">${initials}</div>`;
                
                if (footerContainer) {
                    footerContainer.innerHTML = `
                        <div class="flex items-center justify-between w-full">
                            <div class="flex items-center gap-2.5 min-w-0">
                                ${avatarHtml}
                                <div class="min-w-0">
                                    <p class="font-medium text-sm text-white truncate" title="${name}">${name}</p>
                                    <p class="text-[10px] text-purple-400 font-semibold flex items-center gap-1 cursor-pointer hover:underline" onclick="openModal('pix-modal')">
                                        <span>💎</span> <span>${userCredits} créditos</span>
                                    </p>
                                </div>
                            </div>
                            <button onclick="handleLogout()" class="text-slate-400 hover:text-red-400 transition-colors p-1.5 rounded-lg hover:bg-white/5 cursor-pointer shrink-0" title="Sair da Conta">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            </button>
                        </div>
                    `;
                }

                if (desktopHeaderAuth) {
                    desktopHeaderAuth.innerHTML = `
                        <div class="flex items-center gap-2.5 bg-slate-900/80 border border-white/10 px-3 py-1.5 rounded-xl backdrop-blur-md">
                            ${avatarHtml}
                            <div class="text-left text-xs min-w-0">
                                <p class="font-bold text-white truncate max-w-[130px]">${name}</p>
                                <p class="text-purple-300 font-medium">${userCredits} créditos</p>
                            </div>
                            <button type="button" onclick="openModal('pix-modal')" class="px-2.5 py-1 bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 rounded-lg font-semibold text-xs transition-colors cursor-pointer" title="Comprar Créditos">
                                + Créditos
                            </button>
                            <button type="button" onclick="handleLogout()" class="text-slate-400 hover:text-red-400 p-1 transition-colors" title="Desconectar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            </button>
                        </div>
                    `;
                }
            } else {
                if (footerContainer) {
                    footerContainer.innerHTML = `
                        <div class="flex flex-col gap-2.5 w-full">
                            <p class="text-[11px] text-slate-400 text-center font-medium">Acesse com sua conta</p>
                            <button type="button" onclick="conectarGoogle()" class="w-full py-2.5 px-3 bg-white hover:bg-slate-100 active:scale-[0.98] text-slate-800 rounded-xl font-bold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-lg transition-all cursor-pointer">
                                ${GOOGLE_ICON_SVG}
                                <span>Entrar com Google</span>
                            </button>
                            <button type="button" onclick="openModal('auth-modal')" class="text-[11px] text-purple-300 hover:text-white text-center transition-colors">
                                ou usar e-mail e senha
                            </button>
                        </div>
                    `;
                }

                if (desktopHeaderAuth) {
                    desktopHeaderAuth.innerHTML = `
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="conectarGoogle()" class="px-3.5 py-2 bg-white hover:bg-slate-100 active:scale-[0.98] text-slate-800 rounded-xl font-bold text-xs sm:text-sm flex items-center gap-2 shadow-md transition-all cursor-pointer border border-slate-200">
                                ${GOOGLE_ICON_SVG}
                                <span>Entrar com Google</span>
                            </button>
                            <button type="button" onclick="openModal('auth-modal')" class="px-3 py-2 bg-slate-900/80 hover:bg-slate-800 text-purple-300 hover:text-white rounded-xl font-medium text-xs border border-white/10 transition-colors">
                                E-mail
                            </button>
                        </div>
                    `;
                }
            }
            updateCreditsUI();
        }
        
        function handleLogout() {
            if (confirm('Deseja desconectar sua conta do Portal 4uLabs?')) {
                userToken = null;
                localStorage.removeItem('keepai_token');
                localStorage.removeItem('user_profile');
                localStorage.removeItem('user_role');
                userData = null;
                userCredits = 0;
                updateAuthUI();
                alert('Conta desconectada com sucesso.');
            }
        }
        
        function checkAuthAndOpen(modalId) {
            if (!userToken) {
                alert('🔑 Você precisa conectar sua conta para gerar documentos com IA.');
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

        // --- GOOGLE OAUTH 2.0 INTEGRATION (ECOSSISTEMA 4ULABS) ---
        const GOOGLE_CLIENT_ID = '569266864432-pd09jbb5no9ekdhdr018fj643nopp817.apps.googleusercontent.com';
        let googleTokenClient = null;

        function initGoogleAuth() {
            if (typeof google === 'undefined' || !google.accounts || !google.accounts.oauth2) {
                setTimeout(initGoogleAuth, 300);
                return;
            }
            try {
                googleTokenClient = google.accounts.oauth2.initTokenClient({
                    client_id: GOOGLE_CLIENT_ID,
                    scope: 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email openid',
                    callback: async (response) => {
                        if (response && response.access_token) {
                            await processarGoogleToken(response.access_token);
                        }
                    },
                    error_callback: (err) => {
                        console.error('[GoogleAuth] Erro no GSI:', err);
                    }
                });
            } catch (e) {
                console.warn('[GoogleAuth] Erro ao instanciar initTokenClient:', e);
            }
        }

        function conectarGoogle() {
            if (googleTokenClient) {
                googleTokenClient.requestAccessToken({ prompt: 'select_account' });
                return;
            }
            if (typeof google !== 'undefined' && google.accounts && google.accounts.oauth2) {
                try {
                    initGoogleAuth();
                    if (googleTokenClient) {
                        googleTokenClient.requestAccessToken({ prompt: 'select_account' });
                        return;
                    }
                } catch (e) {
                    console.error('[GoogleAuth] Erro ao conectar:', e);
                }
            }
            alert('Inicializando serviços do Google... Por favor, clique novamente em instantes.');
        }

        async function processarGoogleToken(accessToken) {
            showLoading('Autenticando com sua Conta Google...');
            try {
                const userInfoRes = await fetch('https://www.googleapis.com/oauth2/v3/userinfo', {
                    headers: { Authorization: `Bearer ${accessToken}` }
                });
                if (!userInfoRes.ok) throw new Error('Falha ao obter perfil do Google');
                const googleUser = await userInfoRes.json();

                const backendRes = await fetch('/app/keepai/api/auth.php?action=google', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        access_token: accessToken,
                        email: googleUser.email,
                        name: googleUser.name || googleUser.given_name || 'Usuário Google',
                        picture: googleUser.picture || ''
                    })
                });

                const data = await backendRes.json();
                if (!data.success) {
                    throw new Error(data.error || 'Erro na autenticação central');
                }

                userToken = data.token;
                userData = data.user;
                userCredits = parseInt(data.user.credits || 0);

                localStorage.setItem('keepai_token', data.token);
                localStorage.setItem('user_profile', JSON.stringify(data.user));
                localStorage.setItem('user_credits', String(userCredits));

                const emailLower = (data.user.email || '').toLowerCase().trim();
                const isAdmin = (emailLower === 'fbr4g4@gmail.com' || emailLower === 'fb4g4@gmail.com');
                if (isAdmin) {
                    localStorage.setItem('user_role', 'admin');
                }

                updateAuthUI();
                updateCreditsUI();
                closeModal('auth-modal');
                hideLoading();
                alert(`🎉 Bem-vindo(a), ${data.user.display_name || data.user.email}!`);
            } catch (err) {
                hideLoading();
                console.error('[GoogleAuth] Erro no login Google:', err);
                alert('❌ Erro no login Google: ' + err.message);
            }
        }

        // --- AUTHENTICATION MODAL LOGIC (EMAIL/SENHA) ---
        let authModalMode = 'login';
        
        function switchAuthMode(mode) {
            authModalMode = mode;
            const tabLogin = document.getElementById('tab-login');
            const tabReg = document.getElementById('tab-register');
            const btnSubmit = document.getElementById('btn-auth-modal-submit');
            const subtitle = document.getElementById('auth-modal-subtitle');
            
            if (mode === 'login') {
                tabLogin.className = 'flex-1 pb-2.5 text-center font-semibold text-xs sm:text-sm border-b-2 border-purple-500 text-white transition-all cursor-pointer';
                tabReg.className = 'flex-1 pb-2.5 text-center font-semibold text-xs sm:text-sm border-b-2 border-transparent text-slate-400 hover:text-white transition-all cursor-pointer';
                btnSubmit.textContent = 'Entrar no Portal 4uLabs';
                subtitle.textContent = 'Conecte sua conta para gerenciar créditos e gerar documentos com IA.';
            } else {
                tabReg.className = 'flex-1 pb-2.5 text-center font-semibold text-xs sm:text-sm border-b-2 border-purple-500 text-white transition-all cursor-pointer';
                tabLogin.className = 'flex-1 pb-2.5 text-center font-semibold text-xs sm:text-sm border-b-2 border-transparent text-slate-400 hover:text-white transition-all cursor-pointer';
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
                localStorage.setItem('user_profile', JSON.stringify(result.user));
                localStorage.setItem('user_credits', String(userCredits));
                
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

        // Mobile Sidebar Toggle
        function toggleSidebar(open) {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            if (!sidebar) return;
            if (open === undefined) {
                open = sidebar.classList.contains('-translate-x-full');
            }
            if (open) {
                sidebar.classList.remove('-translate-x-full');
                if (backdrop) backdrop.classList.remove('hidden');
                document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
            } else {
                sidebar.classList.add('-translate-x-full');
                if (backdrop) backdrop.classList.add('hidden');
                document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
            }
        }

        // Navigation
        function showSection(section) {
            document.querySelectorAll('section[id$="-section"]').forEach(s => s.classList.add('hidden'));
            const targetSection = document.getElementById(`${section}-section`);
            if (targetSection) {
                targetSection.classList.remove('hidden');
            }
            
            document.querySelectorAll('.sidebar-item').forEach(item => {
                item.classList.remove('active');
                if (item.dataset.section === section) {
                    item.classList.add('active');
                }
            });

            // Update mobile header current section title
            const sectionTitles = {
                'dashboard': 'Dashboard',
                'pgr': 'PGR (NR-01)',
                'pcmat': 'PCMAT (NR-18)',
                'apr': 'APR Digital',
                'epi': 'Controle de EPI',
                'treinamentos': 'Treinamentos'
            };
            const mobileSectionEl = document.getElementById('mobile-current-section');
            if (mobileSectionEl && sectionTitles[section]) {
                mobileSectionEl.textContent = sectionTitles[section];
            }

            // Close mobile sidebar if open
            toggleSidebar(false);
            
            updateStats();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Modal Functions
        function openModal(id) {
            if (id === 'pix-modal' && !userToken) {
                alert('🔑 Conecte sua conta com Google ou e-mail antes de recarregar créditos.');
                openModal('auth-modal');
                return;
            }
            const el = document.getElementById(id);
            if (el) {
                el.classList.remove('hidden');
                el.classList.add('flex');
            }
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
            
            // Inicializa Google OAuth 2.0
            initGoogleAuth();
            
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
    <!-- ECALC SUITE UNIFIED ARCHITECTURE -->
    <script src="../assets/suite/suite-nav.js?v=20260929_3"></script>
</body>
</html>
