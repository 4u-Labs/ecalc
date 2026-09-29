/**
 * ECALC SUITE - Unified Navigation, App Switcher & Shared Project State
 * Core Component for the "Engineering Office Suite"
 */

(function () {
    'use strict';

    // 1. Registry of Suite Apps
    const SUITE_APPS = [
        // Estrutural & Geotécnica
        { id: 'vaos', title: 'Vigas e Vãos', cat: 'estrutural', icon: '🏗️', url: 'vaos.html', desc: 'Pré-dimensionamento e cálculo NBR 6118' },
        { id: 'pilares', title: 'Pilares em Concreto', cat: 'estrutural', icon: '🏛️', url: 'pilares.html', desc: 'Esbeltez, armaduras e momentos de 2ª ordem' },
        { id: 'lajes', title: 'Lajes Maciças e Treliçadas', cat: 'estrutural', icon: '📐', url: 'lajes.html', desc: 'Momentos, flechas e vigotas treliçadas' },
        { id: 'fundacoes', title: 'Fundações & Sapatas', cat: 'estrutural', icon: '⚓', url: 'fundacoes.html', desc: 'Sapatas isoladas, estacas e NSPT' },
        { id: 'arrimo', title: 'Muros de Arrimo', cat: 'estrutural', icon: '🧱', url: 'arrimo.html', desc: 'Empuxo, tombamento e deslizamento' },
        { id: 'aco', title: 'Estruturas de Aço', cat: 'estrutural', icon: '🔩', url: 'aco.html', desc: 'Perfis laminados e soldados NBR 8800' },
        { id: 'escadas', title: 'Escadas em Concreto', cat: 'estrutural', icon: '🪜', url: 'escadas.html', desc: 'Blondel, dimensionamento e armaduras' },
        { id: 'telhados', title: 'Estruturas de Telhados', cat: 'estrutural', icon: '🏠', url: 'telhados.html', desc: 'Tesouras, terças e cargas de vento' },
        { id: 'terraplenagem', title: 'Terraplenagem & Taludes', cat: 'estrutural', icon: '🚜', url: 'terraplenagem.html', desc: 'Corte, aterro e empolamento' },

        // Materiais & Concreto
        { id: 'dosagem', title: 'Dosagem de Concreto', cat: 'materiais', icon: '🧪', url: 'dosagem.html', desc: 'Métodos ACI, IPT e Curva de Abrams' },
        { id: 'consumo', title: 'Consumo de Materiais', cat: 'materiais', icon: '📦', url: 'consumo.html', desc: 'Argamassas, tintas e reboco por m²' },
        { id: 'alvenaria', title: 'Alvenaria Estrutural & Vedação', cat: 'materiais', icon: '🧱', url: 'alvenaria.html', desc: 'Blocos de concreto e cerâmicos' },
        { id: 'impermeabilizacao', title: 'Impermeabilização', cat: 'materiais', icon: '🛡️', url: 'impermeabilizacao.html', desc: 'Mantas, tintas asfálticas e telas' },
        { id: 'acabamentos', title: 'Pisos & Acabamentos', cat: 'materiais', icon: '✨', url: 'acabamentos.html', desc: 'Porcelanatos, rejuntes e argamassas AC' },

        // MEP / Instalações Prediais
        { id: 'tubulacoes', title: 'Tubulações & Hidráulica', cat: 'mep', icon: '💧', url: 'tubulacoes.html', desc: 'Método dos pesos Hunter e Hazen-Williams' },
        { id: 'eletrica', title: 'Instalações Elétricas', cat: 'mep', icon: '⚡', url: 'eletrica.html', desc: 'Quadro de cargas e queda de tensão NBR 5410' },
        { id: 'luminotecnico', title: 'Luminotécnico', cat: 'mep', icon: '💡', url: 'luminotecnico.html', desc: 'Método dos lúmens e distribuição no teto' },
        { id: 'solar', title: 'Energia Solar Fotovoltaica', cat: 'mep', icon: '☀️', url: 'solar.html', desc: 'Irradiação CRESESB, módulos e Payback' },
        { id: 'gas', title: 'Instalações de Gás (GLP/GN)', cat: 'mep', icon: '🔥', url: 'gas.html', desc: 'Dimensionamento de tubulações de gás' },
        { id: 'incendio', title: 'Sistemas de Incêndio', cat: 'mep', icon: '🚒', url: 'incendio.html', desc: 'Hidrantes, RTI e bombas de incêndio' },
        { id: 'fossa', title: 'Fossa Séptica & Sumidouro', cat: 'mep', icon: '🌱', url: 'fossa.html', desc: 'Tratamento de esgoto NBR 7229/13969' },
        { id: 'reservatorios', title: 'Reservatórios de Água', cat: 'mep', icon: '🚰', url: 'reservatorios.html', desc: 'Capacidade, cisterna e caixa d\'água' },
        { id: 'arcondicionado', title: 'Climatização & HVAC', cat: 'mep', icon: '❄️', url: 'arcondicionado.html', desc: 'Carga térmica em BTUs e renovação' },

        // Gestão, Custos & Campo
        { id: 'orcamento', title: 'Planilha Orçamentária SINAPI', cat: 'gestao', icon: '💰', url: 'orcamento.html', desc: 'Composições analíticas e BDI 2025' },
        { id: 'sistemas', title: 'Sistemas Construtivos', cat: 'gestao', icon: '📊', url: 'sistemas.html', desc: 'Comparativo de custos: Alvenaria vs Drywall vs Steel' },
        { id: 'diario', title: 'Diário de Obra Digital', cat: 'gestao', icon: '📋', url: 'diario.html', desc: 'Relatórios diários, clima, mão de obra e fotos' },
        { id: 'checklist', title: 'Fiscalização Técnica & Checklists', cat: 'gestao', icon: '✅', url: 'checklist.html', desc: 'Conformidade por etapa da obra' },
        { id: 'canteiro', title: 'Logística de Canteiro NR-18', cat: 'gestao', icon: '🏗️', url: 'canteiro.html', desc: 'Áreas de vivência e instalações sanitárias' },
        { id: 'seguranca', title: 'PGR & Segurança do Trabalho', cat: 'gestao', icon: '👷', url: 'seguranca/index.php', desc: 'Programa de Gerenciamento de Riscos' },

        // Suíte Central Integrada
        { id: 'cadclone', title: 'CADClone 2D (Web CAD)', cat: 'suite', icon: '📐', url: '../cadclone/', desc: 'Visualizador e editor de projetos DWG/DXF' },
        { id: 'fotolaudo', title: 'FotoLaudo (Câmera Técnica)', cat: 'suite', icon: '📸', url: '../fotolaudo/', desc: 'Laudos periciais com geolocalização e fotos' },
        { id: 'office', title: 'OfficeClone Suite', cat: 'suite', icon: '📄', url: 'https://4u.ia.br/app/office/', desc: 'Documentos, planilhas e apresentações' },
        { id: 'powercalc', title: 'PowerCalc Científica', cat: 'suite', icon: '🧮', url: 'https://4u.ia.br/app/powercalc/', desc: '57+ fórmulas de engenharia e física' },
        { id: 'soundmeter', title: 'SoundMeter Acústico', cat: 'suite', icon: '🔊', url: 'https://4u.ia.br/app/soundmeter/', desc: 'Medição em dB e isolamento acústico NBR 10151' },
        { id: 'contratos', title: '4uSign Contratos & ART', cat: 'suite', icon: '✍️', url: 'https://4u.ia.br/app/4usign/#hub', desc: 'Assinaturas digitais de projetos e contratos' },
        { id: 'keepai', title: 'Smart Notes IA', cat: 'suite', icon: '💡', url: 'https://4u.ia.br/app/keepai/', desc: 'Bloco de notas inteligente para campo e escritório' },
        { id: 'nbr', title: 'Guia de Normas ABNT NBR', cat: 'suite', icon: '⚙️', url: 'nbr.html', desc: 'Índice interativo de exigências técnicas' },
        { id: 'conversor', title: 'Conversor de Unidades', cat: 'suite', icon: '🔄', url: 'conversor.html', desc: 'Pressão, momento, vazão, tensão e volume' }
    ];

    // 2. Project State Management
    const STORAGE_KEY = 'ecalc_suite_project';

    function getProject() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (raw) return JSON.parse(raw);
        } catch (e) {
            console.error('Erro ao ler projeto do localStorage:', e);
        }
        return {
            nomeObra: 'Edifício Residencial Padrão',
            cliente: 'Cliente Padrão',
            responsavelTecnico: 'Engenheiro Civil Responsável',
            creaCau: 'CREA 123456/D',
            artRrt: 'ART 2026/001452',
            cidadeUf: 'São Paulo - SP',
            data: new Date().toISOString().split('T')[0]
        };
    }

    function saveProject(data) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            updateProjectBadge(data);
            window.dispatchEvent(new CustomEvent('ecalc:project-updated', { detail: data }));
            showToast('Projeto atualizado com sucesso!');
        } catch (e) {
            console.error('Erro ao salvar projeto:', e);
        }
    }

    function showToast(msg) {
        let toast = document.querySelector('.ecalc-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'ecalc-toast';
            document.body.appendChild(toast);
        }
        toast.textContent = msg;
        toast.style.display = 'block';
        setTimeout(() => {
            if (toast) toast.style.display = 'none';
        }, 3000);
    }

    // 3. UI Injections
    function injectStyles() {
        if (!document.getElementById('ecalc-suite-critical-css')) {
            const style = document.createElement('style');
            style.id = 'ecalc-suite-critical-css';
            style.textContent = `
                @media (max-width: 768px) {
                    #ecalc-suite-bar .ecalc-project-pill,
                    #ecalc-suite-bar .ecalc-nav-cadclone,
                    #ecalc-suite-bar .ecalc-nav-office,
                    #ecalc-suite-bar [data-ecalc-hide-mobile="true"],
                    .ecalc-project-pill,
                    .ecalc-nav-cadclone,
                    .ecalc-nav-office {
                        display: none !important;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        if (!document.getElementById('ecalc-suite-css')) {
            const link = document.createElement('link');
            link.id = 'ecalc-suite-css';
            link.rel = 'stylesheet';
            // Compute relative path to assets/suite/suite-nav.css with cache-busting
            const isSubdir = window.location.pathname.includes('/seguranca/');
            link.href = (isSubdir ? '../' : '') + 'assets/suite/suite-nav.css?v=20260929_3';
            document.head.appendChild(link);
        }
    }

    function injectSuiteBar() {
        if (document.getElementById('ecalc-suite-bar')) return;

        const project = getProject();
        const isSubdir = window.location.pathname.includes('/seguranca/');
        const rootPath = isSubdir ? '../' : './';

        const bar = document.createElement('div');
        bar.id = 'ecalc-suite-bar';
        bar.innerHTML = `
            <div class="ecalc-bar-left">
                <a href="${rootPath}index.php" class="ecalc-brand" title="Voltar ao Hub ECALC">
                    <img src="${rootPath}assets/icon-ecalc-64.png" alt="ECALC" class="ecalc-brand-icon-img">
                    <span>ECALC</span>
                    <span class="ecalc-brand-tag">SUITE</span>
                </a>

                <button type="button" class="ecalc-btn-icon" id="ecalc-btn-switcher" title="Alternar Aplicativo (9 Pontos)">
                    <div class="ecalc-grid-icon">
                        <span></span><span></span><span></span>
                        <span></span><span></span><span></span>
                        <span></span><span></span><span></span>
                    </div>
                </button>

                <div class="ecalc-project-pill hidden md:inline-flex" id="ecalc-btn-project" data-ecalc-hide-mobile="true" title="Editar dados da obra ativa">
                    <span class="dot"></span>
                    <span class="ecalc-project-name" id="ecalc-bar-project-name">${project.nomeObra || 'Definir Obra'}</span>
                    <svg style="width: 12px; height: 12px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>

            <div class="ecalc-bar-right">
                <a href="${isSubdir ? '../../' : '../'}cadclone/" target="_blank" class="ecalc-nav-btn ecalc-nav-cadclone hidden md:inline-flex" data-ecalc-hide-mobile="true" title="Abrir Editor CADClone">
                    <span>📐 CADClone</span>
                </a>
                <a href="https://4u.ia.br/app/office/" target="_blank" class="ecalc-nav-btn ecalc-nav-office hidden md:inline-flex" data-ecalc-hide-mobile="true" title="Abrir OfficeClone Suite">
                    <span>📄 OfficeClone</span>
                </a>
                <a href="${rootPath}index.php" class="ecalc-nav-btn ecalc-nav-hub" title="Hub Principal">
                    <span>🏠 Hub</span>
                </a>
            </div>
        `;

        document.body.prepend(bar);
        setupBarEvents();
    }

    function updateProjectBadge(project) {
        const badge = document.getElementById('ecalc-bar-project-name');
        if (badge) {
            badge.textContent = project.nomeObra || 'Definir Obra';
        }
    }

    function injectAppSwitcherModal() {
        if (document.getElementById('ecalc-switcher-modal')) return;

        const isSubdir = window.location.pathname.includes('/seguranca/');
        const prefix = isSubdir ? '../' : '';

        const modal = document.createElement('div');
        modal.id = 'ecalc-switcher-modal';
        modal.className = 'ecalc-modal-backdrop';
        modal.innerHTML = `
            <div class="ecalc-switcher-window">
                <div class="ecalc-switcher-header">
                    <div class="ecalc-switcher-search">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input type="text" id="ecalc-switcher-input" placeholder="Alternar rapidamente para qualquer ferramenta... (ex: Vigas, SINAPI, Elétrica)">
                    </div>
                    <button type="button" class="ecalc-btn-icon" id="ecalc-close-switcher" title="Fechar (Esc)">✕</button>
                </div>
                <div class="ecalc-switcher-tabs">
                    <button class="ecalc-tab-btn active" data-filter="all">Todas (${SUITE_APPS.length})</button>
                    <button class="ecalc-tab-btn" data-filter="estrutural">🏗️ Estruturas</button>
                    <button class="ecalc-tab-btn" data-filter="materiais">🧱 Concreto & Materiais</button>
                    <button class="ecalc-tab-btn" data-filter="mep">💧 Instalações MEP</button>
                    <button class="ecalc-tab-btn" data-filter="gestao">📊 Gestão & Campo</button>
                    <button class="ecalc-tab-btn" data-filter="suite">🛠️ Suíte Global</button>
                </div>
                <div class="ecalc-switcher-body" id="ecalc-switcher-cards">
                    ${SUITE_APPS.map(app => `
                        <a href="${app.url.startsWith('http') || app.url.startsWith('../') ? app.url : prefix + app.url}" class="ecalc-app-card" data-cat="${app.cat}">
                            <div class="ecalc-app-icon">${app.icon}</div>
                            <div class="ecalc-app-info">
                                <div class="ecalc-app-title">${app.title}</div>
                                <div class="ecalc-app-cat">${app.desc}</div>
                            </div>
                        </a>
                    `).join('')}
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        setupSwitcherEvents();
    }

    function injectProjectDrawer() {
        if (document.getElementById('ecalc-project-drawer')) return;

        const drawer = document.createElement('div');
        drawer.id = 'ecalc-project-drawer';
        drawer.innerHTML = `
            <div class="ecalc-drawer-backdrop" id="ecalc-drawer-backdrop"></div>
            <div class="ecalc-drawer" id="ecalc-drawer-panel">
                <div class="ecalc-drawer-header">
                    <h3>
                        <span>🏗️</span>
                        <span>Projeto Atual Compartilhado</span>
                    </h3>
                    <button type="button" class="ecalc-btn-icon" id="ecalc-close-drawer">✕</button>
                </div>
                <div class="ecalc-drawer-body">
                    <p style="font-size: 0.8rem; color: #94a3b8; margin: 0 0 0.5rem 0;">
                        Os dados definidos aqui preenchem automaticamente os relatórios, memoriais ABNT, ART e cabeçalhos em todas as ferramentas da suíte.
                    </p>
                    <div class="ecalc-form-group">
                        <label>Nome do Edifício / Obra</label>
                        <input type="text" id="ecalc-field-obra" placeholder="Ex: Residencial Vista do Parque">
                    </div>
                    <div class="ecalc-form-group">
                        <label>Cliente / Contratante</label>
                        <input type="text" id="ecalc-field-cliente" placeholder="Ex: Construtora Alfa Ltda">
                    </div>
                    <div class="ecalc-form-group">
                        <label>Responsável Técnico (RT)</label>
                        <input type="text" id="ecalc-field-rt" placeholder="Ex: Eng. Fabiano Santos">
                    </div>
                    <div class="ecalc-form-group">
                        <label>Registro CREA / CAU</label>
                        <input type="text" id="ecalc-field-crea" placeholder="Ex: CREA 123456/D - SP">
                    </div>
                    <div class="ecalc-form-group">
                        <label>Número ART / RRT</label>
                        <input type="text" id="ecalc-field-art" placeholder="Ex: ART 2026/089421">
                    </div>
                    <div class="ecalc-form-group">
                        <label>Cidade / UF</label>
                        <input type="text" id="ecalc-field-cidade" placeholder="Ex: São Paulo - SP">
                    </div>
                    <div class="ecalc-form-group">
                        <label>Data de Emissão</label>
                        <input type="date" id="ecalc-field-data">
                    </div>
                </div>
                <div class="ecalc-drawer-footer">
                    <button type="button" class="ecalc-btn-save" id="ecalc-save-project">Salvar na Suíte</button>
                    <button type="button" class="ecalc-btn-clear" id="ecalc-clear-project" title="Limpar dados">Limpar</button>
                </div>
            </div>
        `;

        document.body.appendChild(drawer);
        setupDrawerEvents();
    }

    // 4. Event Handlers
    function setupBarEvents() {
        const btnSwitcher = document.getElementById('ecalc-btn-switcher');
        const btnProject = document.getElementById('ecalc-btn-project');

        btnSwitcher?.addEventListener('click', openAppSwitcher);
        btnProject?.addEventListener('click', openProjectDrawer);
    }

    function openAppSwitcher() {
        const modal = document.getElementById('ecalc-switcher-modal');
        if (modal) {
            modal.classList.add('active');
            const input = document.getElementById('ecalc-switcher-input');
            setTimeout(() => input?.focus(), 50);
        }
    }

    function closeAppSwitcher() {
        const modal = document.getElementById('ecalc-switcher-modal');
        if (modal) modal.classList.remove('active');
    }

    function setupSwitcherEvents() {
        const modal = document.getElementById('ecalc-switcher-modal');
        const closeBtn = document.getElementById('ecalc-close-switcher');
        const input = document.getElementById('ecalc-switcher-input');
        const tabBtns = modal?.querySelectorAll('.ecalc-tab-btn');
        const cards = modal?.querySelectorAll('.ecalc-app-card');

        closeBtn?.addEventListener('click', closeAppSwitcher);
        modal?.addEventListener('click', (e) => {
            if (e.target === modal) closeAppSwitcher();
        });

        function filterCards() {
            const query = (input?.value || '').toLowerCase().trim();
            const activeTab = modal?.querySelector('.ecalc-tab-btn.active')?.dataset.filter || 'all';

            cards?.forEach(card => {
                const title = card.querySelector('.ecalc-app-title')?.textContent.toLowerCase() || '';
                const desc = card.querySelector('.ecalc-app-cat')?.textContent.toLowerCase() || '';
                const cat = card.dataset.cat;

                const matchQuery = query === '' || title.includes(query) || desc.includes(query);
                const matchTab = activeTab === 'all' || cat === activeTab;

                card.style.display = (matchQuery && matchTab) ? 'flex' : 'none';
            });
        }

        input?.addEventListener('input', filterCards);

        tabBtns?.forEach(btn => {
            btn.addEventListener('click', () => {
                tabBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                filterCards();
            });
        });
    }

    function openProjectDrawer() {
        const drawer = document.getElementById('ecalc-drawer-panel');
        const backdrop = document.getElementById('ecalc-drawer-backdrop');
        const project = getProject();

        // Populate form
        document.getElementById('ecalc-field-obra').value = project.nomeObra || '';
        document.getElementById('ecalc-field-cliente').value = project.cliente || '';
        document.getElementById('ecalc-field-rt').value = project.responsavelTecnico || '';
        document.getElementById('ecalc-field-crea').value = project.creaCau || '';
        document.getElementById('ecalc-field-art').value = project.artRrt || '';
        document.getElementById('ecalc-field-cidade').value = project.cidadeUf || '';
        document.getElementById('ecalc-field-data').value = project.data || new Date().toISOString().split('T')[0];

        drawer?.classList.add('active');
        backdrop?.classList.add('active');
    }

    function closeProjectDrawer() {
        const drawer = document.getElementById('ecalc-drawer-panel');
        const backdrop = document.getElementById('ecalc-drawer-backdrop');
        drawer?.classList.remove('active');
        backdrop?.classList.remove('active');
    }

    function setupDrawerEvents() {
        const closeBtn = document.getElementById('ecalc-close-drawer');
        const backdrop = document.getElementById('ecalc-drawer-backdrop');
        const saveBtn = document.getElementById('ecalc-save-project');
        const clearBtn = document.getElementById('ecalc-clear-project');

        closeBtn?.addEventListener('click', closeProjectDrawer);
        backdrop?.addEventListener('click', closeProjectDrawer);

        saveBtn?.addEventListener('click', () => {
            const updated = {
                nomeObra: document.getElementById('ecalc-field-obra').value.trim(),
                cliente: document.getElementById('ecalc-field-cliente').value.trim(),
                responsavelTecnico: document.getElementById('ecalc-field-rt').value.trim(),
                creaCau: document.getElementById('ecalc-field-crea').value.trim(),
                artRrt: document.getElementById('ecalc-field-art').value.trim(),
                cidadeUf: document.getElementById('ecalc-field-cidade').value.trim(),
                data: document.getElementById('ecalc-field-data').value
            };
            saveProject(updated);
            closeProjectDrawer();
        });

        clearBtn?.addEventListener('click', () => {
            if (confirm('Deseja limpar os dados do projeto atual?')) {
                const empty = {
                    nomeObra: '',
                    cliente: '',
                    responsavelTecnico: '',
                    creaCau: '',
                    artRrt: '',
                    cidadeUf: '',
                    data: new Date().toISOString().split('T')[0]
                };
                saveProject(empty);
                openProjectDrawer();
            }
        });
    }

    // Keyboard Shortcuts
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAppSwitcher();
            closeProjectDrawer();
        }
        // Alt + M to open App Switcher
        if (e.altKey && (e.key === 'm' || e.key === 'M')) {
            e.preventDefault();
            openAppSwitcher();
        }
        // Alt + P to open Project Drawer
        if (e.altKey && (e.key === 'p' || e.key === 'P')) {
            e.preventDefault();
            openProjectDrawer();
        }
    });

    // 5. Expose Global Public API
    window.ECALC_SUITE = {
        getProject,
        saveProject,
        openProjectDrawer,
        closeProjectDrawer,
        openAppSwitcher,
        closeAppSwitcher,
        apps: SUITE_APPS
    };

    // Auto-init on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSuite);
    } else {
        initSuite();
    }

    function ensureFavicon() {
        const isSubdir = window.location.pathname.includes('/seguranca/');
        const rootPath = isSubdir ? '../' : './';
        let link = document.querySelector("link[rel*='icon']");
        if (!link) {
            link = document.createElement('link');
            link.rel = 'icon';
            document.head.appendChild(link);
        }
        link.type = 'image/png';
        link.href = rootPath + 'assets/icon-ecalc-32.png';

        let apple = document.querySelector("link[rel='apple-touch-icon']");
        if (!apple) {
            apple = document.createElement('link');
            apple.rel = 'apple-touch-icon';
            document.head.appendChild(apple);
        }
        apple.href = rootPath + 'assets/icon-ecalc-192.png';
    }

    function initSuite() {
        injectStyles();
        ensureFavicon();
        injectSuiteBar();
        injectAppSwitcherModal();
        injectProjectDrawer();
    }
})();
