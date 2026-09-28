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
        abrirNoCadClone,
        baixarDXF
    };
})();
