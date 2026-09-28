/**
 * ECALC SUITE - Official ABNT Calculation Memorial Engine
 * Generates professional technical calculation reports for ART/CREA/CAU
 */

(function () {
    'use strict';

    function injectMemorialStyles() {
        if (document.getElementById('ecalc-memorial-css')) return;
        const style = document.createElement('style');
        style.id = 'ecalc-memorial-css';
        style.textContent = `
            .ecalc-memorial-modal {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.85);
                backdrop-filter: blur(8px);
                z-index: 10010;
                display: none;
                overflow-y: auto;
                padding: 20px 10px;
                font-family: 'Inter', system-ui, -apple-system, sans-serif;
            }
            .ecalc-memorial-modal.active {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .ecalc-memorial-actions {
                position: sticky;
                top: 10px;
                z-index: 10015;
                display: flex;
                gap: 10px;
                margin-bottom: 20px;
                background: rgba(15, 23, 42, 0.9);
                padding: 10px 18px;
                border-radius: 40px;
                border: 1px solid rgba(0, 210, 255, 0.4);
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
            }
            .ecalc-memorial-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 16px;
                border-radius: 20px;
                font-size: 0.85rem;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.2s;
                border: none;
            }
            .ecalc-memorial-btn-print {
                background: linear-gradient(135deg, #0066FF, #00D2FF);
                color: #ffffff;
                box-shadow: 0 4px 15px rgba(0, 210, 255, 0.3);
            }
            .ecalc-memorial-btn-print:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 20px rgba(0, 210, 255, 0.5);
            }
            .ecalc-memorial-btn-close {
                background: rgba(255, 255, 255, 0.1);
                color: #cbd5e1;
            }
            .ecalc-memorial-btn-close:hover {
                background: rgba(239, 68, 68, 0.2);
                color: #fca5a5;
            }
            
            /* A4 Paper Container */
            .ecalc-a4-sheet {
                width: 210mm;
                min-height: 297mm;
                padding: 18mm 20mm;
                margin: 0 auto 30px auto;
                background: #ffffff;
                color: #0f172a;
                box-shadow: 0 15px 45px rgba(0, 0, 0, 0.5);
                box-sizing: border-box;
                font-size: 11pt;
                line-height: 1.5;
            }

            .ecalc-sheet-header {
                border-bottom: 2px solid #0f172a;
                padding-bottom: 12px;
                margin-bottom: 16px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 15px;
            }
            .ecalc-sheet-logo {
                font-size: 1.3rem;
                font-weight: 900;
                letter-spacing: -0.02em;
                color: #0284c7;
            }
            .ecalc-sheet-title {
                text-align: center;
                flex: 1;
            }
            .ecalc-sheet-title h1 {
                font-size: 13pt;
                font-weight: 800;
                margin: 0;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #0f172a;
            }
            .ecalc-sheet-title p {
                font-size: 9pt;
                margin: 2px 0 0 0;
                color: #475569;
                font-weight: 600;
            }
            .ecalc-sheet-meta {
                text-align: right;
                font-size: 8pt;
                color: #64748b;
                line-height: 1.3;
            }

            /* Project Table */
            .ecalc-table-project {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 16px;
                font-size: 9pt;
                background: #f8fafc;
                border: 1px solid #cbd5e1;
            }
            .ecalc-table-project td {
                padding: 5px 8px;
                border: 1px solid #cbd5e1;
            }
            .ecalc-table-project strong {
                color: #1e293b;
            }

            /* Section Headings */
            .ecalc-sec-title {
                font-size: 10.5pt;
                font-weight: 800;
                color: #0369a1;
                border-left: 3px solid #0284c7;
                padding-left: 8px;
                margin: 14px 0 8px 0;
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }

            /* Parameters Grid */
            .ecalc-data-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 6px 14px;
                font-size: 9pt;
                margin-bottom: 12px;
                background: #fdfdfd;
                padding: 10px;
                border: 1px solid #e2e8f0;
                border-radius: 4px;
            }
            .ecalc-data-row {
                display: flex;
                justify-content: space-between;
                border-bottom: 1px dotted #cbd5e1;
                padding-bottom: 2px;
            }
            .ecalc-data-label {
                color: #475569;
            }
            .ecalc-data-val {
                font-weight: 700;
                color: #0f172a;
            }

            /* Formula Box */
            .ecalc-formula-box {
                background: #f1f5f9;
                border-left: 3px solid #3b82f6;
                padding: 8px 12px;
                margin: 8px 0;
                font-family: 'Courier New', monospace;
                font-size: 9pt;
                color: #1e293b;
                border-radius: 0 4px 4px 0;
            }

            /* Compliance Badges */
            .ecalc-status-badge {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                padding: 3px 8px;
                border-radius: 4px;
                font-size: 8pt;
                font-weight: 800;
                text-transform: uppercase;
            }
            .ecalc-status-pass {
                background: #dcfce7;
                color: #166534;
                border: 1px solid #86efac;
            }
            .ecalc-status-warn {
                background: #fef3c7;
                color: #92400e;
                border: 1px solid #fde68a;
            }
            .ecalc-status-fail {
                background: #fee2e2;
                color: #991b1b;
                border: 1px solid #fca5a5;
            }

            /* Scaled Drawing Canvas/SVG */
            .ecalc-drawing-box {
                margin: 12px 0;
                padding: 12px;
                border: 1px dashed #94a3b8;
                border-radius: 6px;
                display: flex;
                flex-direction: column;
                align-items: center;
                background: #fafafa;
            }
            .ecalc-drawing-box svg {
                max-width: 100%;
                height: auto;
            }

            /* Signature Block */
            .ecalc-sheet-footer {
                margin-top: 25px;
                padding-top: 14px;
                border-top: 1px solid #cbd5e1;
                display: flex;
                align-items: flex-end;
                justify-content: space-between;
                font-size: 8pt;
            }
            .ecalc-signature-line {
                width: 250px;
                text-align: center;
            }
            .ecalc-signature-line .line {
                border-top: 1px solid #0f172a;
                margin-bottom: 4px;
            }
            .ecalc-qr-code {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .ecalc-qr-code img {
                width: 60px;
                height: 60px;
            }

            /* PRINT CSS: Strict A4 Optimization */
            @media print {
                body * {
                    visibility: hidden;
                }
                .ecalc-memorial-modal,
                .ecalc-memorial-modal * {
                    visibility: visible;
                }
                .ecalc-memorial-modal {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    background: transparent !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    display: block !important;
                }
                .ecalc-memorial-actions {
                    display: none !important;
                }
                .ecalc-a4-sheet {
                    width: 100% !important;
                    min-height: auto !important;
                    box-shadow: none !important;
                    padding: 10mm 12mm !important;
                    margin: 0 !important;
                    page-break-after: avoid;
                }
                @page {
                    size: A4 portrait;
                    margin: 8mm;
                }
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Open Memorial Modal
     * @param {Object} options
     * options = {
     *   titulo: 'Memorial de Cálculo de Viga em Concreto Armado',
     *   norma: 'ABNT NBR 6118:2023',
     *   elemento: 'Viga V-101 (Seção 15x40 cm)',
     *   inputs: [{ label: 'Vão Teórico (L)', val: '5.00 m' }, ...],
     *   resultados: [{ label: 'Momento Fletor (Md)', val: '45.2 kNm' }, ...],
     *   formulas: ['Md = 1.4 * (g + q) * L² / 8', 'As = Md / (0.85 * fyd * d)'],
     *   verificacoes: [
     *      { item: 'Estado Limite Último (ELU - Flexão)', calc: 'As,calc = 3.80 cm² ≤ As,max', status: 'pass' },
     *      { item: 'Estado Limite de Serviço (ELS - Flecha)', calc: 'a,tot = 1.42 cm ≤ L/250 = 2.00 cm', status: 'pass' }
     *   ],
     *   svgSection: '<svg>...</svg>',
     *   notas: 'Memorial gerado pela Suíte ECALC PRO em conformidade com as normas ABNT vigentes.'
     * }
     */
    function gerarMemorial(options) {
        injectMemorialStyles();

        const project = (typeof window.ECALC_SUITE !== 'undefined' && window.ECALC_SUITE.getProject)
            ? window.ECALC_SUITE.getProject()
            : {
                nomeObra: 'Edifício Residencial Padrão',
                cliente: 'Cliente Padrão',
                responsavelTecnico: 'Engenheiro Civil Responsável',
                creaCau: 'CREA 123456/D',
                artRrt: 'ART 2026/001452',
                cidadeUf: 'São Paulo - SP',
                data: new Date().toISOString().split('T')[0]
            };

        let modal = document.getElementById('ecalc-memorial-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'ecalc-memorial-modal';
            modal.className = 'ecalc-memorial-modal';
            document.body.appendChild(modal);
        }

        const dataFormatada = project.data
            ? project.data.split('-').reverse().join('/')
            : new Date().toLocaleDateString('pt-BR');

        const qrText = encodeURIComponent(`ECALC-VERIFY:${project.artRrt || 'ART-VALID'}:${project.creaCau || 'CREA'}`);
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${qrText}`;

        modal.innerHTML = `
            <div class="ecalc-memorial-actions">
                <button type="button" class="ecalc-memorial-btn ecalc-memorial-btn-print" id="ecalc-mem-print-btn">
                    <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Imprimir / Salvar em PDF</span>
                </button>
                <button type="button" class="ecalc-memorial-btn ecalc-memorial-btn-close" id="ecalc-mem-close-btn">
                    <span>✕ Fechar</span>
                </button>
            </div>

            <div class="ecalc-a4-sheet">
                <!-- Cabeçalho Oficial -->
                <div class="ecalc-sheet-header">
                    <div class="ecalc-sheet-logo">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <img src="${(window.location.pathname.includes('/seguranca/') ? '../' : './')}assets/icon-ecalc-64.png" alt="ECALC" style="width: 34px; height: 34px; border-radius: 7px; object-fit: cover; box-shadow: 0 1px 4px rgba(0,0,0,0.15);">
                            <div>
                                <span style="font-size: 1.15rem; font-weight: 900; color: #0284c7; letter-spacing: -0.02em;">ECALC</span>
                                <span style="font-size: 7.5pt; display: block; color: #475569; font-weight: 600;">ENGENHARIA PRO</span>
                            </div>
                        </div>
                    </div>
                    <div class="ecalc-sheet-title">
                        <h1>${options.titulo || 'Memorial de Cálculo Técnico'}</h1>
                        <p>${options.norma || 'Norma Regulamentadora ABNT'} | ${options.elemento || 'Elemento Estrutural'}</p>
                    </div>
                    <div class="ecalc-sheet-meta">
                        <div><strong>Data:</strong> ${dataFormatada}</div>
                        <div><strong>Folha:</strong> 1/1</div>
                        <div><strong>Rev:</strong> 00</div>
                    </div>
                </div>

                <!-- Tabela de Identificação da Obra / RT -->
                <table class="ecalc-table-project">
                    <tr>
                        <td style="width: 50%;"><strong>Edifício / Obra:</strong> ${project.nomeObra || '-'}</td>
                        <td style="width: 50%;"><strong>Cliente / Contratante:</strong> ${project.cliente || '-'}</td>
                    </tr>
                    <tr>
                        <td><strong>Responsável Técnico:</strong> ${project.responsavelTecnico || '-'}</td>
                        <td><strong>Registro Profissional:</strong> ${project.creaCau || '-'}</td>
                    </tr>
                    <tr>
                        <td><strong>Número ART / RRT:</strong> ${project.artRrt || '-'}</td>
                        <td><strong>Localização:</strong> ${project.cidadeUf || '-'}</td>
                    </tr>
                </table>

                <!-- 1. Parâmetros de Entrada -->
                <div class="ecalc-sec-title">1. Dados de Entrada e Materiais</div>
                <div class="ecalc-data-grid">
                    ${(options.inputs || []).map(inp => `
                        <div class="ecalc-data-row">
                            <span class="ecalc-data-label">${inp.label}</span>
                            <span class="ecalc-data-val">${inp.val}</span>
                        </div>
                    `).join('')}
                </div>

                <!-- 2. Memória de Cálculo & Fórmulas -->
                ${(options.formulas && options.formulas.length > 0) ? `
                    <div class="ecalc-sec-title">2. Formulação Normativa e Memória de Cálculo</div>
                    ${options.formulas.map(f => `<div class="ecalc-formula-box">${f}</div>`).join('')}
                ` : ''}

                <!-- 3. Resultados Obtidos -->
                <div class="ecalc-sec-title">3. Resultados Dimensionados</div>
                <div class="ecalc-data-grid">
                    ${(options.resultados || []).map(res => `
                        <div class="ecalc-data-row">
                            <span class="ecalc-data-label">${res.label}</span>
                            <span class="ecalc-data-val" style="color: #0369a1;">${res.val}</span>
                        </div>
                    `).join('')}
                </div>

                <!-- 4. Verificações de Estados Limites (ELU / ELS) -->
                ${(options.verificacoes && options.verificacoes.length > 0) ? `
                    <div class="ecalc-sec-title">4. Verificações de Estados Limites (ELU & ELS)</div>
                    <table class="ecalc-table-project" style="margin-bottom: 8px;">
                        <thead>
                            <tr style="background: #e2e8f0; font-weight: 700;">
                                <td style="width: 45%;">Verificação</td>
                                <td style="width: 35%;">Critério de Verificação</td>
                                <td style="width: 20%; text-align: center;">Resultado</td>
                            </tr>
                        </thead>
                        <tbody>
                            ${options.verificacoes.map(v => {
                                const badgeClass = v.status === 'pass' ? 'ecalc-status-pass' : (v.status === 'warn' ? 'ecalc-status-warn' : 'ecalc-status-fail');
                                const badgeText = v.status === 'pass' ? '✓ CONFORME' : (v.status === 'warn' ? '⚠ ATENÇÃO' : '✕ NÃO CONFORME');
                                return `
                                    <tr>
                                        <td><strong>${v.item}</strong></td>
                                        <td>${v.calc}</td>
                                        <td style="text-align: center;">
                                            <span class="ecalc-status-badge ${badgeClass}">${badgeText}</span>
                                        </td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                ` : ''}

                <!-- 5. Seção Gráfica em Escala -->
                ${options.svgSection ? `
                    <div class="ecalc-sec-title">${options.tituloGrafico || options.svgTitle || ((options.norma || '').includes('6118') ? '5. Seção Gráfica e Detalhamento da Armadura' : '5. Seção Gráfica e Esquema Técnico')}</div>
                    <div class="ecalc-drawing-box">
                        ${options.svgSection}
                        <span style="font-size: 8pt; color: #64748b; margin-top: 4px;">${options.legendaGrafico || options.svgLegend || ((options.norma || '').includes('6118') ? 'Detalhamento esquemático em escala (dimensões em cm e bitolas nominais).' : 'Detalhamento esquemático em escala.')}</span>
                    </div>
                ` : ''}

                <!-- 6. Notas Técnicas -->
                <div style="font-size: 8pt; color: #64748b; margin-top: 10px; line-height: 1.4;">
                    <strong>Notas:</strong> ${options.notas || 'O presente memorial reflete os cálculos analíticos em conformidade com as normas ABNT aplicáveis. O dimensionamento final deve ser aprovado pelo Responsável Técnico.'}
                </div>

                <!-- Rodapé Oficial com Assinatura & QR Code -->
                <div class="ecalc-sheet-footer">
                    <div class="ecalc-qr-code">
                        <img src="${qrUrl}" alt="QR Autenticação" onerror="this.style.display='none'">
                        <div>
                            <strong>Autenticidade Técnica</strong><br>
                            <span style="color: #64748b;">Validação ART/CREA</span><br>
                            <span style="color: #0284c7; font-family: monospace;">${project.artRrt || 'ART-DIGITAL'}</span>
                        </div>
                    </div>

                    <div class="ecalc-signature-line">
                        <div class="line"></div>
                        <strong>${project.responsavelTecnico || 'Responsável Técnico'}</strong><br>
                        <span>${project.creaCau || 'Registro CREA/CAU'}</span>
                    </div>
                </div>
            </div>
        `;

        modal.classList.add('active');

        document.getElementById('ecalc-mem-close-btn').onclick = () => modal.classList.remove('active');
        document.getElementById('ecalc-mem-print-btn').onclick = () => window.print();

        modal.onclick = (e) => {
            if (e.target === modal) modal.classList.remove('active');
        };
    }

    // Expose API
    window.ECALC_MEMORIAL = {
        gerar: gerarMemorial
    };
})();
