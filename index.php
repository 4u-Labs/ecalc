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

    <title>ECALC - Cálculos Técnicos para Engenharia | 4U.IA.BR</title>
    <meta name="description" content="ECALC - Plataforma com 40 ferramentas técnicas, calculadoras especializadas, normas ABNT NBR e relatórios para engenharia civil.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Orbitron:wght@500;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="ECALC - Cálculos Técnicos para Engenharia">
    <meta property="og:description" content="Obras mais seguras, rápidas e precisas. 40 calculadoras e ferramentas técnicas profissionais.">
    <meta property="og:image" content="https://4u.ia.br/app/engenharia/assets/banner-ecalc.png">
    
    <style>
        :root {
            --primary: #00D2FF; /* Electric Cyan */
            --secondary: #0066FF; /* Electric Blue */
            --accent-amber: #F59E0B; /* Industrial Amber Gold (ECALC Ruler) */
            --bg-dark: #030611; /* Obsidian Navy */
            --bg-card: rgba(11, 18, 33, 0.75);
            --text-main: #f8fafc;
            --text-muted: #8da2ba;
            --transition-speed: 250ms;
            --transition-curve: cubic-bezier(0.16, 1, 0.3, 1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            background: radial-gradient(circle at 50% 10%, #0d172e 0%, #030611 100%);
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
                radial-gradient(circle at 15% 35%, rgba(0, 210, 255, 0.08) 0%, transparent 35%),
                radial-gradient(circle at 85% 25%, rgba(0, 102, 255, 0.08) 0%, transparent 35%),
                radial-gradient(ellipse at 50% 60%, rgba(245, 158, 11, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 50% 90%, rgba(0, 210, 255, 0.04) 0%, transparent 60%);
        }

        .bg-grid {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background-image: 
                linear-gradient(rgba(0, 210, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 210, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            mask-image: linear-gradient(to bottom, black 50%, transparent 100%);
            -webkit-mask-image: linear-gradient(to bottom, black 50%, transparent 100%);
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

        /* Hero Banner Showcase */
        .hero-banner-glow {
            position: absolute;
            inset: -15px;
            background: radial-gradient(circle at 20% 50%, rgba(0, 210, 255, 0.28) 0%, transparent 60%),
                        radial-gradient(circle at 50% 50%, rgba(245, 158, 11, 0.22) 0%, transparent 55%),
                        radial-gradient(circle at 80% 50%, rgba(0, 102, 255, 0.28) 0%, transparent 60%);
            filter: blur(35px);
            z-index: 0;
            opacity: 0.85;
            pointer-events: none;
            animation: pulseGlow 6s ease-in-out infinite alternate;
        }

        @keyframes pulseGlow {
            0% { opacity: 0.65; transform: scale(0.98); }
            100% { opacity: 0.95; transform: scale(1.02); }
        }

        .hero-banner-wrapper {
            position: relative;
            border-radius: 28px;
            padding: 2px;
            background: linear-gradient(135deg, rgba(0, 210, 255, 0.4) 0%, rgba(245, 158, 11, 0.35) 50%, rgba(0, 102, 255, 0.45) 100%);
            box-shadow: 0 25px 60px -15px rgba(0, 102, 255, 0.35), 0 0 35px rgba(0, 210, 255, 0.15);
        }

        .hero-banner-inner {
            border-radius: 26px;
            overflow: hidden;
            background: #030611;
            position: relative;
        }

        /* Feature Pillars (from Banner Badges) */
        .feature-pillar {
            background: rgba(11, 18, 33, 0.6);
            border: 1px solid rgba(0, 210, 255, 0.16);
            backdrop-filter: blur(12px);
            border-radius: 18px;
            padding: 0.75rem 0.6rem;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            user-select: none;
        }

        .feature-pillar:hover {
            background: rgba(0, 210, 255, 0.12);
            border-color: rgba(0, 210, 255, 0.55);
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0, 210, 255, 0.25);
        }

        .feature-pillar:active {
            transform: translateY(-1px);
        }

        /* Inputs */
        .search-input {
            background: rgba(8, 13, 25, 0.75);
            border: 1px solid rgba(0, 210, 255, 0.25);
            backdrop-filter: blur(14px);
            transition: all var(--transition-speed) var(--transition-curve);
            color: #ffffff;
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(0, 210, 255, 0.2), 0 0 25px rgba(0, 210, 255, 0.2);
            background: rgba(8, 13, 25, 0.95);
        }

        /* Filters */
        .filter-btn {
            background: rgba(11, 18, 33, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #94a3b8;
            transition: all var(--transition-speed) ease;
        }

        .filter-btn:hover {
            background: rgba(0, 210, 255, 0.08);
            border-color: rgba(0, 210, 255, 0.4);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .filter-btn.active {
            background: linear-gradient(135deg, rgba(0, 102, 255, 0.25) 0%, rgba(0, 210, 255, 0.25) 100%);
            border-color: #00D2FF;
            color: #00D2FF;
            box-shadow: 0 0 15px rgba(0, 210, 255, 0.25);
        }

        .filter-btn[data-filter="estrutural"].active {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.25) 0%, rgba(37, 99, 235, 0.35) 100%);
            border-color: #3b82f6;
            color: #93c5fd;
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.25);
        }
        .filter-btn[data-filter="hidraulica"].active {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.25) 0%, rgba(8, 145, 178, 0.35) 100%);
            border-color: #06b6d4;
            color: #67e8f9;
            box-shadow: 0 0 15px rgba(6, 182, 212, 0.25);
        }
        .filter-btn[data-filter="materiais"].active {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.25) 0%, rgba(5, 150, 105, 0.35) 100%);
            border-color: #10b981;
            color: #6ee7b7;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.25);
        }
        .filter-btn[data-filter="eletrica"].active {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.25) 0%, rgba(217, 119, 6, 0.35) 100%);
            border-color: #f59e0b;
            color: #fde68a;
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.25);
        }
        .filter-btn[data-filter="orcamento"].active {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.25) 0%, rgba(22, 163, 74, 0.35) 100%);
            border-color: #22c55e;
            color: #86efac;
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.25);
        }
        .filter-btn[data-filter="outros"].active {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.25) 0%, rgba(79, 70, 229, 0.35) 100%);
            border-color: #6366f1;
            color: #a5b4fc;
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.25);
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

        /* Image-based Cards */
        .tool-card-image {
            padding: 0 !important;
            aspect-ratio: 4 / 3;
            display: flex;
            overflow: hidden;
            border-radius: 24px;
        }

        /* Enforce complete hiding when filtered */
        .tool-card.is-hidden,
        .tool-card.hidden,
        .tool-card-image.is-hidden,
        .tool-card-image.hidden,
        .tool-card[style*="display: none"] {
            display: none !important;
        }

        .tool-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            border-radius: 24px;
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .tool-card-image:hover img {
            transform: scale(1.04);
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

    <!-- Floating Navigation Bar -->
    <header class="w-full sticky top-0 z-40 backdrop-blur-xl bg-[#030611]/85 border-b border-cyan-500/15 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <!-- Brand Link with 5-click easter egg preserved -->
            <a href="./" id="logo-link" class="flex items-center gap-3.5 group cursor-pointer no-underline select-none">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-400 via-blue-600 to-cyan-400 p-[2px] shadow-[0_0_20px_rgba(0,210,255,0.35)] group-hover:shadow-[0_0_30px_rgba(245,158,11,0.55)] transition-all duration-300">
                    <div class="w-full h-full bg-[#030611] rounded-[10px] flex items-center justify-center font-bold text-xl text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-cyan-300 to-white font-['Orbitron']">
                        Ξ
                    </div>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-2xl font-black tracking-wider font-['Orbitron'] text-white group-hover:text-cyan-300 transition-colors">
                            <span class="text-amber-400">E</span>CALC
                        </span>
                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-md bg-cyan-500/10 text-cyan-400 border border-cyan-500/30">PRO</span>
                    </div>
                    <span class="text-[11px] text-slate-400 font-medium tracking-wide block -mt-1">Cálculos Técnicos para Engenharia</span>
                </div>
            </a>

            <!-- Quick Badges & Links -->
            <div class="flex items-center gap-3 sm:gap-4">
                <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-900/80 border border-slate-700/60 text-xs text-slate-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    <span class="font-medium">41 Ferramentas Ativas</span>
                </div>

                <a href="https://4u.ia.br" target="_blank" class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold uppercase tracking-wider text-slate-200 bg-cyan-500/10 hover:bg-cyan-500/20 border border-cyan-500/30 hover:border-cyan-400 transition-all duration-200 group">
                    <span>4U.IA.BR</span>
                    <svg class="w-3.5 h-3.5 text-cyan-400 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Showcase Section with Banner (Width matches Cards Grid) -->
    <section class="container mx-auto px-4 pt-6 pb-8 text-center animate-fade-in opacity-0" style="animation-delay: 0.15s;">
        <!-- Banner Showcase Card (w-full to span the entire container width) -->
        <div class="relative w-full mx-auto mb-8">
            <div class="hero-banner-glow"></div>
            <div class="hero-banner-wrapper">
                <div class="hero-banner-inner">
                    <picture class="w-full block">
                        <source srcset="assets/banner-ecalc.webp?v=2" type="image/webp">
                        <img src="assets/banner-ecalc.png?v=2" 
                             alt="ECALC - Cálculos Técnicos para Engenharia - Obras mais seguras, rápidas e precisas" 
                             class="w-full h-auto block select-none" 
                             width="1024" 
                             height="376" 
                             loading="eager" 
                             fetchpriority="high">
                    </picture>
                </div>
            </div>
        </div>

        <!-- Trust & Compliance Badges -->
        <div class="flex flex-wrap justify-center items-center gap-2.5 sm:gap-3.5 mb-8">
            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold tracking-wide">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                SINAPI 2025 Integrado
            </span>
            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/30 text-blue-400 text-xs font-semibold tracking-wide">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                Normas ABNT NBR em Dia
            </span>
            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-semibold tracking-wide">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                Memoriais de Cálculo & PDFs
            </span>
            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 text-xs font-semibold tracking-wide">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                100% PWA & Acesso Offline
            </span>
        </div>

        <!-- 6 Interactive Feature Pillars (w-full to match container width) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 w-full mx-auto mb-6">
            <button type="button" class="feature-pillar group" data-feature-action="formula">
                <span class="text-2xl mb-1.5 block group-hover:scale-110 transition-transform">🧮</span>
                <span class="text-xs font-bold text-slate-200 group-hover:text-cyan-300 block">57+ Fórmulas</span>
                <span class="text-[10px] text-slate-400 block">PowerCalc & Mat</span>
            </button>
            <button type="button" class="feature-pillar group" data-feature-action="estrutural">
                <span class="text-2xl mb-1.5 block group-hover:scale-110 transition-transform">🏗️</span>
                <span class="text-xs font-bold text-slate-200 group-hover:text-blue-300 block">Obras & Estruturas</span>
                <span class="text-[10px] text-slate-400 block">Vigas, Pilares, Lajes</span>
            </button>
            <button type="button" class="feature-pillar group" data-feature-action="materiais">
                <span class="text-2xl mb-1.5 block group-hover:scale-110 transition-transform">🧱</span>
                <span class="text-xs font-bold text-slate-200 group-hover:text-emerald-300 block">Materiais & Consumo</span>
                <span class="text-[10px] text-slate-400 block">Concreto & Traço</span>
            </button>
            <button type="button" class="feature-pillar group" data-feature-action="dimension">
                <span class="text-2xl mb-1.5 block group-hover:scale-110 transition-transform">📐</span>
                <span class="text-xs font-bold text-slate-200 group-hover:text-purple-300 block">Dimensionamentos</span>
                <span class="text-[10px] text-slate-400 block">Tubulações & Gás</span>
            </button>
            <button type="button" class="feature-pillar group" data-feature-action="reports">
                <span class="text-2xl mb-1.5 block group-hover:scale-110 transition-transform">📄</span>
                <span class="text-xs font-bold text-slate-200 group-hover:text-rose-300 block">Relatórios Técnicos</span>
                <span class="text-[10px] text-slate-400 block">Laudos, PGR & Diário</span>
            </button>
            <button type="button" class="feature-pillar group" data-feature-action="nbr">
                <span class="text-2xl mb-1.5 block group-hover:scale-110 transition-transform">⚙️</span>
                <span class="text-xs font-bold text-slate-200 group-hover:text-amber-300 block">Normas NBR</span>
                <span class="text-[10px] text-slate-400 block">Guia ABNT & Regras</span>
            </button>
        </div>
    </section>

    <!-- Search and Category Filter Section (Container width matches Cards Grid) -->
    <div class="container mx-auto px-4 pb-10 animate-fade-in opacity-0" style="animation-delay: 0.25s;" id="searchSection">
        <div class="max-w-2xl mx-auto relative mb-6">
            <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input type="text" id="searchInput" class="search-input w-full py-4 pl-12 pr-24 rounded-2xl text-slate-100 placeholder-slate-400 focus:outline-none text-base" placeholder="O que você precisa calcular hoje? (Pressione '/' para buscar)">
            <div class="absolute inset-y-0 right-4 flex items-center gap-2">
                <button type="button" id="clearSearch" class="hidden text-slate-400 hover:text-white p-1 rounded-md transition-colors" title="Limpar pesquisa">✕</button>
                <kbd class="hidden sm:inline-block px-2 py-1 text-[11px] font-mono text-slate-400 bg-slate-800/80 rounded border border-slate-700/80 select-none">/</kbd>
            </div>
        </div>

        <!-- Filter Category Buttons -->
        <div class="flex flex-wrap justify-center gap-2 md:gap-3 max-w-4xl mx-auto category-filters">
            <button class="filter-btn active px-5 py-2.5 rounded-full text-sm font-semibold tracking-wide" data-filter="all">Todas</button>
            <button class="filter-btn px-5 py-2.5 rounded-full text-sm font-medium tracking-wide" data-filter="estrutural">🏗️ Estrutural</button>
            <button class="filter-btn px-5 py-2.5 rounded-full text-sm font-medium tracking-wide" data-filter="hidraulica">💧 Hidráulica</button>
            <button class="filter-btn px-5 py-2.5 rounded-full text-sm font-medium tracking-wide" data-filter="materiais">🧱 Materiais</button>
            <button class="filter-btn px-5 py-2.5 rounded-full text-sm font-medium tracking-wide" data-filter="eletrica">⚡ Elétrica</button>
            <button class="filter-btn px-5 py-2.5 rounded-full text-sm font-medium tracking-wide" data-filter="orcamento">💰 Orçamento</button>
            <button class="filter-btn px-5 py-2.5 rounded-full text-sm font-medium tracking-wide" data-filter="outros">🛠️ Outros & Gestão</button>
        </div>

        <!-- Result Status Counter Badge -->
        <div class="text-center mt-5">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-slate-900/80 border border-slate-700/60 text-xs text-slate-300">
                <span>Exibindo</span>
                <span id="toolCount" class="font-['Orbitron'] font-bold text-cyan-400 text-sm">41</span>
                <span>ferramentas</span>
                <span id="activeFilterLabel" class="text-slate-400 font-medium hidden"></span>
            </span>
        </div>
    </div>

    <!-- Main Grid -->
    <main class="container mx-auto px-4 pb-24">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="toolsGrid">
            <!-- Tool Cards -->
            <a href="dosagem.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="materiais" style="animation-delay: 0.1s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/dosagem.webp" type="image/webp">
                    <img src="assets/cards/dosagem.jpg" alt="Dosagem de Concreto" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Dosagem de Concreto</h2>
                    <p>Métodos ACI, IPT e NBR 12655. Traço, correções de umidade e custos.</p>
                </div>
            </a>

            <a href="consumo.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="materiais" style="animation-delay: 0.15s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/consumo.webp" type="image/webp">
                    <img src="assets/cards/consumo.jpg" alt="Consumo de Materiais" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Consumo de Materiais</h2>
                    <p>Cálculo de tintas, argamassas e revestimentos por m².</p>
                </div>
            </a>

            <a href="aco.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="estrutural" style="animation-delay: 0.18s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/aco.webp" type="image/webp">
                    <img src="assets/cards/aco.jpg" alt="Cálculo de Aço" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Cálculo de Aço</h2>
                    <p>Dimensionamento de armaduras, tabelas e pesos nominais.</p>
                </div>
            </a>

            <a href="lajes.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="estrutural" style="animation-delay: 0.2s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/lajes.webp" type="image/webp">
                    <img src="assets/cards/lajes.jpg" alt="Lajes Maciças/Treliçadas" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Lajes Maciças/Treliçadas</h2>
                    <p>Dimensionamento de espessura, carga e quantitativos.</p>
                </div>
            </a>

            <a href="vaos.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="estrutural" style="animation-delay: 0.22s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/vaos.webp" type="image/webp">
                    <img src="assets/cards/vaos.jpg" alt="Vigas e Vãos" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Vigas e Vãos</h2>
                    <p>Pré-dimensionamento de vãos, flechas e cortantes.</p>
                </div>
            </a>

            <a href="pilares.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="estrutural" style="animation-delay: 0.25s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/pilares.webp" type="image/webp">
                    <img src="assets/cards/pilares.jpg" alt="Pilares" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Pilares</h2>
                    <p>Dimensionamento de área de aço e esbeltez.</p>
                </div>
            </a>

            <a href="fundacoes.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="estrutural" style="animation-delay: 0.28s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/fundacoes.webp" type="image/webp">
                    <img src="assets/cards/fundacoes.jpg" alt="Fundações" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Fundações</h2>
                    <p>Sapatas e blocos de coroamento. NBR 6122.</p>
                </div>
            </a>

            <a href="terraplenagem.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="estrutural" style="animation-delay: 0.3s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/terraplenagem.webp" type="image/webp">
                    <img src="assets/cards/terraplenagem.jpg" alt="Terraplenagem" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Terraplenagem</h2>
                    <p>Cálculo de volumes de corte, aterro e empolamento.</p>
                </div>
            </a>

            <a href="escadas.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="estrutural" style="animation-delay: 0.32s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/escadas.webp" type="image/webp">
                    <img src="assets/cards/escadas.jpg" alt="Calculadora de Escadas" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Calculadora de Escadas</h2>
                    <p>Dimensionamento Blondel, NBR 9050 e espelhos.</p>
                </div>
            </a>

            <a href="telhados.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="estrutural" style="animation-delay: 0.35s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/telhados.webp" type="image/webp">
                    <img src="assets/cards/telhados.jpg" alt="Telhados" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Telhados</h2>
                    <p>Madeiramento, inclinação e quantidade de telhas.</p>
                </div>
            </a>

            <a href="orcamento.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="orcamento" style="animation-delay: 0.38s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/orcamento.webp" type="image/webp">
                    <img src="assets/cards/orcamento.jpg" alt="Orçamento CUB" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Orçamento CUB</h2>
                    <p>Estimativa paramétrica baseada no SINDUSCON.</p>
                </div>
            </a>

            <a href="sistemas.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="orcamento" style="animation-delay: 0.4s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/sistemas.webp" type="image/webp">
                    <img src="assets/cards/sistemas.jpg" alt="Sistemas Construtivos" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Sistemas Construtivos</h2>
                    <p>Comparativo Steel Frame, Wood Frame e Convencional.</p>
                </div>
            </a>

            <a href="eletrica.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="eletrica" style="animation-delay: 0.42s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/eletrica.webp" type="image/webp">
                    <img src="assets/cards/eletrica.jpg" alt="Instalações Elétricas" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Instalações Elétricas</h2>
                    <p>Dimensionamento de cabos e disjuntores NBR 5410.</p>
                </div>
            </a>

            <a href="luminotecnico.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="eletrica" style="animation-delay: 0.45s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/luminotecnico.webp" type="image/webp">
                    <img src="assets/cards/luminotecnico.jpg" alt="Luminotécnico" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Luminotécnico</h2>
                    <p>Cálculo de iluminância (Lux) e método dos lúmens.</p>
                </div>
            </a>

            <a href="solar.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="eletrica" style="animation-delay: 0.48s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/solar.webp" type="image/webp">
                    <img src="assets/cards/solar.jpg" alt="Energia Solar" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Energia Solar</h2>
                    <p>Dimensionamento fotovoltaico e geração estimada.</p>
                </div>
            </a>

            <a href="arcondicionado.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="eletrica" style="animation-delay: 0.5s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/arcondicionado.webp" type="image/webp">
                    <img src="assets/cards/arcondicionado.jpg" alt="Ar Condicionado" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Ar Condicionado</h2>
                    <p>Cálculo de carga térmica em BTUs.</p>
                </div>
            </a>

            <a href="reservatorios.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="hidraulica" style="animation-delay: 0.52s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/reservatorios.webp" type="image/webp">
                    <img src="assets/cards/reservatorios.jpg" alt="Reservatórios" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Reservatórios</h2>
                    <p>Volume de consumo diário e reserva técnica.</p>
                </div>
            </a>

            <a href="tubulacoes.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="hidraulica" style="animation-delay: 0.55s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/tubulacoes.webp" type="image/webp">
                    <img src="assets/cards/tubulacoes.jpg" alt="Tubulações" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Tubulações</h2>
                    <p>Perda de carga, diâmetros e pressões.</p>
                </div>
            </a>

            <a href="fossa.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="hidraulica" style="animation-delay: 0.58s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/fossa.webp" type="image/webp">
                    <img src="assets/cards/fossa.jpg" alt="Fossa Séptica" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Fossa Séptica</h2>
                    <p>Dimensionamento de tratamento de esgoto (NBR 7229).</p>
                </div>
            </a>

            <a href="alvenaria.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="materiais" style="animation-delay: 0.6s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/alvenaria.webp" type="image/webp">
                    <img src="assets/cards/alvenaria.jpg" alt="Alvenaria" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Alvenaria</h2>
                    <p>Quantitativo de blocos e argamassa de assentamento.</p>
                </div>
            </a>

            <a href="impermeabilizacao.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="materiais" style="animation-delay: 0.62s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/impermeabilizacao.webp" type="image/webp">
                    <img src="assets/cards/impermeabilizacao.jpg" alt="Impermeabilização" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Impermeabilização</h2>
                    <p>Consumo de mantas e emulsões asfálticas.</p>
                </div>
            </a>

            <a href="termico.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.65s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/termico.webp" type="image/webp">
                    <img src="assets/cards/termico.jpg" alt="Desempenho Térmico" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Desempenho Térmico</h2>
                    <p>Transmitância e capacidade térmica. NBR 15220 / 15575.</p>
                </div>
            </a>

            <a href="diario.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.68s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/diario.webp" type="image/webp">
                    <img src="assets/cards/diario.jpg" alt="Diário de Obra" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Diário de Obra</h2>
                    <p>Registro diário de atividades, clima e equipe com exportação PDF.</p>
                </div>
            </a>

            <a href="checklist.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.7s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/checklist.webp" type="image/webp">
                    <img src="assets/cards/checklist.jpg" alt="Checklist de Obra" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Checklist de Obra</h2>
                    <p>Verificação técnica de etapas construtivas e segurança.</p>
                </div>
            </a>

            <a href="canteiro.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.72s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/canteiro.webp" type="image/webp">
                    <img src="assets/cards/canteiro.jpg" alt="Canteiro de Obras" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Canteiro de Obras</h2>
                    <p>Planejamento de áreas de vivência e armazenagem.</p>
                </div>
            </a>

            <!-- CADClone - Software CAD 2D Profissional (PWA) -->
            <a href="../cadclone/" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="outros estrutural" style="animation-delay: 0.74s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/cadclone.webp" type="image/webp">
                    <img src="assets/cards/cadclone.jpg" alt="CADClone" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>CADClone</h2>
                    <p>Software de CAD 2D no navegador. Comandos AutoCAD, DXF, calco PDF e PWA offline.</p>
                </div>
            </a>

            <!-- FotoLaudo - Câmera Técnica & Relatórios de Engenharia (PWA) -->
            <a href="../fotolaudo/" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="outros estrutural" style="animation-delay: 0.745s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/fotolaudo.webp" type="image/webp">
                    <img src="assets/cards/fotolaudo.jpg" alt="FotoLaudo" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>FotoLaudo</h2>
                    <p>Câmera técnica pericial com GPS, coordenadas UTM, azimute, cotas CAD e emissão de laudos em PDF.</p>
                </div>
            </a>

            <!-- PowerCalc - Calculadora Científica & Engenharia (PWA) -->
            <a href="https://4u.ia.br/app/powercalc/" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.75s;">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/powercalc.webp" type="image/webp">
                    <img src="assets/cards/powercalc.jpg" alt="PowerCalc" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>PowerCalc</h2>
                    <p>Calculadora científica, programador e de engenharia com 57+ fórmulas integradas.</p>
                </div>
            </a>

            <a href="conversor.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.78s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/conversor.webp" type="image/webp">
                    <img src="assets/cards/conversor.jpg" alt="Conversor de Unidades" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Conversor de Unidades</h2>
                    <p>Pressão, força, torque, área e volume.</p>
                </div>
            </a>

            <a href="nbr.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.8s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/nbr.webp" type="image/webp">
                    <img src="assets/cards/nbr.jpg" alt="Guia NBR" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Guia NBR</h2>
                    <p>Catálogo rápido das principais normas técnicas da construção.</p>
                </div>
            </a>

            <!-- SafeWork Pro - Segurança do Trabalho -->
            <a href="seguranca/index.php" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.82s;">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/safework.webp" type="image/webp">
                    <img src="assets/cards/safework.jpg" alt="SafeWork Pro" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>SafeWork Pro</h2>
                    <p>Geração de PGR (NR-01), PCMAT (NR-18), APR, Termos de EPI e Certificados.</p>
                </div>
            </a>

            <!-- Gerador de Contratos (4uSign) -->
            <a href="https://4u.ia.br/app/4usign/#hub" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.83s;">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/contratos.webp" type="image/webp">
                    <img src="assets/cards/contratos.jpg" alt="Gerador de Contratos" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Gerador de Contratos</h2>
                    <p>Crie contratos profissionais com Inteligência Artificial e assinatura digital.</p>
                </div>
            </a>

            <!-- Sound Meter - Decibelímetro Digital (PWA) -->
            <a href="https://4u.ia.br/app/soundmeter/" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.84s;">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/soundmeter.webp" type="image/webp">
                    <img src="assets/cards/soundmeter.jpg" alt="Sound Meter" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Sound Meter</h2>
                    <p>Medidor de nível sonoro em tempo real (dB) via microfone e análise de espectro acústico.</p>
                </div>
            </a>

            <!-- Smart Notes (KeepAI) -->
            <a href="https://4u.ia.br/app/keepai/" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.85s;">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/smartnotes.webp" type="image/webp">
                    <img src="assets/cards/smartnotes.jpg" alt="Smart Notes A.i." loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Smart Notes A.i.</h2>
                    <p>Bloco de anotações rápidas de campo, memoriais descritivos e listas técnicas com recursos de IA.</p>
                </div>
            </a>

            <!-- OfficeClone - Suíte de Escritório Completa (PWA) -->
            <a href="https://4u.ia.br/app/office/" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.855s;">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/office.webp" type="image/webp">
                    <img src="assets/cards/office.jpg" alt="OfficeClone" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>OfficeClone</h2>
                    <p>Sua suíte de escritório completa no navegador com ExcelClone, WordClone, PointClone, ProjectClone, FreePdf e KeepAi.</p>
                </div>
            </a>

            <!-- Muro de Arrimo -->
            <a href="arrimo.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="estrutural" style="animation-delay: 0.86s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/arrimo.webp" type="image/webp">
                    <img src="assets/cards/arrimo.jpg" alt="Muro de Arrimo" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Muro de Arrimo</h2>
                    <p>Dimensionamento de estabilidade, empuxo de terra e muros de gravidade.</p>
                </div>
            </a>

            <!-- Acabamentos -->
            <a href="acabamentos.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="materiais" style="animation-delay: 0.86s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/revestimentos.webp" type="image/webp">
                    <img src="assets/cards/revestimentos.jpg" alt="Revestimentos" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Revestimentos</h2>
                    <p>Cálculo de pisos, azulejos, rodapés e soleiras com estimativa de perdas.</p>
                </div>
            </a>

            <!-- Isolamento Acústico -->
            <a href="acustica.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.88s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/acustica.webp" type="image/webp">
                    <img src="assets/cards/acustica.jpg" alt="Isolamento Acústico" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Isolamento Acústico</h2>
                    <p>Cálculo de tempo de reverberação e atenuação de paredes e pisos.</p>
                </div>
            </a>

            <!-- Dimensionamento de Gás -->
            <a href="gas.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="hidraulica" style="animation-delay: 0.9s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/gas.webp" type="image/webp">
                    <img src="assets/cards/gas.jpg" alt="Dimensionamento de Gás" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Dimensionamento de Gás</h2>
                    <p>Cálculo de perda de carga e diâmetro de tubulações GLP/GN. NBR 15526.</p>
                </div>
            </a>

            <a href="incendio.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="hidraulica" style="animation-delay: 0.92s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/incendio.webp" type="image/webp">
                    <img src="assets/cards/incendio.jpg" alt="Sistemas de Incêndio" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Sistemas de Incêndio</h2>
                    <p>Cálculo de hidrantes, extintores e rotas de fuga. NBR 13714.</p>
                </div>
            </a>

            <!-- Topografia -->
            <a href="topografia.html" class="tool-card tool-card-image group relative rounded-3xl animate-fade-in opacity-0" data-category="outros" style="animation-delay: 0.94s">
                <picture class="w-full h-full block">
                    <source srcset="assets/cards/topografia.webp" type="image/webp">
                    <img src="assets/cards/topografia.jpg" alt="Topografia e Declividade" loading="lazy">
                </picture>
                <div class="sr-only">
                    <h2>Topografia e Declividade</h2>
                    <p>Nivelamento, declividade de rampas, azimutes e coordenadas.</p>
                </div>
            </a>
        </div>

        <!-- No Results State -->
        <div id="noResults" class="hidden text-center py-16 px-6 bg-slate-900/40 rounded-3xl border border-cyan-500/20 max-w-lg mx-auto my-12 backdrop-blur-md">
            <div class="text-5xl mb-3 animate-bounce">🔍</div>
            <h3 class="text-xl font-bold text-slate-100 mb-1 font-['Orbitron']">Nenhuma ferramenta encontrada</h3>
            <p class="text-sm text-slate-400 mb-6">Não encontramos resultados para sua busca ou categoria selecionada.</p>
            <button type="button" id="btnResetSearch" class="px-6 py-2.5 rounded-xl bg-cyan-500/20 hover:bg-cyan-500/30 text-cyan-300 border border-cyan-500/40 text-xs font-semibold uppercase tracking-wider transition-all duration-200 shadow-[0_0_15px_rgba(0,210,255,0.2)]">
                Limpar Pesquisa e Ver Todas (41)
            </button>
        </div>
    </main>

    <footer class="footer-clean py-10 text-center text-slate-500/60 border-t border-slate-800/40 mt-12">
        <p class="text-[11px] uppercase tracking-[0.2em] font-medium">&copy; 2026 4U.IA.BR &bull; ECALC Engenharia. Todos os direitos reservados.</p>
        <p class="text-[10px] tracking-wider mt-1 text-slate-500/80">Desenvolvido com tecnologia de ponta por <a href="https://4u.ia.br" target="_blank" class="text-cyan-400 hover:text-cyan-300 hover:underline transition-colors font-semibold">4u.ia.br</a>.</p>
    </footer>

    <!-- Back to top -->
    <button id="backToTop" class="back-to-top fixed bottom-8 right-8 w-12 h-12 rounded-full text-white flex items-center justify-center text-xl opacity-0 invisible transition-all duration-300 hover:-translate-y-1 z-50">
        ↑
    </button>

    <script>
        // Particles
        const particlesContainer = document.getElementById('particles');
        if (particlesContainer) {
            for (let i = 0; i < 25; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                particle.style.animationDuration = (15 + Math.random() * 10) + 's';
                particle.style.opacity = Math.random() * 0.5;
                particlesContainer.appendChild(particle);
            }
        }

        // Search & Filter Elements
        const searchInput = document.getElementById('searchInput');
        const clearSearchBtn = document.getElementById('clearSearch');
        const btnResetSearch = document.getElementById('btnResetSearch');
        const toolCards = document.querySelectorAll('.tool-card');
        const filterBtns = document.querySelectorAll('.filter-btn');
        const noResults = document.getElementById('noResults');
        const toolCountSpan = document.getElementById('toolCount');
        const activeFilterLabel = document.getElementById('activeFilterLabel');

        function normalizeText(str) {
            return (str || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim();
        }

        function filterTools() {
            const rawTerm = searchInput ? searchInput.value : '';
            const term = normalizeText(rawTerm);
            const activeFilterBtn = document.querySelector('.filter-btn.active');
            const category = activeFilterBtn ? activeFilterBtn.dataset.filter : 'all';
            let count = 0;

            if (clearSearchBtn) {
                if (rawTerm.trim().length > 0) {
                    clearSearchBtn.classList.remove('hidden');
                } else {
                    clearSearchBtn.classList.add('hidden');
                }
            }

            toolCards.forEach(card => {
                const title = normalizeText(card.querySelector('h2')?.textContent || card.querySelector('img')?.alt || '');
                const desc = normalizeText(card.querySelector('p')?.textContent || '');
                const cardCat = (card.dataset.category || '').toLowerCase();
                
                const matchesSearch = term === '' || title.includes(term) || desc.includes(term);
                const catList = cardCat.split(/\s+/);
                const matchesCategory = category === 'all' || catList.includes(category);

                if (matchesSearch && matchesCategory) {
                    card.classList.remove('hidden', 'is-hidden', 'absolute');
                    card.style.setProperty('display', 'flex', 'important');
                    card.style.opacity = '1';
                    count++;
                } else {
                    card.classList.add('hidden', 'is-hidden');
                    card.classList.remove('absolute');
                    card.style.setProperty('display', 'none', 'important');
                }
            });

            if (toolCountSpan) {
                toolCountSpan.textContent = count;
            }

            if (activeFilterLabel) {
                let labelParts = [];
                if (category !== 'all' && activeFilterBtn) {
                    labelParts.push(`em ${activeFilterBtn.textContent.trim()}`);
                }
                if (rawTerm.trim() !== '') {
                    labelParts.push(`para "${rawTerm.trim()}"`);
                }
                if (labelParts.length > 0) {
                    activeFilterLabel.textContent = labelParts.join(' ');
                    activeFilterLabel.classList.remove('hidden');
                } else {
                    activeFilterLabel.classList.add('hidden');
                }
            }
            
            if (count === 0) {
                noResults?.classList.remove('hidden');
            } else {
                noResults?.classList.add('hidden');
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', filterTools);
        }

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', () => {
                searchInput.value = '';
                clearSearchBtn.classList.add('hidden');
                searchInput.focus();
                filterTools();
            });
        }

        if (btnResetSearch) {
            btnResetSearch.addEventListener('click', () => {
                searchInput.value = '';
                if (clearSearchBtn) clearSearchBtn.classList.add('hidden');
                filterBtns.forEach(b => b.dataset.filter === 'all' ? b.classList.add('active') : b.classList.remove('active'));
                filterTools();
            });
        }

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                filterTools();
            });
        });

        // 6 Interactive Feature Pillars (from banner)
        document.querySelectorAll('.feature-pillar').forEach(pillar => {
            pillar.addEventListener('click', () => {
                const action = pillar.dataset.featureAction;
                if (!action) return;

                if (action === 'estrutural' || action === 'materiais') {
                    if (searchInput) searchInput.value = '';
                    if (clearSearchBtn) clearSearchBtn.classList.add('hidden');
                    filterBtns.forEach(b => {
                        if (b.dataset.filter === action) b.classList.add('active');
                        else b.classList.remove('active');
                    });
                    filterTools();
                } else if (action === 'formula') {
                    filterBtns.forEach(b => b.dataset.filter === 'all' ? b.classList.add('active') : b.classList.remove('active'));
                    if (searchInput) {
                        searchInput.value = 'cálculo';
                        if (clearSearchBtn) clearSearchBtn.classList.remove('hidden');
                    }
                    filterTools();
                } else if (action === 'dimension') {
                    if (searchInput) searchInput.value = '';
                    if (clearSearchBtn) clearSearchBtn.classList.add('hidden');
                    filterBtns.forEach(b => b.dataset.filter === 'hidraulica' ? b.classList.add('active') : b.classList.remove('active'));
                    filterTools();
                } else if (action === 'reports') {
                    filterBtns.forEach(b => b.dataset.filter === 'all' ? b.classList.add('active') : b.classList.remove('active'));
                    if (searchInput) {
                        searchInput.value = 'relatório';
                        if (clearSearchBtn) clearSearchBtn.classList.remove('hidden');
                    }
                    filterTools();
                } else if (action === 'nbr') {
                    filterBtns.forEach(b => b.dataset.filter === 'all' ? b.classList.add('active') : b.classList.remove('active'));
                    if (searchInput) {
                        searchInput.value = 'nbr';
                        if (clearSearchBtn) clearSearchBtn.classList.remove('hidden');
                    }
                    filterTools();
                }

                // Smooth scroll to search/grid
                const targetElem = document.getElementById('searchSection') || document.getElementById('toolsGrid');
                if (targetElem) {
                    const topPos = targetElem.getBoundingClientRect().top + window.pageYOffset - 90;
                    window.scrollTo({ top: topPos, behavior: 'smooth' });
                }
            });
        });

        // Keyboard Shortcut: '/' to focus search, 'Esc' to clear
        window.addEventListener('keydown', (e) => {
            if (e.key === '/' && document.activeElement !== searchInput) {
                e.preventDefault();
                searchInput?.focus();
                searchInput?.select();
            } else if (e.key === 'Escape' && document.activeElement === searchInput) {
                searchInput.value = '';
                if (clearSearchBtn) clearSearchBtn.classList.add('hidden');
                filterTools();
                searchInput.blur();
            }
        });

        // Back to Top
        const btnTop = document.getElementById('backToTop');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                btnTop?.classList.remove('opacity-0', 'invisible');
            } else {
                btnTop?.classList.add('opacity-0', 'invisible');
            }
        });

        btnTop?.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Mouse Move Glow Effect on Cards
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

        // Easter Egg Logo (5 cliques para o login admin)
        const logoLink = document.getElementById('logo-link');
        if (logoLink) {
            logoLink.addEventListener('click', function(e) {
                const now = Date.now();
                let clicks = parseInt(localStorage.getItem('logo_clicks') || '0');
                let lastClick = parseInt(localStorage.getItem('logo_last_click') || '0');

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