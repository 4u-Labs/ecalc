/**
 * ECALC SUITE - CAD Bridge & DXF Generator (AutoCAD R12/2000 ASCII)
 * Generates technical 2D drawings and bridges directly into CADClone
 */

(function () {
    'use strict';

    class DXFBuilder {
        constructor() {
            this.entities = [];
            this.layers = new Set(['0', 'CONCRETO', 'ARMADURA', 'ESTRIBOS', 'COTAS', 'TEXTO']);
        }

        addLine(x1, y1, x2, y2, layer = '0', color = 7) {
            this.entities.push(`  0\nLINE\n  8\n${layer}\n 62\n${color}\n 10\n${x1.toFixed(3)}\n 20\n${y1.toFixed(3)}\n 30\n0.0\n 11\n${x2.toFixed(3)}\n 21\n${y2.toFixed(3)}\n 31\n0.0`);
        }

        addRect(x, y, w, h, layer = '0', color = 7) {
            this.addLine(x, y, x + w, y, layer, color);
            this.addLine(x + w, y, x + w, y + h, layer, color);
            this.addLine(x + w, y + h, x, y + h, layer, color);
            this.addLine(x, y + h, x, y, layer, color);
        }

        addCircle(cx, cy, r, layer = '0', color = 7) {
            this.entities.push(`  0\nCIRCLE\n  8\n${layer}\n 62\n${color}\n 10\n${cx.toFixed(3)}\n 20\n${cy.toFixed(3)}\n 30\n0.0\n 40\n${r.toFixed(3)}`);
        }

        addText(x, y, height, text, layer = 'TEXTO', color = 7) {
            this.entities.push(`  0\nTEXT\n  8\n${layer}\n 62\n${color}\n 10\n${x.toFixed(3)}\n 20\n${y.toFixed(3)}\n 30\n0.0\n 40\n${height.toFixed(3)}\n  1\n${text}`);
        }

        addDimension(x1, y1, x2, y2, offset, text, layer = 'COTAS') {
            // Schematic dimension line with ticks and text
            const isHoriz = Math.abs(y2 - y1) < 0.001;
            if (isHoriz) {
                const dy = y1 + offset;
                this.addLine(x1, y1, x1, dy + (offset > 0 ? 5 : -5), layer, 3);
                this.addLine(x2, y2, x2, dy + (offset > 0 ? 5 : -5), layer, 3);
                this.addLine(x1, dy, x2, dy, layer, 3);
                // Ticks (45 deg)
                this.addLine(x1 - 3, dy - 3, x1 + 3, dy + 3, layer, 3);
                this.addLine(x2 - 3, dy - 3, x2 + 3, dy + 3, layer, 3);
                // Text
                const midX = (x1 + x2) / 2;
                this.addText(midX - 10, dy + 4, 8, text, layer, 3);
            } else {
                const dx = x1 + offset;
                this.addLine(x1, y1, dx + (offset > 0 ? 5 : -5), y1, layer, 3);
                this.addLine(x2, y2, dx + (offset > 0 ? 5 : -5), y2, layer, 3);
                this.addLine(dx, y1, dx, y2, layer, 3);
                this.addLine(dx - 3, y1 - 3, dx + 3, y1 + 3, layer, 3);
                this.addLine(dx - 3, y2 - 3, dx + 3, y2 + 3, layer, 3);
                const midY = (y1 + y2) / 2;
                this.addText(dx + 5, midY - 4, 8, text, layer, 3);
            }
        }

        build() {
            let out = [];
            // HEADER SECTION
            out.push("  0\nSECTION\n  2\nHEADER\n  9\n$ACADVER\n  1\nAC1009\n  0\nENDSEC");

            // TABLES SECTION
            out.push("  0\nSECTION\n  2\nTABLES\n  0\nTABLE\n  2\nLAYER\n 70\n6");
            const layerDefs = [
                { name: '0', color: 7 },
                { name: 'CONCRETO', color: 4 }, // Cyan
                { name: 'ARMADURA', color: 1 }, // Red
                { name: 'ESTRIBOS', color: 2 }, // Yellow
                { name: 'COTAS', color: 3 },    // Green
                { name: 'TEXTO', color: 7 }     // White
            ];
            for (const l of layerDefs) {
                out.push(`  0\nLAYER\n  2\n${l.name}\n 70\n0\n 62\n${l.color}\n  6\nCONTINUOUS`);
            }
            out.push("  0\nENDTAB\n  0\nENDSEC");

            // BLOCKS SECTION
            out.push("  0\nSECTION\n  2\nBLOCKS\n  0\nENDSEC");

            // ENTITIES SECTION
            out.push("  0\nSECTION\n  2\nENTITIES");
            out.push(this.entities.join("\n"));
            out.push("  0\nENDSEC");

            // EOF
            out.push("  0\nEOF\n");
            return out.join("\n");
        }
    }

    /**
     * Generate complete DXF for a Beam (Cross Section + Longitudinal View)
     */
    function gerarDxfViga(params) {
        const dxf = new DXFBuilder();
        const bw = (params.bw || 15) * 10; // convert cm to mm
        const h = (params.h || 40) * 10;
        const span = (params.span || 5) * 1000; // m to mm
        const cobr = (params.cobrimento || 2.5) * 10;
        const nome = params.nome || 'V-101';

        // 1. SEÇÃO TRANSVERSAL (X: 0 a bw, Y: 0 a h)
        // Concreto
        dxf.addRect(0, 0, bw, h, 'CONCRETO', 4);

        // Estribo
        const estriboX = cobr;
        const estriboY = cobr;
        const estriboW = bw - (2 * cobr);
        const estriboH = h - (2 * cobr);
        dxf.addRect(estriboX, estriboY, estriboW, estriboH, 'ESTRIBOS', 2);

        // Armadura Inferior (Positiva)
        const nInf = params.nBarrasInf || 3;
        const diamInf = (params.bitolaInf || 12.5);
        const rInf = diamInf / 2;
        const spacingXInf = nInf > 1 ? (estriboW - diamInf) / (nInf - 1) : 0;
        for (let i = 0; i < nInf; i++) {
            const bx = estriboX + (diamInf / 2) + (i * spacingXInf);
            const by = estriboY + (diamInf / 2);
            dxf.addCircle(bx, by, rInf, 'ARMADURA', 1);
        }

        // Armadura Superior (Porta-estribos / Negativa)
        const nSup = params.nBarrasSup || 2;
        const diamSup = (params.bitolaSup || 8.0);
        const rSup = diamSup / 2;
        const spacingXSup = nSup > 1 ? (estriboW - diamSup) / (nSup - 1) : 0;
        for (let i = 0; i < nSup; i++) {
            const bx = estriboX + (diamSup / 2) + (i * spacingXSup);
            const by = estriboY + estriboH - (diamSup / 2);
            dxf.addCircle(bx, by, rSup, 'ARMADURA', 1);
        }

        // Cotas da Seção
        dxf.addDimension(0, 0, bw, 0, -35, `${params.bw} cm`, 'COTAS');
        dxf.addDimension(0, 0, 0, h, -35, `${params.h} cm`, 'COTAS');
        dxf.addText(0, h + 25, 14, `SEÇÃO A-A (${nome})`, 'TEXTO', 7);
        dxf.addText(0, h + 8, 9, `Inf: ${nInf}%%c${params.bitolaInf}mm | Sup: ${nSup}%%c${params.bitolaSup}mm`, 'TEXTO', 7);
        dxf.addText(0, -60, 9, `Estribos: %%c${params.bitolaEstribo || '5.0'} c/${params.espacoEstribo || '15'}cm`, 'TEXTO', 7);

        // 2. ELEVAÇÃO LONGITUDINAL (À direita da seção)
        const elevX = bw + 150;
        const elevY = 0;
        dxf.addRect(elevX, elevY, span, h, 'CONCRETO', 4);

        // Apoios esquemáticos (pilares 20x20 cm abaixo)
        dxf.addRect(elevX, elevY - 200, 200, 200, 'CONCRETO', 4);
        dxf.addRect(elevX + span - 200, elevY - 200, 200, 200, 'CONCRETO', 4);

        // Barra inferior com ganchos nas pontas
        const hook = 150;
        const barYInf = elevY + cobr;
        dxf.addLine(elevX + cobr, barYInf + hook, elevX + cobr, barYInf, 'ARMADURA', 1);
        dxf.addLine(elevX + cobr, barYInf, elevX + span - cobr, barYInf, 'ARMADURA', 1);
        dxf.addLine(elevX + span - cobr, barYInf, elevX + span - cobr, barYInf + hook, 'ARMADURA', 1);

        // Barra superior com ganchos
        const barYSup = elevY + h - cobr;
        dxf.addLine(elevX + cobr, barYSup - hook, elevX + cobr, barYSup, 'ARMADURA', 1);
        dxf.addLine(elevX + cobr, barYSup, elevX + span - cobr, barYSup, 'ARMADURA', 1);
        dxf.addLine(elevX + span - cobr, barYSup, elevX + span - cobr, barYSup - hook, 'ARMADURA', 1);

        // Distribuição de Estribos na elevação
        const sEstribo = (params.espacoEstribo || 15) * 10;
        let curX = elevX + 50;
        while (curX < elevX + span - 50) {
            dxf.addLine(curX, elevY + cobr, curX, elevY + h - cobr, 'ESTRIBOS', 2);
            curX += sEstribo;
        }

        // Cotas da Elevação
        dxf.addDimension(elevX, elevY, elevX + span, elevY, -60, `L = ${(span / 1000).toFixed(2)} m`, 'COTAS');
        dxf.addText(elevX, elevY + h + 25, 14, `ELEVAÇÃO LONGITUDINAL - ${nome} (VÃO ${(span / 1000).toFixed(2)}m)`, 'TEXTO', 7);

        return dxf.build();
    }

    /**
     * Generate complete DXF for a Column (Cross Section + Elevation with rebars)
     */
    function gerarDxfPilar(params) {
        const dxf = new DXFBuilder();
        const b = (params.b || 20) * 10; // mm
        const h = (params.h || 30) * 10;
        const cobr = (params.cobrimento || 2.5) * 10;
        const nome = params.nome || 'P-101';
        const nBarras = params.nBarras || 4;
        const bitola = params.bitola || 12.5;
        const rBarra = bitola / 2;

        // 1. SEÇÃO TRANSVERSAL DO PILAR
        dxf.addRect(0, 0, b, h, 'CONCRETO', 4);

        // Estribo
        const estriboW = b - (2 * cobr);
        const estriboH = h - (2 * cobr);
        dxf.addRect(cobr, cobr, estriboW, estriboH, 'ESTRIBOS', 2);

        // Armaduras Longitudinais (4 cantos obrigatórios)
        const corners = [
            [cobr + rBarra + 2, cobr + rBarra + 2],
            [b - cobr - rBarra - 2, cobr + rBarra + 2],
            [cobr + rBarra + 2, h - cobr - rBarra - 2],
            [b - cobr - rBarra - 2, h - cobr - rBarra - 2]
        ];
        corners.forEach(([cx, cy]) => dxf.addCircle(cx, cy, rBarra, 'ARMADURA', 1));

        // Barras intermediárias se nBarras > 4
        if (nBarras === 6) {
            const midY = h / 2;
            dxf.addCircle(cobr + rBarra + 2, midY, rBarra, 'ARMADURA', 1);
            dxf.addCircle(b - cobr - rBarra - 2, midY, rBarra, 'ARMADURA', 1);
        } else if (nBarras >= 8) {
            const midY1 = cobr + rBarra + (estriboH / 3);
            const midY2 = cobr + rBarra + (2 * estriboH / 3);
            dxf.addCircle(cobr + rBarra + 2, midY1, rBarra, 'ARMADURA', 1);
            dxf.addCircle(cobr + rBarra + 2, midY2, rBarra, 'ARMADURA', 1);
            dxf.addCircle(b - cobr - rBarra - 2, midY1, rBarra, 'ARMADURA', 1);
            dxf.addCircle(b - cobr - rBarra - 2, midY2, rBarra, 'ARMADURA', 1);
        }

        // Cotas
        dxf.addDimension(0, 0, b, 0, -35, `${params.b} cm`, 'COTAS');
        dxf.addDimension(0, 0, 0, h, -35, `${params.h} cm`, 'COTAS');
        dxf.addText(0, h + 25, 14, `SEÇÃO TRANSVERSAL - ${nome}`, 'TEXTO', 7);
        dxf.addText(0, h + 8, 9, `Armadura: ${nBarras}%%c${bitola}mm | Estribo: %%c${params.bitolaEstribo || '5.0'} c/${params.espacoEstribo || '15'}cm`, 'TEXTO', 7);

        // 2. ELEVAÇÃO DO PILAR (Pé-direito H = 3.00m)
        const elevX = b + 150;
        const peDireito = (params.altura || 3.0) * 1000; // mm
        dxf.addRect(elevX, 0, b, peDireito, 'CONCRETO', 4);

        // Barras com esperas (+50cm no topo)
        const espera = 500;
        dxf.addLine(elevX + cobr + rBarra, 0, elevX + cobr + rBarra, peDireito + espera, 'ARMADURA', 1);
        dxf.addLine(elevX + b - cobr - rBarra, 0, elevX + b - cobr - rBarra, peDireito + espera, 'ARMADURA', 1);

        // Distribuição de Estribos na Elevação
        const sEst = (params.espacoEstribo || 15) * 10;
        let yEst = 50;
        while (yEst < peDireito) {
            dxf.addLine(elevX + cobr, yEst, elevX + b - cobr, yEst, 'ESTRIBOS', 2);
            yEst += sEst;
        }

        dxf.addDimension(elevX, 0, elevX, peDireito, -45, `H = ${(peDireito / 1000).toFixed(2)} m`, 'COTAS');
        dxf.addText(elevX, peDireito + espera + 15, 12, `ELEVAÇÃO - ${nome} (ESPERAS +50cm)`, 'TEXTO', 7);

        return dxf.build();
    }

    /**
     * Generate complete DXF for a Slab (Planta com Armadura / Vigotas)
     */
    function gerarDxfLaje(params) {
        const dxf = new DXFBuilder();
        const lx = (params.lx || 4) * 1000; // mm
        const ly = (params.ly || 5) * 1000;
        const h = (params.h || 10) * 10;
        const nome = params.nome || 'L-101';
        const tipo = params.tipo || 'macica';

        // 1. PLANTA DA LAJE
        dxf.addRect(0, 0, lx, ly, 'CONCRETO', 4);

        if (tipo === 'trelicada') {
            // Vigotas unidirecionais paralelas ao menor vão (lx)
            const sVigota = 420; // 42 cm entre eixos padrão
            let curY = sVigota;
            while (curY < ly) {
                dxf.addLine(0, curY, lx, curY, 'ARMADURA', 1);
                dxf.addLine(0, curY - 30, lx, curY - 30, 'ESTRIBOS', 2);
                dxf.addLine(0, curY + 30, lx, curY + 30, 'ESTRIBOS', 2);
                curY += sVigota;
            }
        } else {
            // Malha bidirecional X e Y
            const sMalha = 150; // c/ 15cm
            let curX = sMalha;
            while (curX < lx) {
                dxf.addLine(curX, 50, curX, ly - 50, 'ARMADURA', 1);
                curX += sMalha;
            }
            let curY = sMalha;
            while (curY < ly) {
                dxf.addLine(50, curY, lx - 50, curY, 'ARMADURA', 1);
                curY += sMalha;
            }
        }

        // Cotas da Planta
        dxf.addDimension(0, 0, lx, 0, -80, `Lx = ${(lx/1000).toFixed(2)} m`, 'COTAS');
        dxf.addDimension(0, 0, 0, ly, -80, `Ly = ${(ly/1000).toFixed(2)} m`, 'COTAS');
        dxf.addText(lx / 2 - 100, ly / 2, 24, `${nome} (h = ${(h/10)}cm)`, 'TEXTO', 7);
        dxf.addText(lx / 2 - 100, ly / 2 - 50, 14, tipo === 'trelicada' ? 'Laje Treliçada c/ EPS' : 'Laje Maciça Bidirecional', 'TEXTO', 7);

        // 2. CORTE TRANSVERSAL DA LAJE (Abaixo da planta)
        const corteY = -350;
        dxf.addRect(0, corteY, lx, h, 'CONCRETO', 4);
        dxf.addLine(20, corteY + 25, lx - 20, corteY + 25, 'ARMADURA', 1);
        dxf.addLine(20, corteY + h - 25, lx - 20, corteY + h - 25, 'ARMADURA', 1);
        dxf.addDimension(0, corteY, 0, corteY + h, -50, `h = ${(h/10)} cm`, 'COTAS');
        dxf.addText(0, corteY - 60, 14, `CORTE TRANSVERSAL - ${nome}`, 'TEXTO', 7);

        return dxf.build();
    }

    /**
     * Generate complete DXF for an Isolated Footing (Sapata Isolada)
     */
    function gerarDxfSapata(params) {
        const dxf = new DXFBuilder();
        const a = (params.a || 140) * 10; // mm
        const b = (params.b || 140) * 10;
        const h = (params.h || 45) * 10;
        const h0 = (params.h0 || 20) * 10;
        const aPilar = (params.aPilar || 20) * 10;
        const bPilar = (params.bPilar || 30) * 10;
        const nome = params.nome || 'S-101';
        const cobr = 40; // 4cm cobrimento de fundação

        // 1. PLANTA DA SAPATA
        dxf.addRect(0, 0, a, b, 'CONCRETO', 4);

        // Pilar centralizado
        const pilarX = (a - aPilar) / 2;
        const pilarY = (b - bPilar) / 2;
        dxf.addRect(pilarX, pilarY, aPilar, bPilar, 'CONCRETO', 4);

        // Malha de Armadura Inferior (X e Y)
        const sX = 150;
        let curX = cobr;
        while (curX < a - cobr) {
            dxf.addLine(curX, cobr, curX, b - cobr, 'ARMADURA', 1);
            curX += sX;
        }
        let curY = cobr;
        while (curY < b - cobr) {
            dxf.addLine(cobr, curY, a - cobr, curY, 'ARMADURA', 1);
            curY += sX;
        }

        // Cotas da Planta
        dxf.addDimension(0, 0, a, 0, -60, `A = ${(a/10)} cm`, 'COTAS');
        dxf.addDimension(0, 0, 0, b, -60, `B = ${(b/10)} cm`, 'COTAS');
        dxf.addText(a / 2 - 80, b + 40, 16, `PLANTA - SAPATA ${nome}`, 'TEXTO', 7);

        // 2. CORTE TRANSVERSAL DA SAPATA (Tronco de Pirâmide)
        const corteX = a + 200;
        // Lastro de concreto magro 5cm
        dxf.addRect(corteX, -50, a, 50, '0', 8);

        // Base da sapata h0
        dxf.addRect(corteX, 0, a, h0, 'CONCRETO', 4);

        // Chanfro superior até o topo h
        dxf.addLine(corteX, h0, corteX + pilarX, h, 'CONCRETO', 4);
        dxf.addLine(corteX + a, h0, corteX + a - pilarX, h, 'CONCRETO', 4);
        dxf.addLine(corteX + pilarX, h, corteX + a - pilarX, h, 'CONCRETO', 4);

        // Pilar de arranque
        dxf.addRect(corteX + pilarX, h, aPilar, 400, 'CONCRETO', 4);

        // Armadura de flexão no fundo com ganchos verticais
        dxf.addLine(corteX + cobr, cobr + 150, corteX + cobr, cobr, 'ARMADURA', 1);
        dxf.addLine(corteX + cobr, cobr, corteX + a - cobr, cobr, 'ARMADURA', 1);
        dxf.addLine(corteX + a - cobr, cobr, corteX + a - cobr, cobr + 150, 'ARMADURA', 1);

        // Cotas do Corte
        dxf.addDimension(corteX, 0, corteX, h, -50, `H = ${(h/10)} cm`, 'COTAS');
        dxf.addDimension(corteX + a, 0, corteX + a, h0, 50, `h0 = ${(h0/10)} cm`, 'COTAS');
        dxf.addText(corteX, h + 430, 16, `CORTE ESQUEMÁTICO - ${nome}`, 'TEXTO', 7);

        return dxf.build();
    }

    /**
     * Bridge: Send DXF directly to CADClone & Open in New Tab
     */
    function abrirNoCadClone(dxfString, filename = 'detalhamento_viga.dxf') {
        try {
            localStorage.setItem('cadclone_external_dxf', dxfString);
            localStorage.setItem('cadclone_external_filename', filename);
            const isSubdir = window.location.pathname.includes('/seguranca/');
            const cadPath = (isSubdir ? '../../' : '../') + 'cadclone/';
            window.open(cadPath, '_blank');
        } catch (e) {
            console.error('Erro ao transferir DXF para CADClone:', e);
            baixarDXF(dxfString, filename);
        }
    }

    /**
     * Download DXF file directly
     */
    function baixarDXF(dxfString, filename = 'detalhamento.dxf') {
        const blob = new Blob([dxfString], { type: 'application/dxf' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // Expose API
    window.ECALC_CAD = {
        DXFBuilder,
        gerarDxfViga,
        gerarDxfPilar,
        gerarDxfLaje,
        gerarDxfSapata,
        abrirNoCadClone,
        baixarDXF
    };
})();
