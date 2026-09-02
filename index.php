<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
$v = time();
?>
<!DOCTYPE html>
<html lang="pt-br" class="scroll-smooth">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>

    <title>Ferramentas de Engenharia | 4U.IA.BR</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Orbitron:wght@400;500;700;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="ΞCALC - Ferramentas de Engenharia">
    <meta property="og:description" content="Plataforma completa com calculadoras e dimensionamentos técnicos profissionais.">
    
    <style>
        :root {
            --primary: #00D2FF; /* Electric Cyan */
            --secondary: #0066FF; /* Electric Blue */
            --accent: #00D2FF; /* Electric Cyan */
            --bg-dark: #030508; /* OLED-Black (PowerCalc style) */
            --bg-card: rgba(12, 17, 26, 0.75); /* Deep Dark Blue Slate */
            --text-main: #ffffff;
            --text-muted: #7d90a6;
            --transition-speed: 250ms;
            --transition-curve: cubic-bezier(0.16, 1, 0.3, 1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            background: radial-gradient(circle at center, #0c121d 0%, #030508 100%);
            color: var(--text-main);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Animated Background */
        .bg-animated {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: 
                radial-gradient(circle at 15% 50%, rgba(0, 210, 255, 0.05) 0%, transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(0, 102, 255, 0.05) 0%, transparent 25%),
                radial-gradient(ellipse at 50% 80%, rgba(0, 210, 255, 0.03) 0%, transparent 60%);
        }

        .bg-grid {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background-image: 
                linear-gradient(rgba(0, 210, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 210, 255, 0.02) 1px, transparent 1px);
            background-size: 50px 50px;
            mask-image: linear-gradient(to bottom, black 40%, transparent 100%);
            -webkit-mask-image: linear-gradient(to bottom, black 40%, transparent 100%);
        }

        /* Floating particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: rgba(0, 210, 255, 0.4);
            border-radius: 50%;
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% { transform: translateY(100vh) translateX(0); opacity: 0; }
            10% { opacity: 0.5; }
            90% { opacity: 0.5; }
            100% { transform: translateY(-20vh) translateX(20px); opacity: 0; }
        }

        /* Hero Typography */
        .hero-title {
            font-family: 'Orbitron', sans-serif;
            background: linear-gradient(135deg, #00D2FF 0%, #0066FF 50%, #00D2FF 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            filter: drop-shadow(0 0 30px rgba(0, 210, 255, 0.35));
        }

        .hero-title::after {
            content: 'ΞCΛLC';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            background: inherit;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: blur(40px);
            opacity: 0.45;
            z-index: -1;
            width: 100%;
        }

        .badge-count {
            background: rgba(0, 210, 255, 0.08);
            border: 1px solid rgba(0, 210, 255, 0.3);
            color: #00D2FF;
            backdrop-filter: blur(8px);
            box-shadow: 0 0 15px rgba(0, 210, 255, 0.12);
        }

        /* Inputs */
        .search-input {
            background: rgba(3, 5, 8, 0.65);
            border: 1px solid rgba(0, 210, 255, 0.25);
            backdrop-filter: blur(12px);
            transition: all var(--transition-speed) var(--transition-curve);
            color: #ffffff;
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(0, 210, 255, 0.2), 0 0 20px rgba(0, 210, 255, 0.15);
            background: rgba(3, 5, 8, 0.85);
        }

        /* Filters */
        .filter-btn {
            background: rgba(12, 17, 26, 0.4);
            border: 1px solid rgba(0, 210, 255, 0.15);
            color: #c2d2e3;
            transition: all var(--transition-speed) ease;
        }

        .filter-btn:hover {
            background: rgba(0, 210, 255, 0.08);
            border-color: rgba(0, 210, 255, 0.45);
            color: #ffffff;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, rgba(0, 102, 255, 0.2) 0%, rgba(0, 210, 255, 0.2) 100%);
            border-color: #00D2FF;
            color: #00D2FF;
            box-shadow: 0 0 15px rgba(0, 210, 255, 0.2);
        }

        /* Cards */
        /* Cards */
        .tool-card {
            background: rgba(15, 23, 42, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 24px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.4);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }

        /* Subtle glowing dots on hover */
        .tool-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(800px circle at var(--mouse-x, 0) var(--mouse-y, 0), rgba(255, 255, 255, 0.06), transparent 40%);
            z-index: 1;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        .tool-card:hover::before {
            opacity: 1;
        }

        .tool-card:hover {
            transform: translateY(-8px);
        }

        /* Category-based glowing on hover */
        .tool-card[data-category="estrutural"]:hover {
            border-color: rgba(59, 130, 246, 0.4);
            box-shadow: 0 12px 40px rgba(59, 130, 246, 0.15), 0 0 20px rgba(59, 130, 246, 0.1);
        }

        .tool-card[data-category="hidraulica"]:hover {
            border-color: rgba(6, 182, 212, 0.4);
            box-shadow: 0 12px 40px rgba(6, 182, 212, 0.15), 0 0 20px rgba(6, 182, 212, 0.1);
        }

        .tool-card[data-category="materiais"]:hover {
            border-color: rgba(16, 185, 129, 0.4);
            box-shadow: 0 12px 40px rgba(16, 185, 129, 0.15), 0 0 20px rgba(16, 185, 129, 0.1);
        }

        .tool-card[data-category="eletrica"]:hover {
            border-color: rgba(245, 158, 11, 0.4);
            box-shadow: 0 12px 40px rgba(245, 158, 11, 0.15), 0 0 20px rgba(245, 158, 11, 0.1);
        }

        .tool-card[data-category="orcamento"]:hover {
            border-color: rgba(34, 197, 94, 0.4);
            box-shadow: 0 12px 40px rgba(34, 197, 94, 0.15), 0 0 20px rgba(34, 197, 94, 0.1);
        }

        .tool-card[data-category="outros"]:hover {
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.15), 0 0 20px rgba(99, 102, 241, 0.1);
        }

        .tool-icon-wrapper {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(6, 182, 212, 0.05) 100%);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .tool-card:hover .tool-icon-wrapper {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(6, 182, 212, 0.1) 100%);
            border-color: rgba(59, 130, 246, 0.2);
            transform: scale(1.1) rotate(5deg);
        }

        .tool-category {
            background: rgba(30, 58, 138, 0.4);
            color: #93c5fd;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        /* Back to top */
        .back-to-top {
            background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #020617;
        }
        ::-webkit-scrollbar-thumb {
            background: #1e293b;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #334155;
        }

        /* Rodapé Estilo Premium 4U.IA.BR */
        .footer-clean { position: relative; padding: 2rem 0; color: #4b5563; }
        .footer-link-group { display: flex; align-items: center; justify-content: center; gap: 1rem; margin-top: 0.5rem; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 500; }
        .footer-dot { width: 3px; height: 3px; border-radius: 50%; background: rgba(59, 130, 246, 0.2); }
        .footer-a { transition: all 0.2s; text-decoration: none; color: inherit; }
        .footer-a:hover { color: #60a5fa; opacity: 1; }

        /* Featured Cards: Cyan (PowerCalc Premium, Gerador de Contratos) */
        .tool-card.featured-cyan {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.15) 0%, rgba(30, 41, 59, 0.4) 100%) !important;
            border-color: rgba(6, 182, 212, 0.35) !important;
            box-shadow: 0 0 15px rgba(6, 182, 212, 0.15) !important;
        }
        .tool-card.featured-cyan:hover {
            border-color: rgba(6, 182, 212, 0.6) !important;
            box-shadow: 0 12px 40px rgba(6, 182, 212, 0.25), 0 0 20px rgba(6, 182, 212, 0.15) !important;
        }
        .featured-cyan .tool-icon-wrapper {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.2) 0%, rgba(30, 41, 59, 0.1) 100%) !important;
            border-color: rgba(6, 182, 212, 0.35) !important;
        }
        .featured-cyan:hover .tool-icon-wrapper {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.3) 0%, rgba(30, 41, 59, 0.15) 100%) !important;
            border-color: rgba(6, 182, 212, 0.55) !important;
        }

        /* Featured Cards: Purple (SafeWork Pro) */
        .tool-card.featured-purple {
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.15) 0%, rgba(30, 41, 59, 0.4) 100%) !important;
            border-color: rgba(139, 92, 246, 0.35) !important;
            box-shadow: 0 0 15px rgba(124, 58, 237, 0.15) !important;
        }
        .tool-card.featured-purple:hover {
            border-color: rgba(139, 92, 246, 0.6) !important;
            box-shadow: 0 12px 40px rgba(124, 58, 237, 0.25), 0 0 20px rgba(124, 58, 237, 0.15) !important;
        }
        .featured-purple .tool-icon-wrapper {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.2) 0%, rgba(124, 58, 237, 0.1) 100%) !important;
            border-color: rgba(139, 92, 246, 0.35) !important;
        }
        .featured-purple:hover .tool-icon-wrapper {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.3) 0%, rgba(124, 58, 237, 0.15) 100%) !important;
            border-color: rgba(139, 92, 246, 0.55) !important;
        }
    </style>
</head>
<body class="antialiased selection:bg-cyan-500 selection:text-white">

    <!-- Background Elements -->
    <div class="bg-animated"></div>
    <div class="bg-grid"></div>
    <div class="particles" id="particles"></div>

    <!-- Navigation/Logo Area -->
    <nav class="w-full flex justify-center py-8 animate-fade-in opacity-0" style="animation-delay: 0.1s;">
        <a href="index.html" id="logo-link" class="flex items-center gap-2 group cursor-pointer no-underline">
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-600 to-cyan-400 flex items-center justify-center text-white font-bold text-xl shadow-[0_0_20px_rgba(0,210,255,0.4)] group-hover:shadow-[0_0_30px_rgba(0,210,255,0.65)] transition-all duration-300">
                Ξ
            </div>
            <span class="text-2xl font-bold tracking-wider font-['Orbitron'] text-slate-100 group-hover:text-cyan-300 transition-colors">4U.IA.BR</span>
        </a>
    </nav>

    <!-- Hero Section -->
    <section class="relative px-4 pt-10 pb-20 text-center max-w-5xl mx-auto animate-fade-in opacity-0" style="animation-delay: 0.2s;">
        <h1 class="hero-title text-5xl md:text-7xl lg:text-8xl font-black mb-6 tracking-tight">
            ΞCΛLC
        </h1>
        <p class="text-slate-400 text-lg md:text-xl max-w-2xl mx-auto mb-10 leading-relaxed font-light">
            Plataforma completa com ferramentas técnicas e calculadoras especializadas para engenharia civil.
            Precisão, normas NBR e metodologia profissional.
        </p>
        
        <div class="inline-flex items-center gap-3 px-6 py-3 rounded-full badge-count mb-4">
            <span class="font-['Orbitron'] text-lg font-bold" id="toolCount">38</span>
            <span class="text-sm uppercase tracking-wider font-medium">Ferramentas Disponíveis</span>
        </div>
        <div class="flex justify-center mb-12">
            <span class="px-4 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold uppercase tracking-widest animate-pulse">
                ✓ SINAPI 2025 Atualizado
            </span>
        </div>

        <div class="flex flex-wrap justify-center gap-8 md:gap-16 mt-4">
            <div class="text-center group">
                <span class="block text-4xl md:text-5xl font-['Orbitron'] font-bold text-cyan-400 mb-2 group-hover:text-cyan-300 transition-colors stat-number" data-count="38">0</span>
                <span class="text-xs uppercase tracking-[0.2em] text-slate-500">Calculadoras</span>
            </div>
            <div class="text-center group">
                <span class="block text-4xl md:text-5xl font-['Orbitron'] font-bold text-cyan-400 mb-2 group-hover:text-cyan-300 transition-colors stat-number" data-count="20">0</span>
                <span class="text-xs uppercase tracking-[0.2em] text-slate-500">Normas NBR</span>
            </div>
            <div class="text-center group">
                <span class="block text-4xl md:text-5xl font-['Orbitron'] font-bold text-cyan-400 mb-2 group-hover:text-cyan-300 transition-colors stat-number" data-count="8">0</span>
                <span class="text-xs uppercase tracking-[0.2em] text-slate-500">Categorias</span>
            </div>
        </div>
    </section>

    <!-- Filter Section -->
    <div class="px-4 pb-12 animate-fade-in opacity-0" style="animation-delay: 0.3s;">
        <div class="max-w-xl mx-auto relative mb-8">
            <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input type="text" id="searchInput" class="search-input w-full py-4 pl-12 pr-6 rounded-2xl text-slate-100 placeholder-slate-500 focus:outline-none" placeholder="O que você precisa calcular hoje?">
        </div>

        <div class="flex flex-wrap justify-center gap-2 md:gap-3 max-w-4xl mx-auto category-filters">
            <button class="filter-btn active px-5 py-2 rounded-full text-sm font-medium" data-filter="all">Todas</button>
            <button class="filter-btn px-5 py-2 rounded-full text-sm font-medium" data-filter="estrutural">Estrutural</button>
            <button class="filter-btn px-5 py-2 rounded-full text-sm font-medium" data-filter="hidraulica">Hidráulica</button>
            <button class="filter-btn px-5 py-2 rounded-full text-sm font-medium" data-filter="materiais">Materiais</button>
            <button class="filter-btn px-5 py-2 rounded-full text-sm font-medium" data-filter="eletrica">Elétrica</button>
            <button class="filter-btn px-5 py-2 rounded-full text-sm font-medium" data-filter="orcamento">Orçamento</button>
            <button class="filter-btn px-5 py-2 rounded-full text-sm font-medium" data-filter="outros">Outros</button>
        </div>
    </div>

    <!-- Main Grid -->
    <main class="container mx-auto px-4 pb-24">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="toolsGrid">
            <!-- Tool Cards -->
            <a href="dosagem.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="materiais" style="animation-delay: 0.1s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Materiais</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🗿</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Dosagem de Concreto</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Métodos ACI, IPT e NBR 12655. Traço, correções de umidade e custos.</p>
            </a>

            <a href="consumo.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="materiais" style="animation-delay: 0.15s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Materiais</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">📐</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Consumo de Materiais</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Cálculo de tintas, argamassas e revestimentos por m².</p>
            </a>

            <a href="aco.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="estrutural" style="animation-delay: 0.18s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Estrutural</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">⛓️</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Cálculo de Aço</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Dimensionamento de armaduras, tabelas e pesos nominais.</p>
            </a>

            <a href="lajes.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="estrutural" style="animation-delay: 0.2s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Estrutural</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🥞</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Lajes Maciças/Treliçadas</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Dimensionamento de espessura, carga e quantitativos.</p>
            </a>

            <a href="vaos.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="estrutural" style="animation-delay: 0.22s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Estrutural</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🌉</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Vigas e Vãos</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Pré-dimensionamento de vãos, flechas e cortantes.</p>
            </a>

            <a href="pilares.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="estrutural" style="animation-delay: 0.25s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Estrutural</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🏛️</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Pilares</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Dimensionamento de área de aço e esbeltez.</p>
            </a>

            <a href="fundacoes.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="estrutural" style="animation-delay: 0.28s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Estrutural</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">⚓</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Fundações</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Sapatas e blocos de coroamento. NBR 6122.</p>
            </a>

            <a href="terraplenagem.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="estrutural" style="animation-delay: 0.3s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Estrutural</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🚜</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Terraplenagem</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Cálculo de volumes de corte, aterro e empolamento.</p>
            </a>

            <a href="escadas.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="estrutural" style="animation-delay: 0.32s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Estrutural</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🪜</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Calculadora de Escadas</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Dimensionamento Blondel, NBR 9050 e espelhos.</p>
            </a>

            <a href="telhados.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="estrutural" style="animation-delay: 0.35s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Estrutural</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🏠</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Telhados</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Madeiramento, inclinação e quantidade de telhas.</p>
            </a>

            <a href="orcamento.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="orcamento" style="animation-delay: 0.38s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Orçamento</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">💰</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Orçamento CUB</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Estimativa paramétrica baseada no SINDUSCON.</p>
            </a>

             <a href="sistemas.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="orcamento" style="animation-delay: 0.4s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Orçamento</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🏗️</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Sistemas Construtivos</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Comparativo Steel Frame, Wood Frame e Convencional.</p>
            </a>

            <a href="eletrica.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="eletrica" style="animation-delay: 0.42s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Elétrica</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">⚡</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Instalações Elétricas</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Dimensionamento de cabos e disjuntores NBR 5410.</p>
            </a>

            <a href="luminotecnico.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="eletrica" style="animation-delay: 0.45s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Elétrica</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">💡</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Luminotécnico</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Cálculo de iluminância (Lux) e método dos lúmens.</p>
            </a>

            <a href="solar.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="eletrica" style="animation-delay: 0.48s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Elétrica</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">☀️</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Energia Solar</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Dimensionamento fotovoltaico e geração estimada.</p>
            </a>

            <a href="arcondicionado.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="eletrica" style="animation-delay: 0.5s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Elétrica</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">❄️</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Ar Condicionado</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Cálculo de carga térmica em BTUs.</p>
            </a>

            <a href="reservatorios.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="hidraulica" style="animation-delay: 0.52s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Hidráulica</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">💧</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Reservatórios</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Volume de consumo diário e reserva técnica.</p>
            </a>

            <a href="tubulacoes.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="hidraulica" style="animation-delay: 0.55s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Hidráulica</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🔧</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Tubulações</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Perda de carga, diâmetros e pressões.</p>
            </a>

            <a href="fossa.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="hidraulica" style="animation-delay: 0.58s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Hidráulica</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🚰</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Fossa Séptica</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Dimensionamento de tratamento de esgoto (NBR 7229).</p>
            </a>

            <a href="alvenaria.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="materiais" style="animation-delay: 0.6s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Materiais</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🧱</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Alvenaria</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Quantitativo de blocos e argamassa de assentamento.</p>
            </a>

            <a href="impermeabilizacao.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="materiais" style="animation-delay: 0.62s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Materiais</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🛡️</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Impermeabilização</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Consumo de mantas e emulsões asfálticas.</p>
            </a>

            <a href="termico.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.65s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Conforto</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🌡️</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Desempenho Térmico</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Transmitância e capacidade térmica. NBR 15220 / 15575.</p>
            </a>

            <a href="diario.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.68s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Gestão</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">📓</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Diário de Obra</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Registro diário de atividades, clima e equipe com exportação PDF.</p>
            </a>

            <a href="checklist.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.7s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Qualidade</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">✅</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Checklist de Obra</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Verificação técnica de etapas construtivas e segurança.</p>
            </a>

            <a href="canteiro.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.72s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Logística</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🏗️</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Canteiro de Obras</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Planejamento de áreas de vivência e armazenagem.</p>
            </a>

            <!-- PowerCalc - Calculadora Científica & Engenharia (PWA) -->
            <a href="powercalc/index.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.75s;">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Cálculos</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🧮</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">PowerCalc</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Calculadora científica, programador e de engenharia com 57+ fórmulas integradas.</p>
            </a>

            <a href="conversor.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.78s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Outros</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🔄</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Conversor de Unidades</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Pressão, força, torque, área e volume.</p>
            </a>

            <a href="nbr.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.8s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Consulta</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">📚</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Guia NBR</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Catálogo rápido das principais normas técnicas da construção.</p>
            </a>

            <!-- SafeWork Pro - Segurança do Trabalho -->
            <a href="seguranca/index.php" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.82s;">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Segurança</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🛡️</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">SafeWork Pro</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Geração de PGR (NR-01), PCMAT (NR-18), APR, Termos de EPI e Certificados.</p>
            </a>

            <!-- Gerador de Contratos -->
            <a href="contratos/index.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.83s;">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Contratos</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">📜</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Gerador de Contratos</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Criação profissional de contratos de prestação de serviços de engenharia.</p>
            </a>

            <!-- Sound Meter - Decibelímetro Digital (PWA) -->
            <a href="soundmeter/index.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.84s;">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Conforto</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🎙️</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Sound Meter</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Medidor de nível sonoro em tempo real (dB) via microfone e análise de espectro acústico.</p>
            </a>

            <!-- Smart Notes - Bloco de Notas Técnico -->
            <a href="notas/index.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.85s;">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Notas</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">📝</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Smart Notes</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Bloco de anotações rápidas de campo, memoriais descritivos e listas técnicas.</p>
            </a>

            <!-- Muro de Arrimo -->
            <a href="arrimo.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="estrutural" style="animation-delay: 0.86s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Estrutural</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">⛰️</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Muro de Arrimo</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Dimensionamento de estabilidade, empuxo de terra e muros de gravidade.</p>
            </a>

            <!-- Acabamentos -->
            <a href="acabamentos.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="materiais" style="animation-delay: 0.86s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Materiais</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🎨</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Revestimentos</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Cálculo de pisos, azulejos, rodapés e soleiras com estimativa de perdas.</p>
            </a>

            <!-- Isolamento Acústico -->
            <a href="acustica.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.88s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Conforto</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🔊</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Isolamento Acústico</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Cálculo de tempo de reverberação e atenuação de paredes e pisos.</p>
            </a>

            <!-- Dimensionamento de Gás -->
            <a href="gas.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="hidraulica" style="animation-delay: 0.9s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Hidráulica</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🔥</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Dimensionamento de Gás</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Cálculo de perda de carga e diâmetro de tubulações GLP/GN. NBR 15526.</p>
            </a>

            <!-- Prevenção de Incêndio -->
            <a href="incendio.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="hidraulica" style="animation-delay: 0.92s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Hidráulica</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🧯</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Sistemas de Incêndio</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Cálculo de hidrantes, extintores e rotas de fuga. NBR 13714.</p>
            </a>

            <!-- Topografia -->
            <a href="topografia.html" class="tool-card group relative rounded-3xl p-8 flex flex-col items-center text-center animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.94s">
                <span class="tool-category absolute top-4 right-4 text-[10px] uppercase font-bold px-2 py-1 rounded-full">Outros</span>
                <div class="tool-icon-wrapper w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 transition-transform duration-500">🗺️</div>
                <h2 class="text-xl font-semibold text-slate-100 mb-3 group-hover:text-blue-400 transition-colors">Topografia e Declividade</h2>
                <p class="text-sm text-slate-400 font-light leading-relaxed">Nivelamento, declividade de rampas, azimutes e coordenadas.</p>
            </a>
        </div>

        <div id="noResults" class="hidden text-center py-20">
            <div class="text-6xl mb-4 opacity-50">🔍</div>
            <h3 class="text-xl font-bold text-slate-300">Nenhuma ferramenta encontrada</h3>
            <p class="text-slate-500">Tente buscar por outro termo.</p>
        </div>
    </main>

    <footer class="footer-clean py-8 text-center text-gray-500/50">
        <p class="text-[10px] uppercase tracking-[0.2em] opacity-50">&copy; 2026 4U.IA.BR. Todos os direitos reservados. Feito com amor por <a href="https://4u.ia.br" target="_blank" class="footer-owner-link text-blue-400/80 hover:text-blue-300 hover:underline transition-all">4u.ia.br</a>.</p>
    </footer>

    <!-- Back to top -->
    <button id="backToTop" class="back-to-top fixed bottom-8 right-8 w-12 h-12 rounded-full text-white flex items-center justify-center text-xl opacity-0 invisible transition-all duration-300 hover:-translate-y-1 z-50">
        ↑
    </button>

    <script>

        // Particles
        const particlesContainer = document.getElementById('particles');
        for (let i = 0; i < 25; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 20 + 's';
            particle.style.animationDuration = (15 + Math.random() * 10) + 's';
            particle.style.opacity = Math.random() * 0.5;
            particlesContainer.appendChild(particle);
        }

        // Animated Counters
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                document.querySelectorAll('.stat-number').forEach(counter => {
                    const target = parseInt(counter.dataset.count);
                    let current = 0;
                    const increment = target / 50;
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            counter.textContent = target;
                            clearInterval(timer);
                        } else {
                            counter.textContent = Math.ceil(current);
                        }
                    }, 30);
                });
                observer.disconnect();
            }
        });
        observer.observe(document.querySelector('.stat-number').parentElement);

        // Search & Filter
        const searchInput = document.getElementById('searchInput');
        const toolCards = document.querySelectorAll('.tool-card');
        const filterBtns = document.querySelectorAll('.filter-btn');
        const noResults = document.getElementById('noResults');
        const toolCountSpan = document.getElementById('toolCount');

        function filterTools() {
            const term = searchInput.value.toLowerCase();
            const category = document.querySelector('.filter-btn.active').dataset.filter;
            let count = 0;

            toolCards.forEach(card => {
                const title = card.querySelector('h2').textContent.toLowerCase();
                const desc = card.querySelector('p').textContent.toLowerCase();
                const cardCat = card.dataset.category;
                
                const matchesSearch = title.includes(term) || desc.includes(term);
                const matchesCategory = category === 'all' || cardCat === category;

                if (matchesSearch && matchesCategory) {
                    card.classList.remove('hidden', 'absolute');
                    card.classList.add('flex');
                    count++;
                } else {
                    card.classList.add('hidden', 'absolute'); // absolute prevents layout shift gaps
                    card.classList.remove('flex');
                }
            });

            toolCountSpan.textContent = count;
            
            if (count === 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        }

        searchInput.addEventListener('input', filterTools);

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                filterTools();
            });
        });

        // Back to Top
        const btnTop = document.getElementById('backToTop');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                btnTop.classList.remove('opacity-0', 'invisible');
            } else {
                btnTop.classList.add('opacity-0', 'invisible');
            }
        });

        btnTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // --- MOUSE MOVE GLOW EFFECT ON CARDS ---
        const toolsGrid = document.getElementById('toolsGrid');
        if (toolsGrid) {
            toolsGrid.addEventListener('mousemove', (e) => {
                const cards = document.querySelectorAll('.tool-card');
                cards.forEach(card => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    card.style.setProperty('--mouse-x', `${x}px`);
                    card.style.setProperty('--mouse-y', `${y}px`);
                });
            });
        }

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
                    window.location.href = 'https://4u.ia.br/admin/login';
                    return;
                }

                // Evita recargas desnecessárias e perda de cliques se o usuário já estiver na Home
                const targetUrl = this.href;
                const currentUrl = window.location.href;
                
                const cleanTarget = targetUrl.replace(/\/+$/, '').replace(/\/index\.html$/, '').replace(/\/index\.php$/, '');
                const cleanCurrent = currentUrl.replace(/\/+$/, '').replace(/\/index\.html$/, '').replace(/\/index\.php$/, '');

                if (cleanTarget === cleanCurrent) {
                    e.preventDefault();
                }
            });
        }
    </script>
</body>
</html>