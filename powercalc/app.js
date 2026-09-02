// Polyfill for chrome.storage.local targeting localStorage in web environment
if (typeof chrome === 'undefined' || !chrome.storage || !chrome.storage.local) {
    window.chrome = {
        storage: {
            local: {
                get: (keys, cb) => {
                    const res = {};
                    const keyArray = Array.isArray(keys) ? keys : [keys];
                    keyArray.forEach(k => {
                        const val = localStorage.getItem(k);
                        if (val !== null) {
                            try { res[k] = JSON.parse(val); }
                            catch(e) { res[k] = val; }
                        }
                    });
                    if (cb) setTimeout(() => cb(res), 1);
                },
                set: (data, cb) => {
                    for (const k in data) {
                        localStorage.setItem(k, typeof data[k] === 'object' ? JSON.stringify(data[k]) : data[k]);
                    }
                    if (cb) setTimeout(cb, 1);
                }
            }
        }
    };
}

document.addEventListener('DOMContentLoaded', () => {
    // === ESTADOS DO APLICATIVO ===
    let formula = '';
    let scientificMemory = 0;
    let activeMode = 'simple';
    let theme = 'dark';
    let calcHistory = [];
    
    const defaultRates = {
        USD: 1,
        BRL: 5.45,
        EUR: 0.92,
        GBP: 0.78,
        JPY: 155.6,
        CAD: 1.37,
        AUD: 1.51,
        CHF: 0.90,
        ARS: 900,
        CLP: 920,
        UYU: 39.5,
        MXN: 18.4,
        CNY: 7.25,
        INR: 83.5,
        BTC: 0.000015,
        ETH: 0.00028,
        SOL: 0.0065,
        BNB: 0.0017,
        XRP: 2.0,
        ADA: 2.1,
        DOGE: 7.0,
        USDT: 1.0,
        USDC: 1.0
    };
    let currentRates = { ...defaultRates };
    let activeFinanceSubtab = 'fin-simple';
    let numberFormat = 'BR';
    let decimalPlaces = 'auto';

    // === ESTADOS DO PROGRAMADOR ===
    let progValue = 0n;
    let progBase = 'HEX';
    let progWordSize = 64; // 64, 32, 16, 8
    let progSigned = false;
    let bitBoardVisible = false;
    let progPendingOp = null;
    let progOperand = null;
    let progInputBuffer = '0';
    let progResetBuffer = false;

    // === ELEMENTOS DIVERSOS ===
    const body = document.body;
    const btnThemeToggle = document.getElementById('btnThemeToggle');
    const themeIcon = btnThemeToggle ? btnThemeToggle.querySelector('.theme-icon') : null;
    const btnConfig = document.getElementById('btnConfig');
    const configModal = document.getElementById('configModal');
    const btnCloseConfig = document.getElementById('btnCloseConfig');
    const logoLink = document.getElementById('logo-link');
    const historyDropdown = document.getElementById('historyDropdown');
    const displayFormula = document.getElementById('displayFormula');
    const displayResult = document.getElementById('displayResult');
    const selectNumFormat = document.getElementById('selectNumFormat');
    const selectDecimals = document.getElementById('selectDecimals');

    // === INICIALIZAÇÃO E STORAGE ===
    if (chrome && chrome.storage && chrome.storage.local) {
        chrome.storage.local.get(['activeMode', 'theme', 'exchangeRates', 'lastRatesSync', 'calcHistory', 'numberFormat', 'decimalPlaces', 'scientificMemory'], (result) => {
            if (result.activeMode) {
                switchMode(result.activeMode);
            } else {
                switchMode('simple');
            }

            if (result.theme) {
                setTheme(result.theme);
            } else {
                setTheme('dark');
            }

            if (result.exchangeRates) {
                currentRates = result.exchangeRates;
            }

            if (result.calcHistory) {
                calcHistory = result.calcHistory;
            }

            if (result.scientificMemory !== undefined) {
                scientificMemory = result.scientificMemory;
                updateMemoryIndicator();
            }

            if (result.numberFormat) {
                numberFormat = result.numberFormat;
                if (selectNumFormat) selectNumFormat.value = numberFormat;
            } else {
                numberFormat = 'BR';
                if (selectNumFormat) selectNumFormat.value = 'BR';
            }

            if (result.decimalPlaces) {
                decimalPlaces = result.decimalPlaces;
                if (selectDecimals) selectDecimals.value = decimalPlaces;
            } else {
                decimalPlaces = 'auto';
                if (selectDecimals) selectDecimals.value = 'auto';
            }

            if (result.lastRatesSync) {
                updateSyncTimeDisplay(result.lastRatesSync);
            } else {
                updateSyncTimeDisplay(null);
            }
            updateCurrencyConversion();
            updateDecimalKeys();

            // Sincronização automática se as taxas tiverem mais de 12 horas
            const now = Date.now();
            const lastSync = result.lastRatesSync || 0;
            if (now - lastSync > 43200000) {
                syncRates();
            }
        });
    } else {
        switchMode('simple');
        setTheme('dark');
        updateSyncTimeDisplay(null);
        updateCurrencyConversion();
        updateDecimalKeys();
    }

    if (selectNumFormat) {
        selectNumFormat.addEventListener('change', () => {
            numberFormat = selectNumFormat.value;
            if (chrome && chrome.storage && chrome.storage.local) {
                chrome.storage.local.set({ numberFormat });
            }
            updateDecimalKeys();
            updateVisor();
            triggerPaneCalculations(activeMode);
        });
    }

    if (selectDecimals) {
        selectDecimals.addEventListener('change', () => {
            decimalPlaces = selectDecimals.value;
            if (chrome && chrome.storage && chrome.storage.local) {
                chrome.storage.local.set({ decimalPlaces });
            }
            updateVisor();
            triggerPaneCalculations(activeMode);
        });
    }

    // === TEMA CLARO / ESCURO ===
    function setTheme(newTheme) {
        theme = newTheme;
        if (theme === 'light') {
            body.classList.add('light-theme');
            if (themeIcon) themeIcon.innerText = '🌙'; // mostra a lua para trocar para escuro
        } else {
            body.classList.remove('light-theme');
            if (themeIcon) themeIcon.innerText = '☀️'; // mostra o sol para trocar para claro
        }
        if (chrome && chrome.storage && chrome.storage.local) {
            chrome.storage.local.set({ theme });
        }
    }

    if (btnThemeToggle) {
        btnThemeToggle.addEventListener('click', () => {
            setTheme(theme === 'dark' ? 'light' : 'dark');
        });
    }

    // === NAVEGAÇÃO LATERAL ===
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            const mode = item.getAttribute('data-mode');
            switchMode(mode);
        });
    });

    function switchMode(mode) {
        activeMode = mode;
        
        // Remove classes ativas
        document.querySelectorAll('.nav-item').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.keyboard-pane').forEach(pane => pane.classList.remove('active'));

        // Adiciona classe ativa
        // Esconde painel técnico de engenharia ao mudar de modo
        const techNotesPanel = document.getElementById('engTechNotes');
        if (techNotesPanel && mode !== 'engineering') techNotesPanel.style.display = 'none';

        const activeNav = document.querySelector(`.nav-item[data-mode="${mode}"]`);
        if (activeNav) activeNav.classList.add('active');

        const activePane = document.getElementById(`pane-${mode}`);
        if (activePane) activePane.classList.add('active');

        // Limpa ou reinicializa layouts
        if (mode === 'simple' || mode === 'scientific') {
            updateVisor();
        } else if (mode === 'programmer') {
            updateProgrammerKeys();
            updateProgrammerDisplay();
        } else {
            // Em outros modos, o visor pode mostrar o resumo ou resultado atual do formulário
            triggerPaneCalculations(mode);
        }

        // Fechar dropdown de histórico se mudar de aba
        if (historyDropdown) {
            historyDropdown.classList.remove('show');
        }

        // Salvar estado
        if (chrome && chrome.storage && chrome.storage.local) {
            chrome.storage.local.set({ activeMode: mode });
        }
    }

    function triggerPaneCalculations(mode) {
        if (mode === 'units') performUnitConversion();
        else if (mode === 'currency') updateCurrencyConversion();
        else if (mode === 'statistics') calculateStatistics();
        else if (mode === 'percentage') calculatePercentage();
        else if (mode === 'finance') calculateFinance();
        else if (mode === 'dates') {
            const selectEl = document.getElementById('dateSubMode');
            if (selectEl) {
                const subtab = selectEl.value;
                if (subtab === 'date-diff') calculateDateDifference();
                else if (subtab === 'date-add') calculateDateAdd();
                else if (subtab === 'date-work') calculateDateWork();
                else if (subtab === 'date-age') calculateDateAge();
                else if (subtab === 'date-weekday') calculateDateWeekday();
                else if (subtab === 'date-leap') calculateDateLeap();
            }
        }
        else if (mode === 'health') {
            const selectEl = document.getElementById('healthSubMode');
            if (selectEl) {
                const subtab = selectEl.value;
                if (subtab === 'health-imc') calculateIMC();
                else if (subtab === 'health-tmb') calculateTMB();
                else if (subtab === 'health-bp') calculateBP();
                else if (subtab === 'health-hr') calculateHR();
                else if (subtab === 'health-ratio') calculateRatio();
                else if (subtab === 'health-water') calculateWater();
                else if (subtab === 'health-preg') calculatePreg();
                else if (subtab === 'health-med') calculateMed();
                else if (subtab === 'health-body') calculateBody();
                else if (subtab === 'health-cardio') calculateCardio();
                else if (subtab === 'health-nefro') calculateNefro();
                else if (subtab === 'health-resp') calculateResp();
                else if (subtab === 'health-ped') calculatePed();
                else if (subtab === 'health-obst') calculateObst();
                else if (subtab === 'health-lab') calculateLab();

            }
        }
        else if (mode === 'matrices') calculateMatrixOperation();
        else if (mode === 'programmer') updateProgrammerDisplay();
        else if (mode === 'engineering') runEngineeringCalculation();
        else if (mode === 'ai') syncAiBalance();
    }

    // === MODAL CONFIG E SOBRE ===
    if (btnConfig && configModal) {
        btnConfig.addEventListener('click', () => {
            configModal.classList.add('show');
        });
    }

    if (btnCloseConfig && configModal) {
        btnCloseConfig.addEventListener('click', () => {
            configModal.classList.remove('show');
        });
    }

    if (configModal) {
        configModal.addEventListener('click', (e) => {
            if (e.target === configModal) {
                configModal.classList.remove('show');
            }
        });
    }

    // EASTER EGG (5 cliques rápidos no logo abre o modal)
    if (logoLink) {
        logoLink.addEventListener('click', (e) => {
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
                if (configModal) configModal.classList.add('show');
            }
        });
    }

    // === HISTÓRICO DE CÁLCULOS ===
    function saveCalculation(expr, res) {
        if (!expr || res === 'Erro' || res === '0') return;
        
        // Evita duplicados idênticos em sequência
        const lastItem = calcHistory[0];
        if (lastItem && lastItem.formula === expr) return;

        calcHistory.unshift({ formula: expr, result: res });
        calcHistory = calcHistory.slice(0, 10); // Limita aos 10 mais recentes

        if (chrome && chrome.storage && chrome.storage.local) {
            chrome.storage.local.set({ calcHistory });
        }
        renderHistory();
    }

    function renderHistory() {
        if (!historyDropdown) return;
        historyDropdown.innerHTML = '';

        if (calcHistory.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'history-item';
            empty.style.textAlign = 'center';
            empty.style.color = 'var(--text-muted)';
            empty.innerText = 'Sem cálculos recentes';
            historyDropdown.appendChild(empty);
            return;
        }

        calcHistory.forEach((item) => {
            const el = document.createElement('div');
            el.className = 'history-item';
            el.innerText = `${formatFormula(item.formula)} = ${item.result}`;
            el.addEventListener('click', (e) => {
                e.stopPropagation();
                formula = item.formula;
                updateVisor();
                historyDropdown.classList.remove('show');
            });
            historyDropdown.appendChild(el);
        });
    }

    if (displayFormula && historyDropdown) {
        displayFormula.addEventListener('click', (e) => {
            if (activeMode !== 'simple' && activeMode !== 'scientific') return;
            e.stopPropagation();
            renderHistory();
            historyDropdown.classList.toggle('show');
        });
    }

    document.addEventListener('click', (e) => {
        if (historyDropdown && !historyDropdown.contains(e.target) && e.target !== displayFormula) {
            historyDropdown.classList.remove('show');
        }
    });

    // === CLIQUE PARA COPIAR RESULTADO ===
    if (displayResult) {
        displayResult.addEventListener('click', () => {
            const textToCopy = displayResult.innerText;
            if (textToCopy === '0' || textToCopy === 'Erro' || textToCopy === 'Sem Limites') return;
            
            navigator.clipboard.writeText(textToCopy).then(() => {
                showToast('Copiado!');
            }).catch(err => {
                console.error('Erro ao copiar:', err);
            });
        });
    }

    function showToast(message) {
        const activeToast = document.querySelector('.display-container .toast-notification');
        if (activeToast) activeToast.remove();

        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.innerText = message;
        toast.style.position = 'absolute';
        toast.style.top = '10px';
        toast.style.right = '20px';
        toast.style.background = 'rgba(79, 140, 255, 0.95)';
        toast.style.color = '#ffffff';
        toast.style.padding = '4px 10px';
        toast.style.borderRadius = '6px';
        toast.style.fontSize = '10px';
        toast.style.fontWeight = 'bold';
        toast.style.zIndex = '1000';
        toast.style.pointerEvents = 'none';
        toast.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.35)';
        toast.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-5px)';

        const container = document.querySelector('.display-container');
        if (container) {
            container.appendChild(toast);
            // Trigger reflow
            toast.offsetHeight;
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-5px)';
                setTimeout(() => toast.remove(), 200);
            }, 1200);
        }
    }


    // === MOTOR ARITMÉTICO / CALCULADORA (SIMPLE & SCIENTIFIC) ===
    // Lexer / Tokenizer
    function tokenize(str) {
        const tokens = [];
        let i = 0;
        while (i < str.length) {
            let char = str[i];
            if (/\s/.test(char)) {
                i++;
                continue;
            }
            if (/[0-9.]/.test(char)) {
                let numStr = "";
                while (i < str.length && /[0-9.]/.test(str[i])) {
                    numStr += str[i];
                    i++;
                }
                tokens.push({ type: 'NUMBER', value: parseFloat(numStr) });
                continue;
            }
            if ('+-*/^%(),'.indexOf(char) !== -1) {
                if (char === ',') {
                    tokens.push({ type: 'COMMA', value: ',' });
                } else {
                    tokens.push({ type: 'OPERATOR', value: char });
                }
                i++;
                continue;
            }
            if (/[a-zA-Z_]/.test(char)) {
                let word = "";
                while (i < str.length && /[a-zA-Z0-9._]/.test(str[i])) {
                    word += str[i];
                    i++;
                }
                if (word === 'Math.PI' || word === 'PI' || word === 'π') {
                    tokens.push({ type: 'NUMBER', value: Math.PI });
                } else if (word === 'Math.E' || word === 'E' || word === 'e') {
                    tokens.push({ type: 'NUMBER', value: Math.E });
                } else {
                    tokens.push({ type: 'FUNCTION', value: word });
                }
                continue;
            }
            i++;
        }
        return tokens;
    }

    // Parser
    function parse(tokens) {
        let index = 0;

        function peek() {
            return tokens[index];
        }

        function consume(expectedType, expectedValue) {
            const token = tokens[index];
            if (!token) throw new Error("Fim inesperado da expressão");
            if (expectedType && token.type !== expectedType) {
                throw new Error(`Token inesperado: esperado ${expectedType}, recebido ${token.type}`);
            }
            if (expectedValue !== undefined && token.value !== expectedValue) {
                throw new Error(`Token inesperado: esperado valor ${expectedValue}, recebido ${token.value}`);
            }
            index++;
            return token;
        }

        function parseExpression() {
            let node = parseTerm();
            while (peek() && peek().type === 'OPERATOR' && (peek().value === '+' || peek().value === '-')) {
                const op = consume().value;
                const right = parseTerm();
                node = { type: 'BINARY', op, left: node, right };
            }
            return node;
        }

        function parseTerm() {
            let node = parseFactor();
            while (peek() && peek().type === 'OPERATOR' && (peek().value === '*' || peek().value === '/' || peek().value === '%')) {
                const op = consume().value;
                const right = parseFactor();
                node = { type: 'BINARY', op, left: node, right };
            }
            return node;
        }

        function parseFactor() {
            let node = parsePrimary();
            if (peek() && peek().type === 'OPERATOR' && peek().value === '^') {
                consume();
                const right = parseFactor();
                node = { type: 'BINARY', op: '^', left: node, right };
            }
            return node;
        }

        function parsePrimary() {
            const token = peek();
            if (!token) throw new Error("Expressão incompleta");

            if (token.type === 'NUMBER') {
                consume();
                return { type: 'NUMBER', value: token.value };
            }

            if (token.type === 'OPERATOR' && token.value === '(') {
                consume();
                const expr = parseExpression();
                consume('OPERATOR', ')');
                return expr;
            }

            if (token.type === 'OPERATOR' && (token.value === '-' || token.value === '+')) {
                const op = consume().value;
                const expr = parsePrimary();
                return { type: 'UNARY', op, argument: expr };
            }

            if (token.type === 'FUNCTION') {
                const funcName = consume().value;
                consume('OPERATOR', '(');
                
                const args = [];
                if (peek() && !(peek().type === 'OPERATOR' && peek().value === ')')) {
                    args.push(parseExpression());
                    while (peek() && peek().type === 'COMMA') {
                        consume();
                        args.push(parseExpression());
                    }
                }
                consume('OPERATOR', ')');
                return { type: 'CALL', name: funcName, arguments: args };
            }

            throw new Error(`Token inválido: ${token.value}`);
        }

        if (tokens.length === 0) return null;
        const ast = parseExpression();
        if (index < tokens.length) {
            throw new Error(`Sintaxe incorreta próximo a: ${tokens[index].value}`);
        }
        return ast;
    }

    // Evaluator
    function evaluate(node) {
        if (!node) return 0;
        if (node.type === 'NUMBER') {
            return node.value;
        }
        if (node.type === 'UNARY') {
            const val = evaluate(node.argument);
            return node.op === '-' ? -val : val;
        }
        if (node.type === 'BINARY') {
            const left = evaluate(node.left);
            const right = evaluate(node.right);
            switch (node.op) {
                case '+': return left + right;
                case '-': return left - right;
                case '*': return left * right;
                case '/': 
                    if (right === 0) throw new Error("Divisão por zero");
                    return left / right;
                case '%': return left % right;
                case '^': return Math.pow(left, right);
            }
        }
        if (node.type === 'CALL') {
            const args = node.arguments.map(evaluate);
            switch (node.name.toLowerCase()) {
                case 'sin': return Math.sin(args[0]);
                case 'cos': return Math.cos(args[0]);
                case 'tan': return Math.tan(args[0]);
                case 'asin': return Math.asin(args[0]);
                case 'acos': return Math.acos(args[0]);
                case 'atan': return Math.atan(args[0]);
                case 'log': return Math.log10(args[0]);
                case 'ln': return Math.log(args[0]);
                case 'sqrt': 
                    if (args[0] < 0) throw new Error("Raiz de negativo");
                    return Math.sqrt(args[0]);
                case 'fact': return factorial(args[0]);
                case 'yroot': 
                    if (args[1] === 0) throw new Error("Raiz zero");
                    if (args[0] < 0 && args[1] % 2 === 0) throw new Error("Raiz par de negativo");
                    return Math.pow(args[0], 1 / args[1]);
                default:
                    throw new Error(`Função desconhecida: ${node.name}`);
            }
        }
        throw new Error("Nó desconhecido");
    }

    function factorial(n) {
        if (n < 0 || !Number.isInteger(n)) throw new Error("Fatorial inválido");
        if (n === 0 || n === 1) return 1;
        let res = 1;
        for (let i = 2; i <= n; i++) res *= i;
        return res;
    }

    function formatNumber(num) {
        if (num === null || num === undefined) return '';
        if (isNaN(num)) return 'Erro';
        if (!isFinite(num)) return 'Sem Limites';
        
        const locale = numberFormat === 'BR' ? 'pt-BR' : 'en-US';
        const options = {};
        if (decimalPlaces === 'auto') {
            options.maximumFractionDigits = 8;
        } else {
            const decs = parseInt(decimalPlaces);
            options.minimumFractionDigits = decs;
            options.maximumFractionDigits = decs;
        }
        return new Intl.NumberFormat(locale, options).format(num);
    }

    function formatFormula(str) {
        let formatted = str
            .replace(/Math\.PI/g, 'π')
            .replace(/Math\.E/g, 'e')
            .replace(/\*/g, ' × ')
            .replace(/\//g, ' ÷ ')
            .replace(/\+/g, ' + ')
            .replace(/-/g, ' - ')
            .replace(/\^/g, ' ^ ');
        
        if (numberFormat === 'BR') {
            formatted = formatted
                .replace(/,/g, '; ')
                .replace(/\./g, ',');
        } else {
            formatted = formatted.replace(/,/g, ', ');
        }
        return formatted;
    }

    function updateDecimalKeys() {
        const decimalButtons = document.querySelectorAll('button[data-val="."]');
        decimalButtons.forEach(btn => {
            btn.innerText = numberFormat === 'BR' ? ',' : '.';
        });
    }

    function updateVisor() {
        if (displayFormula) displayFormula.innerText = formatFormula(formula);
        
        if (!formula) {
            if (displayResult) displayResult.innerText = '0';
            return;
        }

        try {
            const tokens = tokenize(formula);
            const ast = parse(tokens);
            if (ast) {
                const result = evaluate(ast);
                if (displayResult) displayResult.innerText = formatNumber(result);
            }
        } catch (e) {
            // Silencia erro enquanto digita
        }
    }

    // Ações das teclas da calculadora
    const calcPanes = ['pane-simple', 'pane-scientific'];
    calcPanes.forEach(paneId => {
        const pane = document.getElementById(paneId);
        if (!pane) return;

        pane.addEventListener('click', (e) => {
            const btn = e.target.closest('.key-btn');
            if (!btn) return;

            const val = btn.getAttribute('data-val');
            const action = btn.getAttribute('data-action');

            if (val !== null) {
                formula += val;
                updateVisor();
            } else if (action !== null) {
                handleCalcAction(action);
            }
        });
    });

    function updateMemoryIndicator() {
        const ind = document.getElementById('memoryIndicator');
        if (ind) {
            ind.style.display = (scientificMemory !== 0) ? 'block' : 'none';
        }
    }

    function handleCalcAction(action) {
        if (action === 'clear') {
            formula = '';
            updateVisor();
        } else if (action === 'backspace') {
            const endings = ['sin(', 'cos(', 'tan(', 'asin(', 'acos(', 'atan(', 'log(', 'ln(', 'sqrt(', 'yroot(', 'fact(', 'Math.PI', 'Math.E'];
            let deleted = false;
            for (let ending of endings) {
                if (formula.endsWith(ending)) {
                    formula = formula.slice(0, -ending.length);
                    deleted = true;
                    break;
                }
            }
            if (!deleted) {
                formula = formula.slice(0, -1);
            }
            updateVisor();
        } else if (action === 'calculate') {
            if (!formula) return;
            try {
                const tokens = tokenize(formula);
                const ast = parse(tokens);
                const result = evaluate(ast);
                const formattedRes = formatNumber(result);
                
                saveCalculation(formula, formattedRes);
                
                if (displayFormula) displayFormula.innerText = formatFormula(formula);
                if (displayResult) displayResult.innerText = formattedRes;
                formula = formattedRes;
            } catch (e) {
                if (displayResult) displayResult.innerText = 'Erro';
            }
        } else if (action === 'paren-open') {
            formula += '(';
            updateVisor();
        } else if (action === 'paren-close') {
            formula += ')';
            updateVisor();
        } else if (action === 'mc') {
            scientificMemory = 0;
            if (chrome && chrome.storage && chrome.storage.local) {
                chrome.storage.local.set({ scientificMemory });
            }
            updateMemoryIndicator();
        } else if (action === 'mr') {
            formula += scientificMemory.toString();
            updateVisor();
        } else if (action === 'ms' || action === 'mplus' || action === 'mminus') {
            let valToUse = 0;
            if (formula) {
                try {
                    const tokens = tokenize(formula);
                    const ast = parse(tokens);
                    valToUse = evaluate(ast);
                    if (isNaN(valToUse) || !isFinite(valToUse)) {
                        valToUse = 0;
                    } else {
                        const formattedRes = formatNumber(valToUse);
                        saveCalculation(formula, formattedRes);
                        if (displayFormula) displayFormula.innerText = formatFormula(formula);
                        if (displayResult) displayResult.innerText = formattedRes;
                        formula = formattedRes;
                    }
                } catch (e) {
                    if (displayResult) displayResult.innerText = 'Erro';
                    return;
                }
            } else {
                const curVal = displayResult ? displayResult.innerText : '0';
                let cleanVal = curVal;
                if (numberFormat === 'BR') {
                    cleanVal = cleanVal.replace(/\./g, '').replace(/,/g, '.');
                } else {
                    cleanVal = cleanVal.replace(/,/g, '');
                }
                const parsed = parseFloat(cleanVal);
                valToUse = isNaN(parsed) ? 0 : parsed;
            }

            if (action === 'ms') {
                scientificMemory = valToUse;
            } else if (action === 'mplus') {
                scientificMemory += valToUse;
            } else if (action === 'mminus') {
                scientificMemory -= valToUse;
            }

            if (chrome && chrome.storage && chrome.storage.local) {
                chrome.storage.local.set({ scientificMemory });
            }
            updateMemoryIndicator();
        } else {
            // Funções científicas
            formula += `${action}(`;
            updateVisor();
        }
    }

    // Teclado físico
    document.addEventListener('keydown', (e) => {
        if (configModal && configModal.classList.contains('show')) return;

        if (activeMode === 'programmer') {
            const key = e.key.toUpperCase();
            
            // Check if key is a digit valid in current base
            const isDigit = /^[0-9A-F]$/.test(key);
            if (isDigit) {
                const radix = getBaseRadix(progBase);
                const digitVal = parseInt(key, 16);
                if (digitVal < radix) {
                    handleProgDigitInput(key);
                }
            } else if (key === 'BACKSPACE') {
                handleProgActionInput('backspace');
            } else if (key === 'ESCAPE') {
                handleProgActionInput('clear');
            } else if (key === 'ENTER' || key === '=') {
                e.preventDefault();
                handleProgActionInput('equals');
            } else if ('+-*/%'.indexOf(key) !== -1) {
                handleProgActionInput(key);
            } else if (key === '&') {
                handleProgActionInput('and');
            } else if (key === '|') {
                handleProgActionInput('or');
            } else if (key === '^') {
                handleProgActionInput('xor');
            } else if (key === '~') {
                handleProgActionInput('not');
            } else if (key === '<') {
                handleProgActionInput('lsh');
            } else if (key === '>') {
                handleProgActionInput('rsh');
            }
            return;
        }

        if (activeMode !== 'simple' && activeMode !== 'scientific') return;

        const key = e.key;

        if (/[0-9]/.test(key)) {
            formula += key;
            updateVisor();
        } else if (key === '.') {
            formula += '.';
            updateVisor();
        } else if (key === ',') {
            // Em modo BR, a vírgula é o separador decimal (insere ponto interno)
            // Em modo US, a vírgula é o separador de argumento
            if (numberFormat === 'BR') {
                formula += '.';
            } else {
                formula += ',';
            }
            updateVisor();
        } else if (key === ';') {
            // Em modo BR, o ponto e vírgula é o separador de argumentos (insere vírgula interna)
            if (numberFormat === 'BR') {
                formula += ',';
            }
            updateVisor();
        } else if ('+-*/^%()'.indexOf(key) !== -1) {
            formula += key;
            updateVisor();
        } else if (key === 'Enter' || key === '=') {
            e.preventDefault();
            handleCalcAction('calculate');
        } else if (key === 'Backspace') {
            handleCalcAction('backspace');
        } else if (key === 'Escape') {
            handleCalcAction('clear');
        }
    });


    // === CONVERSOR DE UNIDADES ===
    const unitCategory = document.getElementById('unitCategory');
    const unitFrom = document.getElementById('unitFrom');
    const unitTo = document.getElementById('unitTo');
    const unitInputVal = document.getElementById('unitInputVal');
    const unitOutputVal = document.getElementById('unitOutputVal');
    const unitSwap = document.querySelector('#pane-units .swap-divider');

    const unitOptions = {
        length: [
            { value: 'm', label: 'Metro (m)' },
            { value: 'km', label: 'Quilômetro (km)' },
            { value: 'cm', label: 'Centímetro (cm)' },
            { value: 'mm', label: 'Milímetro (mm)' },
            { value: 'in', label: 'Polegada (in)' },
            { value: 'ft', label: 'Pé (ft)' },
            { value: 'yd', label: 'Jarda (yd)' },
            { value: 'mi', label: 'Milha (mi)' }
        ],
        weight: [
            { value: 'kg', label: 'Quilograma (kg)' },
            { value: 'g', label: 'Grama (g)' },
            { value: 'mg', label: 'Miligrama (mg)' },
            { value: 'lb', label: 'Libra (lb)' },
            { value: 'oz', label: 'Onça (oz)' }
        ],
        temp: [
            { value: 'C', label: 'Celsius (°C)' },
            { value: 'F', label: 'Fahrenheit (°F)' },
            { value: 'K', label: 'Kelvin (K)' }
        ],
        area: [
            { value: 'm2', label: 'Metro Quad. (m²)' },
            { value: 'km2', label: 'Quilômetro Quad. (km²)' },
            { value: 'cm2', label: 'Centímetro Quad. (cm²)' },
            { value: 'mm2', label: 'Milímetro Quad. (mm²)' },
            { value: 'ha', label: 'Hectare (ha)' },
            { value: 'acre', label: 'Acre (ac)' },
            { value: 'in2', label: 'Pol. Quadrada (in²)' },
            { value: 'ft2', label: 'Pé Quadrado (ft²)' }
        ],
        volume: [
            { value: 'l', label: 'Litro (L)' },
            { value: 'ml', label: 'Mililitro (mL)' },
            { value: 'm3', label: 'Metro Cúbico (m³)' },
            { value: 'cup', label: 'Xícara (cup)' },
            { value: 'gal', label: 'Galão (gal)' },
            { value: 'pt', label: 'Pinto (pt)' },
            { value: 'floz', label: 'Onça Fluida (fl oz)' }
        ],
        speed: [
            { value: 'm_s', label: 'Metro por Seg (m/s)' },
            { value: 'km_h', label: 'Quilômetro/Hora (km/h)' },
            { value: 'mph', label: 'Milhas por Hora (mph)' },
            { value: 'knot', label: 'Nós (kt)' }
        ],
        time: [
            { value: 'ms', label: 'Milissegundo (ms)' },
            { value: 's', label: 'Segundo (s)' },
            { value: 'min', label: 'Minuto (min)' },
            { value: 'h', label: 'Hora (h)' },
            { value: 'day', label: 'Dia (d)' },
            { value: 'week', label: 'Semana (sem)' },
            { value: 'month', label: 'Mês (mes)' },
            { value: 'year', label: 'Ano (ano)' }
        ],
        energy: [
            { value: 'J', label: 'Joule (J)' },
            { value: 'kJ', label: 'Quilojoule (kJ)' },
            { value: 'cal', label: 'Caloria (cal)' },
            { value: 'kcal', label: 'Quilocaloria (kcal)' },
            { value: 'Wh', label: 'Watt-hora (Wh)' },
            { value: 'kWh', label: 'Quilowatt-hora (kWh)' },
            { value: 'BTU', label: 'BTU' }
        ],
        storage: [
            { value: 'b', label: 'Bit (b)' },
            { value: 'B', label: 'Byte (B)' },
            { value: 'KB', label: 'Kilobyte (KB)' },
            { value: 'MB', label: 'Megabyte (MB)' },
            { value: 'GB', label: 'Gigabyte (GB)' },
            { value: 'TB', label: 'Terabyte (TB)' },
            { value: 'PB', label: 'Petabyte (PB)' }
        ],
        pressure: [
            { value: 'Pa', label: 'Pascal (Pa)' },
            { value: 'kPa', label: 'Kilopascal (kPa)' },
            { value: 'bar', label: 'Bar (bar)' },
            { value: 'atm', label: 'Atmosfera (atm)' },
            { value: 'psi', label: 'PSI' },
            { value: 'mmHg', label: 'Milímetros de Mercúrio (mmHg)' }
        ],
        angle: [
            { value: 'deg', label: 'Graus (°)' },
            { value: 'rad', label: 'Radianos (rad)' },
            { value: 'grad', label: 'Grados (grad)' }
        ],
        power: [
            { value: 'W', label: 'Watt (W)' },
            { value: 'kW', label: 'Kilowatt (kW)' },
            { value: 'hp', label: 'Cavalo-vapor Mecânico (hp)' },
            { value: 'cv', label: 'Cavalo-vapor Métrico (cv)' },
            { value: 'btu_h', label: 'BTU por Hora (BTU/h)' }
        ]
    };

    const unitRatios = {
        length: { m: 1, km: 1000, cm: 0.01, mm: 0.001, in: 0.0254, ft: 0.3048, yd: 0.9144, mi: 1609.344 },
        weight: { kg: 1, g: 0.001, mg: 0.000001, lb: 0.45359237, oz: 0.028349523 },
        area: { m2: 1, km2: 1000000, cm2: 0.0001, mm2: 0.000001, ha: 10000, acre: 4046.8564224, in2: 0.00064516, ft2: 0.09290304 },
        volume: { l: 1, ml: 0.001, m3: 1000, cup: 0.2365882365, gal: 3.785411784, pt: 0.473176473, floz: 0.0295735295625 },
        speed: { m_s: 1, km_h: 0.27777777777778, mph: 0.44704, knot: 0.51444444444444 },
        time: { ms: 0.001, s: 1, min: 60, h: 3600, day: 86400, week: 604800, month: 2629746, year: 31556952 },
        energy: { J: 1, kJ: 1000, cal: 4.184, kcal: 4184, Wh: 3600, kWh: 3600000, BTU: 1055.05585 },
        storage: { b: 0.125, B: 1, KB: 1024, MB: 1048576, GB: 1073741824, TB: 1099511627776, PB: 1125899906842624 },
        pressure: { Pa: 1, kPa: 1000, bar: 100000, atm: 101325, psi: 6894.757293168, mmHg: 133.322387415 },
        angle: { deg: 1, rad: 57.29577951308232, grad: 0.9 },
        power: { W: 1, kW: 1000, hp: 745.699872, cv: 735.49875, btu_h: 0.29307107 }
    };

    function populateUnits() {
        if (!unitCategory || !unitFrom || !unitTo) return;
        const cat = unitCategory.value;
        const options = unitOptions[cat] || [];

        unitFrom.innerHTML = '';
        unitTo.innerHTML = '';

        options.forEach((opt) => {
            const el1 = document.createElement('option');
            el1.value = opt.value;
            el1.innerText = opt.label;
            unitFrom.appendChild(el1);

            const el2 = document.createElement('option');
            el2.value = opt.value;
            el2.innerText = opt.label;
            unitTo.appendChild(el2);
        });

        if (options.length > 1) {
            unitFrom.selectedIndex = 0;
            unitTo.selectedIndex = 1;
        }

        performUnitConversion();
    }

    function convertTemperature(value, from, to) {
        let tempInC;
        if (from === 'C') tempInC = value;
        else if (from === 'F') tempInC = (value - 32) * 5/9;
        else if (from === 'K') tempInC = value - 273.15;

        if (to === 'C') return tempInC;
        else if (to === 'F') return tempInC * 9/5 + 32;
        else if (to === 'K') return tempInC + 273.15;
        return value;
    }

    function performUnitConversion() {
        if (!unitCategory || !unitFrom || !unitTo || !unitInputVal || !unitOutputVal) return;
        const cat = unitCategory.value;
        const from = unitFrom.value;
        const to = unitTo.value;
        const val = parseFloat(unitInputVal.value);

        if (isNaN(val)) {
            unitOutputVal.value = '';
            return;
        }

        let result;
        if (cat === 'temp') {
            result = convertTemperature(val, from, to);
        } else {
            const ratios = unitRatios[cat];
            if (ratios && ratios[from] && ratios[to]) {
                const valInBase = val * ratios[from];
                result = valInBase / ratios[to];
            } else {
                result = val;
            }
        }

        unitOutputVal.value = formatNumber(result);

        if (displayFormula) displayFormula.innerText = `Conversão de ${cat}`;
        if (displayResult) displayResult.innerText = formatNumber(result);
    }

    if (unitCategory) unitCategory.addEventListener('change', populateUnits);
    if (unitFrom) unitFrom.addEventListener('change', performUnitConversion);
    if (unitTo) unitTo.addEventListener('change', performUnitConversion);
    if (unitInputVal) unitInputVal.addEventListener('input', performUnitConversion);

    if (unitSwap) {
        unitSwap.addEventListener('click', () => {
            const temp = unitFrom.value;
            unitFrom.value = unitTo.value;
            unitTo.value = temp;
            performUnitConversion();
        });
    }

    populateUnits();


    // === CONVERSOR DE MOEDAS ===
    const currencyInputVal = document.getElementById('currencyInputVal');
    const currencyOutputVal = document.getElementById('currencyOutputVal');
    const currencyFrom = document.getElementById('currencyFrom');
    const currencyTo = document.getElementById('currencyTo');
    const btnSyncRates = document.getElementById('btnSyncRates');
    const currencySwap = document.querySelector('#pane-currency .swap-divider');
    const currencySpread = document.getElementById('currencySpread');
    const currencyIof = document.getElementById('currencyIof');
    const currencyFee = document.getElementById('currencyFee');

    function updateSyncTimeDisplay(timestamp) {
        const syncText = document.getElementById('currencyLastSyncText');
        if (!syncText) return;

        if (!timestamp) {
            syncText.innerText = 'Cotações Offline';
            return;
        }

        const date = new Date(timestamp);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        
        syncText.innerText = `Atualizado: ${day}/${month} às ${hours}:${minutes}`;
    }

    function updateCurrencyConversion() {
        if (!currencyInputVal || !currencyOutputVal || !currencyFrom || !currencyTo) return;
        
        const from = currencyFrom.value;
        const to = currencyTo.value;
        const val = parseFloat(currencyInputVal.value);
        const rateText = document.getElementById('currencyRateText');
        const inverseText = document.getElementById('currencyInverseText');

        const spread = currencySpread ? parseFloat(currencySpread.value) || 0 : 0;
        const iof = currencyIof ? parseFloat(currencyIof.value) || 0 : 0;
        const fee = currencyFee ? parseFloat(currencyFee.value) || 0 : 0;

        if (isNaN(val)) {
            currencyOutputVal.value = '';
            updateMultiCurrencyGrid(NaN);
            return;
        }

        const rateFrom = currentRates[from] || defaultRates[from];
        const rateTo = currentRates[to] || defaultRates[to];

        // Deduz tarifa
        const convertibleVal = Math.max(0, val - fee);
        
        // Conversão básica
        const valInUSD = convertibleVal / rateFrom;
        const baseResult = valInUSD * rateTo;

        // Aplica spread
        const valAfterSpread = baseResult * (1 - spread / 100);

        // Aplica IOF
        const finalResult = valAfterSpread * (1 - iof / 100);

        currencyOutputVal.value = formatNumber(finalResult);

        // Taxa direta
        const singleRate = rateTo / rateFrom;
        if (rateText) {
            const decimals = singleRate < 0.001 ? 8 : 4;
            const locale = numberFormat === 'BR' ? 'pt-BR' : 'en-US';
            const fmtRate = new Intl.NumberFormat(locale, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(singleRate);
            rateText.innerText = `Taxa: 1 ${from} = ${fmtRate} ${to}`;
        }

        // Taxa inversa
        const inverseRate = rateFrom / rateTo;
        if (inverseText) {
            const decimals = inverseRate < 0.001 ? 8 : 4;
            const locale = numberFormat === 'BR' ? 'pt-BR' : 'en-US';
            const fmtInverse = new Intl.NumberFormat(locale, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(inverseRate);
            inverseText.innerText = `Inversa: 1 ${to} = ${fmtInverse} ${from}`;
        }

        // Grade rápida multimoedas
        updateMultiCurrencyGrid(val);

        if (displayFormula) displayFormula.innerText = `Câmbio: ${from} ➔ ${to}`;
        if (displayResult) displayResult.innerText = formatNumber(finalResult);
    }

    function updateMultiCurrencyGrid(val) {
        const grid = document.getElementById('currencyMultiGrid');
        if (!grid) return;
        grid.innerHTML = '';

        if (isNaN(val)) return;

        const from = currencyFrom.value;
        const rateFrom = currentRates[from] || defaultRates[from];

        let targets = [];
        if (from === 'BRL') {
            targets = ['USD', 'EUR', 'GBP', 'BTC'];
        } else if (from === 'USD') {
            targets = ['BRL', 'EUR', 'GBP', 'BTC'];
        } else if (from === 'EUR') {
            targets = ['USD', 'BRL', 'GBP', 'BTC'];
        } else {
            targets = ['USD', 'BRL', 'EUR', 'BTC'];
        }

        const spread = currencySpread ? parseFloat(currencySpread.value) || 0 : 0;
        const iof = currencyIof ? parseFloat(currencyIof.value) || 0 : 0;
        const fee = currencyFee ? parseFloat(currencyFee.value) || 0 : 0;

        targets.forEach(to => {
            const rateTo = currentRates[to] || defaultRates[to];
            const convertibleVal = Math.max(0, val - fee);
            const valInUSD = convertibleVal / rateFrom;
            const baseResult = valInUSD * rateTo;
            const valAfterSpread = baseResult * (1 - spread / 100);
            const finalResult = valAfterSpread * (1 - iof / 100);

            const card = document.createElement('div');
            card.style.background = 'var(--bg-card)';
            card.style.border = '1px solid var(--border-color)';
            card.style.boxShadow = 'var(--shadow-inset)';
            card.style.borderRadius = '8px';
            card.style.padding = '4px';
            card.style.textAlign = 'center';

            const symbolSpan = document.createElement('span');
            symbolSpan.style.fontSize = '8px';
            symbolSpan.style.color = 'var(--text-secondary)';
            symbolSpan.style.display = 'block';
            symbolSpan.style.textTransform = 'uppercase';
            symbolSpan.innerText = to;

            const valStrong = document.createElement('strong');
            valStrong.style.fontSize = '10px';
            valStrong.style.color = 'var(--accent-color)';
            valStrong.style.display = 'block';
            valStrong.style.fontFamily = "'JetBrains Mono', monospace";
            valStrong.style.whiteSpace = 'nowrap';
            valStrong.style.overflow = 'hidden';
            valStrong.style.textOverflow = 'ellipsis';
            valStrong.innerText = formatNumber(finalResult);

            card.appendChild(symbolSpan);
            card.appendChild(valStrong);
            grid.appendChild(card);
        });
    }

    if (currencyInputVal) currencyInputVal.addEventListener('input', updateCurrencyConversion);
    if (currencyFrom) currencyFrom.addEventListener('change', updateCurrencyConversion);
    if (currencyTo) currencyTo.addEventListener('change', updateCurrencyConversion);
    if (currencySpread) currencySpread.addEventListener('input', updateCurrencyConversion);
    if (currencyIof) currencyIof.addEventListener('input', updateCurrencyConversion);
    if (currencyFee) currencyFee.addEventListener('input', updateCurrencyConversion);
    
    if (currencySwap) {
        currencySwap.addEventListener('click', () => {
            const temp = currencyFrom.value;
            currencyFrom.value = currencyTo.value;
            currencyTo.value = temp;
            updateCurrencyConversion();
        });
    }

    async function syncRates() {
        if (!btnSyncRates) return;
        btnSyncRates.innerText = '⌛...';
        try {
            const res = await fetch('https://api.coinbase.com/v2/exchange-rates?currency=USD');
            if (!res.ok) throw new Error('Falha na API Coinbase');
            const data = await res.json();
            
            if (data && data.data && data.data.rates) {
                const apiRates = data.data.rates;
                Object.keys(currentRates).forEach(cur => {
                    if (apiRates[cur]) {
                        currentRates[cur] = parseFloat(apiRates[cur]);
                    }
                });

                if (chrome && chrome.storage && chrome.storage.local) {
                    chrome.storage.local.set({ 
                        exchangeRates: currentRates,
                        lastRatesSync: Date.now()
                    });
                }

                updateSyncTimeDisplay(Date.now());
                setTimeout(updateCurrencyConversion, 800);
            }
        } catch (e) {
            console.error('Falha de sincronização:', e);
            const syncText = document.getElementById('currencyLastSyncText');
            if (syncText) syncText.innerText = 'Erro ao atualizar. Usando offline.';
        } finally {
            btnSyncRates.innerText = '🔄 Atualizar';
        }
    }

    if (btnSyncRates) btnSyncRates.addEventListener('click', syncRates);


    // === ESTATÍSTICA ===
    const statsSubMode = document.getElementById('statsSubMode');
    const statsProbType = document.getElementById('statsProbType');

    function parseNumbers(text) {
        if (!text) return [];
        let cleanedText = text;
        let previous;
        if (numberFormat === 'BR') {
            // Remove pontos de milhar (ex: 1.234,5 -> 1234,5)
            do {
                previous = cleanedText;
                cleanedText = cleanedText.replace(/(\d)\.(\d{3})(?!\d)/g, '$1$2');
            } while (cleanedText !== previous);
        } else {
            // Remove vírgulas de milhar (ex: 1,234.5 -> 1234.5)
            do {
                previous = cleanedText;
                cleanedText = cleanedText.replace(/(\d),(\d{3})(?!\d)/g, '$1$2');
            } while (cleanedText !== previous);
        }

        const nums = [];
        if (numberFormat === 'BR') {
            const rawTokens = cleanedText.split(/[\s;\n]+/);
            rawTokens.forEach(t => {
                let cleanedToken = t.trim().replace(/^[,;]+|[,;]+$/g, '');
                if (!cleanedToken) return;

                const commaCount = (cleanedToken.match(/,/g) || []).length;
                if (commaCount === 1) {
                    const numStr = cleanedToken.replace(',', '.');
                    const val = Number(numStr);
                    if (!isNaN(val)) nums.push(val);
                } else if (commaCount > 1) {
                    const subTokens = cleanedToken.split(',');
                    subTokens.forEach(st => {
                        const val = Number(st.trim());
                        if (!isNaN(val)) nums.push(val);
                    });
                } else {
                    const val = Number(cleanedToken);
                    if (!isNaN(val)) nums.push(val);
                }
            });
        } else {
            const rawTokens = cleanedText.split(/[\s,;\n]+/);
            rawTokens.forEach(t => {
                const cleanedToken = t.trim().replace(/^[,;]+|[,;]+$/g, '');
                if (!cleanedToken) return;
                const val = Number(cleanedToken);
                if (!isNaN(val)) nums.push(val);
            });
        }
        return nums;
    }

    function calculateStatistics() {
        if (!statsSubMode) return;
        const mode = statsSubMode.value;
        if (mode === 'stats-desc') {
            calculateStatsDesc();
        } else if (mode === 'stats-weighted') {
            calculateStatsWeighted();
        } else if (mode === 'stats-regression') {
            calculateStatsRegression();
        } else if (mode === 'stats-ci') {
            calculateStatsCi();
        } else if (mode === 'stats-prob') {
            calculateStatsProb();
        }
    }

    function calculateStatsDesc() {
        const statsInput = document.getElementById('statsInput');
        if (!statsInput) return;
        const nums = parseNumbers(statsInput.value);
        const ids = ['mean', 'median', 'mode', 'count', 'sum', 'range', 'min', 'max', 'std', 'var', 'pop-std', 'pop-var', 'se', 'cv', 'q1', 'q3', 'iqr', 'outliers'];

        if (nums.length === 0) {
            ids.forEach(id => {
                const el = document.getElementById(`stat-${id}`);
                if (el) el.innerText = '-';
            });
            if (displayFormula) displayFormula.innerText = 'Estatística Descritiva';
            if (displayResult) displayResult.innerText = '0';
            return;
        }

        const N = nums.length;
        const sum = nums.reduce((acc, curr) => acc + curr, 0);
        const mean = sum / N;

        const minVal = Math.min(...nums);
        const maxVal = Math.max(...nums);
        const rangeVal = maxVal - minVal;

        const sorted = [...nums].sort((a, b) => a - b);
        let median;
        if (N % 2 !== 0) {
            median = sorted[Math.floor(N / 2)];
        } else {
            median = (sorted[N / 2 - 1] + sorted[N / 2]) / 2;
        }

        const counts = {};
        let maxCount = 0;
        nums.forEach(n => {
            counts[n] = (counts[n] || 0) + 1;
            if (counts[n] > maxCount) maxCount = counts[n];
        });

        let modeText = 'Não há';
        if (maxCount > 1) {
            const modes = [];
            for (let k in counts) {
                if (counts[k] === maxCount) {
                    modes.push(formatNumber(Number(k)));
                }
            }
            const joiner = numberFormat === 'BR' ? '; ' : ', ';
            modeText = modes.join(joiner);
        }

        const sqDiffs = nums.reduce((acc, curr) => acc + Math.pow(curr - mean, 2), 0);
        let varianceSample = 0;
        if (N > 1) {
            varianceSample = sqDiffs / (N - 1);
        }
        const stdDevSample = Math.sqrt(varianceSample);
        const variancePop = sqDiffs / N;
        const stdDevPop = Math.sqrt(variancePop);
        const stdError = N > 1 ? stdDevSample / Math.sqrt(N) : 0;
        const coefVar = mean !== 0 ? (stdDevSample / mean) * 100 : 0;

        // Quartiles (Interpolation)
        const getPercentile = (p) => {
            const pos = p * (N - 1);
            const idx = Math.floor(pos);
            const frac = pos - idx;
            if (idx < N - 1) {
                return sorted[idx] + frac * (sorted[idx + 1] - sorted[idx]);
            }
            return sorted[idx];
        };
        const q1 = getPercentile(0.25);
        const q3 = getPercentile(0.75);
        const iqr = q3 - q1;

        // Outliers (Tukey's Filter)
        const lowerLimit = q1 - 1.5 * iqr;
        const upperLimit = q3 + 1.5 * iqr;
        const outliers = nums.filter(x => x < lowerLimit || x > upperLimit);
        const joiner = numberFormat === 'BR' ? '; ' : ', ';
        const outliersText = outliers.length > 0 ? outliers.map(formatNumber).join(joiner) : 'Nenhum';

        // Update UI
        document.getElementById('stat-mean').innerText = formatNumber(mean);
        document.getElementById('stat-median').innerText = formatNumber(median);
        document.getElementById('stat-mode').innerText = modeText;
        document.getElementById('stat-count').innerText = N;
        document.getElementById('stat-sum').innerText = formatNumber(sum);
        document.getElementById('stat-range').innerText = formatNumber(rangeVal);
        document.getElementById('stat-min').innerText = formatNumber(minVal);
        document.getElementById('stat-max').innerText = formatNumber(maxVal);
        document.getElementById('stat-std').innerText = N > 1 ? formatNumber(stdDevSample) : '-';
        document.getElementById('stat-var').innerText = N > 1 ? formatNumber(varianceSample) : '-';
        document.getElementById('stat-pop-std').innerText = formatNumber(stdDevPop);
        document.getElementById('stat-pop-var').innerText = formatNumber(variancePop);
        document.getElementById('stat-se').innerText = N > 1 ? formatNumber(stdError) : '-';
        document.getElementById('stat-cv').innerText = N > 1 && mean !== 0 ? `${formatNumber(coefVar)}%` : '-';
        
        document.getElementById('stat-q1').innerText = formatNumber(q1);
        document.getElementById('stat-q3').innerText = formatNumber(q3);
        document.getElementById('stat-iqr').innerText = formatNumber(iqr);
        document.getElementById('stat-outliers').innerText = outliersText;

        if (displayFormula) displayFormula.innerText = `Estatística Descritiva (N=${N})`;
        if (displayResult) displayResult.innerText = `Média: ${formatNumber(mean)}`;
    }

    function calculateStatsWeighted() {
        const statsWeightedInput = document.getElementById('statsWeightedInput');
        if (!statsWeightedInput) return;
        const text = statsWeightedInput.value;

        const lines = text.split('\n');
        let sumValW = 0;
        let sumW = 0;
        let validPairs = 0;

        lines.forEach(line => {
            const lineClean = line.trim();
            if (!lineClean) return;

            let parts;
            if (numberFormat === 'BR') {
                parts = lineClean.split(/[\t;]+/);
                if (parts.length < 2) {
                    parts = lineClean.split(/\s+/);
                }
            } else {
                parts = lineClean.split(/[\t,;]+/);
                if (parts.length < 2) {
                    parts = lineClean.split(/\s+/);
                }
            }

            const lineNums = [];
            parts.forEach(part => {
                const cleaned = part.trim();
                if (!cleaned) return;
                let val;
                if (numberFormat === 'BR') {
                    val = Number(cleaned.replace(',', '.'));
                } else {
                    val = Number(cleaned);
                }
                if (!isNaN(val)) lineNums.push(val);
            });

            if (lineNums.length >= 2) {
                const value = lineNums[0];
                const weight = lineNums[1];
                sumValW += value * weight;
                sumW += weight;
                validPairs++;
            } else if (lineNums.length === 1) {
                const value = lineNums[0];
                const weight = 1;
                sumValW += value * weight;
                sumW += weight;
                validPairs++;
            }
        });

        const weightedMeanEl = document.getElementById('stat-weighted-mean');
        const sumWeightsEl = document.getElementById('stat-weighted-sum-weights');
        const totalSumEl = document.getElementById('stat-weighted-total-sum');

        if (validPairs === 0) {
            if (weightedMeanEl) weightedMeanEl.innerText = '-';
            if (sumWeightsEl) sumWeightsEl.innerText = '-';
            if (totalSumEl) totalSumEl.innerText = '-';
            if (displayFormula) displayFormula.innerText = 'Média Ponderada';
            if (displayResult) displayResult.innerText = '0';
            return;
        }

        const weightedMean = sumW !== 0 ? sumValW / sumW : 0;

        if (weightedMeanEl) weightedMeanEl.innerText = formatNumber(weightedMean);
        if (sumWeightsEl) sumWeightsEl.innerText = formatNumber(sumW);
        if (totalSumEl) totalSumEl.innerText = formatNumber(sumValW);

        if (displayFormula) displayFormula.innerText = `Média Ponderada (N=${validPairs})`;
        if (displayResult) displayResult.innerText = `Média: ${formatNumber(weightedMean)}`;
    }

    function calculateStatsRegression() {
        const statsRegXInput = document.getElementById('statsRegXInput');
        const statsRegYInput = document.getElementById('statsRegYInput');
        if (!statsRegXInput || !statsRegYInput) return;

        const X = parseNumbers(statsRegXInput.value);
        const Y = parseNumbers(statsRegYInput.value);
        const N = Math.min(X.length, Y.length);

        const rEl = document.getElementById('stat-reg-r');
        const r2El = document.getElementById('stat-reg-r2');
        const aEl = document.getElementById('stat-reg-a');
        const bEl = document.getElementById('stat-reg-b');
        const eqEl = document.getElementById('stat-reg-equation');

        if (N < 2) {
            if (rEl) rEl.innerText = '-';
            if (r2El) r2El.innerText = '-';
            if (aEl) aEl.innerText = '-';
            if (bEl) bEl.innerText = '-';
            if (eqEl) eqEl.innerText = '-';
            if (displayFormula) displayFormula.innerText = 'Regressão Linear';
            if (displayResult) displayResult.innerText = '0';
            return;
        }

        let sumX = 0;
        let sumY = 0;
        for (let i = 0; i < N; i++) {
            sumX += X[i];
            sumY += Y[i];
        }
        const meanX = sumX / N;
        const meanY = sumY / N;

        let SSxx = 0;
        let SSyy = 0;
        let SSxy = 0;
        for (let i = 0; i < N; i++) {
            const diffX = X[i] - meanX;
            const diffY = Y[i] - meanY;
            SSxx += diffX * diffX;
            SSyy += diffY * diffY;
            SSxy += diffX * diffY;
        }

        let r = 0;
        if (SSxx * SSyy > 0) {
            r = SSxy / Math.sqrt(SSxx * SSyy);
        }
        const r2 = r * r;

        let a = 0;
        if (SSxx !== 0) {
            a = SSxy / SSxx;
        }
        const b = meanY - a * meanX;

        // format equation
        let eq = 'Y = ';
        const strA = formatNumber(a);
        const strB = formatNumber(Math.abs(b));
        if (a !== 0) {
            eq += `${strA}X`;
            if (b > 0) eq += ` + ${strB}`;
            else if (b < 0) eq += ` - ${strB}`;
        } else {
            eq += formatNumber(b);
        }

        if (rEl) rEl.innerText = formatNumber(r);
        if (r2El) r2El.innerText = formatNumber(r2);
        if (aEl) aEl.innerText = formatNumber(a);
        if (bEl) bEl.innerText = formatNumber(b);
        if (eqEl) eqEl.innerText = eq;

        if (displayFormula) displayFormula.innerText = `Regressão Linear (N=${N})`;
        if (displayResult) displayResult.innerText = `r = ${formatNumber(r)}`;
    }

    function calculateStatsCi() {
        const statsCiInput = document.getElementById('statsCiInput');
        if (!statsCiInput) return;
        const nums = parseNumbers(statsCiInput.value);
        const N = nums.length;

        const meanEl = document.getElementById('stat-ci-mean');
        const nEl = document.getElementById('stat-ci-n');
        const meEl = document.getElementById('stat-ci-me');
        const intervalEl = document.getElementById('stat-ci-interval');

        if (N < 2) {
            if (meanEl) meanEl.innerText = '-';
            if (nEl) nEl.innerText = '-';
            if (meEl) meEl.innerText = '-';
            if (intervalEl) intervalEl.innerText = '-';
            if (displayFormula) displayFormula.innerText = 'Intervalo de Confiança';
            if (displayResult) displayResult.innerText = '0';
            return;
        }

        const sum = nums.reduce((acc, curr) => acc + curr, 0);
        const mean = sum / N;

        const sqDiffs = nums.reduce((acc, curr) => acc + Math.pow(curr - mean, 2), 0);
        const variance = sqDiffs / (N - 1);
        const stdDev = Math.sqrt(variance);

        const level = document.getElementById('statsCiLevel').value;
        let z = 1.95996;
        if (level === '90') z = 1.64485;
        else if (level === '99') z = 2.57583;

        const me = z * (stdDev / Math.sqrt(N));
        const lower = mean - me;
        const upper = mean + me;
        const sep = numberFormat === 'BR' ? '; ' : ', ';

        if (meanEl) meanEl.innerText = formatNumber(mean);
        if (nEl) nEl.innerText = N;
        if (meEl) meEl.innerText = formatNumber(me);
        if (intervalEl) intervalEl.innerText = `[${formatNumber(lower)}${sep}${formatNumber(upper)}]`;

        if (displayFormula) displayFormula.innerText = `Int. de Confiança (${level}%)`;
        if (displayResult) displayResult.innerText = `IC: [${formatNumber(lower)}${sep}${formatNumber(upper)}]`;
    }

    function calculateStatsProb() {
        const probType = document.getElementById('statsProbType').value;

        if (probType === 'normal') {
            const meanVal = Number(document.getElementById('statsProbNormMean').value);
            const stdVal = Number(document.getElementById('statsProbNormStd').value);
            const xVal = Number(document.getElementById('statsProbNormX').value);

            if (isNaN(meanVal) || isNaN(stdVal) || isNaN(xVal) || stdVal <= 0) {
                document.getElementById('stat-prob-norm-z').innerText = '-';
                document.getElementById('stat-prob-norm-left').innerText = '-';
                document.getElementById('stat-prob-norm-right').innerText = '-';
                if (displayFormula) displayFormula.innerText = 'Distribuição Normal';
                if (displayResult) displayResult.innerText = '0';
                return;
            }

            const z = (xVal - meanVal) / stdVal;

            const normCDF = (zVal) => {
                const neg = zVal < 0;
                if (neg) zVal = -zVal;
                const t = 1.0 / (1.0 + 0.2316419 * zVal);
                const c1 = 0.319381530;
                const c2 = -0.356563782;
                const c3 = 1.781477937;
                const c4 = -1.821255978;
                const c5 = 1.330274429;
                const poly = t * (c1 + t * (c2 + t * (c3 + t * (c4 + t * c5))));
                const exp = Math.exp(-zVal * zVal / 2.0);
                const p = 1.0 - (1.0 / Math.sqrt(2.0 * Math.PI)) * exp * poly;
                return neg ? 1.0 - p : p;
            };

            const leftProb = normCDF(z);
            const rightProb = 1.0 - leftProb;

            document.getElementById('stat-prob-norm-z').innerText = formatNumber(z);
            document.getElementById('stat-prob-norm-left').innerText = `${formatNumber(leftProb * 100)}%`;
            document.getElementById('stat-prob-norm-right').innerText = `${formatNumber(rightProb * 100)}%`;

            if (displayFormula) displayFormula.innerText = `Dist. Normal (μ=${formatNumber(meanVal)}, σ=${formatNumber(stdVal)})`;
            if (displayResult) displayResult.innerText = `P(Z < X): ${formatNumber(leftProb * 100)}%`;

        } else {
            const nVal = parseInt(document.getElementById('statsProbBinN').value);
            const pVal = Number(document.getElementById('statsProbBinP').value);
            const kVal = parseInt(document.getElementById('statsProbBinK').value);

            if (isNaN(nVal) || isNaN(pVal) || isNaN(kVal) || nVal < 0 || pVal < 0 || pVal > 1 || kVal < 0 || kVal > nVal) {
                document.getElementById('stat-prob-bin-eq').innerText = '-';
                document.getElementById('stat-prob-bin-le').innerText = '-';
                document.getElementById('stat-prob-bin-ge').innerText = '-';
                if (displayFormula) displayFormula.innerText = 'Distribuição Binomial';
                if (displayResult) displayResult.innerText = '0';
                return;
            }

            const binomCoeff = (n, i) => {
                if (i < 0 || i > n) return 0;
                if (i === 0 || i === n) return 1;
                if (i > n / 2) i = n - i;
                let res = 1;
                for (let j = 1; j <= i; j++) {
                    res = res * (n - j + 1) / j;
                }
                return res;
            };

            const binomPMF = (n, p, i) => {
                return binomCoeff(n, i) * Math.pow(p, i) * Math.pow(1 - p, n - i);
            };

            const eqProb = binomPMF(nVal, pVal, kVal);
            
            let leProb = 0;
            for (let i = 0; i <= kVal; i++) {
                leProb += binomPMF(nVal, pVal, i);
            }

            let geProb = 0;
            for (let i = kVal; i <= nVal; i++) {
                geProb += binomPMF(nVal, pVal, i);
            }

            document.getElementById('stat-prob-bin-eq').innerText = `${formatNumber(eqProb * 100)}%`;
            document.getElementById('stat-prob-bin-le').innerText = `${formatNumber(leProb * 100)}%`;
            document.getElementById('stat-prob-bin-ge').innerText = `${formatNumber(geProb * 100)}%`;

            if (displayFormula) displayFormula.innerText = `Dist. Binomial (n=${nVal}, p=${formatNumber(pVal)})`;
            if (displayResult) displayResult.innerText = `P(X = k): ${formatNumber(eqProb * 100)}%`;
        }
    }

    function updateStatsInputsVisibility() {
        if (!statsSubMode) return;
        const mode = statsSubMode.value;

        const panes = document.querySelectorAll('#pane-statistics .sub-pane');
        panes.forEach(pane => {
            if (pane.id === `sub-${mode}`) {
                pane.classList.add('active');
            } else {
                pane.classList.remove('active');
            }
        });

        updateKeyboardTargetForStats();
    }

    function updateKeyboardTargetForStats() {
        if (!statsSubMode) return;
        const mode = statsSubMode.value;
        const pad = document.querySelector('#pane-statistics .compact-pad');
        if (!pad) return;

        let targetId = '';
        if (mode === 'stats-desc') {
            targetId = 'statsInput';
        } else if (mode === 'stats-weighted') {
            targetId = 'statsWeightedInput';
        } else if (mode === 'stats-regression') {
            targetId = 'statsRegXInput';
        } else if (mode === 'stats-ci') {
            targetId = 'statsCiInput';
        } else if (mode === 'stats-prob') {
            const probType = document.getElementById('statsProbType').value;
            if (probType === 'normal') {
                targetId = 'statsProbNormX';
            } else {
                targetId = 'statsProbBinK';
            }
        }

        if (targetId) {
            pad.setAttribute('data-target', targetId);
            const inputEl = document.getElementById(targetId);
            if (inputEl) inputEl.focus();
        }
    }

    function updateProbInputs() {
        const type = document.getElementById('statsProbType');
        if (!type) return;
        const typeVal = type.value;
        const normInputs = document.getElementById('prob-normal-inputs');
        const binInputs = document.getElementById('prob-binomial-inputs');
        const normResults = document.getElementById('prob-normal-results');
        const binResults = document.getElementById('prob-binomial-results');

        if (typeVal === 'normal') {
            if (normInputs) normInputs.style.display = 'block';
            if (binInputs) binInputs.style.display = 'none';
            if (normResults) normResults.style.display = 'grid';
            if (binResults) binResults.style.display = 'none';
        } else {
            if (normInputs) normInputs.style.display = 'none';
            if (binInputs) binInputs.style.display = 'block';
            if (normResults) normResults.style.display = 'none';
            if (binResults) binResults.style.display = 'grid';
        }
    }

    if (statsSubMode) {
        statsSubMode.addEventListener('change', () => {
            updateStatsInputsVisibility();
            calculateStatistics();
        });
    }

    if (statsProbType) {
        statsProbType.addEventListener('change', () => {
            updateProbInputs();
            updateKeyboardTargetForStats();
            calculateStatistics();
        });
    }

    // Attach listeners to all inputs in stats pane
    const statsInputs = document.querySelectorAll('#pane-statistics .panel-input, #pane-statistics .panel-select, #pane-statistics .panel-textarea');
    statsInputs.forEach(input => {
        input.addEventListener('input', calculateStatistics);
        input.addEventListener('change', calculateStatistics);
    });

    const statsButtons = document.querySelectorAll('#pane-statistics .submit-btn');
    statsButtons.forEach(btn => {
        btn.addEventListener('click', calculateStatistics);
    });

    // Initial updates
    updateStatsInputsVisibility();
    updateProbInputs();


    // === PORCENTAGEM & REGRA DE TRÊS ===
    // === PORCENTAGEM & REGRA DE TRÊS ===
    const pctSubMode = document.getElementById('pctSubMode');

    function calculatePctDirect() {
        const valEl = document.getElementById('pctDirectVal');
        const baseEl = document.getElementById('pctDirectBase');
        const card = document.getElementById('pctDirectResultCard');
        const resultEl = document.getElementById('pctDirectResult');
        if (!valEl || !baseEl || !resultEl) return;

        const val = parseFloat(valEl.value);
        const base = parseFloat(baseEl.value);

        if (isNaN(val) || isNaN(base)) {
            if (card) card.style.display = 'none';
            return;
        }

        const res = (val / 100) * base;
        resultEl.innerText = formatNumber(res);
        if (card) card.style.display = 'block';

        if (displayFormula) displayFormula.innerText = `${formatNumber(val)}% de ${formatNumber(base)}`;
        if (displayResult) displayResult.innerText = formatNumber(res);
    }

    function calculatePctRatio() {
        const partEl = document.getElementById('pctRatioPart');
        const wholeEl = document.getElementById('pctRatioWhole');
        const card = document.getElementById('pctRatioResultCard');
        const resultEl = document.getElementById('pctRatioResult');
        if (!partEl || !wholeEl || !resultEl) return;

        const part = parseFloat(partEl.value);
        const whole = parseFloat(wholeEl.value);

        if (isNaN(part) || isNaN(whole)) {
            if (card) card.style.display = 'none';
            return;
        }

        if (whole === 0) {
            resultEl.innerText = 'Divisão por 0';
            if (card) card.style.display = 'block';
            return;
        }

        const res = (part / whole) * 100;
        resultEl.innerText = `${formatNumber(res)}%`;
        if (card) card.style.display = 'block';

        if (displayFormula) displayFormula.innerText = `${formatNumber(part)} ÷ ${formatNumber(whole)}`;
        if (displayResult) displayResult.innerText = `${formatNumber(res)}%`;
    }

    function calculatePctDiff() {
        const startEl = document.getElementById('pctDiffStart');
        const endEl = document.getElementById('pctDiffEnd');
        const card = document.getElementById('pctDiffResultCard');
        const resultEl = document.getElementById('pctDiffResult');
        if (!startEl || !endEl || !resultEl) return;

        const start = parseFloat(startEl.value);
        const end = parseFloat(endEl.value);

        if (isNaN(start) || isNaN(end)) {
            if (card) card.style.display = 'none';
            return;
        }

        if (start === 0) {
            resultEl.innerText = 'Divisão por 0';
            if (card) card.style.display = 'block';
            return;
        }

        const pct = ((end - start) / start) * 100;
        const sign = pct > 0 ? '+' : '';
        const actionText = pct > 0 ? '(Aumento)' : pct < 0 ? '(Redução)' : '';
        
        resultEl.innerText = `${sign}${formatNumber(pct)}% ${actionText}`;
        if (card) card.style.display = 'block';

        if (displayFormula) displayFormula.innerText = `Var. de ${formatNumber(start)} para ${formatNumber(end)}`;
        if (displayResult) displayResult.innerText = `${sign}${formatNumber(pct)}%`;
    }

    function calculatePctChange() {
        const baseEl = document.getElementById('pctChangeBase');
        const pctEl = document.getElementById('pctChangePct');
        const opEl = document.getElementById('pctChangeOp');
        const card = document.getElementById('pctChangeResultCard');
        const resultEl = document.getElementById('pctChangeResult');
        if (!baseEl || !pctEl || !opEl || !resultEl) return;

        const base = parseFloat(baseEl.value);
        const pct = parseFloat(pctEl.value);
        const op = opEl.value;

        if (isNaN(base) || isNaN(pct)) {
            if (card) card.style.display = 'none';
            return;
        }

        let res = 0;
        if (op === 'add') {
            res = base * (1 + pct / 100);
        } else {
            res = base * (1 - pct / 100);
        }

        resultEl.innerText = formatNumber(res);
        if (card) card.style.display = 'block';

        const sign = op === 'add' ? '+' : '-';
        if (displayFormula) displayFormula.innerText = `${formatNumber(base)} ${sign} ${formatNumber(pct)}%`;
        if (displayResult) displayResult.innerText = formatNumber(res);
    }

    function calculatePctOriginal() {
        const finalEl = document.getElementById('pctOriginalFinal');
        const pctEl = document.getElementById('pctOriginalPct');
        const opEl = document.getElementById('pctOriginalOp');
        const card = document.getElementById('pctOriginalResultCard');
        const resultEl = document.getElementById('pctOriginalResult');
        if (!finalEl || !pctEl || !opEl || !resultEl) return;

        const finalVal = parseFloat(finalEl.value);
        const pct = parseFloat(pctEl.value);
        const op = opEl.value;

        if (isNaN(finalVal) || isNaN(pct)) {
            if (card) card.style.display = 'none';
            return;
        }

        let res = 0;
        if (op === 'discount') {
            if (pct >= 100) {
                resultEl.innerText = 'Erro (% >= 100)';
                if (card) card.style.display = 'block';
                return;
            }
            res = finalVal / (1 - pct / 100);
        } else {
            res = finalVal / (1 + pct / 100);
        }

        resultEl.innerText = formatNumber(res);
        if (card) card.style.display = 'block';

        const desc = op === 'discount' ? 'antes de desc. de' : 'antes de aum. de';
        if (displayFormula) displayFormula.innerText = `${formatNumber(finalVal)} ${desc} ${formatNumber(pct)}%`;
        if (displayResult) displayResult.innerText = formatNumber(res);
    }

    function calculateRuleOfThree() {
        const threeA = document.getElementById('threeA');
        const threeB = document.getElementById('threeB');
        const threeC = document.getElementById('threeC');
        const threeX = document.getElementById('threeX');
        const typeEl = document.getElementById('pctThreeType');
        if (!threeA || !threeB || !threeC || !threeX || !typeEl) return;

        const a = parseFloat(threeA.value);
        const b = parseFloat(threeB.value);
        const c = parseFloat(threeC.value);
        const isInverse = typeEl.value === 'inverse';

        if (isNaN(a) || isNaN(b) || isNaN(c)) {
            threeX.value = '';
            return;
        }

        if (isInverse) {
            if (c === 0) {
                threeX.value = 'Erro (C=0)';
                return;
            }
            const x = (a * b) / c;
            threeX.value = formatNumber(x);
            if (displayFormula) displayFormula.innerText = `Regra 3 Inv.: (${formatNumber(a)} × ${formatNumber(b)}) ÷ ${formatNumber(c)}`;
            if (displayResult) displayResult.innerText = formatNumber(x);
        } else {
            if (a === 0) {
                threeX.value = 'Erro (A=0)';
                return;
            }
            const x = (b * c) / a;
            threeX.value = formatNumber(x);
            if (displayFormula) displayFormula.innerText = `Regra 3 Dir.: (${formatNumber(c)} × ${formatNumber(b)}) ÷ ${formatNumber(a)}`;
            if (displayResult) displayResult.innerText = formatNumber(x);
        }
    }

    function calculatePercentage() {
        if (!pctSubMode) return;
        const mode = pctSubMode.value;
        if (mode === 'pct-direct') {
            calculatePctDirect();
        } else if (mode === 'pct-ratio') {
            calculatePctRatio();
        } else if (mode === 'pct-diff') {
            calculatePctDiff();
        } else if (mode === 'pct-change') {
            calculatePctChange();
        } else if (mode === 'pct-original') {
            calculatePctOriginal();
        } else if (mode === 'pct-three') {
            calculateRuleOfThree();
        }
    }

    function updatePctInputsVisibility() {
        if (!pctSubMode) return;
        const mode = pctSubMode.value;

        const panes = document.querySelectorAll('#pane-percentage .sub-pane');
        panes.forEach(pane => {
            if (pane.id === `sub-${mode}`) {
                pane.classList.add('active');
            } else {
                pane.classList.remove('active');
            }
        });

        updateKeyboardTargetForPct();
    }

    function updateKeyboardTargetForPct() {
        if (!pctSubMode) return;
        const mode = pctSubMode.value;
        const pad = document.querySelector('#pane-percentage .compact-pad');
        if (!pad) return;

        let targetId = '';
        if (mode === 'pct-direct') {
            targetId = 'pctDirectVal';
        } else if (mode === 'pct-ratio') {
            targetId = 'pctRatioPart';
        } else if (mode === 'pct-diff') {
            targetId = 'pctDiffStart';
        } else if (mode === 'pct-change') {
            targetId = 'pctChangeBase';
        } else if (mode === 'pct-original') {
            targetId = 'pctOriginalFinal';
        } else if (mode === 'pct-three') {
            targetId = 'threeA';
        }

        if (targetId) {
            pad.setAttribute('data-target', targetId);
            const inputEl = document.getElementById(targetId);
            if (inputEl) inputEl.focus();
        }
    }

    if (pctSubMode) {
        pctSubMode.addEventListener('change', () => {
            updatePctInputsVisibility();
            calculatePercentage();
        });
    }

    // Attach listeners dynamically to all inputs under #pane-percentage
    const pctInputs = document.querySelectorAll('#pane-percentage .panel-input, #pane-percentage .panel-select');
    pctInputs.forEach(input => {
        input.addEventListener('input', calculatePercentage);
        input.addEventListener('change', calculatePercentage);
    });

    const pctButtons = document.querySelectorAll('#pane-percentage .submit-btn');
    pctButtons.forEach(btn => {
        btn.addEventListener('click', calculatePercentage);
    });

    // Initialize layout and state
    updatePctInputsVisibility();


    // === FINANÇAS ===
    const financeSubMode = document.getElementById('financeSubMode');

    function formatCurrency(value) {
        const locale = numberFormat === 'BR' ? 'pt-BR' : 'en-US';
        return new Intl.NumberFormat(locale, { style: 'currency', currency: 'BRL' }).format(value);
    }

    function calculateFinSimple() {
        const capEl = document.getElementById('finSimpleCapital');
        const rateEl = document.getElementById('finSimpleRate');
        const timeEl = document.getElementById('finSimpleTime');
        const jurosEl = document.getElementById('finSimpleJurosVal');
        const avgEl = document.getElementById('finSimpleAvgVal');
        const totalEl = document.getElementById('finSimpleTotalVal');
        if (!capEl || !rateEl || !timeEl) return;

        const cap = parseFloat(capEl.value);
        const rate = parseFloat(rateEl.value);
        const time = parseFloat(timeEl.value);

        if (isNaN(cap) || isNaN(rate) || isNaN(time)) {
            if (jurosEl) jurosEl.innerText = formatCurrency(0);
            if (avgEl) avgEl.innerText = formatCurrency(0);
            if (totalEl) totalEl.innerText = formatCurrency(0);
            return;
        }

        const juros = cap * (rate / 100) * time;
        const total = cap + juros;
        const avgMonthly = time > 0 ? juros / time : 0;

        if (jurosEl) jurosEl.innerText = formatCurrency(juros);
        if (avgEl) avgEl.innerText = formatCurrency(avgMonthly);
        if (totalEl) totalEl.innerText = formatCurrency(total);

        if (displayFormula) displayFormula.innerText = `Juros Simples: J = ${formatNumber(cap)} × ${formatNumber(rate)}% × ${formatNumber(time)}`;
        if (displayResult) displayResult.innerText = formatCurrency(total);
    }

    function calculateFinCompound() {
        const capEl = document.getElementById('finCompoundCapital');
        const rateEl = document.getElementById('finCompoundRate');
        const timeEl = document.getElementById('finCompoundTime');
        const jurosEl = document.getElementById('finCompoundJurosVal');
        const avgEl = document.getElementById('finCompoundAvgVal');
        const totalEl = document.getElementById('finCompoundTotalVal');
        if (!capEl || !rateEl || !timeEl) return;

        const cap = parseFloat(capEl.value);
        const rate = parseFloat(rateEl.value);
        const time = parseFloat(timeEl.value);

        if (isNaN(cap) || isNaN(rate) || isNaN(time)) {
            if (jurosEl) jurosEl.innerText = formatCurrency(0);
            if (avgEl) avgEl.innerText = formatCurrency(0);
            if (totalEl) totalEl.innerText = formatCurrency(0);
            return;
        }

        const total = cap * Math.pow(1 + rate / 100, time);
        const juros = total - cap;
        const avgMonthly = time > 0 ? juros / time : 0;

        if (jurosEl) jurosEl.innerText = formatCurrency(juros);
        if (avgEl) avgEl.innerText = formatCurrency(avgMonthly);
        if (totalEl) totalEl.innerText = formatCurrency(total);

        if (displayFormula) displayFormula.innerText = `Juros Compostos: M = ${formatNumber(cap)} × (1 + ${formatNumber(rate)}%)^${formatNumber(time)}`;
        if (displayResult) displayResult.innerText = formatCurrency(total);
    }

    function calculateFinInvest() {
        const capEl = document.getElementById('finInvestCapital');
        const monthlyEl = document.getElementById('finInvestMonthly');
        const rateEl = document.getElementById('finInvestRate');
        const rateTypeEl = document.getElementById('finInvestRateType');
        const timeEl = document.getElementById('finInvestTime');
        const timeTypeEl = document.getElementById('finInvestTimeType');

        const totalInvestedEl = document.getElementById('finInvestTotalInvested');
        const totalInterestEl = document.getElementById('finInvestTotalInterest');
        const totalFinalEl = document.getElementById('finInvestTotalFinal');

        if (!capEl || !monthlyEl || !rateEl || !rateTypeEl || !timeEl || !timeTypeEl) return;

        const cap = parseFloat(capEl.value) || 0;
        const monthly = parseFloat(monthlyEl.value) || 0;
        const rate = parseFloat(rateEl.value) || 0;
        const time = parseFloat(timeEl.value) || 0;

        const tMeses = timeTypeEl.value === 'years' ? time * 12 : time;

        let rMensal = rate / 100;
        if (rateTypeEl.value === 'yearly') {
            rMensal = Math.pow(1 + rate / 100, 1 / 12) - 1;
        }

        if (isNaN(cap) || isNaN(monthly) || isNaN(rate) || isNaN(time) || tMeses <= 0) {
            if (totalInvestedEl) totalInvestedEl.innerText = formatCurrency(0);
            if (totalInterestEl) totalInterestEl.innerText = formatCurrency(0);
            if (totalFinalEl) totalFinalEl.innerText = formatCurrency(0);
            return;
        }

        const totalInvested = cap + (monthly * tMeses);
        let totalFinal = 0;

        if (rMensal === 0) {
            totalFinal = totalInvested;
        } else {
            const compoundCap = cap * Math.pow(1 + rMensal, tMeses);
            const compoundAportes = monthly * ((Math.pow(1 + rMensal, tMeses) - 1) / rMensal);
            totalFinal = compoundCap + compoundAportes;
        }

        const totalInterest = Math.max(0, totalFinal - totalInvested);

        if (totalInvestedEl) totalInvestedEl.innerText = formatCurrency(totalInvested);
        if (totalInterestEl) totalInterestEl.innerText = formatCurrency(totalInterest);
        if (totalFinalEl) totalFinalEl.innerText = formatCurrency(totalFinal);

        if (displayFormula) displayFormula.innerText = `Investimentos: Cap=${formatNumber(cap)} + ${formatNumber(tMeses)}×${formatNumber(monthly)}`;
        if (displayResult) displayResult.innerText = formatCurrency(totalFinal);
    }

    function calculateFinAmort() {
        const valEl = document.getElementById('finAmortValue');
        const rateEl = document.getElementById('finAmortRate');
        const timeEl = document.getElementById('finAmortTime');

        const pricePmtEl = document.getElementById('finAmortPricePmt');
        const priceTotalEl = document.getElementById('finAmortPriceTotal');
        const priceInterestEl = document.getElementById('finAmortPriceInterest');

        const sacPmtRangeEl = document.getElementById('finAmortSacPmtRange');
        const sacTotalEl = document.getElementById('finAmortSacTotal');
        const sacInterestEl = document.getElementById('finAmortSacInterest');

        if (!valEl || !rateEl || !timeEl) return;

        const pv = parseFloat(valEl.value);
        const rate = parseFloat(rateEl.value);
        const n = parseFloat(timeEl.value);

        if (isNaN(pv) || isNaN(rate) || isNaN(n) || n <= 0) {
            const zero = formatCurrency(0);
            if (pricePmtEl) pricePmtEl.innerText = zero;
            if (priceTotalEl) priceTotalEl.innerText = zero;
            if (priceInterestEl) priceInterestEl.innerText = zero;
            if (sacPmtRangeEl) sacPmtRangeEl.innerText = zero;
            if (sacTotalEl) sacTotalEl.innerText = zero;
            if (sacInterestEl) sacInterestEl.innerText = zero;
            return;
        }

        const i = rate / 100;

        let pmtPrice = 0;
        let totalPrice = 0;
        let interestPrice = 0;

        if (i === 0) {
            pmtPrice = pv / n;
            totalPrice = pv;
            interestPrice = 0;
        } else {
            pmtPrice = pv * (i * Math.pow(1 + i, n)) / (Math.pow(1 + i, n) - 1);
            totalPrice = pmtPrice * n;
            interestPrice = totalPrice - pv;
        }

        const amortizationSac = pv / n;
        const pmt1Sac = amortizationSac + (pv * i);
        const pmtNSac = amortizationSac + (amortizationSac * i);
        const interestSac = ((pmt1Sac + pmtNSac) * n) / 2 - pv;
        const totalSac = pv + interestSac;

        if (pricePmtEl) pricePmtEl.innerText = formatCurrency(pmtPrice);
        if (priceTotalEl) priceTotalEl.innerText = formatCurrency(totalPrice);
        if (priceInterestEl) priceInterestEl.innerText = formatCurrency(interestPrice);

        if (sacPmtRangeEl) {
            sacPmtRangeEl.innerText = `${formatCurrency(pmt1Sac)} / ${formatCurrency(pmtNSac)}`;
        }
        if (sacTotalEl) sacTotalEl.innerText = formatCurrency(totalSac);
        if (sacInterestEl) sacInterestEl.innerText = formatCurrency(interestSac);

        if (displayFormula) displayFormula.innerText = `PRICE vs SAC: Financiado=${formatNumber(pv)}, n=${formatNumber(n)}`;
        if (displayResult) displayResult.innerText = `PRICE: ${formatCurrency(totalPrice)} | SAC: ${formatCurrency(totalSac)}`;
    }

    function calculateFinFire() {
        const incomeEl = document.getElementById('finFireIncome');
        const capitalEl = document.getElementById('finFireCapital');
        const monthlyEl = document.getElementById('finFireMonthly');
        const rateEl = document.getElementById('finFireRate');

        const targetCapitalEl = document.getElementById('finFireTargetCapital');
        const yearsEl = document.getElementById('finFireYears');
        const aportesEl = document.getElementById('finFireAportes');

        if (!incomeEl || !capitalEl || !monthlyEl || !rateEl) return;

        const income = parseFloat(incomeEl.value) || 0;
        const cap = parseFloat(capitalEl.value) || 0;
        const monthly = parseFloat(monthlyEl.value) || 0;
        const rateYearly = parseFloat(rateEl.value) || 0;

        const rateMonthly = Math.pow(1 + rateYearly / 100, 1 / 12) - 1;

        if (isNaN(income) || isNaN(cap) || isNaN(monthly) || isNaN(rateYearly) || rateMonthly <= 0) {
            if (targetCapitalEl) targetCapitalEl.innerText = '-';
            if (yearsEl) yearsEl.innerText = '-';
            if (aportesEl) aportesEl.innerText = '-';
            return;
        }

        const targetCapital = income / rateMonthly;
        if (targetCapitalEl) targetCapitalEl.innerText = formatCurrency(targetCapital);

        if (cap * rateMonthly + monthly <= 0 || targetCapital <= cap) {
            if (targetCapital <= cap) {
                if (yearsEl) yearsEl.innerText = 'Meta Atingida!';
                if (aportesEl) aportesEl.innerText = formatCurrency(0);
            } else {
                if (yearsEl) yearsEl.innerText = 'Impossível com taxas/aportes atuais';
                if (aportesEl) aportesEl.innerText = '-';
            }
            return;
        }

        const num = targetCapital * rateMonthly + monthly;
        const den = cap * rateMonthly + monthly;
        const tMonths = Math.log(num / den) / Math.log(1 + rateMonthly);

        const totalYears = Math.floor(tMonths / 12);
        const totalMonths = Math.round(tMonths % 12);

        let timeText = '';
        if (totalYears > 0) {
            timeText += `${totalYears} ano(s)`;
            if (totalMonths > 0) timeText += ` e ${totalMonths} mês(es)`;
        } else {
            timeText += `${Math.round(tMonths)} mês(es)`;
        }

        const totalAportes = monthly * tMonths;

        if (yearsEl) yearsEl.innerText = timeText;
        if (aportesEl) aportesEl.innerText = formatCurrency(totalAportes);

        if (displayFormula) displayFormula.innerText = `FIRE: Renda=${formatCurrency(income)}, Meta=${formatCurrency(targetCapital)}`;
        if (displayResult) displayResult.innerText = timeText;
    }

    function calculateFinRates() {
        const valEl = document.getElementById('finRatesValue');
        const typeEl = document.getElementById('finRatesType');
        const resultTextEl = document.getElementById('finRatesResultText');

        if (!valEl || !typeEl || !resultTextEl) return;

        const rate = parseFloat(valEl.value);
        if (isNaN(rate)) {
            resultTextEl.innerText = '-';
            return;
        }

        let resultRate = 0;
        let desc = '';
        if (typeEl.value === 'm-to-y') {
            resultRate = (Math.pow(1 + rate / 100, 12) - 1) * 100;
            desc = `${formatNumber(rate)}% a.m. = ${formatNumber(resultRate)}% a.a.`;
        } else {
            resultRate = (Math.pow(1 + rate / 100, 1 / 12) - 1) * 100;
            desc = `${formatNumber(rate)}% a.a. = ${formatNumber(resultRate)}% a.m.`;
        }

        resultTextEl.innerText = `${formatNumber(resultRate, 4)}%`;

        if (displayFormula) displayFormula.innerText = `Conversor: ${desc}`;
        if (displayResult) displayResult.innerText = `${formatNumber(resultRate, 4)}%`;
    }

    function calculateFinMargin() {
        const baseEl = document.getElementById('finMarginBase');
        const pctEl = document.getElementById('finMarginPct');
        const opEl = document.getElementById('finMarginOp');

        const valEl = document.getElementById('finMarginResultVal');
        const diffEl = document.getElementById('finMarginResultDiff');

        const label1 = document.getElementById('finMarginResultLabel1');
        const label2 = document.getElementById('finMarginResultLabel2');

        if (!baseEl || !pctEl || !opEl || !valEl || !diffEl) return;

        const base = parseFloat(baseEl.value);
        const pct = parseFloat(pctEl.value);
        const op = opEl.value;

        if (isNaN(base) || isNaN(pct)) {
            valEl.innerText = '-';
            diffEl.innerText = '-';
            return;
        }

        let finalVal = 0;
        let diffVal = 0;

        if (op === 'discount') {
            if (label1) label1.innerText = 'Preço com Desconto';
            if (label2) label2.innerText = 'Valor Economizado';
            diffVal = base * (pct / 100);
            finalVal = base - diffVal;
        } else if (op === 'margin') {
            if (label1) label1.innerText = 'Preço de Venda';
            if (label2) label2.innerText = 'Lucro Esperado';
            if (pct >= 100) {
                valEl.innerText = 'Erro (Margem >= 100%)';
                diffEl.innerText = '-';
                return;
            }
            finalVal = base / (1 - pct / 100);
            diffVal = finalVal - base;
        } else {
            if (label1) label1.innerText = 'Preço de Venda';
            if (label2) label2.innerText = 'Lucro Esperado';
            finalVal = base * (1 + pct / 100);
            diffVal = finalVal - base;
        }

        valEl.innerText = formatCurrency(finalVal);
        diffEl.innerText = formatCurrency(diffVal);

        if (displayFormula) displayFormula.innerText = `Desconto/Margem: Base=${formatNumber(base)}, Pct=${formatNumber(pct)}%`;
        if (displayResult) displayResult.innerText = formatCurrency(finalVal);
    }

    function calculateFinance() {
        if (!financeSubMode) return;
        const mode = financeSubMode.value;
        if (mode === 'fin-simple') {
            calculateFinSimple();
        } else if (mode === 'fin-compound') {
            calculateFinCompound();
        } else if (mode === 'fin-invest') {
            calculateFinInvest();
        } else if (mode === 'fin-amort') {
            calculateFinAmort();
        } else if (mode === 'fin-fire') {
            calculateFinFire();
        } else if (mode === 'fin-rates') {
            calculateFinRates();
        } else if (mode === 'fin-margin') {
            calculateFinMargin();
        }
    }

    function updateFinanceInputsVisibility() {
        if (!financeSubMode) return;
        const mode = financeSubMode.value;

        const panes = document.querySelectorAll('#pane-finance .sub-pane');
        panes.forEach(pane => {
            if (pane.id === `sub-${mode}`) {
                pane.classList.add('active');
            } else {
                pane.classList.remove('active');
            }
        });

        updateKeyboardTargetForFinance();
    }

    function updateKeyboardTargetForFinance() {
        if (!financeSubMode) return;
        const mode = financeSubMode.value;
        const pad = document.querySelector('#pane-finance .compact-pad');
        if (!pad) return;

        let targetId = '';
        if (mode === 'fin-simple') {
            targetId = 'finSimpleCapital';
        } else if (mode === 'fin-compound') {
            targetId = 'finCompoundCapital';
        } else if (mode === 'fin-invest') {
            targetId = 'finInvestCapital';
        } else if (mode === 'fin-amort') {
            targetId = 'finAmortValue';
        } else if (mode === 'fin-fire') {
            targetId = 'finFireIncome';
        } else if (mode === 'fin-rates') {
            targetId = 'finRatesValue';
        } else if (mode === 'fin-margin') {
            targetId = 'finMarginBase';
        }

        if (targetId) {
            pad.setAttribute('data-target', targetId);
            const inputEl = document.getElementById(targetId);
            if (inputEl) inputEl.focus();
        }
    }

    if (financeSubMode) {
        financeSubMode.addEventListener('change', () => {
            updateFinanceInputsVisibility();
            calculateFinance();
        });
    }

    // Attach listeners dynamically to all inputs under #pane-finance
    const financeInputs = document.querySelectorAll('#pane-finance .panel-input, #pane-finance .panel-select');
    financeInputs.forEach(input => {
        input.addEventListener('input', calculateFinance);
        input.addEventListener('change', calculateFinance);
    });

    const finButtons = document.querySelectorAll('#pane-finance .submit-btn');
    finButtons.forEach(btn => {
        btn.addEventListener('click', calculateFinance);
    });

    // Initialize layout and state
    updateFinanceInputsVisibility();


    // === CÁLCULO DE DATAS ===
    const dateStart = document.getElementById('dateStart');
    const dateEnd = document.getElementById('dateEnd');
    const dateBase = document.getElementById('dateBase');
    const dateDaysOffset = document.getElementById('dateDaysOffset');
    const dateOpType = document.getElementById('dateOpType');

    const btnDateDiffCalc = document.getElementById('btnDateDiffCalc');
    const btnDateAddCalc = document.getElementById('btnDateAddCalc');

    const todayISO = new Date().toISOString().split('T')[0];
    if (dateStart) dateStart.value = todayISO;
    if (dateEnd) dateEnd.value = todayISO;
    if (dateBase) dateBase.value = todayISO;

    // === CONTROLE DE VISIBILIDADE E SUB-MÓDULOS DE DATA ===
    const dateSubMode = document.getElementById('dateSubMode');
    const dateWorkType = document.getElementById('dateWorkType');

    function updateDateInputsVisibility() {
        if (!dateSubMode) return;
        const mode = dateSubMode.value;

        // Oculta todos os sub-panes
        document.querySelectorAll('#pane-dates .sub-pane').forEach(p => p.classList.remove('active'));
        
        // Exibe o sub-pane ativo
        const activePane = document.getElementById(`sub-${mode}`);
        if (activePane) activePane.classList.add('active');

        // Foca automaticamente no campo numérico adequado do módulo ativo para o teclado virtual
        updateKeyboardTargetForDate();
    }

    function updateKeyboardTargetForDate() {
        if (!dateSubMode) return;
        const mode = dateSubMode.value;
        const pad = document.querySelector('#pane-dates .compact-pad');
        if (!pad) return;

        let targetId = '';
        if (mode === 'date-add') {
            targetId = 'dateDaysOffset';
        } else if (mode === 'date-work') {
            const typeVal = dateWorkType ? dateWorkType.value : 'count';
            if (typeVal === 'offset') {
                targetId = 'dateWorkOffset';
            }
        }

        if (targetId) {
            pad.setAttribute('data-target', targetId);
            const inputEl = document.getElementById(targetId);
            if (inputEl) inputEl.focus();
        }
    }

    if (dateSubMode) {
        dateSubMode.addEventListener('change', () => {
            updateDateInputsVisibility();
            triggerPaneCalculations('dates');
        });
    }

    if (dateWorkType) {
        dateWorkType.addEventListener('change', () => {
            const val = dateWorkType.value;
            const wrapCount = document.getElementById('wrapper-date-work-count');
            const wrapOffset = document.getElementById('wrapper-date-work-offset');
            if (val === 'count') {
                if (wrapCount) wrapCount.style.display = 'block';
                if (wrapOffset) wrapOffset.style.display = 'none';
            } else {
                if (wrapCount) wrapCount.style.display = 'none';
                if (wrapOffset) wrapOffset.style.display = 'block';
            }
            updateKeyboardTargetForDate();
            calculateDateWork();
        });
    }

    function getDateDifference(d1, d2) {
        let start = new Date(d1);
        let end = new Date(d2);
        if (start > end) {
            let temp = start;
            start = end;
            end = temp;
        }
        let years = end.getFullYear() - start.getFullYear();
        let months = end.getMonth() - start.getMonth();
        let days = end.getDate() - start.getDate();
        
        if (days < 0) {
            months--;
            let prevMonth = new Date(end.getFullYear(), end.getMonth(), 0);
            days += prevMonth.getDate();
        }
        if (months < 0) {
            years--;
            months += 12;
        }
        return { years, months, days };
    }

    function calculateDateDifference() {
        if (!dateStart || !dateEnd) return;
        const startVal = dateStart.value;
        const endVal = dateEnd.value;
        const card = document.getElementById('dateDiffResult');

        if (!startVal || !endVal) {
            if (card) card.style.display = 'none';
            return;
        }

        const start = new Date(startVal + 'T00:00:00');
        const end = new Date(endVal + 'T00:00:00');

        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        const breakdown = getDateDifference(start, end);

        if (card) {
            card.style.display = 'block';
            document.getElementById('dateDiffDays').innerText = `${formatNumber(diffDays)} dia(s)`;

            const parts = [];
            if (breakdown.years > 0) parts.push(`${formatNumber(breakdown.years)} ano(s)`);
            if (breakdown.months > 0) parts.push(`${formatNumber(breakdown.months)} mê(s)es`);
            if (breakdown.days > 0) parts.push(`${formatNumber(breakdown.days)} dia(s)`);

            document.getElementById('dateDiffBreakdown').innerText = parts.length === 0 ? 'Mesma data' : parts.join(', ');

            // Equivalência em semanas e horas
            const weeksVal = Number((diffDays / 7).toFixed(1));
            const hoursVal = diffDays * 24;
            const extraEl = document.getElementById('dateDiffExtra');
            if (extraEl) {
                extraEl.innerText = `${formatNumber(weeksVal)} semana(s) ou ${formatNumber(hoursVal)} hora(s)`;
            }
        }

        if (displayFormula) displayFormula.innerText = 'Diferença entre datas';
        if (displayResult) displayResult.innerText = `${diffDays} dias`;
    }

    function calculateDateAdd() {
        if (!dateBase || !dateDaysOffset || !dateOpType) return;
        const baseVal = dateBase.value;
        const offset = parseInt(dateDaysOffset.value);
        const op = dateOpType.value;
        const card = document.getElementById('dateAddResult');

        if (!baseVal || isNaN(offset)) {
            if (card) card.style.display = 'none';
            return;
        }

        const date = new Date(baseVal + 'T00:00:00');
        const multiplier = op === 'add' ? 1 : -1;
        date.setDate(date.getDate() + multiplier * offset);

        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        const formatted = `${day}/${month}/${year}`;

        if (card) {
            card.style.display = 'block';
            document.getElementById('dateCalculatedDisplay').innerText = formatted;
        }

        if (displayFormula) displayFormula.innerText = `Data ${op === 'add' ? '+' : '-'} ${offset} dias`;
        if (displayResult) displayResult.innerText = formatted;
    }

    // === DIAS ÚTEIS ===
    function calculateDateWork() {
        const typeEl = document.getElementById('dateWorkType');
        const card = document.getElementById('dateWorkResult');
        const primaryEl = document.getElementById('dateWorkPrimary');
        const secondaryEl = document.getElementById('dateWorkSecondary');

        if (!typeEl || !card || !primaryEl || !secondaryEl) return;
        const type = typeEl.value;

        if (type === 'count') {
            const startInput = document.getElementById('dateWorkStart');
            const endInput = document.getElementById('dateWorkEnd');
            if (!startInput || !endInput) return;
            const startVal = startInput.value;
            const endVal = endInput.value;

            if (!startVal || !endVal) {
                card.style.display = 'none';
                return;
            }

            const start = new Date(startVal + 'T00:00:00');
            const end = new Date(endVal + 'T00:00:00');

            let d1 = new Date(start);
            let d2 = new Date(end);
            if (d1 > d2) {
                let temp = d1;
                d1 = d2;
                d2 = temp;
            }

            let totalDays = Math.round((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;
            let workDays = 0;
            let weekends = 0;
            let current = new Date(d1);

            for (let i = 0; i < totalDays; i++) {
                let day = current.getDay();
                if (day === 0 || day === 6) {
                    weekends++;
                } else {
                    workDays++;
                }
                current.setDate(current.getDate() + 1);
            }

            card.style.display = 'block';
            primaryEl.innerText = `${workDays} dia(s) úteis`;
            secondaryEl.innerText = `Total: ${totalDays} dias | Finais de Semana: ${weekends} dias`;

            if (displayFormula) displayFormula.innerText = 'Dias Úteis entre duas datas';
            if (displayResult) displayResult.innerText = `${workDays} úteis`;
        } else {
            const baseInput = document.getElementById('dateWorkBase');
            const offsetInput = document.getElementById('dateWorkOffset');
            const opSelect = document.getElementById('dateWorkOp');

            if (!baseInput || !offsetInput || !opSelect) return;
            const baseVal = baseInput.value;
            const offset = parseInt(offsetInput.value);
            const op = opSelect.value;

            if (!baseVal || isNaN(offset)) {
                card.style.display = 'none';
                return;
            }

            let date = new Date(baseVal + 'T00:00:00');
            let daysRemaining = Math.abs(offset);
            let step = op === 'add' ? 1 : -1;

            while (daysRemaining > 0) {
                date.setDate(date.getDate() + step);
                let day = date.getDay();
                if (day !== 0 && day !== 6) {
                    daysRemaining--;
                }
            }

            const dayStr = String(date.getDate()).padStart(2, '0');
            const monthStr = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const formatted = `${dayStr}/${monthStr}/${year}`;

            const weekdayNames = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
            const targetWeekday = weekdayNames[date.getDay()];

            card.style.display = 'block';
            primaryEl.innerText = formatted;
            secondaryEl.innerText = `Cai em uma ${targetWeekday}`;

            if (displayFormula) displayFormula.innerText = `Data ${op === 'add' ? '+' : '-'} ${offset} dias úteis`;
            if (displayResult) displayResult.innerText = formatted;
        }
    }

    // === CALCULADORA DE IDADE & SIGNO ===
    function calculateDateAge() {
        const birthInput = document.getElementById('dateBirth');
        const card = document.getElementById('dateAgeResult');
        const exactEl = document.getElementById('dateAgeExact');
        const signEl = document.getElementById('dateAgeSign');
        const nextBdayEl = document.getElementById('dateAgeNextBday');
        const statsEl = document.getElementById('dateAgeStats');

        if (!birthInput || !card || !exactEl || !signEl || !nextBdayEl || !statsEl) return;
        const birthVal = birthInput.value;

        if (!birthVal) {
            card.style.display = 'none';
            return;
        }

        const birth = new Date(birthVal + 'T00:00:00');
        const today = new Date();
        today.setHours(0,0,0,0);

        if (birth > today) {
            card.style.display = 'block';
            exactEl.innerText = 'Data de nascimento no futuro';
            signEl.innerText = '-';
            nextBdayEl.innerText = '-';
            statsEl.innerText = '-';
            return;
        }

        let years = today.getFullYear() - birth.getFullYear();
        let months = today.getMonth() - birth.getMonth();
        let days = today.getDate() - birth.getDate();

        if (days < 0) {
            months--;
            let prevMonth = new Date(today.getFullYear(), today.getMonth(), 0);
            days += prevMonth.getDate();
        }
        if (months < 0) {
            years--;
            months += 12;
        }

        let nextBday = new Date(today.getFullYear(), birth.getMonth(), birth.getDate());
        if (nextBday < today) {
            nextBday.setFullYear(today.getFullYear() + 1);
        }
        const diffMs = nextBday - today;
        const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24));

        let nextBdayText = '';
        if (diffDays === 0 || (birth.getMonth() === today.getMonth() && birth.getDate() === today.getDate())) {
            nextBdayText = 'Hoje! 🎉';
        } else {
            const nextMonths = Math.floor(diffDays / 30);
            const nextDays = diffDays % 30;
            if (nextMonths > 0) {
                nextBdayText = `${nextMonths}m e ${nextDays}d (${diffDays}d)`;
            } else {
                nextBdayText = `${diffDays} dia(s)`;
            }
        }

        const zodiac = getZodiacSign(birth);
        const totalLivedDays = Math.floor((today - birth) / (1000 * 60 * 60 * 24));
        const totalHours = totalLivedDays * 24;

        card.style.display = 'block';
        exactEl.innerText = `${years} ano(s), ${months} mê(s)es e ${days} dia(s)`;
        signEl.innerText = `${zodiac.icon} ${zodiac.name}`;
        nextBdayEl.innerText = nextBdayText;

        const locale = numberFormat === 'BR' ? 'pt-BR' : 'en-US';
        const fmtInt = new Intl.NumberFormat(locale, { maximumFractionDigits: 0 });
        statsEl.innerText = `Total vivido: ${fmtInt.format(totalLivedDays)} dias ou ${fmtInt.format(totalHours)} horas.`;

        if (displayFormula) displayFormula.innerText = `Idade Calculada`;
        if (displayResult) displayResult.innerText = `${years} anos`;
    }

    function getZodiacSign(date) {
        const day = date.getDate();
        const month = date.getMonth() + 1;
        if ((month == 3 && day >= 21) || (month == 4 && day <= 19)) return { name: "Áries", icon: "♈" };
        if ((month == 4 && day >= 20) || (month == 5 && day <= 20)) return { name: "Touro", icon: "♉" };
        if ((month == 5 && day >= 21) || (month == 6 && day <= 20)) return { name: "Gêmeos", icon: "♊" };
        if ((month == 6 && day >= 21) || (month == 7 && day <= 22)) return { name: "Câncer", icon: "♋" };
        if ((month == 7 && day >= 23) || (month == 8 && day <= 22)) return { name: "Leão", icon: "♌" };
        if ((month == 8 && day >= 23) || (month == 9 && day <= 22)) return { name: "Virgem", icon: "♍" };
        if ((month == 9 && day >= 23) || (month == 10 && day <= 22)) return { name: "Libra", icon: "♎" };
        if ((month == 10 && day >= 23) || (month == 11 && day <= 21)) return { name: "Escorpião", icon: "♏" };
        if ((month == 11 && day >= 22) || (month == 12 && day <= 21)) return { name: "Sagitário", icon: "♐" };
        if ((month == 12 && day >= 22) || (month == 1 && day <= 19)) return { name: "Capricórnio", icon: "♑" };
        if ((month == 1 && day >= 20) || (month == 2 && day <= 18)) return { name: "Aquário", icon: "♒" };
        if ((month == 2 && day >= 19) || (month == 3 && day <= 20)) return { name: "Peixes", icon: "♓" };
        return { name: "Desconhecido", icon: "❓" };
    }

    // === DESCOBRIR DIA DA SEMANA ===
    function calculateDateWeekday() {
        const input = document.getElementById('dateWeekdayVal');
        const card = document.getElementById('dateWeekdayResult');
        const display = document.getElementById('dateWeekdayDisplay');

        if (!input || !card || !display) return;
        const val = input.value;

        if (!val) {
            card.style.display = 'none';
            return;
        }

        const date = new Date(val + 'T00:00:00');
        const weekdayNames = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
        const weekday = weekdayNames[date.getDay()];

        card.style.display = 'block';
        display.innerText = weekday;

        if (displayFormula) displayFormula.innerText = 'Dia da Semana correspondente';
        if (displayResult) displayResult.innerText = weekday;
    }

    // === ANO BISSEXTO & DIA ORDINAL ===
    function calculateDateLeap() {
        const input = document.getElementById('dateLeapDate');
        const card = document.getElementById('dateLeapResult');
        const yearDisplay = document.getElementById('dateLeapYearDisplay');
        const ordinalDisplay = document.getElementById('dateLeapOrdinalDisplay');
        const remainingDisplay = document.getElementById('dateLeapRemainingDisplay');

        if (!input || !card || !yearDisplay || !ordinalDisplay || !remainingDisplay) return;
        const val = input.value;

        if (!val) {
            card.style.display = 'none';
            return;
        }

        const date = new Date(val + 'T00:00:00');
        const year = date.getFullYear();

        const isLeap = (year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0);
        const totalDaysInYear = isLeap ? 366 : 365;

        const start = new Date(year, 0, 0);
        const diff = date - start;
        const oneDay = 1000 * 60 * 60 * 24;
        const ordinalDay = Math.floor(diff / oneDay);
        const remainingDays = totalDaysInYear - ordinalDay;

        card.style.display = 'block';
        yearDisplay.innerText = isLeap ? 'Sim (366 dias)' : 'Não (365 dias)';
        ordinalDisplay.innerText = `${ordinalDay}º dia`;
        remainingDisplay.innerText = `Faltam ${remainingDays} dias para o fim do ano de ${year}.`;

        if (displayFormula) displayFormula.innerText = `Ano & Dia Ordinal (${year})`;
        if (displayResult) displayResult.innerText = isLeap ? 'Ano Bissexto' : 'Ano Normal';
    }

    // === VINCULAÇÃO DE EVENTOS DE DATAS ===
    if (btnDateDiffCalc) btnDateDiffCalc.addEventListener('click', calculateDateDifference);
    if (dateStart) dateStart.addEventListener('change', calculateDateDifference);
    if (dateEnd) dateEnd.addEventListener('change', calculateDateDifference);
 
    if (btnDateAddCalc) btnDateAddCalc.addEventListener('click', calculateDateAdd);
    if (dateBase) dateBase.addEventListener('change', calculateDateAdd);
    if (dateDaysOffset) dateDaysOffset.addEventListener('input', calculateDateAdd);
    if (dateOpType) dateOpType.addEventListener('change', calculateDateAdd);

    const dateWorkStart = document.getElementById('dateWorkStart');
    const dateWorkEnd = document.getElementById('dateWorkEnd');
    const dateWorkBase = document.getElementById('dateWorkBase');
    const dateWorkOffset = document.getElementById('dateWorkOffset');
    const dateWorkOp = document.getElementById('dateWorkOp');
    const btnDateWorkCalc = document.getElementById('btnDateWorkCalc');

    if (btnDateWorkCalc) btnDateWorkCalc.addEventListener('click', calculateDateWork);
    if (dateWorkStart) dateWorkStart.addEventListener('change', calculateDateWork);
    if (dateWorkEnd) dateWorkEnd.addEventListener('change', calculateDateWork);
    if (dateWorkBase) dateWorkBase.addEventListener('change', calculateDateWork);
    if (dateWorkOffset) dateWorkOffset.addEventListener('input', calculateDateWork);
    if (dateWorkOp) dateWorkOp.addEventListener('change', calculateDateWork);

    const dateBirth = document.getElementById('dateBirth');
    const btnDateAgeCalc = document.getElementById('btnDateAgeCalc');
    if (btnDateAgeCalc) btnDateAgeCalc.addEventListener('click', calculateDateAge);
    if (dateBirth) dateBirth.addEventListener('change', calculateDateAge);

    const dateWeekdayVal = document.getElementById('dateWeekdayVal');
    const btnDateWeekdayCalc = document.getElementById('btnDateWeekdayCalc');
    if (btnDateWeekdayCalc) btnDateWeekdayCalc.addEventListener('click', calculateDateWeekday);
    if (dateWeekdayVal) dateWeekdayVal.addEventListener('change', calculateDateWeekday);

    const dateLeapDate = document.getElementById('dateLeapDate');
    const btnDateLeapCalc = document.getElementById('btnDateLeapCalc');
    if (btnDateLeapCalc) btnDateLeapCalc.addEventListener('click', calculateDateLeap);
    if (dateLeapDate) dateLeapDate.addEventListener('change', calculateDateLeap);

    // Inicialização das datas padrão
    if (dateWorkStart) dateWorkStart.value = todayISO;
    if (dateWorkEnd) dateWorkEnd.value = todayISO;
    if (dateWorkBase) dateWorkBase.value = todayISO;
    if (dateBirth) dateBirth.value = '2000-01-01'; 
    if (dateWeekdayVal) dateWeekdayVal.value = todayISO;
    if (dateLeapDate) dateLeapDate.value = todayISO;

    // Atualiza a exibição de data inicial
    updateDateInputsVisibility();


    // === SAÚDE (IMC) ===
    const healthWeight = document.getElementById('healthWeight');
    const healthHeight = document.getElementById('healthHeight');
    const btnHealthCalc = document.getElementById('btnHealthCalc');

    function calculateIMC() {
        if (!healthWeight || !healthHeight) return;
        const weight = parseFloat(healthWeight.value);
        const height = parseFloat(healthHeight.value);
        const card = document.getElementById('imcResultCard');
        const valueEl = document.getElementById('imcValue');
        const statusEl = document.getElementById('imcStatus');

        if (isNaN(weight) || isNaN(height) || height === 0) {
            if (card) card.style.display = 'none';
            return;
        }

        const heightM = height / 100;
        const imc = weight / (heightM * heightM);

        const locale = numberFormat === 'BR' ? 'pt-BR' : 'en-US';
        const formattedIMC = new Intl.NumberFormat(locale, {
            minimumFractionDigits: 1,
            maximumFractionDigits: 1
        }).format(imc);

        if (card && valueEl && statusEl) {
            card.style.display = 'block';
            valueEl.innerText = formattedIMC;

            let statusText = '';
            let colorBg = '';
            let colorText = '';

            if (imc < 18.5) {
                statusText = 'Abaixo do Peso';
                colorBg = 'rgba(79, 140, 255, 0.15)';
                colorText = '#6AA8FF';
            } else if (imc >= 18.5 && imc < 25) {
                statusText = 'Peso Normal';
                colorBg = 'rgba(16, 185, 129, 0.15)';
                colorText = '#10B981';
            } else if (imc >= 25 && imc < 30) {
                statusText = 'Sobrepeso';
                colorBg = 'rgba(245, 158, 11, 0.15)';
                colorText = '#F59E0B';
            } else {
                statusText = 'Obesidade';
                colorBg = 'rgba(239, 68, 68, 0.15)';
                colorText = '#EF4444';
            }

            statusEl.innerText = statusText;
            statusEl.style.backgroundColor = colorBg;
            statusEl.style.color = colorText;

            // Peso Ideal e Água Recomendada
            const minWeight = 18.5 * (heightM * heightM);
            const maxWeight = 24.9 * (heightM * heightM);
            const waterIntake = weight * 0.035;

            const fmtOneDec = new Intl.NumberFormat(locale, { minimumFractionDigits: 1, maximumFractionDigits: 1 });
            const rangeEl = document.getElementById('imcWeightRange');
            const waterEl = document.getElementById('imcWaterIntake');

            if (rangeEl) {
                rangeEl.innerText = `${fmtOneDec.format(minWeight)} - ${fmtOneDec.format(maxWeight)} kg`;
            }
            if (waterEl) {
                waterEl.innerText = `${fmtOneDec.format(waterIntake)} L`;
            }
        }

        if (displayFormula) displayFormula.innerText = 'Índice de Massa Corporal (IMC)';
        if (displayResult) displayResult.innerText = formattedIMC;
    }

    if (btnHealthCalc) btnHealthCalc.addEventListener('click', calculateIMC);
    if (healthWeight) healthWeight.addEventListener('input', () => triggerPaneCalculations('health'));
    if (healthHeight) healthHeight.addEventListener('input', () => triggerPaneCalculations('health'));


    // === TECLADOS NUMÉRICOS VIRTUAIS COMPACTOS ===
    const inputFocusTracker = {};

    function setupInputFocusTracking() {
        const inputs = document.querySelectorAll('.panel-input:not([readonly]), .panel-textarea');
        inputs.forEach(input => {
            if (input.dataset.tracked) return;
            input.dataset.tracked = "true";

            const pane = input.closest('.keyboard-pane');
            if (pane && !inputFocusTracker[pane.id]) {
                inputFocusTracker[pane.id] = input;
                const pad = pane.querySelector('.compact-pad');
                if (pad) {
                    pad.setAttribute('data-target', input.id);
                }
            }

            input.addEventListener('focus', () => {
                if (pane) {
                    inputFocusTracker[pane.id] = input;
                    const pad = pane.querySelector('.compact-pad');
                    if (pad) {
                        pad.setAttribute('data-target', input.id);
                    }
                }
            });
        });
    }

    function setupCompactPads() {
        const pads = document.querySelectorAll('.compact-pad');
        pads.forEach(pad => {
            pad.addEventListener('click', (e) => {
                const btn = e.target.closest('button');
                if (!btn) return;

                const targetId = pad.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (!input) return;

                e.preventDefault();

                const val = btn.getAttribute('data-val');
                const action = btn.getAttribute('data-action');
                let currentVal = input.value;

                if (val !== null) {
                    if (currentVal === '0' && val !== '.') {
                        currentVal = val;
                    } else {
                        currentVal += val;
                    }
                } else if (action === 'backspace') {
                    currentVal = currentVal.slice(0, -1);
                } else if (action === 'clear-input') {
                    currentVal = '';
                }

                input.value = currentVal;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.focus();
            });
        });
    }

    // === CALCULADORA DE MATRIZES ---
    let currentMatrixDim = 3;
    let activeMatrixMode = 'single'; // 'single' ou 'binary'
    let currentMatrixOp = 'det';

    function generateMatrixGrid(gridId, dim) {
        const grid = document.getElementById(gridId);
        if (!grid) return;
        grid.innerHTML = '';
        grid.style.gridTemplateColumns = `repeat(${dim}, 1fr)`;
        
        for (let i = 0; i < dim; i++) {
            for (let j = 0; j < dim; j++) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'matrix-cell-input panel-input';
                input.id = `${gridId}-${i}-${j}`;
                input.value = (i === j) ? '1' : '0';
                
                input.addEventListener('input', () => {
                    calculateMatrixOperation();
                });
                
                grid.appendChild(input);
            }
        }
    }

    function getMatrixValues(gridId) {
        const dim = currentMatrixDim;
        const matrix = [];
        for (let i = 0; i < dim; i++) {
            const row = [];
            for (let j = 0; j < dim; j++) {
                const el = document.getElementById(`${gridId}-${i}-${j}`);
                const val = el ? parseFloat(el.value) : 0;
                row.push(isNaN(val) ? 0 : val);
            }
            matrix.push(row);
        }
        return matrix;
    }

    function renderResultMatrix(matrix) {
        const container = document.getElementById('matrixResultGridContainer');
        if (!container) return;
        container.innerHTML = '';
        
        const grid = document.createElement('div');
        grid.className = 'matrix-result-grid';
        grid.style.gridTemplateColumns = `repeat(${matrix[0].length}, 1fr)`;
        
        for (let i = 0; i < matrix.length; i++) {
            for (let j = 0; j < matrix[i].length; j++) {
                const cell = document.createElement('div');
                cell.className = 'matrix-result-cell';
                cell.innerText = formatNumber(matrix[i][j]);
                cell.title = matrix[i][j];
                grid.appendChild(cell);
            }
        }
        container.appendChild(grid);
    }

    function renderTwoResultMatrices(label1, mat1, label2, mat2) {
        const container = document.getElementById('matrixResultGridContainer');
        if (!container) return;
        container.innerHTML = '';

        const block1 = document.createElement('div');
        block1.style.textAlign = 'center';
        block1.style.width = '100%';
        const span1 = document.createElement('span');
        span1.className = 'panel-label';
        span1.innerText = label1;
        block1.appendChild(span1);
        
        const grid1 = document.createElement('div');
        grid1.className = 'matrix-result-grid';
        grid1.style.gridTemplateColumns = `repeat(${mat1[0].length}, 1fr)`;
        grid1.style.marginTop = '4px';
        for (let i = 0; i < mat1.length; i++) {
            for (let j = 0; j < mat1[i].length; j++) {
                const cell = document.createElement('div');
                cell.className = 'matrix-result-cell';
                cell.innerText = formatNumber(mat1[i][j]);
                cell.title = mat1[i][j];
                grid1.appendChild(cell);
            }
        }
        block1.appendChild(grid1);

        const block2 = document.createElement('div');
        block2.style.textAlign = 'center';
        block2.style.width = '100%';
        block2.style.marginTop = '8px';
        const span2 = document.createElement('span');
        span2.className = 'panel-label';
        span2.innerText = label2;
        block2.appendChild(span2);

        const grid2 = document.createElement('div');
        grid2.className = 'matrix-result-grid';
        grid2.style.gridTemplateColumns = `repeat(${mat2[0].length}, 1fr)`;
        grid2.style.marginTop = '4px';
        for (let i = 0; i < mat2.length; i++) {
            for (let j = 0; j < mat2[i].length; j++) {
                const cell = document.createElement('div');
                cell.className = 'matrix-result-cell';
                cell.innerText = formatNumber(mat2[i][j]);
                cell.title = mat2[i][j];
                grid2.appendChild(cell);
            }
        }
        block2.appendChild(grid2);

        container.appendChild(block1);
        container.appendChild(block2);
    }

    // --- ALGORITMOS MATEMÁTICOS ---
    function getDeterminant(m) {
        const n = m.length;
        if (n === 1) return m[0][0];
        if (n === 2) return m[0][0] * m[1][1] - m[0][1] * m[1][0];
        let det = 0;
        for (let j = 0; j < n; j++) {
            const subMat = [];
            for (let i = 1; i < n; i++) {
                const row = m[i].filter((_, colIdx) => colIdx !== j);
                subMat.push(row);
            }
            const sign = j % 2 === 0 ? 1 : -1;
            det += sign * m[0][j] * getDeterminant(subMat);
        }
        return det;
    }

    function getInverse(m) {
        const n = m.length;
        const aug = [];
        for (let i = 0; i < n; i++) {
            const row = [...m[i]];
            for (let j = 0; j < n; j++) {
                row.push(i === j ? 1 : 0);
            }
            aug.push(row);
        }
        for (let i = 0; i < n; i++) {
            let pivotRow = i;
            for (let r = i + 1; r < n; r++) {
                if (Math.abs(aug[r][i]) > Math.abs(aug[pivotRow][i])) {
                    pivotRow = r;
                }
            }
            if (Math.abs(aug[pivotRow][i]) < 1e-9) {
                return null;
            }
            if (pivotRow !== i) {
                const temp = aug[i];
                aug[i] = aug[pivotRow];
                aug[pivotRow] = temp;
            }
            const pivot = aug[i][i];
            for (let j = i; j < 2 * n; j++) {
                aug[i][j] /= pivot;
            }
            for (let r = 0; r < n; r++) {
                if (r !== i) {
                    const factor = aug[r][i];
                    for (let j = i; j < 2 * n; j++) {
                        aug[r][j] -= factor * aug[i][j];
                    }
                }
            }
        }
        const inv = [];
        for (let i = 0; i < n; i++) {
            inv.push(aug[i].slice(n));
        }
        return inv;
    }

    function getTranspose(m) {
        const n = m.length;
        const transpose = [];
        for (let j = 0; j < n; j++) {
            const row = [];
            for (let i = 0; i < n; i++) {
                row.push(m[i][j]);
            }
            transpose.push(row);
        }
        return transpose;
    }

    function getRank(m) {
        const mat = m.map(row => [...row]);
        const R = mat.length;
        const C = mat[0].length;
        let rank = 0;
        for (let col = 0; col < C; col++) {
            let pivotRow = -1;
            for (let r = rank; r < R; r++) {
                if (Math.abs(mat[r][col]) > 1e-9) {
                    pivotRow = r;
                    break;
                }
            }
            if (pivotRow !== -1) {
                const temp = mat[rank];
                mat[rank] = mat[pivotRow];
                mat[pivotRow] = temp;
                for (let r = rank + 1; r < R; r++) {
                    const factor = mat[r][col] / mat[rank][col];
                    for (let c = col; c < C; c++) {
                        mat[r][c] -= factor * mat[rank][c];
                    }
                }
                rank++;
            }
        }
        return rank;
    }

    function getUpperTriangular(m) {
        const n = m.length;
        const mat = m.map(row => [...row]);
        for (let i = 0; i < n; i++) {
            let pivotRow = i;
            for (let r = i + 1; r < n; r++) {
                if (Math.abs(mat[r][i]) > Math.abs(mat[pivotRow][i])) {
                    pivotRow = r;
                }
            }
            if (pivotRow !== i && Math.abs(mat[pivotRow][i]) > 1e-9) {
                const temp = mat[i];
                mat[i] = mat[pivotRow];
                mat[pivotRow] = temp;
            }
            if (Math.abs(mat[i][i]) > 1e-9) {
                for (let r = i + 1; r < n; r++) {
                    const factor = mat[r][i] / mat[i][i];
                    for (let c = i; c < n; c++) {
                        mat[r][c] -= factor * mat[i][c];
                    }
                }
            }
        }
        for (let i = 0; i < n; i++) {
            for (let j = 0; j < n; j++) {
                if (Math.abs(mat[i][j]) < 1e-9) mat[i][j] = 0;
            }
        }
        return mat;
    }

    function getDiagonal(m) {
        const n = m.length;
        const mat = [];
        for (let i = 0; i < n; i++) {
            const row = [];
            for (let j = 0; j < n; j++) {
                row.push(i === j ? m[i][j] : 0);
            }
            mat.push(row);
        }
        return mat;
    }

    function multiplyByScalar(m, scalar) {
        return m.map(row => row.map(val => val * scalar));
    }

    function matrixPower(m, k) {
        const n = m.length;
        let res = [];
        for (let i = 0; i < n; i++) {
            const row = [];
            for (let j = 0; j < n; j++) {
                row.push(i === j ? 1 : 0);
            }
            res.push(row);
        }
        if (k === 0) return res;
        
        const multiply = (a, b) => {
            const c = [];
            for (let i = 0; i < n; i++) {
                const row = [];
                for (let j = 0; j < n; j++) {
                    let sum = 0;
                    for (let x = 0; x < n; x++) {
                        sum += a[i][x] * b[x][j];
                    }
                    row.push(sum);
                }
                c.push(row);
            }
            return c;
        };

        let temp = m;
        let exp = k;
        while (exp > 0) {
            if (exp % 2 === 1) {
                res = multiply(res, temp);
            }
            temp = multiply(temp, temp);
            exp = Math.floor(exp / 2);
        }
        return res;
    }

    function multiplyMatrices(a, b) {
        const n = a.length;
        const c = [];
        for (let i = 0; i < n; i++) {
            const row = [];
            for (let j = 0; j < n; j++) {
                let sum = 0;
                for (let x = 0; x < n; x++) {
                    sum += a[i][x] * b[x][j];
                }
                row.push(sum);
            }
            c.push(row);
        }
        return c;
    }

    function addMatrices(a, b) {
        return a.map((row, r) => row.map((val, c) => val + b[r][c]));
    }

    function luDecomposition(m) {
        const n = m.length;
        const L = [];
        const U = [];
        for (let i = 0; i < n; i++) {
            const lRow = [];
            const uRow = [];
            for (let j = 0; j < n; j++) {
                lRow.push(i === j ? 1 : 0);
                uRow.push(0);
            }
            L.push(lRow);
            U.push(uRow);
        }

        for (let i = 0; i < n; i++) {
            for (let k = i; k < n; k++) {
                let sum = 0;
                for (let j = 0; j < i; j++) {
                    sum += L[i][j] * U[j][k];
                }
                U[i][k] = m[i][k] - sum;
            }
            for (let k = i + 1; k < n; k++) {
                let sum = 0;
                for (let j = 0; j < i; j++) {
                    sum += L[k][j] * U[j][i];
                }
                if (Math.abs(U[i][i]) < 1e-9) {
                    return null;
                }
                L[k][i] = (m[k][i] - sum) / U[i][i];
            }
        }
        return { L, U };
    }

    function choleskyDecomposition(m) {
        const n = m.length;
        for (let i = 0; i < n; i++) {
            for (let j = i + 1; j < n; j++) {
                if (Math.abs(m[i][j] - m[j][i]) > 1e-9) {
                    return { error: 'Matriz deve ser simétrica.' };
                }
            }
        }
        const L = [];
        for (let i = 0; i < n; i++) {
            const row = [];
            for (let j = 0; j < n; j++) row.push(0);
            L.push(row);
        }
        for (let i = 0; i < n; i++) {
            for (let j = 0; j <= i; j++) {
                let sum = 0;
                for (let k = 0; k < j; k++) {
                    sum += L[i][k] * L[j][k];
                }
                if (i === j) {
                    const val = m[i][i] - sum;
                    if (val <= 0) {
                        return { error: 'A matriz deve ser definida positiva.' };
                    }
                    L[i][j] = Math.sqrt(val);
                } else {
                    if (Math.abs(L[j][j]) < 1e-9) {
                        return { error: 'Fatoração impossível (divisão por zero).' };
                    }
                    L[i][j] = (m[i][j] - sum) / L[j][j];
                }
            }
        }
        return { L };
    }

    // --- COORDENAÇÃO DE OPERAÇÃO ---
    function calculateMatrixOperation() {
        if (activeMode !== 'matrices') return;
        
        const matA = getMatrixValues('grid-matrix-a');
        const matB = getMatrixValues('grid-matrix-b');
        const paramEl = document.getElementById('matrixParamVal');
        const paramVal = paramEl ? parseFloat(paramEl.value) : 2;
        
        const card = document.getElementById('matrixResultCard');
        const labelEl = document.getElementById('matrixResultLabel');
        const valueEl = document.getElementById('matrixResultValue');
        const gridContainer = document.getElementById('matrixResultGridContainer');
        
        if (!card || !labelEl || !valueEl || !gridContainer) return;
        
        card.style.display = 'block';
        labelEl.innerText = '';
        valueEl.innerText = '';
        gridContainer.innerHTML = '';
        
        let resultStr = '';
        let formulaStr = '';

        try {
            switch (currentMatrixOp) {
                case 'det': {
                    formulaStr = `Det(A) para dimensão ${currentMatrixDim}x${currentMatrixDim}`;
                    const det = getDeterminant(matA);
                    resultStr = formatNumber(det);
                    valueEl.innerText = resultStr;
                    break;
                }
                case 'inv': {
                    formulaStr = `Inversa A⁻¹ para dimensão ${currentMatrixDim}x${currentMatrixDim}`;
                    const inv = getInverse(matA);
                    if (inv === null) {
                        valueEl.innerText = 'Matriz Singular (Det = 0)';
                    } else {
                        renderResultMatrix(inv);
                    }
                    break;
                }
                case 'trans': {
                    formulaStr = `Transposta Aᵀ para dimensão ${currentMatrixDim}x${currentMatrixDim}`;
                    const trans = getTranspose(matA);
                    renderResultMatrix(trans);
                    break;
                }
                case 'rank': {
                    formulaStr = `Posto de A (Rank) para dimensão ${currentMatrixDim}x${currentMatrixDim}`;
                    const rank = getRank(matA);
                    resultStr = rank.toString();
                    valueEl.innerText = resultStr;
                    break;
                }
                case 'tri': {
                    formulaStr = `Matriz Triangular (Upper) de A`;
                    const tri = getUpperTriangular(matA);
                    renderResultMatrix(tri);
                    break;
                }
                case 'diag': {
                    formulaStr = `Matriz Diagonal de A`;
                    const diag = getDiagonal(matA);
                    renderResultMatrix(diag);
                    break;
                }
                case 'scale': {
                    formulaStr = `A × ${paramVal} (Escalar)`;
                    const scaled = multiplyByScalar(matA, paramVal);
                    renderResultMatrix(scaled);
                    break;
                }
                case 'pow': {
                    const exponent = Math.floor(paramVal);
                    if (exponent < 0 || isNaN(exponent)) {
                        valueEl.innerText = 'Expoente deve ser inteiro ≥ 0';
                        break;
                    }
                    formulaStr = `A^${exponent} (Potência)`;
                    const powered = matrixPower(matA, exponent);
                    renderResultMatrix(powered);
                    break;
                }
                case 'lu': {
                    formulaStr = `Decomposição LU (A = L × U)`;
                    const lu = luDecomposition(matA);
                    if (lu === null) {
                        valueEl.innerText = 'Erro: Diagonal nula na decomposição.';
                    } else {
                        renderTwoResultMatrices('Matriz L (Lower)', lu.L, 'Matriz U (Upper)', lu.U);
                    }
                    break;
                }
                case 'chol': {
                    formulaStr = `Fatoração de Cholesky (A = L × Lᵀ)`;
                    const chol = choleskyDecomposition(matA);
                    if (chol.error) {
                        valueEl.innerText = chol.error;
                    } else {
                        renderTwoResultMatrices('Matriz L', chol.L, 'Matriz Lᵀ (Transposta)', getTranspose(chol.L));
                    }
                    break;
                }
                case 'add': {
                    formulaStr = `Soma (A + B)`;
                    const sum = addMatrices(matA, matB);
                    renderResultMatrix(sum);
                    break;
                }
                case 'mul': {
                    formulaStr = `Multiplicação (A × B)`;
                    const prod = multiplyMatrices(matA, matB);
                    renderResultMatrix(prod);
                    break;
                }
            }
        } catch (e) {
            valueEl.innerText = 'Erro de cálculo';
            console.error(e);
        }

        labelEl.innerText = formulaStr;
        if (displayFormula) displayFormula.innerText = formulaStr;
        if (displayResult) {
            if (valueEl.innerText) {
                displayResult.innerText = valueEl.innerText;
            } else {
                displayResult.innerText = 'Matriz';
            }
        }
    }

    function copyMatrixResultToClipboard() {
        const valEl = document.getElementById('matrixResultValue');
        const gridContainer = document.getElementById('matrixResultGridContainer');
        let textToCopy = '';

        if (valEl && valEl.innerText.trim() !== '' && valEl.innerText !== 'Matriz') {
            textToCopy = valEl.innerText;
        } else if (gridContainer) {
            const grids = gridContainer.querySelectorAll('.matrix-result-grid');
            const parts = [];
            grids.forEach((grid, idx) => {
                const cols = parseInt(grid.style.gridTemplateColumns.match(/\d+/)[0]);
                const cells = grid.querySelectorAll('.matrix-result-cell');
                
                const label = grid.previousElementSibling;
                if (label && label.classList.contains('panel-label')) {
                    parts.push(label.innerText);
                }

                let row = [];
                cells.forEach((cell, cellIdx) => {
                    row.push(cell.title || cell.innerText);
                    if ((cellIdx + 1) % cols === 0) {
                        parts.push(row.join('\t'));
                        row = [];
                    }
                });
                if (idx < grids.length - 1) parts.push('');
            });
            textToCopy = parts.join('\n');
        }

        if (textToCopy) {
            navigator.clipboard.writeText(textToCopy).then(() => {
                showToast('Copiado!');
            }).catch(err => {
                console.error('Erro ao copiar:', err);
            });
        }
    }

    function setupMatrixEvents() {
        const dimTabs = document.querySelectorAll('.tab-dim');
        dimTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                dimTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                currentMatrixDim = parseInt(tab.getAttribute('data-dim'));
                generateMatrixGrid('grid-matrix-a', currentMatrixDim);
                generateMatrixGrid('grid-matrix-b', currentMatrixDim);
                setupInputFocusTracking();
                calculateMatrixOperation();
            });
        });

        const modeTabs = document.querySelectorAll('.tab-mode');
        modeTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                modeTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                activeMatrixMode = tab.getAttribute('data-matmode');
                
                const blockB = document.getElementById('matrix-block-b');
                const binaryBtns = document.querySelectorAll('.matrix-ops-grid .binary-op');
                if (activeMatrixMode === 'binary') {
                    if (blockB) blockB.classList.remove('hidden');
                    binaryBtns.forEach(btn => btn.disabled = false);
                    currentMatrixOp = 'mul';
                } else {
                    if (blockB) blockB.classList.add('hidden');
                    binaryBtns.forEach(btn => btn.disabled = true);
                    if (currentMatrixOp === 'add' || currentMatrixOp === 'mul') {
                        currentMatrixOp = 'det';
                    }
                }
                calculateMatrixOperation();
            });
        });

        const opBtns = document.querySelectorAll('.matrix-op-btn');
        opBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const op = btn.getAttribute('data-op');
                currentMatrixOp = op;
                calculateMatrixOperation();
            });
        });

        const matrixParam = document.getElementById('matrixParamVal');
        if (matrixParam) {
            matrixParam.addEventListener('input', () => {
                calculateMatrixOperation();
            });
        }

        const btnCopyMatrix = document.getElementById('btnCopyMatrix');
        if (btnCopyMatrix) {
            btnCopyMatrix.addEventListener('click', copyMatrixResultToClipboard);
        }
    }

    // === CONTROLE DE VISIBILIDADE E SUB-MÓDULOS DE SAÚDE ===
    const healthSubMode = document.getElementById('healthSubMode');
    const wrapperHealthGlobal = document.getElementById('wrapper-health-global');
    const wrapperHealthWeight = document.getElementById('wrapper-health-weight');
    const wrapperHealthHeight = document.getElementById('wrapper-health-height');

    function updateHealthInputsVisibility() {
        if (!healthSubMode) return;
        const mode = healthSubMode.value;

        // Oculta todos os sub-panes
        document.querySelectorAll('#pane-health .sub-pane').forEach(p => p.classList.remove('active'));
        
        // Exibe o sub-pane ativo
        const activePane = document.getElementById(`sub-${mode}`);
        if (activePane) activePane.classList.add('active');

        // Mostra/Oculta Peso e Altura de forma inteligente
        if (mode === 'health-imc' || mode === 'health-tmb') {
            if (wrapperHealthGlobal) wrapperHealthGlobal.style.display = 'flex';
            if (wrapperHealthWeight) wrapperHealthWeight.style.display = 'block';
            if (wrapperHealthHeight) wrapperHealthHeight.style.display = 'block';
        } else if (mode === 'health-water') {
            if (wrapperHealthGlobal) wrapperHealthGlobal.style.display = 'flex';
            if (wrapperHealthWeight) wrapperHealthWeight.style.display = 'block';
            if (wrapperHealthHeight) wrapperHealthHeight.style.display = 'none';
        } else if (mode === 'health-ratio') {
            if (wrapperHealthGlobal) wrapperHealthGlobal.style.display = 'flex';
            if (wrapperHealthWeight) wrapperHealthWeight.style.display = 'none';
            if (wrapperHealthHeight) wrapperHealthHeight.style.display = 'block';
        } else {
            // BP, HR, Gestacional, Clínicos não usam peso/altura global
            if (wrapperHealthGlobal) wrapperHealthGlobal.style.display = 'none';
        }

        // Atualizações específicas das sub-seções dos novos modos
        if (mode === 'health-med') updateMedFields();
        else if (mode === 'health-body') updateBodyFields();
        else if (mode === 'health-cardio') updateCardioFields();
        else if (mode === 'health-nefro') updateNefroFields();
        else if (mode === 'health-resp') updateRespFields();
        else if (mode === 'health-ped') updatePedFields();
        else if (mode === 'health-obst') updateObstFields();
        else if (mode === 'health-lab') updateLabUnits();

        // Foca automaticamente no campo numérico padrão do módulo ativo para o teclado virtual
        updateKeyboardTargetForHealth();
    }

    function updateKeyboardTargetForHealth() {
        if (!healthSubMode) return;
        const mode = healthSubMode.value;
        const pad = document.querySelector('#pane-health .compact-pad');
        if (!pad) return;

        let targetId = 'healthWeight';
        if (mode === 'health-imc' || mode === 'health-tmb') {
            targetId = 'healthWeight';
        } else if (mode === 'health-water') {
            targetId = 'healthWeight';
        } else if (mode === 'health-ratio') {
            targetId = 'healthWaist';
        } else if (mode === 'health-bp') {
            targetId = 'healthBpSystolic';
        } else if (mode === 'health-hr') {
            targetId = 'healthHrAge';
        } else if (mode === 'health-med') {
            const medCalcType = document.getElementById('medCalcType');
            const type = medCalcType ? medCalcType.value : 'mgkg';
            if (type === 'mgkg') targetId = 'medDoseMgkg';
            else if (type === 'bsa') targetId = 'medDoseBsa';
            else if (type === 'mgml') targetId = 'medDoseMg';
            else if (type === 'gota') targetId = 'medGotaVol';
            else if (type === 'infusao') targetId = 'medInfVol';
            else if (type === 'bomba') targetId = 'medBombaDose';
        } else if (mode === 'health-body') {
            const bodyCalcType = document.getElementById('bodyCalcType');
            const type = bodyCalcType ? bodyCalcType.value : 'ideal';
            if (type === 'ideal' || type === 'bsa') targetId = 'bodyWeight';
            else if (type === 'fat') targetId = 'bodyWeight';
        } else if (mode === 'health-cardio') {
            const cardioCalcType = document.getElementById('cardioCalcType');
            const type = cardioCalcType ? cardioCalcType.value : 'pam';
            if (type === 'pam') targetId = 'cardioPas';
            else if (type === 'qtc') targetId = 'cardioQT';
            else targetId = '';
        } else if (mode === 'health-nefro') {
            const nefroCalcType = document.getElementById('nefroCalcType');
            const type = nefroCalcType ? nefroCalcType.value : 'cg';
            if (type === 'cg' || type === 'egfr') targetId = 'nefroCreat';
            else if (type === 'na-corr') targetId = 'nefroNaMedido';
            else if (type === 'osmo') targetId = 'nefroOsmoNa';
        } else if (mode === 'health-resp') {
            const respCalcType = document.getElementById('respCalcType');
            const type = respCalcType ? respCalcType.value : 'pf';
            if (type === 'pf') targetId = 'respPao2';
            else if (type === 'oi') targetId = 'respMap';
            else if (type === 'vm') targetId = 'respVt';
            else if (type === 'ibw') targetId = 'respHeight';
        } else if (mode === 'health-ped') {
            const pedCalcType = document.getElementById('pedCalcType');
            const type = pedCalcType ? pedCalcType.value : 'dose';
            if (type === 'dose') targetId = 'pedPeso';
            else if (type === 'pesoidade') targetId = 'pedIdadeAnos';
            else if (type === 'hidratacao') targetId = 'pedHollidayPeso';
        } else if (mode === 'health-obst') {
            const obstCalcType = document.getElementById('obstCalcType');
            const type = obstCalcType ? obstCalcType.value : 'dpp';
            if (type === 'ganho') targetId = 'obstPesoPreg';
            else targetId = '';
        } else if (mode === 'health-lab') {
            targetId = 'labValue';
        }

        if (targetId) {
            pad.setAttribute('data-target', targetId);
            const inputEl = document.getElementById(targetId);
            if (inputEl) inputEl.focus();
        }
    }

    if (healthSubMode) {
        healthSubMode.addEventListener('change', () => {
            updateHealthInputsVisibility();
            triggerPaneCalculations('health');
        });
    }

    // === TMB (TAXA METABÓLICA BASAL) ===
    const healthAge = document.getElementById('healthAge');
    const healthGender = document.getElementById('healthGender');
    const healthActivity = document.getElementById('healthActivity');
    const btnTbcCalc = document.getElementById('btnTbcCalc');

    function calculateTMB() {
        if (!healthWeight || !healthHeight || !healthAge || !healthGender || !healthActivity) return;
        
        const weight = parseFloat(healthWeight.value);
        const height = parseFloat(healthHeight.value);
        const age = parseFloat(healthAge.value);
        const gender = healthGender.value;
        const activity = parseFloat(healthActivity.value);
        
        const card = document.getElementById('tmbResultCard');
        const tmbValEl = document.getElementById('tmbValue');
        const tdeeValEl = document.getElementById('tdeeValue');
        const loseWeightEl = document.getElementById('tmbLoseWeight');
        const gainWeightEl = document.getElementById('tmbGainWeight');
        
        if (isNaN(weight) || isNaN(height) || isNaN(age) || age <= 0) {
            if (card) card.style.display = 'none';
            return;
        }

        // Fórmula de Mifflin-St Jeor
        let tmb = 0;
        if (gender === 'male') {
            tmb = 10 * weight + 6.25 * height - 5 * age + 5;
        } else {
            tmb = 10 * weight + 6.25 * height - 5 * age - 161;
        }

        const tdee = tmb * activity;
        const loseCal = tdee - 500;
        const gainCal = tdee + 500;

        const locale = numberFormat === 'BR' ? 'pt-BR' : 'en-US';
        const fmtInt = new Intl.NumberFormat(locale, { maximumFractionDigits: 0 });

        if (card && tmbValEl && tdeeValEl && loseWeightEl && gainWeightEl) {
            card.style.display = 'block';
            tmbValEl.innerText = `${fmtInt.format(Math.round(tmb))} kcal`;
            tdeeValEl.innerText = `${fmtInt.format(Math.round(tdee))} kcal`;
            loseWeightEl.innerText = `${fmtInt.format(Math.round(Math.max(1200, loseCal)))} kcal`;
            gainWeightEl.innerText = `${fmtInt.format(Math.round(gainCal))} kcal`;
        }

        if (displayFormula) displayFormula.innerText = `Necessidade Calórica Diária (TMB)`;
        if (displayResult) displayResult.innerText = `${fmtInt.format(Math.round(tdee))} kcal`;
    }

    if (btnTbcCalc) btnTbcCalc.addEventListener('click', calculateTMB);
    if (healthAge) healthAge.addEventListener('input', calculateTMB);
    if (healthGender) healthGender.addEventListener('change', calculateTMB);
    if (healthActivity) healthActivity.addEventListener('change', calculateTMB);


    // === PRESSÃO ARTERIAL ===
    const btnBpCalc = document.getElementById('btnBpCalc');
    const healthBpSystolic = document.getElementById('healthBpSystolic');
    const healthBpDiastolic = document.getElementById('healthBpDiastolic');

    function calculateBP() {
        if (!healthBpSystolic || !healthBpDiastolic) return;
        const sys = parseFloat(healthBpSystolic.value);
        const dia = parseFloat(healthBpDiastolic.value);
        const card = document.getElementById('bpResultCard');
        const valueEl = document.getElementById('bpValue');
        const statusEl = document.getElementById('bpStatus');
        const recEl = document.getElementById('bpRecommendation');

        if (isNaN(sys) || isNaN(dia) || sys <= 0 || dia <= 0) {
            if (card) card.style.display = 'none';
            return;
        }

        if (card && valueEl && statusEl && recEl) {
            card.style.display = 'block';
            valueEl.innerText = `${sys}/${dia} mmHg`;

            let statusText = '';
            let recText = '';
            let colorBg = '';
            let colorText = '';

            // Classificação da Pressão Arterial (AHA/SBC)
            if (sys < 120 && dia < 80) {
                statusText = 'Normal';
                recText = 'Sua pressão está na faixa ideal. Continue com bons hábitos!';
                colorBg = 'rgba(16, 185, 129, 0.15)';
                colorText = '#10B981';
            } else if (sys >= 120 && sys <= 129 && dia < 80) {
                statusText = 'Elevada';
                recText = 'Pressão arterial levemente elevada. Cuide de sua alimentação e pratique atividades físicas.';
                colorBg = 'rgba(245, 158, 11, 0.15)';
                colorText = '#F59E0B';
            } else if ((sys >= 130 && sys <= 139) || (dia >= 80 && dia <= 89)) {
                statusText = 'Hipertensão Estágio 1';
                recText = 'Indicativo de hipertensão leve. Procure um médico para orientação preventiva.';
                colorBg = 'rgba(249, 115, 22, 0.15)';
                colorText = '#F97316';
            } else if (sys >= 140 || dia >= 90) {
                if (sys > 180 || dia > 120) {
                    statusText = 'Crise Hipertensiva';
                    recText = 'ALERTA: Pressão severamente alta! Em caso de dor no peito, falta de ar ou visão borrada, procure socorro médico urgente.';
                    colorBg = 'rgba(239, 68, 68, 0.25)';
                    colorText = '#EF4444';
                    card.classList.add('pulse-alert');
                } else {
                    statusText = 'Hipertensão Estágio 2';
                    recText = 'Indicativo de hipertensão moderada/grave. Recomenda-se acompanhamento médico frequente.';
                    colorBg = 'rgba(239, 68, 68, 0.15)';
                    colorText = '#EF4444';
                    card.classList.remove('pulse-alert');
                }
            }

            if (sys <= 180 && dia <= 120) {
                card.classList.remove('pulse-alert');
            }

            statusEl.innerText = statusText;
            statusEl.style.backgroundColor = colorBg;
            statusEl.style.color = colorText;
            recEl.innerText = recText;
        }

        if (displayFormula) displayFormula.innerText = 'Classificação da Pressão Arterial';
        if (displayResult) displayResult.innerText = `${sys}/${dia} mmHg`;
    }

    if (btnBpCalc) btnBpCalc.addEventListener('click', calculateBP);
    if (healthBpSystolic) healthBpSystolic.addEventListener('input', calculateBP);
    if (healthBpDiastolic) healthBpDiastolic.addEventListener('input', calculateBP);


    // === FRENQUÊNCIA CARDÍACA ALVO ===
    const btnHrCalc = document.getElementById('btnHrCalc');
    const healthHrAge = document.getElementById('healthHrAge');
    const healthHrResting = document.getElementById('healthHrResting');

    function calculateHR() {
        if (!healthHrAge || !healthHrResting) return;
        const age = parseFloat(healthHrAge.value);
        const rest = parseFloat(healthHrResting.value);
        const card = document.getElementById('hrResultCard');

        if (isNaN(age) || isNaN(rest) || age <= 0 || rest <= 0) {
            if (card) card.style.display = 'none';
            return;
        }

        const fcm = 220 - age;
        const fcres = fcm - rest;

        if (card) {
            card.style.display = 'block';
            const hrMaxVal = document.getElementById('hrMaxVal');
            if (hrMaxVal) hrMaxVal.innerText = `${fcm} bpm`;

            const getZoneString = (minPct, maxPct) => {
                const min = Math.round(rest + (fcres * minPct));
                const max = Math.round(rest + (fcres * maxPct));
                return `${min} - ${max} bpm`;
            };

            document.getElementById('hrZone1').innerText = getZoneString(0.5, 0.6);
            document.getElementById('hrZone2').innerText = getZoneString(0.6, 0.7);
            document.getElementById('hrZone3').innerText = getZoneString(0.7, 0.8);
            document.getElementById('hrZone4').innerText = getZoneString(0.8, 0.9);
            document.getElementById('hrZone5').innerText = getZoneString(0.9, 1.0);
        }

        if (displayFormula) displayFormula.innerText = `Zonas Alvo de Treinamento (Karvonen)`;
        if (displayResult) displayResult.innerText = `FCM: ${fcm} bpm`;
    }

    if (btnHrCalc) btnHrCalc.addEventListener('click', calculateHR);
    if (healthHrAge) healthHrAge.addEventListener('input', calculateHR);
    if (healthHrResting) healthHrResting.addEventListener('input', calculateHR);


    // === RELAÇÕES CORPORAIS ===
    const btnRatioCalc = document.getElementById('btnRatioCalc');
    const healthWaist = document.getElementById('healthWaist');
    const healthHip = document.getElementById('healthHip');
    const healthRatioGender = document.getElementById('healthRatioGender');

    function calculateRatio() {
        if (!healthWaist || !healthHip || !healthRatioGender || !healthHeight) return;
        const waist = parseFloat(healthWaist.value);
        const hip = parseFloat(healthHip.value);
        const height = parseFloat(healthHeight.value);
        const gender = healthRatioGender.value;
        const card = document.getElementById('ratioResultCard');

        if (isNaN(waist) || isNaN(hip) || isNaN(height) || waist <= 0 || hip <= 0 || height <= 0) {
            if (card) card.style.display = 'none';
            return;
        }

        const rcq = waist / hip;
        const rce = waist / height;

        const locale = numberFormat === 'BR' ? 'pt-BR' : 'en-US';
        const fmtTwoDec = new Intl.NumberFormat(locale, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        if (card) {
            card.style.display = 'block';
            document.getElementById('rcqValue').innerText = fmtTwoDec.format(rcq);
            document.getElementById('rceValue').innerText = fmtTwoDec.format(rce);

            // Classificação RCQ
            let rcqStatus = '';
            let rcqBg = '';
            let rcqText = '';
            if (gender === 'male') {
                if (rcq < 0.90) {
                    rcqStatus = 'Normal';
                    rcqBg = 'rgba(16, 185, 129, 0.15)';
                    rcqText = '#10B981';
                } else if (rcq >= 0.90 && rcq <= 0.95) {
                    rcqStatus = 'Moderado';
                    rcqBg = 'rgba(245, 158, 11, 0.15)';
                    rcqText = '#F59E0B';
                } else {
                    rcqStatus = 'Alto Risco';
                    rcqBg = 'rgba(239, 68, 68, 0.15)';
                    rcqText = '#EF4444';
                }
            } else {
                if (rcq < 0.80) {
                    rcqStatus = 'Normal';
                    rcqBg = 'rgba(16, 185, 129, 0.15)';
                    rcqText = '#10B981';
                } else if (rcq >= 0.80 && rcq <= 0.85) {
                    rcqStatus = 'Moderado';
                    rcqBg = 'rgba(245, 158, 11, 0.15)';
                    rcqText = '#F59E0B';
                } else {
                    rcqStatus = 'Alto Risco';
                    rcqBg = 'rgba(239, 68, 68, 0.15)';
                    rcqText = '#EF4444';
                }
            }

            const rcqEl = document.getElementById('rcqStatus');
            rcqEl.innerText = rcqStatus;
            rcqEl.style.backgroundColor = rcqBg;
            rcqEl.style.color = rcqText;

            // Classificação RCE
            let rceStatus = '';
            let rceBg = '';
            let rceText = '';
            if (rce <= 0.50) {
                rceStatus = 'Normal';
                rceBg = 'rgba(16, 185, 129, 0.15)';
                rceText = '#10B981';
            } else {
                rceStatus = 'Alto Risco';
                rceBg = 'rgba(239, 68, 68, 0.15)';
                rceText = '#EF4444';
            }

            const rceEl = document.getElementById('rceStatus');
            rceEl.innerText = rceStatus;
            rceEl.style.backgroundColor = rceBg;
            rceEl.style.color = rceText;

            // Recomendação geral
            let rec = '';
            if (rcqStatus === 'Alto Risco' || rceStatus === 'Alto Risco') {
                rec = 'Atenção: Razão cintura-estatura ou cintura-quadril acima do recomendado. Indica acúmulo de gordura abdominal.';
            } else {
                rec = 'Excelente! Suas relações corporais indicam bom equilíbrio e menor risco de doenças metabólicas.';
            }
            document.getElementById('ratioRecommendation').innerText = rec;
        }

        if (displayFormula) displayFormula.innerText = 'Relações Corporais (Cintura/Quadril/Altura)';
        if (displayResult) displayResult.innerText = `RCQ: ${fmtTwoDec.format(rcq)}`;
    }

    if (btnRatioCalc) btnRatioCalc.addEventListener('click', calculateRatio);
    if (healthWaist) healthWaist.addEventListener('input', calculateRatio);
    if (healthHip) healthHip.addEventListener('input', calculateRatio);
    if (healthRatioGender) healthRatioGender.addEventListener('change', calculateRatio);


    // === HIDRATAÇÃO AVANÇADA ===
    const btnWaterCalc = document.getElementById('btnWaterCalc');
    const healthWaterExercise = document.getElementById('healthWaterExercise');
    const healthWaterClimate = document.getElementById('healthWaterClimate');

    function calculateWater() {
        if (!healthWeight || !healthWaterExercise || !healthWaterClimate) return;
        const weight = parseFloat(healthWeight.value);
        const exercise = parseFloat(healthWaterExercise.value);
        const climate = healthWaterClimate.value;
        const card = document.getElementById('waterResultCard');

        if (isNaN(weight) || weight <= 0) {
            if (card) card.style.display = 'none';
            return;
        }

        const baseIntake = weight * 35; // 35 ml/kg
        const exerciseIntake = (exercise / 30) * 250; // +250ml a cada 30 min
        
        let climateIntake = 0;
        if (climate === 'moderate') climateIntake = 250;
        else if (climate === 'hot') climateIntake = 500;

        const totalMl = baseIntake + exerciseIntake + climateIntake;
        const totalL = totalMl / 1000;
        const glasses = Math.round(totalMl / 250);

        const locale = numberFormat === 'BR' ? 'pt-BR' : 'en-US';
        const fmtOneDec = new Intl.NumberFormat(locale, { minimumFractionDigits: 1, maximumFractionDigits: 1 });

        if (card) {
            card.style.display = 'block';
            document.getElementById('waterTotalVal').innerText = `${fmtOneDec.format(totalL)} L`;
            document.getElementById('waterBreakdown').innerText = `Equivale a aproximadamente ${glasses} copos de 250ml`;
            document.getElementById('waterBasePart').innerText = `${fmtOneDec.format(baseIntake / 1000)}L`;
            document.getElementById('waterExtraPart').innerText = `${fmtOneDec.format((exerciseIntake + climateIntake) / 1000)}L`;
        }

        if (displayFormula) displayFormula.innerText = 'Água Diária Recomendada';
        if (displayResult) displayResult.innerText = `${fmtOneDec.format(totalL)} L`;
    }

    if (btnWaterCalc) btnWaterCalc.addEventListener('click', calculateWater);
    if (healthWaterExercise) healthWaterExercise.addEventListener('change', calculateWater);
    if (healthWaterClimate) healthWaterClimate.addEventListener('change', calculateWater);


    // === CALCULADORA GESTACIONAL (DPP) ===
    const btnPregCalc = document.getElementById('btnPregCalc');
    const healthPregDum = document.getElementById('healthPregDum');

    function calculatePreg() {
        if (!healthPregDum) return;
        const dumVal = healthPregDum.value;
        const card = document.getElementById('pregResultCard');

        if (!dumVal) {
            if (card) card.style.display = 'none';
            return;
        }

        const dum = new Date(dumVal + 'T00:00:00');
        const today = new Date();
        today.setHours(0,0,0,0);

        // Regra de Naegele / 280 dias
        const dpp = new Date(dum.getTime() + (280 * 24 * 60 * 60 * 1000));
        
        const diffMs = today - dum;
        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

        if (diffDays < 0 || diffDays > 300) {
            if (card) {
                card.style.display = 'block';
                document.getElementById('pregAgeVal').innerText = 'DUM inválida ou muito antiga';
                document.getElementById('pregDppVal').innerText = '-';
                document.getElementById('pregTrimesterVal').innerText = '-';
                document.getElementById('pregProgressBar').style.width = '0%';
            }
            return;
        }

        const weeks = Math.floor(diffDays / 7);
        const remDays = diffDays % 7;

        let trimesterText = '';
        if (weeks < 14) trimesterText = '1º Trimestre (Desenvolvimento Inicial)';
        else if (weeks < 28) trimesterText = '2º Trimestre (Crescimento e Movimentos)';
        else trimesterText = '3º Trimestre (Preparação para o Parto)';

        const progress = Math.min(100, (diffDays / 280) * 100);

        const dppD = String(dpp.getDate()).padStart(2, '0');
        const dppM = String(dpp.getMonth() + 1).padStart(2, '0');
        const dppY = dpp.getFullYear();
        const formattedDpp = `${dppD}/${dppM}/${dppY}`;

        if (card) {
            card.style.display = 'block';
            document.getElementById('pregAgeVal').innerText = `${weeks} semanas e ${remDays} dias`;
            document.getElementById('pregDppVal').innerText = formattedDpp;
            document.getElementById('pregTrimesterVal').innerText = trimesterText;
            document.getElementById('pregProgressBar').style.width = `${progress}%`;
        }

        if (displayFormula) displayFormula.innerText = 'Data Provável do Parto (DPP)';
        if (displayResult) displayResult.innerText = formattedDpp;
    }

    if (btnPregCalc) btnPregCalc.addEventListener('click', calculatePreg);
    if (healthPregDum) healthPregDum.addEventListener('change', calculatePreg);

    // === NOVAS FUNÇÕES AUXILIARES DE CAMPOS CLÍNICOS ===
    function updateMedFields() {
        const medCalcType = document.getElementById('medCalcType');
        if (!medCalcType) return;
        const val = medCalcType.value;
        const subMgkg = document.getElementById('med-sub-mgkg');
        const subBsa = document.getElementById('med-sub-bsa');
        const subMgml = document.getElementById('med-sub-mgml');
        const subGota = document.getElementById('med-sub-gota');
        const subInf = document.getElementById('med-sub-infusao');
        const subBomba = document.getElementById('med-sub-bomba');
        
        if (subMgkg) subMgkg.style.display = val === 'mgkg' ? 'block' : 'none';
        if (subBsa) subBsa.style.display = val === 'bsa' ? 'block' : 'none';
        if (subMgml) subMgml.style.display = val === 'mgml' ? 'block' : 'none';
        if (subGota) subGota.style.display = val === 'gota' ? 'block' : 'none';
        if (subInf) subInf.style.display = val === 'infusao' ? 'block' : 'none';
        if (subBomba) subBomba.style.display = val === 'bomba' ? 'block' : 'none';
        updateKeyboardTargetForHealth();
    }

    function updateBodyFields() {
        const bodyCalcType = document.getElementById('bodyCalcType');
        if (!bodyCalcType) return;
        const val = bodyCalcType.value;
        const bodyGender = document.getElementById('bodyGender');
        const gender = bodyGender ? bodyGender.value : 'male';
        
        const fatFields = document.getElementById('bodyFatFields');
        const hipRow = document.getElementById('bodyFatHipRow');
        const hipWrap = hipRow ? hipRow.querySelector('.input-wrap-half:first-child') : null;
        const genderWrap = hipRow ? hipRow.querySelector('.input-wrap-half:last-child') : null;
        
        const weightInput = document.getElementById('bodyWeight');
        const heightInput = document.getElementById('bodyHeight');
        const weightRow = weightInput ? weightInput.closest('.input-row-flex') : null;
        
        if (val === 'ideal') {
            if (weightRow) weightRow.style.display = 'flex';
            if (weightInput) weightInput.closest('.input-wrap-half').style.display = 'block';
            if (heightInput) heightInput.closest('.input-wrap-half').style.display = 'block';
            if (fatFields) fatFields.style.display = 'none';
            if (hipRow) {
                hipRow.style.display = 'flex';
                if (hipWrap) hipWrap.style.display = 'none'; // esconde Quadril
                if (genderWrap) {
                    genderWrap.style.display = 'block';
                    genderWrap.style.width = '100%';
                }
            }
        } else if (val === 'bsa') {
            if (weightRow) weightRow.style.display = 'flex';
            if (weightInput) weightInput.closest('.input-wrap-half').style.display = 'block';
            if (heightInput) heightInput.closest('.input-wrap-half').style.display = 'block';
            if (fatFields) fatFields.style.display = 'none';
            if (hipRow) hipRow.style.display = 'none';
        } else if (val === 'fat') {
            if (weightRow) weightRow.style.display = 'flex';
            if (weightInput) weightInput.closest('.input-wrap-half').style.display = 'block';
            if (heightInput) heightInput.closest('.input-wrap-half').style.display = 'block';
            if (fatFields) fatFields.style.display = 'flex';
            if (hipRow) {
                hipRow.style.display = 'flex';
                if (genderWrap) {
                    genderWrap.style.display = 'block';
                    genderWrap.style.width = '50%';
                }
                if (hipWrap) {
                    hipWrap.style.display = gender === 'female' ? 'block' : 'none';
                    if (gender === 'male') {
                        if (genderWrap) genderWrap.style.width = '100%';
                    } else {
                        if (genderWrap) genderWrap.style.width = '50%';
                    }
                }
            }
        }
        updateKeyboardTargetForHealth();
    }

    function updateCardioFields() {
        const cardioCalcType = document.getElementById('cardioCalcType');
        if (!cardioCalcType) return;
        const val = cardioCalcType.value;
        const subPam = document.getElementById('cardio-sub-pam');
        const subChads = document.getElementById('cardio-sub-chads');
        const subHasbled = document.getElementById('cardio-sub-hasbled');
        const subQtc = document.getElementById('cardio-sub-qtc');
        
        if (subPam) subPam.style.display = val === 'pam' ? 'block' : 'none';
        if (subChads) subChads.style.display = val === 'chads' ? 'block' : 'none';
        if (subHasbled) subHasbled.style.display = val === 'hasbled' ? 'block' : 'none';
        if (subQtc) subQtc.style.display = val === 'qtc' ? 'block' : 'none';
        updateKeyboardTargetForHealth();
    }

    function updateNefroFields() {
        const nefroCalcType = document.getElementById('nefroCalcType');
        if (!nefroCalcType) return;
        const val = nefroCalcType.value;
        
        const creatInput = document.getElementById('nefroCreat');
        const creatAgeRow = creatInput ? creatInput.closest('.input-row-flex') : null;
        const weightGenderRow = document.getElementById('nefroWeightGenderRow');
        const weightWrap = weightGenderRow ? weightGenderRow.querySelector('.input-wrap-half:first-child') : null;
        const genderWrap = weightGenderRow ? weightGenderRow.querySelector('.input-wrap-half:last-child') : null;
        
        const naFields = document.getElementById('nefroNaCorrFields');
        const osmoFields = document.getElementById('nefroOsmoFields');
        
        if (val === 'cg') {
            if (creatAgeRow) creatAgeRow.style.display = 'flex';
            if (weightGenderRow) {
                weightGenderRow.style.display = 'flex';
                if (weightWrap) {
                    weightWrap.style.display = 'block';
                    weightWrap.style.width = '50%';
                }
                if (genderWrap) {
                    genderWrap.style.display = 'block';
                    genderWrap.style.width = '50%';
                }
            }
            if (naFields) naFields.style.display = 'none';
            if (osmoFields) osmoFields.style.display = 'none';
        } else if (val === 'egfr') {
            if (creatAgeRow) creatAgeRow.style.display = 'flex';
            if (weightGenderRow) {
                weightGenderRow.style.display = 'flex';
                if (weightWrap) weightWrap.style.display = 'none';
                if (genderWrap) {
                    genderWrap.style.display = 'block';
                    genderWrap.style.width = '100%';
                }
            }
            if (naFields) naFields.style.display = 'none';
            if (osmoFields) osmoFields.style.display = 'none';
        } else if (val === 'na-corr') {
            if (creatAgeRow) creatAgeRow.style.display = 'none';
            if (weightGenderRow) weightGenderRow.style.display = 'none';
            if (naFields) naFields.style.display = 'block';
            if (osmoFields) osmoFields.style.display = 'none';
        } else if (val === 'osmo') {
            if (creatAgeRow) creatAgeRow.style.display = 'none';
            if (weightGenderRow) weightGenderRow.style.display = 'none';
            if (naFields) naFields.style.display = 'none';
            if (osmoFields) osmoFields.style.display = 'block';
        }
        updateKeyboardTargetForHealth();
    }

    function updateRespFields() {
        const respCalcType = document.getElementById('respCalcType');
        if (!respCalcType) return;
        const val = respCalcType.value;
        const subPf = document.getElementById('resp-sub-pf');
        const subOi = document.getElementById('resp-sub-oi');
        const subVm = document.getElementById('resp-sub-vm');
        const subIbw = document.getElementById('resp-sub-ibw');
        
        if (subPf) subPf.style.display = val === 'pf' ? 'block' : 'none';
        if (subOi) subOi.style.display = val === 'oi' ? 'block' : 'none';
        if (subVm) subVm.style.display = val === 'vm' ? 'block' : 'none';
        if (subIbw) subIbw.style.display = val === 'ibw' ? 'block' : 'none';
        updateKeyboardTargetForHealth();
    }

    function updatePedFields() {
        const pedCalcType = document.getElementById('pedCalcType');
        if (!pedCalcType) return;
        const val = pedCalcType.value;
        const subDose = document.getElementById('ped-sub-dose');
        const subPeso = document.getElementById('ped-sub-pesoidade');
        const subHidr = document.getElementById('ped-sub-hidratacao');
        
        if (subDose) subDose.style.display = val === 'dose' ? 'block' : 'none';
        if (subPeso) subPeso.style.display = val === 'pesoidade' ? 'block' : 'none';
        if (subHidr) subHidr.style.display = val === 'hidratacao' ? 'block' : 'none';
        updateKeyboardTargetForHealth();
    }

    function updateObstFields() {
        const obstCalcType = document.getElementById('obstCalcType');
        if (!obstCalcType) return;
        const val = obstCalcType.value;
        const dumRow = document.getElementById('obstDumRow');
        const ganhoFields = document.getElementById('obstGanhoFields');
        
        if (dumRow) dumRow.style.display = (val === 'dpp' || val === 'ig') ? 'block' : 'none';
        if (ganhoFields) ganhoFields.style.display = val === 'ganho' ? 'block' : 'none';
        updateKeyboardTargetForHealth();
    }

    function updateLabUnits() {
        const labCalcType = document.getElementById('labCalcType');
        const labFromUnit = document.getElementById('labFromUnit');
        if (!labCalcType || !labFromUnit) return;
        const type = labCalcType.value;
        
        labFromUnit.innerHTML = '';
        if (type === 'glicose' || type === 'colesterol' || type === 'ureia') {
            labFromUnit.innerHTML = `
                <option value="a">mg/dL → mmol/L</option>
                <option value="b">mmol/L → mg/dL</option>
            `;
        } else if (type === 'hb') {
            labFromUnit.innerHTML = `
                <option value="a">g/dL → mmol/L</option>
                <option value="b">mmol/L → g/dL</option>
            `;
        } else if (type === 'creat') {
            labFromUnit.innerHTML = `
                <option value="a">mg/dL → μmol/L</option>
                <option value="b">μmol/L → mg/dL</option>
            `;
        } else if (type === 'na') {
            labFromUnit.innerHTML = `
                <option value="a">mEq/L → mmol/L</option>
                <option value="b">mmol/L → mEq/L</option>
            `;
        }
        updateKeyboardTargetForHealth();
    }

    // === CÁLCULOS CLÍNICOS ADICIONAIS ===

    // 1. Dosagem de Medicamentos
    function calculateMed() {
        const medCalcType = document.getElementById('medCalcType');
        if (!medCalcType) return;
        const val = medCalcType.value;
        const card = document.getElementById('medResultCard');
        const label = document.getElementById('medResultLabel');
        const valueEl = document.getElementById('medResultValue');
        
        if (!card || !valueEl || !label) return;

        let resultText = '—';
        let labelText = 'Resultado';

        const locale = numberFormat === 'BR' ? 'pt-BR' : 'en-US';
        const fmtTwoDec = new Intl.NumberFormat(locale, { minimumFractionDigits: 0, maximumFractionDigits: 2 });

        if (val === 'mgkg') {
            const dose = parseFloat(document.getElementById('medDoseMgkg').value);
            const peso = parseFloat(document.getElementById('medWeightMgkg').value);
            labelText = 'Dose Calculada';
            if (!isNaN(dose) && !isNaN(peso) && peso > 0) {
                resultText = `${fmtTwoDec.format(dose * peso)} mg`;
            }
        } else if (val === 'bsa') {
            const dose = parseFloat(document.getElementById('medDoseBsa').value);
            const bsa = parseFloat(document.getElementById('medBsaVal').value);
            labelText = 'Dose Calculada';
            if (!isNaN(dose) && !isNaN(bsa) && bsa > 0) {
                resultText = `${fmtTwoDec.format(dose * bsa)} mg`;
            }
        } else if (val === 'mgml') {
            const doseMg = parseFloat(document.getElementById('medDoseMg').value);
            const conc = parseFloat(document.getElementById('medConc').value);
            labelText = 'Volume a Administrar';
            if (!isNaN(doseMg) && !isNaN(conc) && conc > 0) {
                resultText = `${fmtTwoDec.format(doseMg / conc)} mL`;
            }
        } else if (val === 'gota') {
            const vol = parseFloat(document.getElementById('medGotaVol').value);
            const time = parseFloat(document.getElementById('medGotaTime').value);
            const equipo = parseFloat(document.getElementById('medGotaEquipo').value);
            labelText = 'Velocidade de Gotejamento';
            if (!isNaN(vol) && !isNaN(time) && time > 0) {
                const gtsMin = (vol * equipo) / time;
                resultText = `${fmtTwoDec.format(gtsMin)} gts/min`;
            }
        } else if (val === 'infusao') {
            const vol = parseFloat(document.getElementById('medInfVol').value);
            const time = parseFloat(document.getElementById('medInfTime').value);
            labelText = 'Taxa de Infusão';
            if (!isNaN(vol) && !isNaN(time) && time > 0) {
                resultText = `${fmtTwoDec.format(vol / time)} mL/h`;
            }
        } else if (val === 'bomba') {
            const dose = parseFloat(document.getElementById('medBombaDose').value);
            const peso = parseFloat(document.getElementById('medBombaPeso').value);
            const conc = parseFloat(document.getElementById('medBombaConc').value);
            labelText = 'Velocidade da Bomba';
            if (!isNaN(dose) && !isNaN(peso) && !isNaN(conc) && conc > 0) {
                const rate = (dose * peso * 60) / conc;
                resultText = `${fmtTwoDec.format(rate)} mL/h`;
            }
        }

        if (resultText !== '—') {
            card.style.display = 'block';
            label.innerText = labelText;
            valueEl.innerText = resultText;
        } else {
            card.style.display = 'none';
        }

        if (displayFormula) displayFormula.innerText = 'Cálculo de Dosagem Médica';
        if (displayResult) displayResult.innerText = resultText;
    }

    // 2. Índices Corporais Avançados
    function calculateBody() {
        const bodyCalcType = document.getElementById('bodyCalcType');
        if (!bodyCalcType) return;
        const val = bodyCalcType.value;
        const card = document.getElementById('bodyResultCard');
        const label = document.getElementById('bodyResultLabel');
        const valueEl = document.getElementById('bodyResultValue');
        const subEl = document.getElementById('bodyResultSub');
        
        if (!card || !valueEl || !label || !subEl) return;

        let resultText = '—';
        let labelText = 'Resultado';
        let subText = '';

        const locale = numberFormat === 'BR' ? 'pt-BR' : 'en-US';
        const fmtTwoDec = new Intl.NumberFormat(locale, { minimumFractionDigits: 1, maximumFractionDigits: 2 });

        if (val === 'ideal') {
            const h = parseFloat(document.getElementById('bodyHeight').value);
            const gender = document.getElementById('bodyGender').value;
            labelText = 'Peso Ideal (Devine)';
            if (!isNaN(h) && h > 0) {
                const inchesAbove60 = (h / 2.54) - 60;
                let devine = 0;
                let robinson = 0;
                if (gender === 'male') {
                    devine = 50.0 + (inchesAbove60 > 0 ? 2.3 * inchesAbove60 : 0);
                    robinson = 52.0 + (inchesAbove60 > 0 ? 1.9 * inchesAbove60 : 0);
                } else {
                    devine = 45.5 + (inchesAbove60 > 0 ? 2.3 * inchesAbove60 : 0);
                    robinson = 49.0 + (inchesAbove60 > 0 ? 1.7 * inchesAbove60 : 0);
                }
                resultText = `${fmtTwoDec.format(devine)} kg`;
                subText = `Fórmula de Robinson: ${fmtTwoDec.format(robinson)} kg`;
            }
        } else if (val === 'bsa') {
            const w = parseFloat(document.getElementById('bodyWeight').value);
            const h = parseFloat(document.getElementById('bodyHeight').value);
            labelText = 'Superfície Corporal BSA';
            if (!isNaN(w) && !isNaN(h) && w > 0 && h > 0) {
                // Mosteller
                const bsaMosteller = Math.sqrt((w * h) / 3600);
                // DuBois
                const bsaDuBois = 0.007184 * Math.pow(w, 0.425) * Math.pow(h, 0.725);
                resultText = `${fmtTwoDec.format(bsaMosteller)} m²`;
                subText = `DuBois: ${fmtTwoDec.format(bsaDuBois)} m²`;
            }
        } else if (val === 'fat') {
            const w = parseFloat(document.getElementById('bodyWeight').value);
            const h = parseFloat(document.getElementById('bodyHeight').value);
            const waist = parseFloat(document.getElementById('bodyWaist').value);
            const neck = parseFloat(document.getElementById('bodyNeck').value);
            const hip = parseFloat(document.getElementById('bodyHip').value);
            const gender = document.getElementById('bodyGender').value;
            labelText = '% Gordura Corporal (Navy)';

            if (!isNaN(w) && !isNaN(h) && !isNaN(waist) && !isNaN(neck) && h > 0) {
                const w_in = waist / 2.54;
                const n_in = neck / 2.54;
                const h_in = h / 2.54;

                let bfp = 0;
                let valid = true;

                if (gender === 'male') {
                    if (w_in > n_in) {
                        bfp = 86.010 * Math.log10(w_in - n_in) - 70.041 * Math.log10(h_in) + 36.76;
                    } else {
                        valid = false;
                    }
                } else {
                    if (!isNaN(hip)) {
                        const hip_in = hip / 2.54;
                        if ((w_in + hip_in) > n_in) {
                            bfp = 163.205 * Math.log10(w_in + hip_in - n_in) - 97.684 * Math.log10(h_in) - 78.387;
                        } else {
                            valid = false;
                        }
                    } else {
                        valid = false;
                    }
                }

                if (valid && bfp > 0) {
                    resultText = `${fmtTwoDec.format(bfp)}%`;
                    
                    // Categorias
                    let cat = '';
                    if (gender === 'male') {
                        if (bfp < 6) cat = 'Gordura Essencial';
                        else if (bfp <= 13) cat = 'Atleta';
                        else if (bfp <= 17) cat = 'Fitness';
                        else if (bfp <= 24) cat = 'Aceitável';
                        else cat = 'Obesidade';
                    } else {
                        if (bfp < 14) cat = 'Gordura Essencial';
                        else if (bfp <= 20) cat = 'Atleta';
                        else if (bfp <= 24) cat = 'Fitness';
                        else if (bfp <= 31) cat = 'Aceitável';
                        else cat = 'Obesidade';
                    }
                    subText = `Classificação: ${cat}`;
                }
            }
        }

        if (resultText !== '—') {
            card.style.display = 'block';
            label.innerText = labelText;
            valueEl.innerText = resultText;
            subEl.innerText = subText;
        } else {
            card.style.display = 'none';
        }

        if (displayFormula) displayFormula.innerText = 'Índices Corporais Avançados';
        if (displayResult) displayResult.innerText = resultText;
    }

    // 3. Cardiologia
    function calculateCardio() {
        const cardioCalcType = document.getElementById('cardioCalcType');
        if (!cardioCalcType) return;
        const val = cardioCalcType.value;
        const card = document.getElementById('cardioResultCard');
        const label = document.getElementById('cardioResultLabel');
        const valueEl = document.getElementById('cardioResultValue');
        const subEl = document.getElementById('cardioResultSub');
        
        if (!card || !valueEl || !label || !subEl) return;

        let resultText = '—';
        let labelText = 'Resultado';
        let subText = '';

        const locale = numberFormat === 'BR' ? 'pt-BR' : 'en-US';
        const fmtOneDec = new Intl.NumberFormat(locale, { minimumFractionDigits: 0, maximumFractionDigits: 1 });

        if (val === 'pam') {
            const pas = parseFloat(document.getElementById('cardioPas').value);
            const pad = parseFloat(document.getElementById('cardioPad').value);
            labelText = 'Pressão Arterial Média (PAM)';
            if (!isNaN(pas) && !isNaN(pad) && pas > 0 && pad > 0) {
                const pam = (pas + 2 * pad) / 3;
                resultText = `${fmtOneDec.format(pam)} mmHg`;
                if (pam < 70) subText = 'Perfusão tecidual pode estar inadequada (<70)';
                else if (pam <= 100) subText = 'Perfusão tecidual adequada (70 - 100)';
                else subText = 'Perfusão elevada (>100)';
            }
        } else if (val === 'chads') {
            labelText = 'Escore CHA₂DS₂-VASc';
            let score = 0;
            if (document.getElementById('chadsIC').checked) score += 1;
            if (document.getElementById('chadsHAS').checked) score += 1;
            if (document.getElementById('chadsIdade75').checked) score += 2;
            else if (document.getElementById('chadsIdade6574').checked) score += 1;
            if (document.getElementById('chadsDM').checked) score += 1;
            if (document.getElementById('chadsAVC').checked) score += 2;
            if (document.getElementById('chadsDoencaVasc').checked) score += 1;
            if (document.getElementById('chadsFeminino').checked) score += 1;

            resultText = `${score} pts`;
            
            const risks = [0, 1.3, 2.2, 3.2, 4.0, 6.7, 9.8, 9.6, 6.7, 15.2];
            const risk = risks[score] !== undefined ? risks[score] : 15.2;
            subText = `Risco anual de AVC estimado: ${risk}%`;
        } else if (val === 'hasbled') {
            labelText = 'Escore HAS-BLED';
            let score = 0;
            if (document.getElementById('hasHAS').checked) score += 1;
            if (document.getElementById('hasFimRen').checked) score += 1; // Simplificado como 1 ponto
            if (document.getElementById('hasAVC2').checked) score += 1;
            if (document.getElementById('hasSangramento').checked) score += 1;
            if (document.getElementById('hasLabile').checked) score += 1;
            if (document.getElementById('hasIdoso').checked) score += 1;
            if (document.getElementById('hasDrugs').checked) score += 1; // Simplificado como 1 ponto

            resultText = `${score} pts`;
            if (score >= 3) {
                subText = 'Alto risco de sangramento. Monitorar com cautela.';
            } else {
                subText = 'Risco de sangramento baixo a moderado.';
            }
        } else if (val === 'qtc') {
            const qt = parseFloat(document.getElementById('cardioQT').value);
            const fc = parseFloat(document.getElementById('cardioFC').value);
            const method = document.getElementById('cardioQtcMethod').value;
            labelText = 'QT Corrigido (QTc)';
            if (!isNaN(qt) && !isNaN(fc) && fc > 0 && qt > 0) {
                const rr = 60 / fc;
                let qtc = 0;
                if (method === 'bazett') {
                    qtc = qt / Math.sqrt(rr);
                } else {
                    qtc = qt / Math.cbrt(rr);
                }
                resultText = `${Math.round(qtc)} ms`;
                if (qtc > 500) {
                    subText = 'Perigo: Alto risco de arritmias (>500 ms)';
                } else if (qtc > 470) {
                    subText = 'Prolongado para homens e mulheres (>470 ms)';
                } else {
                    subText = 'Normal (geralmente < 440 ms)';
                }
            }
        }

        if (resultText !== '—') {
            card.style.display = 'block';
            label.innerText = labelText;
            valueEl.innerText = resultText;
            subEl.innerText = subText;
        } else {
            card.style.display = 'none';
        }

        if (displayFormula) displayFormula.innerText = 'Cardiologia Baseada em Evidências';
        if (displayResult) displayResult.innerText = resultText;
    }

    // 4. Nefrologia
    function calculateNefro() {
        const nefroCalcType = document.getElementById('nefroCalcType');
        if (!nefroCalcType) return;
        const val = nefroCalcType.value;
        const card = document.getElementById('nefroResultCard');
        const label = document.getElementById('nefroResultLabel');
        const valueEl = document.getElementById('nefroResultValue');
        const subEl = document.getElementById('nefroResultSub');
        
        if (!card || !valueEl || !label || !subEl) return;

        let resultText = '—';
        let labelText = 'Resultado';
        let subText = '';

        const locale = numberFormat === 'BR' ? 'pt-BR' : 'en-US';
        const fmtOneDec = new Intl.NumberFormat(locale, { minimumFractionDigits: 0, maximumFractionDigits: 1 });

        if (val === 'cg') {
            const creat = parseFloat(document.getElementById('nefroCreat').value);
            const age = parseFloat(document.getElementById('nefroAge').value);
            const w = parseFloat(document.getElementById('nefroWeight').value);
            const gender = document.getElementById('nefroGender').value;
            labelText = 'Clearance de Creatinina (C-G)';
            if (!isNaN(creat) && !isNaN(age) && !isNaN(w) && creat > 0 && age > 0 && w > 0) {
                let clcr = ((140 - age) * w) / (72 * creat);
                if (gender === 'female') clcr *= 0.85;
                resultText = `${fmtOneDec.format(clcr)} mL/min`;
                if (clcr > 90) subText = 'Função renal preservada (>90 mL/min)';
                else if (clcr >= 60) subText = 'Disfunção leve (60 - 89 mL/min)';
                else if (clcr >= 30) subText = 'Disfunção moderada (30 - 59 mL/min)';
                else subText = 'Disfunção grave / falência renal (<30 mL/min)';
            }
        } else if (val === 'egfr') {
            const creat = parseFloat(document.getElementById('nefroCreat').value);
            const age = parseFloat(document.getElementById('nefroAge').value);
            const gender = document.getElementById('nefroGender').value;
            labelText = 'eGFR (CKD-EPI 2021)';
            if (!isNaN(creat) && !isNaN(age) && creat > 0 && age > 0) {
                let egfr = 0;
                if (gender === 'female') {
                    const k = 0.7;
                    const alpha = -0.241;
                    egfr = 142 * Math.pow(Math.min(creat / k, 1), alpha) * Math.pow(Math.max(creat / k, 1), -1.200) * Math.pow(0.9938, age) * 1.012;
                } else {
                    const k = 0.9;
                    const alpha = -0.302;
                    egfr = 142 * Math.pow(Math.min(creat / k, 1), alpha) * Math.pow(Math.max(creat / k, 1), -1.200) * Math.pow(0.9938, age);
                }
                resultText = `${fmtOneDec.format(egfr)} mL/min/1.73m²`;
                
                if (egfr >= 90) subText = 'G1 — Normal ou elevado';
                else if (egfr >= 60) subText = 'G2 — Levemente diminuído';
                else if (egfr >= 45) subText = 'G3a — Diminuído leve a moderado';
                else if (egfr >= 30) subText = 'G3b — Diminuído moderado a grave';
                else if (egfr >= 15) subText = 'G4 — Severamente diminuído';
                else subText = 'G5 — Falência renal (Estágio terminal)';
            }
        } else if (val === 'na-corr') {
            const na = parseFloat(document.getElementById('nefroNaMedido').value);
            const gli = parseFloat(document.getElementById('nefroGlicose').value);
            labelText = 'Sódio Corrigido (Hiperglicemia)';
            if (!isNaN(na) && !isNaN(gli) && na > 0 && gli > 0) {
                const factor = gli > 400 ? 2.4 : 1.6;
                const naCorr = na + factor * ((gli - 100) / 100);
                resultText = `${fmtOneDec.format(naCorr)} mEq/L`;
                subText = `Correção feita usando fator ${factor} mEq/L por 100mg/dL de glicose`;
            }
        } else if (val === 'osmo') {
            const na = parseFloat(document.getElementById('nefroOsmoNa').value);
            const gli = parseFloat(document.getElementById('nefroOsmoGli').value);
            const ureia = parseFloat(document.getElementById('nefroOsmoUreia').value);
            labelText = 'Osmolaridade Plasmática';
            if (!isNaN(na) && !isNaN(gli) && !isNaN(ureia) && na > 0) {
                const osmo = 2 * na + (gli / 18) + (ureia / 6);
                resultText = `${fmtOneDec.format(osmo)} mOsm/kg`;
                if (osmo < 275) subText = 'Hipoosmolaridade (<275)';
                else if (osmo <= 295) subText = 'Normal (275 - 295 mOsm/kg)';
                else subText = 'Hiperosmolaridade (>295)';
            }
        }

        if (resultText !== '—') {
            card.style.display = 'block';
            label.innerText = labelText;
            valueEl.innerText = resultText;
            subEl.innerText = subText;
        } else {
            card.style.display = 'none';
        }

        if (displayFormula) displayFormula.innerText = 'Função Renal e Eletrólitos';
        if (displayResult) displayResult.innerText = resultText;
    }

    // 5. Respiratória
    function calculateResp() {
        const respCalcType = document.getElementById('respCalcType');
        if (!respCalcType) return;
        const val = respCalcType.value;
        const card = document.getElementById('respResultCard');
        const label = document.getElementById('respResultLabel');
        const valueEl = document.getElementById('respResultValue');
        const subEl = document.getElementById('respResultSub');
        
        if (!card || !valueEl || !label || !subEl) return;

        let resultText = '—';
        let labelText = 'Resultado';
        let subText = '';

        const locale = numberFormat === 'BR' ? 'pt-BR' : 'en-US';
        const fmtOneDec = new Intl.NumberFormat(locale, { minimumFractionDigits: 0, maximumFractionDigits: 1 });

        if (val === 'pf') {
            const pao2 = parseFloat(document.getElementById('respPao2').value);
            const fio2 = parseFloat(document.getElementById('respFio2').value);
            labelText = 'Relação PaO₂ / FiO₂';
            if (!isNaN(pao2) && !isNaN(fio2) && pao2 > 0 && fio2 > 0) {
                const pf = pao2 / (fio2 / 100);
                resultText = fmtOneDec.format(pf);
                if (pf > 300) subText = 'Normal / Sem SDRA';
                else if (pf > 200) subText = 'SDRA Leve (Berlim)';
                else if (pf > 100) subText = 'SDRA Moderada (Berlim)';
                else subText = 'SDRA Grave (Berlim)';
            }
        } else if (val === 'oi') {
            const map = parseFloat(document.getElementById('respMap').value);
            const fio2 = parseFloat(document.getElementById('respFio2Oi').value);
            const pao2 = parseFloat(document.getElementById('respPao2Oi').value);
            labelText = 'Índice de Oxigenação (OI)';
            if (!isNaN(map) && !isNaN(fio2) && !isNaN(pao2) && pao2 > 0) {
                const oi = (fio2 * map) / pao2;
                resultText = fmtOneDec.format(oi);
                if (oi < 4) subText = 'Excelente oxigenação';
                else if (oi < 8) subText = 'Disfunção respiratória leve';
                else if (oi < 16) subText = 'Disfunção respiratória moderada';
                else subText = 'Disfunção respiratória grave (considere ECMO se >25)';
            }
        } else if (val === 'vm') {
            const vt = parseFloat(document.getElementById('respVt').value);
            const fr = parseFloat(document.getElementById('respFr').value);
            labelText = 'Ventilação Minuto (VE)';
            if (!isNaN(vt) && !isNaN(fr) && vt > 0 && fr > 0) {
                const ve = (vt / 1000) * fr;
                resultText = `${fmtOneDec.format(ve)} L/min`;
                subText = 'Valor fisiológico em repouso: 5 - 8 L/min';
            }
        } else if (val === 'ibw') {
            const h = parseFloat(document.getElementById('respHeight').value);
            const gender = document.getElementById('respGender').value;
            labelText = 'Peso Predito Pulmonar (IBW)';
            if (!isNaN(h) && h > 0) {
                let ibw = 0;
                if (gender === 'male') {
                    ibw = 50.0 + 0.91 * (h - 152.4);
                } else {
                    ibw = 45.5 + 0.91 * (h - 152.4);
                }
                resultText = `${fmtOneDec.format(ibw)} kg`;
                subText = `Volume corrente ideal (6 mL/kg): ${Math.round(ibw * 6)} mL`;
            }
        }

        if (resultText !== '—') {
            card.style.display = 'block';
            label.innerText = labelText;
            valueEl.innerText = resultText;
            subEl.innerText = subText;
        } else {
            card.style.display = 'none';
        }

        if (displayFormula) displayFormula.innerText = 'Parâmetros Ventilatórios';
        if (displayResult) displayResult.innerText = resultText;
    }

    // 6. Pediatria
    function calculatePed() {
        const pedCalcType = document.getElementById('pedCalcType');
        if (!pedCalcType) return;
        const val = pedCalcType.value;
        const card = document.getElementById('pedResultCard');
        const label = document.getElementById('pedResultLabel');
        const valueEl = document.getElementById('pedResultValue');
        const subEl = document.getElementById('pedResultSub');
        
        if (!card || !valueEl || !label || !subEl) return;

        let resultText = '—';
        let labelText = 'Resultado';
        let subText = '';

        const locale = numberFormat === 'BR' ? 'pt-BR' : 'en-US';
        const fmtOneDec = new Intl.NumberFormat(locale, { minimumFractionDigits: 0, maximumFractionDigits: 1 });

        if (val === 'dose') {
            const peso = parseFloat(document.getElementById('pedPeso').value);
            const dose = parseFloat(document.getElementById('pedDose').value);
            const maxInput = document.getElementById('pedDoseMax').value;
            const freq = parseInt(document.getElementById('pedFreq').value);
            labelText = 'Dose Pediátrica Calculada';
            
            if (!isNaN(peso) && !isNaN(dose) && peso > 0 && dose > 0) {
                let total = peso * dose;
                if (maxInput) {
                    const max = parseFloat(maxInput);
                    if (!isNaN(max) && max > 0) {
                        total = Math.min(total, max);
                    }
                }
                const perDose = total / freq;
                resultText = `${fmtOneDec.format(total)} mg/dia`;
                
                let freqText = 'vez ao dia';
                if (freq === 2) freqText = 'a cada 12h (2x/dia)';
                else if (freq === 3) freqText = 'a cada 8h (3x/dia)';
                else if (freq === 4) freqText = 'a cada 6h (4x/dia)';
                
                subText = `Prescrição: ${fmtOneDec.format(perDose)} mg ${freqText}`;
            }
        } else if (val === 'pesoidade') {
            const anos = parseFloat(document.getElementById('pedIdadeAnos').value) || 0;
            const meses = parseFloat(document.getElementById('pedIdadeMeses').value) || 0;
            labelText = 'Estimativa de Peso Ideal';
            
            const totalMonths = anos * 12 + meses;
            if (totalMonths > 0) {
                let weight = 0;
                if (totalMonths < 12) {
                    weight = totalMonths * 0.5 + 3.5;
                } else if (totalMonths < 72) {
                    weight = anos * 2 + 8;
                } else {
                    weight = (anos * 7 - 5) / 2;
                }
                resultText = `${fmtOneDec.format(weight)} kg`;
                subText = 'Fórmulas práticas (APLS) para uso emergencial.';
            }
        } else if (val === 'hidratacao') {
            const peso = parseFloat(document.getElementById('pedHollidayPeso').value);
            labelText = 'Regra de Holliday-Segar';
            if (!isNaN(peso) && peso > 0) {
                let ml = 0;
                if (peso <= 10) {
                    ml = peso * 100;
                } else if (peso <= 20) {
                    ml = 1000 + (peso - 10) * 50;
                } else {
                    ml = 1500 + (peso - 20) * 20;
                }
                resultText = `${fmtOneDec.format(ml)} mL/dia`;
                subText = `Velocidade de Infusão: ${fmtOneDec.format(ml / 24)} mL/h`;
            }
        }

        if (resultText !== '—') {
            card.style.display = 'block';
            label.innerText = labelText;
            valueEl.innerText = resultText;
            subEl.innerText = subText;
        } else {
            card.style.display = 'none';
        }

        if (displayFormula) displayFormula.innerText = 'Ajustes e Pediatria';
        if (displayResult) displayResult.innerText = resultText;
    }

    // 8. Obstetrícia
    function calculateObst() {
        const obstCalcType = document.getElementById('obstCalcType');
        if (!obstCalcType) return;
        const val = obstCalcType.value;
        const card = document.getElementById('obstResultCard');
        const label = document.getElementById('obstResultLabel');
        const valueEl = document.getElementById('obstResultValue');
        const subEl = document.getElementById('obstResultSub');
        
        if (!card || !valueEl || !label || !subEl) return;

        let resultText = '—';
        let labelText = 'Resultado';
        let subText = '';

        const locale = numberFormat === 'BR' ? 'pt-BR' : 'en-US';
        const fmtOneDec = new Intl.NumberFormat(locale, { minimumFractionDigits: 0, maximumFractionDigits: 1 });

        if (val === 'dpp' || val === 'ig') {
            const dumVal = document.getElementById('obstDum').value;
            if (dumVal) {
                const dum = new Date(dumVal + 'T00:00:00');
                const today = new Date();
                today.setHours(0,0,0,0);
                
                const dpp = new Date(dum.getTime() + (280 * 24 * 60 * 60 * 1000));
                const diffMs = today - dum;
                const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
                
                const weeks = Math.floor(diffDays / 7);
                const remDays = diffDays % 7;

                const dppD = String(dpp.getDate()).padStart(2, '0');
                const dppM = String(dpp.getMonth() + 1).padStart(2, '0');
                const dppY = dpp.getFullYear();
                const formattedDpp = `${dppD}/${dppM}/${dppY}`;

                if (val === 'dpp') {
                    labelText = 'Data Provável do Parto';
                    resultText = formattedDpp;
                    const rem = Math.floor((dpp - today) / (1000 * 60 * 60 * 24));
                    if (rem > 0) subText = `Faltam ${rem} dias para o parto estimado`;
                    else if (rem === 0) subText = 'Data provável do parto é hoje!';
                    else subText = 'Parto ocorrido ou data ultrapassada';
                } else {
                    labelText = 'Idade Gestacional';
                    if (diffDays >= 0 && diffDays <= 300) {
                        resultText = `${weeks} sem e ${remDays} dias`;
                        if (weeks < 14) subText = '1º Trimestre';
                        else if (weeks < 28) subText = '2º Trimestre';
                        else subText = '3º Trimestre';
                    } else {
                        resultText = 'Data inválida';
                    }
                }
            }
        } else if (val === 'ganho') {
            const w = parseFloat(document.getElementById('obstPesoPreg').value);
            const h = parseFloat(document.getElementById('obstAlturaPreg').value);
            labelText = 'Ganho de Peso Recomendado';
            if (!isNaN(w) && !isNaN(h) && w > 0 && h > 0) {
                const imc = w / Math.pow(h / 100, 2);
                let range = '';
                let cat = '';
                if (imc < 18.5) {
                    range = '12.5 - 18.0 kg';
                    cat = 'Baixo Peso';
                } else if (imc < 25.0) {
                    range = '11.5 - 16.0 kg';
                    cat = 'Eutrofia';
                } else if (imc < 30.0) {
                    range = '7.0 - 11.5 kg';
                    cat = 'Sobrepeso';
                } else {
                    range = '5.0 - 9.0 kg';
                    cat = 'Obesidade';
                }
                resultText = range;
                subText = `IMC Pré-gestacional: ${fmtOneDec.format(imc)} kg/m² (${cat})`;
            }
        }

        if (resultText !== '—') {
            card.style.display = 'block';
            label.innerText = labelText;
            valueEl.innerText = resultText;
            subEl.innerText = subText;
        } else {
            card.style.display = 'none';
        }

        if (displayFormula) displayFormula.innerText = 'Obstetrícia Avançada';
        if (displayResult) displayResult.innerText = resultText;
    }

    // 8. Laboratório / Conversões
    function calculateLab() {
        const labCalcType = document.getElementById('labCalcType');
        const labFromUnit = document.getElementById('labFromUnit');
        if (!labCalcType || !labFromUnit) return;
        const val = parseFloat(document.getElementById('labValue').value);
        const type = labCalcType.value;
        const unit = labFromUnit.value;
        const card = document.getElementById('labResultCard');
        const label = document.getElementById('labResultLabel');
        const valueEl = document.getElementById('labResultValue');
        const refEl = document.getElementById('labResultRef');
        
        if (!card || !valueEl || !label || !refEl) return;

        let resultText = '—';
        let labelText = 'Conversão Efetuada';
        let refText = '';

        const locale = numberFormat === 'BR' ? 'pt-BR' : 'en-US';
        const fmtTwoDec = new Intl.NumberFormat(locale, { minimumFractionDigits: 0, maximumFractionDigits: 3 });

        if (isNaN(val)) {
            card.style.display = 'none';
            return;
        }

        if (type === 'glicose') {
            if (unit === 'a') {
                const res = val / 18.016;
                resultText = `${fmtTwoDec.format(res)} mmol/L`;
            } else {
                const res = val * 18.016;
                resultText = `${fmtTwoDec.format(res)} mg/dL`;
            }
            refText = 'Glicemia normal em jejum: 70 - 99 mg/dL (3.9 - 5.5 mmol/L)';
        } else if (type === 'colesterol') {
            if (unit === 'a') {
                const res = val / 38.67;
                resultText = `${fmtTwoDec.format(res)} mmol/L`;
            } else {
                const res = val * 38.67;
                resultText = `${fmtTwoDec.format(res)} mg/dL`;
            }
            refText = 'Colesterol total ideal: < 200 mg/dL (< 5.18 mmol/L)';
        } else if (type === 'hb') {
            if (unit === 'a') {
                const res = val * 0.6206;
                resultText = `${fmtTwoDec.format(res)} mmol/L`;
            } else {
                const res = val / 0.6206;
                resultText = `${fmtTwoDec.format(res)} g/dL`;
            }
            refText = 'Hemoglobina ref: Homens 13.8 - 17.2 g/dL, Mulheres 12.1 - 15.1 g/dL';
        } else if (type === 'creat') {
            if (unit === 'a') {
                const res = val * 88.4;
                resultText = `${fmtTwoDec.format(res)} μmol/L`;
            } else {
                const res = val / 88.4;
                resultText = `${fmtTwoDec.format(res)} mg/dL`;
            }
            refText = 'Creatinina ref: Homens 0.7 - 1.3 mg/dL, Mulheres 0.6 - 1.1 mg/dL';
        } else if (type === 'na') {
            resultText = `${fmtTwoDec.format(val)} mmol/L (ou mEq/L)`;
            refText = 'Relação direta de 1:1. Sódio ref: 135 - 145 mEq/L. Potássio ref: 3.5 - 5.0 mEq/L.';
        } else if (type === 'ureia') {
            if (unit === 'a') {
                const res = val / 6.006;
                resultText = `${fmtTwoDec.format(res)} mmol/L`;
            } else {
                const res = val * 6.006;
                resultText = `${fmtTwoDec.format(res)} mg/dL`;
            }
            refText = 'Ureia sérica normal: 15 - 45 mg/dL (2.5 - 7.5 mmol/L)';
        }

        if (resultText !== '—') {
            card.style.display = 'block';
            label.innerText = labelText;
            valueEl.innerText = resultText;
            refEl.innerText = refText;
        } else {
            card.style.display = 'none';
        }

        if (displayFormula) displayFormula.innerText = 'Conversões Clínicas';
        if (displayResult) displayResult.innerText = resultText;
    }

    // === VINCULAÇÃO DE EVENTOS CLÍNICOS ===
    
    // Med
    const btnMedCalc = document.getElementById('btnMedCalc');
    if (btnMedCalc) btnMedCalc.addEventListener('click', calculateMed);
    const medInputs = ['medDoseMgkg', 'medWeightMgkg', 'medDoseBsa', 'medBsaVal', 'medDoseMg', 'medConc', 'medGotaVol', 'medGotaTime', 'medInfVol', 'medInfTime', 'medBombaDose', 'medBombaPeso', 'medBombaConc'];
    medInputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', calculateMed);
    });
    const medSelects = ['medCalcType', 'medGotaEquipo'];
    medSelects.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => {
            updateMedFields();
            calculateMed();
        });
    });

    // Body
    const btnBodyCalc = document.getElementById('btnBodyCalc');
    if (btnBodyCalc) btnBodyCalc.addEventListener('click', calculateBody);
    const bodyInputs = ['bodyWeight', 'bodyHeight', 'bodyWaist', 'bodyNeck', 'bodyHip'];
    bodyInputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', calculateBody);
    });
    const bodySelects = ['bodyCalcType', 'bodyGender'];
    bodySelects.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => {
            updateBodyFields();
            calculateBody();
        });
    });

    // Cardio
    const btnCardioCalc = document.getElementById('btnCardioCalc');
    if (btnCardioCalc) btnCardioCalc.addEventListener('click', calculateCardio);
    const cardioInputs = ['cardioPas', 'cardioPad', 'cardioQT', 'cardioFC'];
    cardioInputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', calculateCardio);
    });
    const cardioSelects = ['cardioCalcType', 'cardioQtcMethod'];
    cardioSelects.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => {
            updateCardioFields();
            calculateCardio();
        });
    });

    // Chads checkboxes
    const chadsCheckboxes = ['chadsIC', 'chadsHAS', 'chadsIdade75', 'chadsDM', 'chadsAVC', 'chadsDoencaVasc', 'chadsIdade6574', 'chadsFeminino'];
    chadsCheckboxes.forEach(id => {
        const cb = document.getElementById(id);
        if (cb) {
            cb.addEventListener('change', () => {
                if (id === 'chadsIdade75' && cb.checked) {
                    const cb65 = document.getElementById('chadsIdade6574');
                    if (cb65) cb65.checked = false;
                }
                if (id === 'chadsIdade6574' && cb.checked) {
                    const cb75 = document.getElementById('chadsIdade75');
                    if (cb75) cb75.checked = false;
                }
                calculateCardio();
            });
        }
    });

    // Hasbled checkboxes
    const hasbledCheckboxes = ['hasHAS', 'hasFimRen', 'hasAVC2', 'hasSangramento', 'hasLabile', 'hasIdoso', 'hasDrugs'];
    hasbledCheckboxes.forEach(id => {
        const cb = document.getElementById(id);
        if (cb) cb.addEventListener('change', calculateCardio);
    });

    // Nefro
    const btnNefroCalc = document.getElementById('btnNefroCalc');
    if (btnNefroCalc) btnNefroCalc.addEventListener('click', calculateNefro);
    const nefroInputs = ['nefroCreat', 'nefroAge', 'nefroWeight', 'nefroNaMedido', 'nefroGlicose', 'nefroOsmoNa', 'nefroOsmoGli', 'nefroOsmoUreia'];
    nefroInputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', calculateNefro);
    });
    const nefroSelects = ['nefroCalcType', 'nefroGender'];
    nefroSelects.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => {
            updateNefroFields();
            calculateNefro();
        });
    });

    // Resp
    const btnRespCalc = document.getElementById('btnRespCalc');
    if (btnRespCalc) btnRespCalc.addEventListener('click', calculateResp);
    const respInputs = ['respPao2', 'respFio2', 'respMap', 'respFio2Oi', 'respPao2Oi', 'respVt', 'respFr', 'respHeight'];
    respInputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', calculateResp);
    });
    const respSelects = ['respCalcType', 'respGender'];
    respSelects.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => {
            updateRespFields();
            calculateResp();
        });
    });

    // Ped
    const btnPedCalc = document.getElementById('btnPedCalc');
    if (btnPedCalc) btnPedCalc.addEventListener('click', calculatePed);
    const pedInputs = ['pedPeso', 'pedDose', 'pedDoseMax', 'pedIdadeAnos', 'pedIdadeMeses', 'pedHollidayPeso'];
    pedInputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', calculatePed);
    });
    const pedSelects = ['pedCalcType', 'pedFreq'];
    pedSelects.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => {
            updatePedFields();
            calculatePed();
        });
    });

    // Obst
    const btnObstCalc = document.getElementById('btnObstCalc');
    if (btnObstCalc) btnObstCalc.addEventListener('click', calculateObst);
    const obstInputs = ['obstPesoPreg', 'obstAlturaPreg'];
    obstInputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', calculateObst);
    });
    const obstSelects = ['obstCalcType'];
    obstSelects.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => {
            updateObstFields();
            calculateObst();
        });
    });
    const obstDum = document.getElementById('obstDum');
    if (obstDum) {
        obstDum.value = todayISO;
        obstDum.addEventListener('change', calculateObst);
    }

    // Lab
    const btnLabCalc = document.getElementById('btnLabCalc');
    if (btnLabCalc) btnLabCalc.addEventListener('click', calculateLab);
    const labInputs = ['labValue'];
    labInputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', calculateLab);
    });
    const labSelects = ['labCalcType', 'labFromUnit'];
    labSelects.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => {
            if (id === 'labCalcType') {
                updateLabUnits();
            }
            calculateLab();
        });
    });

    // Inicialização da data DUM padrão para hoje
    if (healthPregDum) {
        healthPregDum.value = todayISO;
    }
    
    // Atualiza a exibição de inputs do painel de saúde logo no início
    updateHealthInputsVisibility();

    // === LÓGICA DA CALCULADORA PROGRAMADOR ===
    function toSigned(val, wordSize) {
        const N = BigInt(wordSize);
        const clipped = val & ((1n << N) - 1n);
        const msb = 1n << (N - 1n);
        if ((clipped & msb) !== 0n) {
            return clipped - (1n << N);
        }
        return clipped;
    }

    function toUnsigned(val, wordSize) {
        const N = BigInt(wordSize);
        return val & ((1n << N) - 1n);
    }

    function getBaseRadix(base) {
        if (base === 'HEX') return 16;
        if (base === 'DEC') return 10;
        if (base === 'OCT') return 8;
        if (base === 'BIN') return 2;
        return 10;
    }

    function parseBuffer(buf, base) {
        if (!buf || buf === '0') return 0n;
        try {
            if (base === 'HEX') return BigInt('0x' + buf);
            if (base === 'OCT') return BigInt('0o' + buf);
            if (base === 'BIN') return BigInt('0b' + buf);
            if (base === 'DEC') return BigInt(buf);
        } catch (e) {
            return 0n;
        }
        return 0n;
    }

    function formatBaseValue(val, base, wordSize, signed) {
        const uVal = toUnsigned(val, wordSize);
        if (base === 'HEX') {
            return uVal.toString(16).toUpperCase();
        } else if (base === 'OCT') {
            return uVal.toString(8);
        } else if (base === 'BIN') {
            return uVal.toString(2);
        } else if (base === 'DEC') {
            if (signed) {
                const sVal = toSigned(uVal, wordSize);
                return sVal.toString(10);
            } else {
                return uVal.toString(10);
            }
        }
        return '0';
    }

    function groupString(str, groupSize) {
        if (str.length <= groupSize) return str;
        const parts = [];
        let i = str.length;
        while (i > 0) {
            const start = Math.max(0, i - groupSize);
            parts.unshift(str.slice(start, i));
            i = start;
        }
        return parts.join(' ');
    }

    function formatBaseValueWithGaps(val, base, wordSize, signed) {
        const raw = formatBaseValue(val, base, wordSize, signed);
        if (base === 'BIN') {
            return groupString(raw, 4);
        }
        if (base === 'HEX') {
            return groupString(raw, 4);
        }
        if (base === 'DEC') {
            const isNeg = raw.startsWith('-');
            const digits = isNeg ? raw.slice(1) : raw;
            const locale = numberFormat === 'BR' ? 'pt-BR' : 'en-US';
            try {
                const formattedDigits = BigInt(digits).toLocaleString(locale);
                return (isNeg ? '-' : '') + formattedDigits;
            } catch (e) {
                return raw;
            }
        }
        return raw;
    }

    function formatProgOpSymbol(op) {
        switch (op) {
            case 'and': return 'AND';
            case 'or': return 'OR';
            case 'xor': return 'XOR';
            case 'lsh': return '<<';
            case 'rsh': return '>>';
            default: return op;
        }
    }

    function performProgOp(val1, val2, op, wordSize, signed) {
        const uVal1 = toUnsigned(val1, wordSize);
        const uVal2 = toUnsigned(val2, wordSize);
        
        let v1 = uVal1;
        let v2 = uVal2;
        
        if (signed) {
            v1 = toSigned(uVal1, wordSize);
            v2 = toSigned(uVal2, wordSize);
        }
        
        let res = 0n;
        switch (op) {
            case '+': res = v1 + v2; break;
            case '-': res = v1 - v2; break;
            case '*': res = v1 * v2; break;
            case '/': 
                if (v2 === 0n) return 0n;
                res = v1 / v2; 
                break;
            case '%':
                if (v2 === 0n) return 0n;
                res = v1 % v2;
                break;
            case 'and': res = uVal1 & uVal2; break;
            case 'or':  res = uVal1 | uVal2; break;
            case 'xor': res = uVal1 ^ uVal2; break;
            case 'lsh': 
                res = uVal1 << uVal2; 
                break;
            case 'rsh': 
                if (signed) {
                    res = v1 >> v2;
                } else {
                    res = uVal1 >> uVal2;
                }
                break;
        }
        
        return toUnsigned(res, wordSize);
    }

    function handleProgDigitInput(digit) {
        let newBuffer = progInputBuffer;
        if (progResetBuffer) {
            newBuffer = digit;
        } else if (newBuffer === '0') {
            newBuffer = digit;
        } else {
            newBuffer += digit;
        }
        
        const val = parseBuffer(newBuffer, progBase);
        const maxUnsigned = (1n << BigInt(progWordSize)) - 1n;
        if (val <= maxUnsigned) {
            progInputBuffer = newBuffer;
            progValue = val;
            progResetBuffer = false;
            updateProgrammerDisplay();
        }
    }

    function handleProgActionInput(action) {
        if (action === 'clear') {
            progValue = 0n;
            progInputBuffer = '0';
            progResetBuffer = false;
            progOperand = null;
            progPendingOp = null;
            updateProgrammerDisplay();
            return;
        }
        
        if (action === 'backspace') {
            if (progResetBuffer) {
                progInputBuffer = '0';
                progResetBuffer = false;
            } else {
                progInputBuffer = progInputBuffer.slice(0, -1);
                if (progInputBuffer === '') {
                    progInputBuffer = '0';
                }
            }
            progValue = parseBuffer(progInputBuffer, progBase);
            updateProgrammerDisplay();
            return;
        }
        
        if (action === 'not') {
            progValue = toUnsigned(~progValue, progWordSize);
            progInputBuffer = progValue.toString(getBaseRadix(progBase)).toUpperCase();
            updateProgrammerDisplay();
            return;
        }
        
        if (action === 'equals') {
            if (progPendingOp && progOperand !== null) {
                const res = performProgOp(progOperand, progValue, progPendingOp, progWordSize, progSigned);
                const opStr = formatProgOpSymbol(progPendingOp);
                const val1Str = formatBaseValue(progOperand, progBase, progWordSize, progSigned);
                const val2Str = formatBaseValue(progValue, progBase, progWordSize, progSigned);
                const resStr = formatBaseValue(res, progBase, progWordSize, progSigned);
                
                if (displayFormula) {
                    displayFormula.innerText = `${val1Str} ${opStr} ${val2Str} =`;
                }
                
                progValue = res;
                progInputBuffer = res.toString(getBaseRadix(progBase)).toUpperCase();
                progOperand = null;
                progPendingOp = null;
                progResetBuffer = true;
                
                saveCalculation(`${val1Str} ${opStr} ${val2Str}`, resStr);
                updateProgrammerDisplay();
            }
            return;
        }
        
        if (['+', '-', '*', '/', '%', 'and', 'or', 'xor', 'lsh', 'rsh'].indexOf(action) !== -1) {
            if (progPendingOp && progOperand !== null && !progResetBuffer) {
                progValue = performProgOp(progOperand, progValue, progPendingOp, progWordSize, progSigned);
            }
            progOperand = progValue;
            progPendingOp = action;
            progResetBuffer = true;
            updateProgrammerDisplay();
        }
    }

    function updateProgrammerDisplay() {
        const valHex = document.getElementById('val-hex');
        const valDec = document.getElementById('val-dec');
        const valOct = document.getElementById('val-oct');
        const valBin = document.getElementById('val-bin');
        
        if (valHex) valHex.innerText = formatBaseValueWithGaps(progValue, 'HEX', progWordSize, progSigned);
        if (valDec) valDec.innerText = formatBaseValueWithGaps(progValue, 'DEC', progWordSize, progSigned);
        if (valOct) valOct.innerText = formatBaseValueWithGaps(progValue, 'OCT', progWordSize, progSigned);
        if (valBin) valBin.innerText = formatBaseValueWithGaps(progValue, 'BIN', progWordSize, progSigned);
        
        if (displayResult) {
            displayResult.innerText = formatBaseValueWithGaps(progValue, progBase, progWordSize, progSigned);
        }
        
        if (displayFormula) {
            if (progPendingOp && progOperand !== null) {
                const opStr = formatProgOpSymbol(progPendingOp);
                const operandStr = formatBaseValue(progOperand, progBase, progWordSize, progSigned);
                displayFormula.innerText = `${operandStr} ${opStr}`;
            } else if (!displayFormula.innerText.endsWith('=')) {
                displayFormula.innerText = '';
            }
        }
        
        renderProgBitGrid();
    }

    function updateProgrammerKeys() {
        const radix = getBaseRadix(progBase);
        const keys = document.querySelectorAll('.programmer-grid button[data-val]');
        keys.forEach(btn => {
            const val = btn.getAttribute('data-val');
            if (/^[0-9A-F]$/i.test(val)) {
                const digitVal = parseInt(val, 16);
                if (digitVal >= radix) {
                    btn.disabled = true;
                } else {
                    btn.disabled = false;
                }
            }
        });
        
        const rows = ['row-hex', 'row-dec', 'row-oct', 'row-bin'];
        rows.forEach(rowId => {
            const rowEl = document.getElementById(rowId);
            if (rowEl) {
                if (rowEl.getAttribute('data-base') === progBase) {
                    rowEl.classList.add('active');
                } else {
                    rowEl.classList.remove('active');
                }
            }
        });
        
        const btnWord = document.getElementById('btn-prog-word');
        const btnSigned = document.getElementById('btn-prog-signed');
        const btnBits = document.getElementById('btn-prog-bits');
        
        if (btnWord) {
            let wordLabel = 'QWORD (64)';
            if (progWordSize === 32) wordLabel = 'DWORD (32)';
            if (progWordSize === 16) wordLabel = 'WORD (16)';
            if (progWordSize === 8) wordLabel = 'BYTE (8)';
            btnWord.innerText = wordLabel;
        }
        
        if (btnSigned) {
            btnSigned.innerText = progSigned ? 'SIGNED' : 'UNSIGNED';
            if (progSigned) {
                btnSigned.classList.add('active');
            } else {
                btnSigned.classList.remove('active');
            }
        }
        
        if (btnBits) {
            if (bitBoardVisible) {
                btnBits.classList.add('active');
            } else {
                btnBits.classList.remove('active');
            }
        }
    }

    function renderProgBitGrid() {
        const grid = document.getElementById('prog-bit-grid');
        if (!grid) return;
        
        grid.innerHTML = '';
        if (!bitBoardVisible) {
            grid.classList.remove('show');
            return;
        }
        grid.classList.add('show');
        
        const wordSize = parseInt(progWordSize);
        
        let rows = [];
        if (wordSize === 64) {
            rows = [
                { start: 63, end: 48 },
                { start: 47, end: 32 },
                { start: 31, end: 16 },
                { start: 15, end: 0 }
            ];
        } else if (wordSize === 32) {
            rows = [
                { start: 31, end: 16 },
                { start: 15, end: 0 }
            ];
        } else if (wordSize === 16) {
            rows = [
                { start: 15, end: 0 }
            ];
        } else if (wordSize === 8) {
            rows = [
                { start: 7, end: 0 }
            ];
        }
        
        const valBig = toUnsigned(progValue, wordSize);
        
        rows.forEach(r => {
            const rowEl = document.createElement('div');
            rowEl.className = 'bit-row';
            
            for (let i = r.start; i >= r.end; i--) {
                const bitBox = document.createElement('div');
                bitBox.className = 'bit-box';
                bitBox.dataset.bit = i;
                
                const isSet = (valBig & (1n << BigInt(i))) !== 0n;
                
                const valEl = document.createElement('span');
                valEl.className = 'bit-val' + (isSet ? ' on' : '');
                valEl.innerText = isSet ? '1' : '0';
                
                const idxEl = document.createElement('span');
                idxEl.className = 'bit-idx';
                idxEl.innerText = i;
                
                bitBox.appendChild(valEl);
                bitBox.appendChild(idxEl);
                
                bitBox.addEventListener('click', () => {
                    progValue = progValue ^ (1n << BigInt(i));
                    progValue = toUnsigned(progValue, progWordSize);
                    progInputBuffer = progValue.toString(getBaseRadix(progBase)).toUpperCase();
                    updateProgrammerDisplay();
                });
                
                rowEl.appendChild(bitBox);
            }
            grid.appendChild(rowEl);
        });
    }

    function setupProgrammerEvents() {
        const baseRows = document.querySelectorAll('.prog-base-row');
        baseRows.forEach(row => {
            row.addEventListener('click', () => {
                const base = row.getAttribute('data-base');
                if (base && base !== progBase) {
                    progBase = base;
                    progInputBuffer = progValue.toString(getBaseRadix(progBase)).toUpperCase();
                    updateProgrammerKeys();
                    updateProgrammerDisplay();
                }
            });
        });
        
        const btnWord = document.getElementById('btn-prog-word');
        if (btnWord) {
            btnWord.addEventListener('click', () => {
                if (progWordSize === 64) progWordSize = 32;
                else if (progWordSize === 32) progWordSize = 16;
                else if (progWordSize === 16) progWordSize = 8;
                else progWordSize = 64;
                
                progValue = toUnsigned(progValue, progWordSize);
                progInputBuffer = progValue.toString(getBaseRadix(progBase)).toUpperCase();
                
                updateProgrammerKeys();
                updateProgrammerDisplay();
            });
        }
        
        const btnSigned = document.getElementById('btn-prog-signed');
        if (btnSigned) {
            btnSigned.addEventListener('click', () => {
                progSigned = !progSigned;
                updateProgrammerKeys();
                updateProgrammerDisplay();
            });
        }
        
        const btnBits = document.getElementById('btn-prog-bits');
        if (btnBits) {
            btnBits.addEventListener('click', () => {
                bitBoardVisible = !bitBoardVisible;
                updateProgrammerKeys();
                updateProgrammerDisplay();
            });
        }
        
        const progGrid = document.querySelector('.programmer-grid');
        if (progGrid) {
            progGrid.addEventListener('click', (e) => {
                const btn = e.target.closest('.key-btn');
                if (!btn || btn.disabled) return;
                
                const val = btn.getAttribute('data-val');
                const action = btn.getAttribute('data-action');
                
                if (val !== null) {
                    if ('+-*/%'.indexOf(val) !== -1) {
                        handleProgActionInput(val);
                    } else {
                        handleProgDigitInput(val);
                    }
                } else if (action !== null) {
                    handleProgActionInput(action);
                }
            });
        }
    }

    // === CALCULADORA DE ENGENHARIA ===
    const engCalculations = {
        estruturas: {
            name: "Estruturas",
            calcs: {
                "mom-fletor": {
                    name: "Momento Fletor",
                    formula: "Concentrada: M = P·L/4 | Distribuída: M = q·L²/8",
                    calcType: "matematico",
                    notes: "Viga biapoiada. Concentrada: P = força pontual no centro [kN]. Distribuída: q = intensidade de carga distribuída [kN/m] (não carga total). Não considera peso próprio nem cargas combinadas.",
                    fields: [
                        { id: "eng_L", label: "Comprimento L (m)", default: "5" },
                        { id: "eng_P", label: "Carga P [kN] ou q [kN/m]", default: "10" },
                        { id: "eng_tipo", label: "Tipo de Carga", type: "select", options: [
                            { value: "conc", label: "Concentrada no Centro (P em kN)" },
                            { value: "dist", label: "Distribuída Uniforme (q em kN/m)" }
                        ]}
                    ],
                    calc: (vals) => {
                        const L = parseFloat(vals.eng_L) || 0;
                        const P = parseFloat(vals.eng_P) || 0;
                        const tipo = vals.eng_tipo;
                        let maxM = 0;
                        if (tipo === 'conc') {
                            // Carga concentrada central: M = P·L/4
                            maxM = (P * L) / 4;
                        } else {
                            // Carga distribuída uniforme: M = q·L²/8 (q em kN/m)
                            maxM = (P * L * L) / 8;
                        }
                        return { label: "Momento Máximo", value: maxM, unit: "kN·m" };
                    }
                },
                "esf-cortante": {
                    name: "Esforço Cortante",
                    formula: "V = P / 2",
                    calcType: "matematico",
                    notes: "Válido apenas para viga biapoiada com carga total P. Hipótese: reação igual em ambos os apoios. Cargas excêntricas ou múltiplos apoios alteram o diagrama de esforços.",
                    fields: [
                        { id: "eng_P", label: "Carga Total P (kN)", default: "10" }
                    ],
                    calc: (vals) => {
                        const P = parseFloat(vals.eng_P) || 0;
                        return { label: "Corte Máximo Vmax", value: P / 2, unit: "kN" };
                    }
                },
                "flecha-viga": {
                    name: "Flecha em Vigas",
                    formula: "δ = P·L³ / (48·E·I)  [carga concentrada central, viga biapoiada]",
                    calcType: "matematico",
                    notes: "Fórmula válida para viga biapoiada com carga concentrada no centro. Para outros casos de carga ou condições de apoio, os coeficientes mudam. Verifique a flecha máxima admissível conforme norma (ex: L/250 para vigas de piso segundo ABNT NBR 6118).",
                    fields: [
                        { id: "eng_P", label: "Carga P (kN)", default: "10" },
                        { id: "eng_L", label: "Comprimento L (m)", default: "5" },
                        { id: "eng_E", label: "Elasticidade E (GPa)", default: "210" },
                        { id: "eng_I", label: "Inércia I (cm⁴)", default: "1000" }
                    ],
                    calc: (vals) => {
                        const P = parseFloat(vals.eng_P) || 0;
                        const L = parseFloat(vals.eng_L) || 0;
                        const E = parseFloat(vals.eng_E) || 0;
                        const I = parseFloat(vals.eng_I) || 0;
                        if (E === 0 || I === 0) return { error: "Divisão por zero!" };
                        // Conversão: P[kN], L[m], E[GPa=10⁶kN/m²], I[cm⁴=10⁻⁸m⁴]
                        // E·I[kN·m²] = E×10⁶ × I×10⁻⁸ = E·I×10⁻²
                        // δ[m] = P·L³ / (48·E·I×10⁻²) = 100·P·L³/(48·E·I)
                        // δ[mm] = 100000·P·L³/(48·E·I)
                        const d = (P * Math.pow(L, 3) * 100000) / (48 * E * I);
                        return { label: "Flecha Máxima δ", value: d, unit: "mm" };
                    }
                },
                "ten-normal": {
                    name: "Tensões Normais (Flexão)",
                    formula: "σ = M·y / I",
                    calcType: "matematico",
                    notes: "Teoria clássica de Euler-Bernoulli. Válida para seções homogêneas e comportamento elástico linear. Comparar com a tensão de escoamento do material e aplicar coeficientes de segurança (ABNT NBR 6118, NBR 8800).",
                    fields: [
                        { id: "eng_M", label: "Momento M (kN·m)", default: "50" },
                        { id: "eng_y", label: "Linha Neutra y (cm)", default: "10" },
                        { id: "eng_I", label: "Inércia I (cm⁴)", default: "5000" }
                    ],
                    calc: (vals) => {
                        const M = parseFloat(vals.eng_M) || 0;
                        const y = parseFloat(vals.eng_y) || 0;
                        const I = parseFloat(vals.eng_I) || 0;
                        if (I === 0) return { error: "Divisão por zero!" };
                        const stress = (1000 * M * y) / I;
                        return { label: "Tensão Máxima σ", value: stress, unit: "MPa" };
                    }
                },
                "ten-cis": {
                    name: "Tensões Cisalhantes",
                    formula: "τmáx = 1,5 · V / (b·h)  [seção retangular]",
                    calcType: "matematico",
                    notes: "Coeficiente 1,5 válido somente para seção retangular. Para seções I, T ou circulares, o coeficiente e a posição da tensão máxima diferem. Comparar com τadm do material (ABNT NBR 7190 para madeira, NBR 8800 para aço).",
                    fields: [
                        { id: "eng_V", label: "Esforço Cortante V (kN)", default: "30" },
                        { id: "eng_b", label: "Base b (cm)", default: "15" },
                        { id: "eng_h", label: "Altura h (cm)", default: "30" }
                    ],
                    calc: (vals) => {
                        const V = parseFloat(vals.eng_V) || 0;
                        const b = parseFloat(vals.eng_b) || 0;
                        const h = parseFloat(vals.eng_h) || 0;
                        if (b === 0 || h === 0) return { error: "Dimensões zeradas!" };
                        const stress = (15 * V) / (b * h);
                        return { label: "Tensão Cisalhante τ", value: stress, unit: "MPa" };
                    }
                },
                "flambagem": {
                    name: "Flambagem (Euler)",
                    formula: "Pcr = π²·E·I / (K·L)²",
                    calcType: "estimativa",
                    notes: "Resultado simplificado (Teoria de Euler, regime elástico). Aplica-se a colunas esbeltas (λ > λlim). Para colunas intermediárias ou curtas, use a fórmula de Rankine ou Johnson. Fator K: engastado-livre=2,0; biapoiado=1,0; engastado-apoiado≈0,7; engastado-engastado=0,5.",
                    fields: [
                        { id: "eng_E", label: "Elasticidade E (GPa)", default: "210" },
                        { id: "eng_I", label: "Inércia I (cm⁴)", default: "1000" },
                        { id: "eng_L", label: "Comprimento L (m)", default: "3" },
                        { id: "eng_K", label: "Fator K (0,5 a 2,0)", default: "1" }
                    ],
                    calc: (vals) => {
                        const E = parseFloat(vals.eng_E) || 0;
                        const I = parseFloat(vals.eng_I) || 0;
                        const L = parseFloat(vals.eng_L) || 0;
                        const K = parseFloat(vals.eng_K) || 1;
                        if (K === 0 || L === 0) return { error: "Comprimento/K zerados!" };
                        const P_cr = (Math.PI * Math.PI * E * I) / (100 * Math.pow(K * L, 2));
                        return { label: "Carga Crítica Pcr", value: P_cr, unit: "kN" };
                    }
                },
                "centroide": {
                    name: "Centroide (Retângulo)",
                    formula: "X̄ = b/2 | Ȳ = h/2",
                    calcType: "matematico",
                    notes: "Válido apenas para seção retangular sólida homogênea. Para seções compostas (I, T, L), calcular o centroide ponderado área a área.",
                    fields: [
                        { id: "eng_b", label: "Base b (cm)", default: "10" },
                        { id: "eng_h", label: "Altura h (cm)", default: "20" }
                    ],
                    calc: (vals) => {
                        const b = parseFloat(vals.eng_b) || 0;
                        const h = parseFloat(vals.eng_h) || 0;
                        return { label: "Centroide (X̄, Ȳ)", value: `${(b/2).toFixed(2)}, ${(h/2).toFixed(2)}`, unit: "cm" };
                    }
                },
                "inercia": {
                    name: "Momento de Inércia",
                    formula: "Ix = b·h³ / 12  [seção retangular]",
                    calcType: "matematico",
                    notes: "Válido apenas para seção retangular em relação ao eixo baricêntrico horizontal. Para outras seções (circular, I, T), as fórmulas são diferentes. Use o Teorema de Steiner para eixos deslocados.",
                    fields: [
                        { id: "eng_b", label: "Base b (cm)", default: "10" },
                        { id: "eng_h", label: "Altura h (cm)", default: "20" }
                    ],
                    calc: (vals) => {
                        const b = parseFloat(vals.eng_b) || 0;
                        const h = parseFloat(vals.eng_h) || 0;
                        const Ix = (b * Math.pow(h, 3)) / 12;
                        return { label: "Inércia Ix", value: Ix, unit: "cm⁴" };
                    }
                }
            }
        },
        fluidos: {
            name: "Fluidos & Hidráulica",
            calcs: {
                "perda-carga": {
                    name: "Perda de Carga (Darcy)",
                    formula: "hf = f · (L/D) · (v²/2g)  [Darcy-Weisbach]",
                    calcType: "estimativa",
                    notes: "Calcula apenas perdas distribuídas (atrito). Não inclui perdas localizadas (curvas, válvulas, etc). O fator de atrito f deve ser determinado pelo diagrama de Moody ou fórmula de Colebrook-White conforme regime de escoamento (Re).",
                    fields: [
                        { id: "eng_f", label: "Fator Atrito f", default: "0.02" },
                        { id: "eng_L", label: "Comprimento L (m)", default: "100" },
                        { id: "eng_D", label: "Diâmetro D (mm)", default: "100" },
                        { id: "eng_v", label: "Velocidade v (m/s)", default: "2" }
                    ],
                    calc: (vals) => {
                        const f = parseFloat(vals.eng_f) || 0;
                        const L = parseFloat(vals.eng_L) || 0;
                        const D = parseFloat(vals.eng_D) || 0;
                        const v = parseFloat(vals.eng_v) || 0;
                        if (D === 0) return { error: "Diâmetro zerado!" };
                        const hf = (1000 * f * L * v * v) / (19.62 * D);
                        return { label: "Perda hf", value: hf, unit: "m" };
                    }
                },
                "manning": {
                    name: "Vazão Canal (Manning)",
                    formula: "Q = (1/n) · A · Rh²/³ · S½",
                    calcType: "estimativa",
                    notes: "Fórmula empírica de Manning, válida para escoamento em regime uniforme permanente. Coeficiente n depende do material e rugosidade do canal (concreto liso: 0.011–0.013; pedra: 0.025–0.030; terra: 0.020–0.030).",
                    fields: [
                        { id: "eng_Rh", label: "Raio Hidráulico Rh (m)", default: "0.5" },
                        { id: "eng_S", label: "Declividade S (m/m)", default: "0.01" },
                        { id: "eng_n", label: "Coef. Manning n", default: "0.013" },
                        { id: "eng_A", label: "Área A (m²)", default: "2" }
                    ],
                    calc: (vals) => {
                        const Rh = parseFloat(vals.eng_Rh) || 0;
                        const S = parseFloat(vals.eng_S) || 0;
                        const n = parseFloat(vals.eng_n) || 0.013;
                        const A = parseFloat(vals.eng_A) || 0;
                        if (n === 0) return { error: "Manning n zerado!" };
                        const Q = (1 / n) * A * Math.pow(Rh, 2/3) * Math.sqrt(S);
                        return { label: "Vazão Q", value: Q, unit: "m³/s" };
                    }
                },
                "reynolds": {
                    name: "Número de Reynolds",
                    formula: "Re = v·D / ν",
                    calcType: "matematico",
                    notes: "Interpretação: Re < 2.300 → Laminar | 2.300–4.000 → Transição | Re > 4.000 → Turbulento. Viscosidade cinémática da água a 20°C ≈ 1,0 × 10⁻⁶ m²/s.",
                    fields: [
                        { id: "eng_v", label: "Velocidade v (m/s)", default: "1.5" },
                        { id: "eng_D", label: "Diâmetro D (m)", default: "0.1" },
                        { id: "eng_nu", label: "Viscosidade (10⁻⁶ m²/s)", default: "1" }
                    ],
                    calc: (vals) => {
                        const v = parseFloat(vals.eng_v) || 0;
                        const D = parseFloat(vals.eng_D) || 0;
                        const nu = parseFloat(vals.eng_nu) || 1;
                        if (nu === 0) return { error: "Viscosidade zerada!" };
                        const Re = (v * D) / (nu * 1e-6);
                        return { label: "Reynolds Re", value: Re, unit: "" };
                    }
                },
                "pres-hidro": {
                    name: "Pressão Hidrostática",
                    formula: "P = ρ · g · h",
                    calcType: "matematico",
                    notes: "Pressão relativa (manométrica) em função da profundidade. Para pressão absoluta, somar a pressão atmosférica (101,325 kPa). Válido para fluido estático e incompressível.",
                    fields: [
                        { id: "eng_rho", label: "Densidade ρ (kg/m³)", default: "1000" },
                        { id: "eng_h", label: "Altura Coluna h (m)", default: "10" }
                    ],
                    calc: (vals) => {
                        const rho = parseFloat(vals.eng_rho) || 0;
                        const h = parseFloat(vals.eng_h) || 0;
                        const P = (rho * 9.80665 * h) / 1000;
                        return { label: "Pressão P", value: P, unit: "kPa" };
                    }
                },
                "vazao-tubo": {
                    name: "Vazão em Tubulação",
                    fields: [
                        { id: "eng_D", label: "Diâmetro D (mm)", default: "50" },
                        { id: "eng_v", label: "Velocidade v (m/s)", default: "2" }
                    ],
                    calc: (vals) => {
                        const D = parseFloat(vals.eng_D) || 0;
                        const v = parseFloat(vals.eng_v) || 0;
                        const Q = (Math.PI * D * D * v) / 4000;
                        return { label: "Vazão Q", value: Q, unit: "L/s" };
                    }
                },
                "pot-bomba": {
                    name: "Potência de Bomba",
                    fields: [
                        { id: "eng_Q", label: "Vazão Q (L/s)", default: "15" },
                        { id: "eng_H", label: "Altura Manométrica H (m)", default: "30" },
                        { id: "eng_eta", label: "Rendimento η (%)", default: "75" }
                    ],
                    calc: (vals) => {
                        const Q = parseFloat(vals.eng_Q) || 0;
                        const H = parseFloat(vals.eng_H) || 0;
                        const eta = parseFloat(vals.eng_eta) || 75;
                        if (eta === 0) return { error: "Rendimento zerado!" };
                        const Pot = (0.981 * Q * H) / eta;
                        return { label: "Potência Bomba", value: Pot, unit: "kW" };
                    }
                },
                "vel-escoa": {
                    name: "Velocidade Escoamento",
                    fields: [
                        { id: "eng_Q", label: "Vazão Q (m³/h)", default: "20" },
                        { id: "eng_D", label: "Diâmetro D (mm)", default: "80" }
                    ],
                    calc: (vals) => {
                        const Q = parseFloat(vals.eng_Q) || 0;
                        const D = parseFloat(vals.eng_D) || 0;
                        if (D === 0) return { error: "Diâmetro zerado!" };
                        const v = (353.68 * Q) / (D * D);
                        return { label: "Velocidade v", value: v, unit: "m/s" };
                    }
                }
            }
        },
        construcao: {
            name: "Construção Civil",
            calcs: {
                "tijolos": {
                    name: "Consumo de Tijolos",
                    fields: [
                        { id: "eng_c", label: "Comp. Tijolo (cm)", default: "19" },
                        { id: "eng_a", label: "Alt. Tijolo (cm)", default: "19" },
                        { id: "eng_j", label: "Espessura Junta (mm)", default: "10" }
                    ],
                    calc: (vals) => {
                        const c = parseFloat(vals.eng_c) || 0;
                        const a = parseFloat(vals.eng_a) || 0;
                        const j = parseFloat(vals.eng_j) || 0;
                        const sum_c = c + (j * 0.1);
                        const sum_a = a + (j * 0.1);
                        if (sum_c === 0 || sum_a === 0) return { error: "Dimensões inválidas!" };
                        const N = 10000 / (sum_c * sum_a);
                        return { label: "Tijolos por m²", value: N, unit: "unid/m²" };
                    }
                },
                "concreto-vol": {
                    name: "Volume de Concreto",
                    fields: [
                        { id: "eng_L", label: "Largura (m)", default: "4" },
                        { id: "eng_C", label: "Comprimento (m)", default: "6" },
                        { id: "eng_E", label: "Espessura (cm)", default: "10" }
                    ],
                    calc: (vals) => {
                        const L = parseFloat(vals.eng_L) || 0;
                        const C = parseFloat(vals.eng_C) || 0;
                        const E = parseFloat(vals.eng_E) || 0;
                        const Vol = L * C * (E * 0.01);
                        return { label: "Volume Total", value: Vol, unit: "m³" };
                    }
                },
                "concreto-traco": {
                    name: "Dosagem de Concreto",
                    fields: [
                        { id: "eng_V", label: "Volume de Concreto (m³)", default: "1" }
                    ],
                    calc: (vals) => {
                        const V = parseFloat(vals.eng_V) || 0;
                        const cimento = V * 350;
                        const areia = V * 0.52;
                        const brita = V * 0.62;
                        const agua = V * 180;
                        return { 
                            label: "Materiais", 
                            value: `Cim: ${cimento.toFixed(0)}kg | Ar: ${areia.toFixed(2)}m³ | Br: ${brita.toFixed(2)}m³`, 
                            unit: `(Água: ${agua.toFixed(0)}L)`
                        };
                    }
                },
                "taxa-armadura": {
                    name: "Taxa de Armadura",
                    fields: [
                        { id: "eng_As", label: "Área de Aço As (cm²)", default: "4.5" },
                        { id: "eng_b", label: "Largura b (cm)", default: "15" },
                        { id: "eng_d", label: "Altura útil d (cm)", default: "35" }
                    ],
                    calc: (vals) => {
                        const As = parseFloat(vals.eng_As) || 0;
                        const b = parseFloat(vals.eng_b) || 0;
                        const d = parseFloat(vals.eng_d) || 0;
                        if (b === 0 || d === 0) return { error: "Dimensões zeradas!" };
                        const rho = (As / (b * d)) * 100;
                        return { label: "Taxa de Aço ρ", value: rho, unit: "%" };
                    }
                },
                "inc-telhado": {
                    name: "Inclinação de Telhado",
                    fields: [
                        { id: "eng_L", label: "Vão Horizontal L (m)", default: "8" },
                        { id: "eng_H", label: "Altura Cumeeira H (m)", default: "2.4" }
                    ],
                    calc: (vals) => {
                        const L = parseFloat(vals.eng_L) || 0;
                        const H = parseFloat(vals.eng_H) || 0;
                        if (L === 0) return { error: "Vão zerado!" };
                        const inc = (H / (L / 2)) * 100;
                        return { label: "Inclinação I", value: inc, unit: "%" };
                    }
                },
                "rup": {
                    name: "RUP (Mão de Obra)",
                    fields: [
                        { id: "eng_HH", label: "Homens-Hora (HH)", default: "40" },
                        { id: "eng_A", label: "Área Realizada (m²)", default: "50" }
                    ],
                    calc: (vals) => {
                        const HH = parseFloat(vals.eng_HH) || 0;
                        const A = parseFloat(vals.eng_A) || 0;
                        if (A === 0) return { error: "Área zerada!" };
                        const RUP = HH / A;
                        return { label: "Produtividade RUP", value: RUP, unit: "HH/m²" };
                    }
                },
                "escavacao": {
                    name: "Volume Escavação",
                    fields: [
                        { id: "eng_L", label: "Comprimento (m)", default: "10" },
                        { id: "eng_b1", label: "Largura Topo (m)", default: "2" },
                        { id: "eng_b2", label: "Largura Base (m)", default: "1.5" },
                        { id: "eng_h", label: "Profundidade (m)", default: "1.2" }
                    ],
                    calc: (vals) => {
                        const L = parseFloat(vals.eng_L) || 0;
                        const b1 = parseFloat(vals.eng_b1) || 0;
                        const b2 = parseFloat(vals.eng_b2) || 0;
                        const h = parseFloat(vals.eng_h) || 0;
                        const Vol = L * ((b1 + b2) / 2) * h;
                        return { label: "Volume Escavado", value: Vol, unit: "m³" };
                    }
                }
            }
        },
        eletrica: {
            name: "Elétrica",
            calcs: {
                "lei-ohm": {
                    name: "Lei de Ohm",
                    formula: "V = I·R | I = V/R | R = V/I",
                    calcType: "matematico",
                    notes: "Relação fundamental válida para circuitos resistivos em corrente contínua. Para CA, a impedância Z substitui R em circuitos com componentes reativos (L e C).",
                    fields: [
                        { id: "eng_modo", label: "Calcular", type: "select", options: [
                            { value: "v", label: "Tensão (V = I * R)" },
                            { value: "i", label: "Corrente (I = V / R)" },
                            { value: "r", label: "Resistência (R = V / I)" }
                        ]},
                        { id: "eng_val1", label: "Corrente I (A) / Tensão V (V)", default: "2" },
                        { id: "eng_val2", label: "Resistência (Ω) / Corrente I (A)", default: "10" }
                    ],
                    calc: (vals) => {
                        const modo = vals.eng_modo;
                        const val1 = parseFloat(vals.eng_val1) || 0;
                        const val2 = parseFloat(vals.eng_val2) || 0;
                        if (modo === 'v') {
                            return { label: "Tensão V", value: val1 * val2, unit: "V" };
                        } else if (modo === 'i') {
                            if (val2 === 0) return { error: "Resistência zerada!" };
                            return { label: "Corrente I", value: val1 / val2, unit: "A" };
                        } else {
                            if (val2 === 0) return { error: "Corrente zerada!" };
                            return { label: "Resistência R", value: val1 / val2, unit: "Ω" };
                        }
                    }
                },
                "pot-mono": {
                    name: "Potência Monofásica",
                    formula: "P = V · I · FP",
                    calcType: "matematico",
                    notes: "FP (Fator de Potência) típico: motores = 0,80–0,92; aquecimentos resistivos = 1,0; lâmpadas fluorescentes = 0,90–0,95. Potência Aparente S = V·I (kVA); Potência Reativa Q = S·sen(φ) (kVAr).",
                    fields: [
                        { id: "eng_V", label: "Tensão V (V)", default: "220" },
                        { id: "eng_I", label: "Corrente I (A)", default: "10" },
                        { id: "eng_FP", label: "Fator Potência FP", default: "0.92" }
                    ],
                    calc: (vals) => {
                        const V = parseFloat(vals.eng_V) || 0;
                        const I = parseFloat(vals.eng_I) || 0;
                        const FP = parseFloat(vals.eng_FP) || 1;
                        const P = V * I * FP;
                        return { label: "Potência Ativa P", value: P / 1000, unit: "kW" };
                    }
                },
                "pot-tri": {
                    name: "Potência Trifásica",
                    formula: "P = √3 · VL · I · FP",
                    calcType: "matematico",
                    notes: "Tensao de linha (entre fases): 380V redes brasileiras. Tensão de fase (entre fase e neutro): ≈ 220V. FP típico de motores indutivos trifásicos: 0,80–0,92. Resultado em kW (potência ativa). Potência aparente S = √3·VL·I (kVA).",
                    fields: [
                        { id: "eng_V", label: "Tensão de Linha VL (V)", default: "380" },
                        { id: "eng_I", label: "Corrente I (A)", default: "15" },
                        { id: "eng_FP", label: "Fator Potência FP", default: "0.85" }
                    ],
                    calc: (vals) => {
                        const V = parseFloat(vals.eng_V) || 0;
                        const I = parseFloat(vals.eng_I) || 0;
                        const FP = parseFloat(vals.eng_FP) || 1;
                        const P = Math.sqrt(3) * V * I * FP;
                        return { label: "Potência Ativa P", value: P / 1000, unit: "kW" };
                    }
                },
                "queda-tensao": {
                    name: "Queda de Tensão",
                    formula: "ΔV = 2 · ρCu · L · I / S  [ρCu = 0,0172 Ω·mm²/m]",
                    calcType: "normativo",
                    notes: "Resultado para cabo de cobre em circuito monofásico (ida e volta = fator 2). ABNT NBR 5410: máximo 7% (distribuição) ou 4% (final). Para alumínio, usar ρAl = 0,0282. Para trifásico, remover fator 2 e usar √3.",
                    fields: [
                        { id: "eng_I", label: "Corrente I (A)", default: "20" },
                        { id: "eng_L", label: "Comprimento L (m)", default: "50" },
                        { id: "eng_S", label: "Seção Cabo (mm²)", default: "4" }
                    ],
                    calc: (vals) => {
                        const I = parseFloat(vals.eng_I) || 0;
                        const L = parseFloat(vals.eng_L) || 0;
                        const S = parseFloat(vals.eng_S) || 4;
                        if (S === 0) return { error: "Seção zerada!" };
                        const dV = (2 * 0.0172 * L * I) / S;
                        return { label: "Queda de Tensão ΔV", value: dV, unit: "V" };
                    }
                },
                "dim-condutor": {
                    name: "Corrente de Projeto",
                    formula: "I = P / (V · FP)  [circuito monofásico]",
                    calcType: "estimativa",
                    notes: "⚠️ Este cálculo fornece APENAS a corrente de projeto (Ip). O dimensionamento completo de condutores e disjuntores requer adicionalmente: (1) fator de correcção de temperatura; (2) agrupamento de cabos; (3) verificação de queda de tensão; (4) capacidade de conducção das Tabelas B1/B2 da ABNT NBR 5410. Não use este valor isolado para selecionar bitola ou disjuntor.",
                    fields: [
                        { id: "eng_P", label: "Potência P (W)", default: "5000" },
                        { id: "eng_V", label: "Tensão V (V)", default: "220" },
                        { id: "eng_FP", label: "Fator Potência FP", default: "0.9" }
                    ],
                    calc: (vals) => {
                        const P = parseFloat(vals.eng_P) || 0;
                        const V = parseFloat(vals.eng_V) || 0;
                        const FP = parseFloat(vals.eng_FP) || 1;
                        if (V === 0 || FP === 0) return { error: "Parâmetros inválidos!" };
                        const I = P / (V * FP);
                        return { label: "Corrente Projeto I", value: I, unit: "A" };
                    }
                },
                "imp-rlc": {
                    name: "Impedância RLC Série",
                    formula: "Z = √(R² + (XL-XC)²)  |  XL = 2πfL  |  XC = 1/(2πfC)",
                    calcType: "matematico",
                    notes: "Válido para circuito série RLC ideal em regime senoidal permanente. Ângulo de fase: φ = arctg((XL-XC)/R). Para circuito paralelo, o cálculo da admitância é diferente.",
                    fields: [
                        { id: "eng_R", label: "Resistência R (Ω)", default: "120" },
                        { id: "eng_L", label: "Indutância L (mH)", default: "150" },
                        { id: "eng_C", label: "Capacitância C (μF)", default: "4.7" },
                        { id: "eng_f", label: "Frequência f (Hz)", default: "60" }
                    ],
                    calc: (vals) => {
                        const R = parseFloat(vals.eng_R) || 0;
                        const L = parseFloat(vals.eng_L) || 0;
                        const C = parseFloat(vals.eng_C) || 1;
                        const f = parseFloat(vals.eng_f) || 60;
                        if (f === 0 || C === 0) return { error: "Parâmetros inválidos!" };
                        const XL = 2 * Math.PI * f * (L * 1e-3);
                        const XC = 1 / (2 * Math.PI * f * (C * 1e-6));
                        const Z = Math.sqrt(R*R + Math.pow(XL - XC, 2));
                        return { label: "Impedância Z", value: Z, unit: "Ω" };
                    }
                },
                "ressonancia": {
                    name: "Frequência Ressonância",
                    formula: "fr = 1 / (2π√(L·C))",
                    calcType: "matematico",
                    notes: "Frequência na qual a reatância indutiva XL se iguala à capacitiva XC, resultando em impedância mínima (série) ou máxima (paralelo). Aplica-se a circuitos ideais sem perdas ôhmicas significativas.",
                    fields: [
                        { id: "eng_L", label: "Indutância L (mH)", default: "10" },
                        { id: "eng_C", label: "Capacitância C (μF)", default: "100" }
                    ],
                    calc: (vals) => {
                        const L = parseFloat(vals.eng_L) || 0;
                        const C = parseFloat(vals.eng_C) || 0;
                        if (L === 0 || C === 0) return { error: "L ou C zerados!" };
                        const fr = 1 / (2 * Math.PI * Math.sqrt((L * 1e-3) * (C * 1e-6)));
                        return { label: "Frequência fr", value: fr, unit: "Hz" };
                    }
                }
            }
        },
        eletronica: {
            name: "Eletrônica",
            calcs: {
                "led-res": {
                    name: "Resistor para LED",
                    formula: "R = (Vcc - Vd) / Id",
                    calcType: "matematico",
                    notes: "Fórmula clássica para limitar corrente em LED. Sempre use o valor comercial de resistor imediatamente superior ao calculado (série E12 ou E24). Corrente típica de LED: 10–20 mA (vermelho/verde/amarelo) e 5–15 mA (azul/branco).",
                    fields: [
                        { id: "eng_Vcc", label: "Tensão Fonte Vcc (V)", default: "12" },
                        { id: "eng_Vd", label: "Tensão LED Vd (V)", default: "2" },
                        { id: "eng_Id", label: "Corrente LED Id (mA)", default: "20" }
                    ],
                    calc: (vals) => {
                        const Vcc = parseFloat(vals.eng_Vcc) || 0;
                        const Vd = parseFloat(vals.eng_Vd) || 0;
                        const Id = parseFloat(vals.eng_Id) || 20;
                        if (Id === 0) return { error: "Corrente zerada!" };
                        const R = (1000 * (Vcc - Vd)) / Id;
                        return { label: "Resistência R", value: R, unit: "Ω" };
                    }
                },
                "filtro-rc": {
                    name: "Corte Passa-Baixas RC",
                    formula: "fc = 1 / (2π·R·C)",
                    calcType: "matematico",
                    notes: "Frequência de corte (−3 dB) de filtro passa-baixas RC de primeira ordem. Para filtro passa-altas RC, a fórmula é a mesma, mas a saída é tomada sobre R. Filtros de ordem superior (Butterworth, Chebyshev) requerem cálculo diferente.",
                    fields: [
                        { id: "eng_R", label: "Resistência R (kΩ)", default: "10" },
                        { id: "eng_C", label: "Capacitância C (μF)", default: "0.1" }
                    ],
                    calc: (vals) => {
                        const R = parseFloat(vals.eng_R) || 0;
                        const C = parseFloat(vals.eng_C) || 0;
                        if (R === 0 || C === 0) return { error: "R ou C zerados!" };
                        const fc = 1000 / (2 * Math.PI * R * C);
                        return { label: "Corte fc", value: fc, unit: "Hz" };
                    }
                },
                "div-tensao": {
                    name: "Divisor de Tensão",
                    formula: "Vout = Vin · R2 / (R1 + R2)",
                    calcType: "matematico",
                    notes: "Válido apenas sem carga (R_carga → ∞). Com carga conectada, a tensão cai pois R2 e R_carga ficam em paralelo. Para circuitos com carga, use um buffer (seguidor de tensão com op-amp).",
                    fields: [
                        { id: "eng_Vin", label: "Tensão Entrada Vin (V)", default: "5" },
                        { id: "eng_R1", label: "Resistor R1 (kΩ)", default: "1" },
                        { id: "eng_R2", label: "Resistor R2 (kΩ)", default: "2" }
                    ],
                    calc: (vals) => {
                        const Vin = parseFloat(vals.eng_Vin) || 0;
                        const R1 = parseFloat(vals.eng_R1) || 0;
                        const R2 = parseFloat(vals.eng_R2) || 0;
                        if (R1 + R2 === 0) return { error: "Soma resistências zerada!" };
                        const Vout = Vin * (R2 / (R1 + R2));
                        return { label: "Tensão Saída Vout", value: Vout, unit: "V" };
                    }
                },
                "timer-555": {
                    name: "Oscilador 555 Astável",
                    formula: "f = 1,44 / ((R1+2·R2)·C)",
                    calcType: "matematico",
                    notes: "Fórmula para o circuito NE555 em modo astável. Duty cycle: D = (R1+R2)/(R1+2R2). Para duty cycle de 50%, use um diodo em paralelo com R2. Valor de C em microfarads e R em kilôhms.",
                    fields: [
                        { id: "eng_R1", label: "Resistor R1 (kΩ)", default: "1" },
                        { id: "eng_R2", label: "Resistor R2 (kΩ)", default: "10" },
                        { id: "eng_C", label: "Capacitância C (μF)", default: "10" }
                    ],
                    calc: (vals) => {
                        const R1 = parseFloat(vals.eng_R1) || 0;
                        const R2 = parseFloat(vals.eng_R2) || 0;
                        const C = parseFloat(vals.eng_C) || 0;
                        if (C === 0 || (R1 + 2*R2) === 0) return { error: "Parâmetros inválidos!" };
                        const f = 1440 / ((R1 + 2*R2) * C);
                        return { label: "Frequência f", value: f, unit: "Hz" };
                    }
                },
                "opamp-gain": {
                    name: "Ganho OP-AMP Não-Inv.",
                    formula: "Av = 1 + Rf/Rin",
                    calcType: "matematico",
                    notes: "Para amplificador inversor: Av = -Rf/Rin. Para seguidor de tensão (buffer): Av = 1 (Rf=0, Rin=∞). Limitações práticas: banda de ganho (GBW) e tensão de alimentação do op-amp definem o ganho máximo real.",
                    fields: [
                        { id: "eng_Rf", label: "Resistência Realim Rf (kΩ)", default: "10" },
                        { id: "eng_Rin", label: "Resistência Entrada Rin (kΩ)", default: "1" }
                    ],
                    calc: (vals) => {
                        const Rf = parseFloat(vals.eng_Rf) || 0;
                        const Rin = parseFloat(vals.eng_Rin) || 1;
                        if (Rin === 0) return { error: "Rin zerado!" };
                        const Av = 1 + (Rf / Rin);
                        return { label: "Ganho Tensão Av", value: Av, unit: "" };
                    }
                },
                "res-cores": {
                    name: "Cores de Resistor (4F)",
                    formula: "R = (F1·10 + F2) × Multiplicador",
                    calcType: "matematico",
                    notes: "Decodificação de resistores de 4 faixas. A 4ª faixa (tolerância) não é incluída aqui: Marrom=±1%, Vermelho=±2%, Ouro=±5%, Prata=±10%. Para resistores de 5 faixas, há 3 dígitos significativos.",
                    fields: [
                        { id: "eng_f1", label: "Faixa 1", type: "select", options: [
                            { value: "0", label: "Preto (0)" }, { value: "1", label: "Marrom (1)" },
                            { value: "2", label: "Vermelho (2)" }, { value: "3", label: "Laranja (3)" },
                            { value: "4", label: "Amarelo (4)" }, { value: "5", label: "Verde (5)" },
                            { value: "6", label: "Azul (6)" }, { value: "7", label: "Violeta (7)" },
                            { value: "8", label: "Cinza (8)" }, { value: "9", label: "Branco (9)" }
                        ]},
                        { id: "eng_f2", label: "Faixa 2", type: "select", options: [
                            { value: "0", label: "Preto (0)" }, { value: "1", label: "Marrom (1)" },
                            { value: "2", label: "Vermelho (2)" }, { value: "3", label: "Laranja (3)" },
                            { value: "4", label: "Amarelo (4)" }, { value: "5", label: "Verde (5)" },
                            { value: "6", label: "Azul (6)" }, { value: "7", label: "Violeta (7)" },
                            { value: "8", label: "Cinza (8)" }, { value: "9", label: "Branco (9)" }
                        ]},
                        { id: "eng_mult", label: "Multiplicador", type: "select", options: [
                            { value: "1", label: "Preto (x1)" }, { value: "10", label: "Marrom (x10)" },
                            { value: "100", label: "Vermelho (x100)" }, { value: "1000", label: "Laranja (x1k)" },
                            { value: "10000", label: "Amarelo (x10k)" }, { value: "100000", label: "Verde (x100k)" },
                            { value: "1000000", label: "Azul (x1M)" }
                        ]}
                    ],
                    calc: (vals) => {
                        const f1 = parseInt(vals.eng_f1) || 0;
                        const f2 = parseInt(vals.eng_f2) || 0;
                        const mult = parseInt(vals.eng_mult) || 1;
                        const value = (f1 * 10 + f2) * mult;
                        let outputStr = value + " Ω";
                        if (value >= 1e6) outputStr = (value / 1e6).toFixed(1) + " MΩ";
                        else if (value >= 1e3) outputStr = (value / 1e3).toFixed(1) + " kΩ";
                        return { label: "Resistência Nominal", value: outputStr, unit: "" };
                    }
                },
                "db-ratio": {
                    name: "Decibéis para Razão",
                    fields: [
                        { id: "eng_db", label: "Ganho dB", default: "20" }
                    ],
                    calc: (vals) => {
                        const db = parseFloat(vals.eng_db) || 0;
                        const ratio = Math.pow(10, db / 10);
                        return { label: "Razão de Potência", value: ratio, unit: "x" };
                    }
                }
            }
        },
        mecanica: {
            name: "Mecânica",
            calcs: {
                "torque": {
                    name: "Torque (Momento)",
                    formula: "T = F · d",
                    calcType: "matematico",
                    notes: "Torque em relação a um eixo quando a força é perpendicular ao braço. Para força com ângulo θ: T = F·d·sen(θ). Em motores elétricos: Torque não é constante com a velocidade; consulte a curva característica do fabricante.",
                    fields: [
                        { id: "eng_F", label: "Força Aplicada F (N)", default: "150" },
                        { id: "eng_d", label: "Braço d (m)", default: "0.5" }
                    ],
                    calc: (vals) => {
                        const F = parseFloat(vals.eng_F) || 0;
                        const d = parseFloat(vals.eng_d) || 0;
                        return { label: "Torque T", value: F * d, unit: "N·m" };
                    }
                },
                "torcao-stress": {
                    name: "Tensão de Torção",
                    formula: "τ = T · r / J  [seção circular sólida]",
                    calcType: "matematico",
                    notes: "Válido para eixo macicço de seção circular. Para eixo tubular, J = π(D⁴-d⁴)/32. Verificar contra tensão de escoamento ao cisalhamento: τadm = σescoamento / (2·FS).",
                    fields: [
                        { id: "eng_T", label: "Torque T (N·m)", default: "200" },
                        { id: "eng_r", label: "Raio Externo Eixo (mm)", default: "25" },
                        { id: "eng_J", label: "Inércia Polar J (cm⁴)", default: "50" }
                    ],
                    calc: (vals) => {
                        const T = parseFloat(vals.eng_T) || 0;
                        const r = parseFloat(vals.eng_r) || 0;
                        const J = parseFloat(vals.eng_J) || 0;
                        if (J === 0) return { error: "Inércia polar zerada!" };
                        // Conversão: T[N·m]×1000=[N·mm], r[mm], J[cm⁴]×10000=[mm⁴]
                        // τ[MPa] = T[N·mm]·r[mm] / J[mm⁴] = (T×1000)·r / (J×10000)
                        // = T·r / (J×10)
                        const stress = (T * r) / (J * 10);
                        return { label: "Tensão Torção τ", value: stress, unit: "MPa" };
                    }
                },
                "pot-motor": {
                    name: "Potência de Motor",
                    formula: "P = T · ω = T · 2πN / 60",
                    calcType: "matematico",
                    notes: "Relação entre potência mecânica, torque e velocidade angular. Para converter entre unidades: 1 CV = 735,5 W = 0,7355 kW. Potência nominal de placa do motor difere da potência consumida no eixo (considerar rendimento).",
                    fields: [
                        { id: "eng_T", label: "Torque T (N·m)", default: "120" },
                        { id: "eng_N", label: "Velocidade N (RPM)", default: "3000" }
                    ],
                    calc: (vals) => {
                        const T = parseFloat(vals.eng_T) || 0;
                        const N = parseFloat(vals.eng_N) || 0;
                        const Pot = (T * 2 * Math.PI * N) / 60000;
                        return { label: "Potência Motor", value: Pot, unit: "kW" };
                    }
                },
                "rel-trans": {
                    name: "Relação Transmissão",
                    formula: "i = Z2 / Z1 = N1 / N2",
                    calcType: "matematico",
                    notes: "Relação de transmissão entre duas engrenagens. i > 1: redução de velocidade. i < 1: multiplicação de velocidade. Para cadeias e correias, usar o diâmetro das polias ou número de dentes dos spróckets.",
                    fields: [
                        { id: "eng_Z1", label: "Dentes Condutora Z1", default: "20" },
                        { id: "eng_Z2", label: "Dentes Conduzida Z2", default: "40" }
                    ],
                    calc: (vals) => {
                        const Z1 = parseFloat(vals.eng_Z1) || 1;
                        const Z2 = parseFloat(vals.eng_Z2) || 1;
                        if (Z1 === 0) return { error: "Z1 zerada!" };
                        return { label: "Relação i", value: Z2 / Z1, unit: ":1" };
                    }
                },
                "pistao-pres": {
                    name: "Pressão de Pistão",
                    formula: "P = F / A = 4F / (πD²)",
                    calcType: "matematico",
                    notes: "Pressão gerada por força num pistão circular. Para pressão em bar: dividir o resultado em kPa por 100. Considerar perdás por atrito (η ≈ 85–95%) em cilindros pneumáticos e hidráulicos reais.",
                    fields: [
                        { id: "eng_F", label: "Força de Empuxo F (N)", default: "500" },
                        { id: "eng_D", label: "Diâmetro Cilindro D (mm)", default: "40" }
                    ],
                    calc: (vals) => {
                        const F = parseFloat(vals.eng_F) || 0;
                        const D = parseFloat(vals.eng_D) || 0;
                        if (D === 0) return { error: "Diâmetro zerado!" };
                        const P = (4000 * F) / (Math.PI * D * D);
                        return { label: "Pressão Interna P", value: P, unit: "kPa" };
                    }
                },
                "dil-termica": {
                    name: "Dilatação Linear",
                    formula: "ΔL = L0 · α · ΔT",
                    calcType: "matematico",
                    notes: "Coeficientes α típicos (×10⁻⁶/°C): aço=12; aluminíio=23; cobre=17; concreto=12; vidro=9; PVC=80. Importante em estruturas metálicas, tubulações e trilhos para dimensionar juntas de dilatacção.",
                    fields: [
                        { id: "eng_L0", label: "Comprimento Inicial L0 (m)", default: "10" },
                        { id: "eng_alpha", label: "Coef. α (10⁻⁶/°C)", default: "12" },
                        { id: "eng_dT", label: "Variação Temp. ΔT (°C)", default: "40" }
                    ],
                    calc: (vals) => {
                        const L0 = parseFloat(vals.eng_L0) || 0;
                        const alpha = parseFloat(vals.eng_alpha) || 0;
                        const dT = parseFloat(vals.eng_dT) || 0;
                        const dL = L0 * alpha * dT * 0.001;
                        return { label: "Variação Comp. ΔL", value: dL, unit: "mm" };
                    }
                },
                "polar-j": {
                    name: "Momento Polar J",
                    formula: "J = πD⁴ / 32  [eixo maciço circular]",
                    calcType: "matematico",
                    notes: "Para eixo circular maciço. Eixo tubular: J = π(D⁴-d⁴)/32. O momento polar J é usado no cálculo de tensão de torção (τ=T·r/J) e no ângulo de twist (φ=T·L/(G·J)).",
                    fields: [
                        { id: "eng_D", label: "Diâmetro Eixo D (mm)", default: "50" }
                    ],
                    calc: (vals) => {
                        const D = parseFloat(vals.eng_D) || 0;
                        const J = (Math.PI * Math.pow(D * 0.1, 4)) / 32;
                        return { label: "Momento Polar J", value: J, unit: "cm⁴" };
                    }
                }
            }
        },
        quimica: {
            name: "Química",
            calcs: {
                "gases-ideais": {
                    name: "Mols Gases Ideais (n)",
                    fields: [
                        { id: "eng_P", label: "Pressão P (atm)", default: "1" },
                        { id: "eng_V", label: "Volume V (L)", default: "22.4" },
                        { id: "eng_T", label: "Temperatura T (°C)", default: "0" }
                    ],
                    calc: (vals) => {
                        const P = parseFloat(vals.eng_P) || 0;
                        const V = parseFloat(vals.eng_V) || 0;
                        const T = parseFloat(vals.eng_T) || 0;
                        const absT = T + 273.15;
                        if (absT === 0) return { error: "Zero absoluto!" };
                        const n = (P * V) / (0.08206 * absT);
                        return { label: "Quantidade de Matéria n", value: n, unit: "mol" };
                    }
                },
                "molaridade": {
                    name: "Concentração (M)",
                    fields: [
                        { id: "eng_m", label: "Massa do Soluto (g)", default: "5.8" },
                        { id: "eng_MM", label: "Massa Molar (g/mol)", default: "58.4" },
                        { id: "eng_V", label: "Volume Total V (L)", default: "1" }
                    ],
                    calc: (vals) => {
                        const m = parseFloat(vals.eng_m) || 0;
                        const MM = parseFloat(vals.eng_MM) || 0;
                        const V = parseFloat(vals.eng_V) || 0;
                        if (MM === 0 || V === 0) return { error: "MM ou Volume zerados!" };
                        const M = m / (MM * V);
                        return { label: "Molaridade M", value: M, unit: "mol/L" };
                    }
                },
                "diluicao": {
                    name: "Diluição Soluções",
                    fields: [
                        { id: "eng_C1", label: "Conc. Inicial C1", default: "1" },
                        { id: "eng_V1", label: "Volume Inicial V1 (L)", default: "0.2" },
                        { id: "eng_C2", label: "Conc. Final C2", default: "0.1" }
                    ],
                    calc: (vals) => {
                        const C1 = parseFloat(vals.eng_C1) || 0;
                        const V1 = parseFloat(vals.eng_V1) || 0;
                        const C2 = parseFloat(vals.eng_C2) || 0;
                        if (C2 === 0) return { error: "C2 zerada!" };
                        const V2 = (C1 * V1) / C2;
                        return { label: "Volume Final V2", value: V2, unit: "L" };
                    }
                },
                "ph": {
                    name: "Cálculo de pH",
                    fields: [
                        { id: "eng_H", label: "Concentração [H+] (mol/L)", default: "0.001" }
                    ],
                    calc: (vals) => {
                        const H = parseFloat(vals.eng_H) || 0;
                        if (H <= 0) return { error: "[H+] inválido!" };
                        const phVal = -Math.log10(H);
                        return { label: "Potencial Hidrog. pH", value: phVal, unit: "" };
                    }
                },
                "calor-reacao": {
                    name: "Calor de Reação Q",
                    fields: [
                        { id: "eng_m", label: "Massa m (g)", default: "100" },
                        { id: "eng_c", label: "Calor Esp. c (J/g°C)", default: "4.18" },
                        { id: "eng_dT", label: "Variação Temp. ΔT (°C)", default: "15" }
                    ],
                    calc: (vals) => {
                        const m = parseFloat(vals.eng_m) || 0;
                        const c = parseFloat(vals.eng_c) || 0;
                        const dT = parseFloat(vals.eng_dT) || 0;
                        const Q = m * c * dT;
                        return { label: "Calor Trocado Q", value: Q / 1000, unit: "kJ" };
                    }
                },
                "arrhenius": {
                    name: "Constante Arrhenius k",
                    fields: [
                        { id: "eng_A", label: "Fator Freq. A (s⁻¹)", default: "1e11" },
                        { id: "eng_Ea", label: "Energia Ativ. Ea (kJ/mol)", default: "50" },
                        { id: "eng_T", label: "Temperatura T (°C)", default: "25" }
                    ],
                    calc: (vals) => {
                        const A = parseFloat(vals.eng_A) || 0;
                        const Ea = parseFloat(vals.eng_Ea) || 0;
                        const T = parseFloat(vals.eng_T) || 0;
                        const R_const = 8.314;
                        const absT = T + 273.15;
                        if (absT === 0) return { error: "Zero absoluto!" };
                        const k = A * Math.exp(-(Ea * 1000) / (R_const * absT));
                        return { label: "Const. Velocidade k", value: k, unit: "s⁻¹" };
                    }
                },
                "estequiometria": {
                    name: "Relação Estequiom.",
                    fields: [
                        { id: "eng_molA", label: "Mols Reagente A", default: "2" },
                        { id: "eng_coefA", label: "Coeficiente A", default: "1" },
                        { id: "eng_coefB", label: "Coeficiente B", default: "2" }
                    ],
                    calc: (vals) => {
                        const molA = parseFloat(vals.eng_molA) || 0;
                        const coefA = parseFloat(vals.eng_coefA) || 1;
                        const coefB = parseFloat(vals.eng_coefB) || 1;
                        if (coefA === 0) return { error: "Coef. A zerado!" };
                        const molB = molA * (coefB / coefA);
                        return { label: "Mols Necessários B", value: molB, unit: "mol" };
                    }
                }
            }
        },
        producao: {
            name: "Produção",
            calcs: {
                "tempo-ciclo": {
                    name: "Tempo de Ciclo (TC)",
                    fields: [
                        { id: "eng_T", label: "Tempo Produtivo (h)", default: "8" },
                        { id: "eng_Q", label: "Qtd Produzida", default: "1000" }
                    ],
                    calc: (vals) => {
                        const T = parseFloat(vals.eng_T) || 0;
                        const Q = parseFloat(vals.eng_Q) || 1;
                        if (Q === 0) return { error: "Quantidade zerada!" };
                        const tc = (T * 3600) / Q;
                        return { label: "Tempo de Ciclo TC", value: tc, unit: "s/unid" };
                    }
                },
                "oee": {
                    name: "Eficiência OEE",
                    fields: [
                        { id: "eng_disp", label: "Disponibilidade (%)", default: "90" },
                        { id: "eng_perf", label: "Performance (%)", default: "95" },
                        { id: "eng_qual", label: "Qualidade (%)", default: "98" }
                    ],
                    calc: (vals) => {
                        const disp = parseFloat(vals.eng_disp) || 0;
                        const perf = parseFloat(vals.eng_perf) || 0;
                        const qual = parseFloat(vals.eng_qual) || 0;
                        const oee = (disp * perf * qual) / 10000;
                        return { label: "Eficiência OEE", value: oee, unit: "%" };
                    }
                },
                "leo": {
                    name: "Lote Econômico LEC",
                    fields: [
                        { id: "eng_D", label: "Demanda Anual D", default: "5000" },
                        { id: "eng_S", label: "Custo por Pedido S ($)", default: "50" },
                        { id: "eng_H", label: "Custo Estocagem H ($/un)", default: "5" }
                    ],
                    calc: (vals) => {
                        const D = parseFloat(vals.eng_D) || 0;
                        const S = parseFloat(vals.eng_S) || 0;
                        const H = parseFloat(vals.eng_H) || 1;
                        if (H === 0) return { error: "Custo estocagem zerado!" };
                        const lec = Math.sqrt((2 * D * S) / H);
                        return { label: "Lote Econômico LEC", value: lec, unit: "unid" };
                    }
                },
                "capacidade": {
                    name: "Capacidade de Linha",
                    fields: [
                        { id: "eng_T", label: "Tempo Operação (min)", default: "480" },
                        { id: "eng_tg", label: "Gargalo Unitário (s)", default: "45" }
                    ],
                    calc: (vals) => {
                        const T = parseFloat(vals.eng_T) || 0;
                        const tg = parseFloat(vals.eng_tg) || 1;
                        if (tg === 0) return { error: "Tempo gargalo zerado!" };
                        const cap = (T * 60) / tg;
                        return { label: "Capacidade Produção", value: cap, unit: "unid" };
                    }
                },
                "ppm-defeitos": {
                    name: "Taxa de Defeito (PPM)",
                    fields: [
                        { id: "eng_def", label: "Peças Defeituosas", default: "5" },
                        { id: "eng_total", label: "Total Produzido", default: "25000" }
                    ],
                    calc: (vals) => {
                        const def = parseFloat(vals.eng_def) || 0;
                        const total = parseFloat(vals.eng_total) || 1;
                        if (total === 0) return { error: "Total zerado!" };
                        const ppm = (def / total) * 1e6;
                        return { label: "Defeitos", value: ppm, unit: "PPM" };
                    }
                },
                "prod-global": {
                    name: "Produtividade Global",
                    fields: [
                        { id: "eng_fat", label: "Faturamento ($)", default: "15000" },
                        { id: "eng_mo", label: "Custo Mão de Obra ($)", default: "3000" },
                        { id: "eng_mat", label: "Custo Materiais ($)", default: "5000" }
                    ],
                    calc: (vals) => {
                        const fat = parseFloat(vals.eng_fat) || 0;
                        const mo = parseFloat(vals.eng_mo) || 0;
                        const mat = parseFloat(vals.eng_mat) || 0;
                        const den = mo + mat;
                        if (den === 0) return { error: "Custos zerados!" };
                        const prod = fat / den;
                        return { label: "Produtividade Global", value: prod, unit: "" };
                    }
                },
                "giro-estoque": {
                    name: "Giro de Estoque",
                    fields: [
                        { id: "eng_cogs", label: "Custo Vendas COGS ($)", default: "80000" },
                        { id: "eng_est", label: "Estoque Médio ($)", default: "15000" }
                    ],
                    calc: (vals) => {
                        const cogs = parseFloat(vals.eng_cogs) || 0;
                        const est = parseFloat(vals.eng_est) || 1;
                        if (est === 0) return { error: "Estoque médio zerado!" };
                        const giro = cogs / est;
                        return { label: "Giro Anual", value: giro, unit: "giros/ano" };
                    }
                }
            }
        }
    };

    function setupEngineeringEvents() {
        const searchInput = document.getElementById('engSearch');
        const categorySelect = document.getElementById('engCategory');
        const calculationSelect = document.getElementById('engCalculation');
        const inputsContainer = document.getElementById('engInputsContainer');
        const calculateBtn = document.getElementById('btnEngCalculate');

        if (!categorySelect || !calculationSelect || !inputsContainer || !calculateBtn) return;

        categorySelect.addEventListener('change', () => {
            const cat = categorySelect.value;
            if (cat) {
                if (searchInput && searchInput.value) searchInput.value = '';
                populateCalculationsSelect(cat);
            }
        });

        calculationSelect.addEventListener('change', () => {
            const cat_calc = calculationSelect.value;
            if (!cat_calc) return;
            const parts = cat_calc.split(':');
            const cat = parts[0];
            const calcId = parts[1];

            if (categorySelect.value !== cat && cat) {
                categorySelect.value = cat;
            }

            renderCalculationFields(cat, calcId);
        });

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                const query = searchInput.value.toLowerCase().trim();
                if (!query) {
                    const activeCat = categorySelect.value || 'estruturas';
                    categorySelect.value = activeCat;
                    populateCalculationsSelect(activeCat);
                    return;
                }

                calculationSelect.innerHTML = '';
                let hasResults = false;
                categorySelect.value = '';

                for (const catId in engCalculations) {
                    const catObj = engCalculations[catId];
                    for (const calcId in catObj.calcs) {
                        const calcObj = catObj.calcs[calcId];
                        if (calcObj.name.toLowerCase().includes(query) || catObj.name.toLowerCase().includes(query)) {
                            const opt = document.createElement('option');
                            opt.value = `${catId}:${calcId}`;
                            opt.innerText = `${calcObj.name} (${catObj.name})`;
                            calculationSelect.appendChild(opt);
                            hasResults = true;
                        }
                    }
                }

                if (hasResults) {
                    calculationSelect.dispatchEvent(new Event('change'));
                } else {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.innerText = 'Nenhum cálculo encontrado';
                    calculationSelect.appendChild(opt);
                    inputsContainer.innerHTML = '';
                }
            });
        }

        calculateBtn.addEventListener('click', () => {
            runEngineeringCalculation();
        });

        categorySelect.value = 'estruturas';
        populateCalculationsSelect('estruturas');
    }

    function populateCalculationsSelect(catId) {
        const calculationSelect = document.getElementById('engCalculation');
        if (!calculationSelect || !engCalculations[catId]) return;

        calculationSelect.innerHTML = '';
        const catObj = engCalculations[catId];
        let firstCalc = null;

        for (const calcId in catObj.calcs) {
            const calcObj = catObj.calcs[calcId];
            const opt = document.createElement('option');
            opt.value = `${catId}:${calcId}`;
            opt.innerText = calcObj.name;
            calculationSelect.appendChild(opt);
            if (!firstCalc) firstCalc = calcId;
        }

        if (firstCalc) {
            calculationSelect.value = `${catId}:${firstCalc}`;
            calculationSelect.dispatchEvent(new Event('change'));
        }
    }

    function renderCalculationFields(catId, calcId) {
        const inputsContainer = document.getElementById('engInputsContainer');
        if (!inputsContainer || !engCalculations[catId] || !engCalculations[catId].calcs[calcId]) return;

        inputsContainer.innerHTML = '';
        const calcObj = engCalculations[catId].calcs[calcId];

        calcObj.fields.forEach(f => {
            const row = document.createElement('div');
            row.className = 'input-row';
            row.style.marginBottom = '4px';

            const label = document.createElement('label');
            label.className = 'panel-label';
            label.innerText = f.label;
            row.appendChild(label);

            if (f.type === 'select') {
                const select = document.createElement('select');
                select.className = 'panel-select';
                select.id = f.id;
                select.style.padding = '4px 6px';
                select.style.fontSize = '11px';

                f.options.forEach(optVal => {
                    const opt = document.createElement('option');
                    opt.value = optVal.value;
                    opt.innerText = optVal.label;
                    select.appendChild(opt);
                });

                row.appendChild(select);
            } else {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'panel-input';
                input.id = f.id;
                input.value = f.default || '';
                input.style.padding = '5px 8px';
                input.style.fontSize = '11px';

                row.appendChild(input);
            }

            inputsContainer.appendChild(row);
        });

        setupInputFocusTracking();

        const firstInput = inputsContainer.querySelector('.panel-input:not([readonly])');
        if (firstInput) {
            firstInput.focus();
        }
    }

    function runEngineeringCalculation() {
        const calculationSelect = document.getElementById('engCalculation');
        const displayFormula = document.querySelector('.display-formula');
        const displayResult = document.querySelector('.display-result');
        const techNotes = document.getElementById('engTechNotes');
        const techFormula = document.getElementById('engTechFormula');
        const techType = document.getElementById('engTechType');
        const techNotice = document.getElementById('engTechNotice');

        if (!calculationSelect || !displayFormula || !displayResult) return;

        const cat_calc = calculationSelect.value;
        if (!cat_calc) return;
        const parts = cat_calc.split(':');
        const cat = parts[0];
        const calcId = parts[1];

        if (!engCalculations[cat] || !engCalculations[cat].calcs[calcId]) return;

        const calcObj = engCalculations[cat].calcs[calcId];
        const vals = {};

        calcObj.fields.forEach(f => {
            const el = document.getElementById(f.id);
            if (el) {
                vals[f.id] = el.value;
            }
        });

        const result = calcObj.calc(vals);

        if (result.error) {
            displayFormula.innerText = `${calcObj.name} (${engCalculations[cat].name})`;
            displayResult.innerText = result.error;
            if (techNotes) techNotes.style.display = 'none';
            return;
        }

        let valFormatted = result.value;
        const numVal = parseFloat(result.value);
        if (!isNaN(numVal) && isFinite(numVal) && String(numVal) === String(result.value)) {
            valFormatted = formatNumber(numVal);
        } else if (typeof result.value === 'number') {
            valFormatted = formatNumber(result.value);
        }

        displayFormula.innerText = `Resultado: ${result.label} (${engCalculations[cat].name})`;
        displayResult.innerText = `${valFormatted} ${result.unit}`.trim();

        // === Painel de Notas Técnicas ===
        if (techNotes) {
            const formula = calcObj.formula || '';
            const calcType = calcObj.calcType || 'estimativa';
            const notes = calcObj.notes || '';

            const typeLabels = {
                'matematico':  '📐 Cálculo Matemático',
                'estimativa':  '⚠️ Estimativa Prática',
                'normativo':   '📋 Dimensionamento Normativo'
            };
            const typeColors = {
                'matematico':  'rgba(0,210,255,0.9)',
                'estimativa':  'rgba(255,180,0,0.9)',
                'normativo':   'rgba(100,220,100,0.9)'
            };

            if (techFormula) techFormula.innerText = formula ? `📐 Fórmula: ${formula}` : '';
            if (techType) {
                techType.style.color = typeColors[calcType] || typeColors['estimativa'];
                techType.innerText = typeLabels[calcType] || typeLabels['estimativa'];
            }
            if (techNotice) techNotice.innerText = notes;

            techNotes.style.display = (formula || notes) ? 'block' : 'none';
        }
    }



    // === BASE DE DADOS DO GUIA TÉCNICO ===
    const technicalGuides = [
        {
            id: "simple",
            name: "Calculadora Simples",
            icon: "🔢",
            desc: "Permite realizar cálculos matemáticos básicos do dia a dia com facilidade.",
            formula: "A + B, A - B, A * B, A / B",
            params: [
                "Operadores básicos: Soma (+), Subtração (-), Multiplicação (×) e Divisão (÷).",
                "Histórico: Armazena as últimas operações executadas.",
                "Porcentagem: Suporta cálculo direto de porcentagem (ex: 50 + 10% calcula 55)."
            ]
        },
        {
            id: "scientific",
            name: "Calculadora Científica",
            icon: "🔬",
            desc: "Fornece funções matemáticas avançadas para trigonometria, exponenciação, logaritmos, constantes e chaves de memória.",
            formula: "sin(x), cos(x), tan(x), log(x), ln(x), x^y, x!, √x | MC, MR, MS, M+, M-",
            params: [
                "Trigonometria: Funções Seno, Cosseno, Tangente e suas inversas. Alterna entre Graus (DEG) e Radianos (RAD).",
                "Logaritmos: Logaritmo de base 10 (log) e logaritmo natural de base e (ln).",
                "Exponenciais e Raízes: Elevação de qualquer potência (x^y), raiz quadrada (√) e raiz cúbica.",
                "Constantes: Acesso rápido aos valores exatos de Pi (π ≈ 3.14159) e Euler (e ≈ 2.71828).",
                "Memória: MC (limpar), MR (recuperar), MS (salvar), M+/M- (somar/subtrair). O indicador 'M' no visor acende quando há valor armazenado."
            ]
        },
        {
            id: "units",
            name: "Conversor de Unidades",
            icon: "📏",
            desc: "Converte de forma instantânea valores entre diferentes escalas físicas de medidas.",
            formula: "Fator de conversão linear ou equações térmicas",
            params: [
                "Comprimento: metros (m), centímetros (cm), milímetros (mm), quilômetros (km), polegadas (in), pés (ft), jardas (yd) e milhas (mi).",
                "Massa: gramas (g), quilogramas (kg), toneladas (t), libras (lb) e onças (oz).",
                "Temperatura: Celsius (°C), Fahrenheit (°F) e Kelvin (K) utilizando escalas exatas.",
                "Área: m², cm², km², hectares, acres, sq ft.",
                "Volume: litros (L), mililitros (mL), m³, galões (gal), xícaras e copos."
            ]
        },
        {
            id: "currency",
            name: "Conversor de Moedas",
            icon: "💵",
            desc: "Realiza a conversão de moedas globais e criptoativos com taxas atualizadas.",
            formula: "Valor convertido = Valor de Entrada * (Taxa Alvo / Taxa Origem)",
            params: [
                "Moedas Fiduciárias: USD, BRL, EUR, GBP, CAD, AUD, CHF, ARS, CLP, UYU, MXN, CNY, INR.",
                "Criptomoedas: Bitcoin (BTC), Ethereum (ETH), Solana (SOL), Binance Coin (BNB), XRP, Cardano (ADA), Dogecoin (DOGE).",
                "Estabilidade: Pareados com USDT e USDC (1.00 USD).",
                "Sincronização: Busca cotações atualizadas em tempo real via API aberta."
            ]
        },
        {
            id: "stats",
            name: "Estatística",
            icon: "📊",
            desc: "Analisa uma lista de dados numéricos calculando as principais métricas descritivas.",
            formula: "Média = ∑xi / N | Variância = ∑(xi - Média)² / (N - 1)",
            params: [
                "Entrada de dados: Insira valores numéricos separados por ponto e vírgula (;).",
                "Média e Mediana: Mede a tendência central dos dados.",
                "Desvio Padrão e Variância: Mede o grau de dispersão dos dados.",
                "Soma e Contagem: Exibe a soma total aritmética e a quantidade (N) de elementos inseridos."
            ]
        },
        {
            id: "pct",
            name: "Porcentagem",
            icon: "📈",
            desc: "Facilita o cálculo rápido de três variações comuns de problemas percentuais.",
            formula: "1) Parte de Total: (Parte / Total) * 100% | 2) Variação: ((B - A) / A) * 100%",
            params: [
                "Parte de um Total: Descobre qual porcentagem um valor representa do todo.",
                "Aumento / Desconto: Aplica acréscimos ou abatimentos sobre um valor inicial.",
                "Diferença Percentual: Calcula o crescimento ou a queda relativa entre duas grandezas (A para B)."
            ]
        },
        {
            id: "finance",
            name: "Finanças",
            icon: "💰",
            desc: "Simula o rendimento de capital, amortizações e o cálculo de independência financeira.",
            formula: "SAC: A_k = Amort + J_k | Price: PMT = P * [i(1+i)^n] / [(1+i)^n - 1] | FIRE: Patrimônio = DespesaAnual * 25",
            params: [
                "Juros Simples e Compostos: Projeta a evolução de capital inicial com taxa e período especificados.",
                "Amortização SAC: Parcelas decrescentes com amortização constante do saldo devedor.",
                "Amortização Price: Prestações fixas ao longo do prazo do financiamento.",
                "Regra do Fogo (FIRE): Estima o patrimônio necessário para aposentadoria perpétua sob a taxa segura de retirada de 4% ao ano."
            ]
        },
        {
            id: "dates",
            name: "Calculadora de Datas",
            icon: "📅",
            desc: "Calcula intervalos temporais exatos e realiza projeções futuras/passadas de calendário.",
            formula: "Diferença = DataFim - DataInicio | Projeção = Data + N dias",
            params: [
                "Diferença de Datas: Mede o número exato de dias e meses transcorridos entre duas datas de referência.",
                "Adicionar ou Subtrair Dias: Projeta uma nova data futura ou passada somando ou removendo dias de uma data base."
            ]
        },
        {
            id: "health",
            name: "Saúde & Bem-estar",
            icon: "❤️",
            desc: "Fornece estimativas básicas de indicadores corporais, gestacionais e de hidratação.",
            formula: "IMC = Peso / Altura² | IAC = (Quadril / (Altura * √Altura)) - 18",
            params: [
                "IMC: Índice de Massa Corporal clássico para avaliação de peso ideal.",
                "IAC: Índice de Adiposidade Corporal que utiliza a circunferência do quadril.",
                "RCQ: Relação Cintura-Quadril para medição de risco cardiovascular associado ao acúmulo de gordura abdominal.",
                "Consumo de Água: Sugestão de hidratação diária personalizada com base no peso corporal.",
                "Gestacional: Estima a idade gestacional e a data provável do parto (DPP) a partir do primeiro dia da última menstruação (DUM)."
            ]
        },
        {
            id: "matrices",
            name: "Calculadora de Matrizes",
            icon: "🧮",
            desc: "Executa operações de álgebra linear com matrizes quadradas de ordem 2x2 ou 3x3.",
            formula: "A × B | Det(A) | A⁻¹ | Aᵀ",
            params: [
                "Determinante: Calcula o determinante de matrizes 2x2 ou 3x3 (regra de Sarrus).",
                "Transposição (Aᵀ): Troca linhas por colunas na matriz selecionada.",
                "Inversão (A⁻¹): Calcula a matriz inversa clássica através dos cofatores (se Det ≠ 0).",
                "Multiplicação (A × B): Executa o produto matricial clássico linha-por-coluna."
            ]
        },
        {
            id: "programmer",
            name: "Programador",
            icon: "💻",
            desc: "Calculadora bitwise completa com suporte a múltiplas bases numéricas, word sizes e grid de 64 bits.",
            formula: "Base: HEX, DEC, OCT, BIN | Word Size: 8, 16, 32, 64-bit",
            params: [
                "Multi-Bases: Alterna instantaneamente entre Hexadecimal, Decimal, Octal e Binário.",
                "Operadores Bitwise: Operações lógicas AND, OR, XOR, NOT, e deslocamento de bits para esquerda (SHL) e direita (SHR).",
                "Representação Assinada: Suporte a Complemento de Dois para números negativos de acordo com a largura de palavra configurada.",
                "Painel de Bits Interativo: Grid visual que permite inverter o valor de bits individuais de 0 a 63 com apenas um clique."
            ]
        },
        {
            id: "ai",
            name: "Engineering AI (Assistente Inteligente)",
            icon: "🧠",
            desc: "Assistente de IA integrado para tirar dúvidas técnicas e sugerir/resolver cálculos em linguagem natural.",
            formula: "Roteador Local Inteligente (Gratuito) | OpenAI GPT-4o-Mini Cloud",
            params: [
                "Processamento Local: Resolve consultas comuns (como Lei de Ohm, Juros, Estatística e Fórmulas de Engenharia) sem custo de créditos.",
                "Pílulas Rápidas: Clique nos atalhos temáticos (Elétrica, Mecânica, Civil, etc.) para digitar prompts de exemplo estruturados.",
                "Integração Keep AI: Cadastre-se para ganhar 10 créditos grátis. Acesso instantâneo de visitante (Guest Trial) também disponível.",
                "Histórico: Acesse as últimas 5 respostas rápidas salvas na gaveta inferior do painel."
            ]
        }
    ];

    const engGuideDetails = {
        "mom-fletor": {
            formula: "M = P * L / 4 (conc.) ou M = P * L² / 8 (dist.)",
            desc: "Mede o momento máximo na viga sob carga concentrada ou distribuída.",
            params: ["L: Comprimento da viga (m)", "P: Carga aplicada (kN)", "Tipo de Carga: Concentrada ou Distribuída"]
        },
        "esf-cortante": {
            formula: "Vmax = P / 2",
            desc: "Calcula a força cortante máxima em viga biapoiada sob carga simétrica.",
            params: ["P: Carga total (kN)"]
        },
        "flecha-viga": {
            formula: "δ = P * L³ * 10⁴ / (48 * E * I)",
            desc: "Calcula o deslocamento vertical máximo (flecha) em uma viga sob carga central.",
            params: ["P: Carga (kN)", "L: Comprimento (m)", "E: Elasticidade (GPa)", "I: Inércia da seção (cm⁴)"]
        },
        "ten-normal": {
            formula: "σ = M * y * 10³ / I",
            desc: "Determina a tensão normal máxima devido à flexão na fibra mais extrema.",
            params: ["M: Momento fletor (kN·m)", "y: Distância da linha neutra (cm)", "I: Momento de inércia (cm⁴)"]
        },
        "ten-cis": {
            formula: "τ = V * Q * 10 / (I * t)",
            desc: "Mede a tensão de cisalhamento transversal em seções prismáticas.",
            params: ["V: Força cortante (kN)", "Q: Primeiro momento de área (cm³)", "I: Momento de inércia (cm⁴)", "t: Espessura da alma (mm)"]
        },
        "flambagem": {
            formula: "Pcr = π² * E * I * 10 / Le²",
            desc: "Carga crítica de Euler para colapso de pilares esbeltos.",
            params: ["E: Elasticidade (GPa)", "I: Inércia mínima (cm⁴)", "Le: Comprimento efetivo (m)"]
        },
        "centroide": {
            formula: "Xg = b / 2, Yg = h / 2",
            desc: "Determina o centro geométrico de uma seção retangular plana.",
            params: ["b: Largura (cm)", "h: Altura (cm)"]
        },
        "inercia": {
            formula: "I = b * h³ / 12",
            desc: "Momento de inércia retangular em relação ao eixo baricêntrico.",
            params: ["b: Largura (cm)", "h: Altura (cm)"]
        },
        "perda-carga": {
            formula: "hf = f * (L / D) * (v² / (2 * g))",
            desc: "Perda de energia por atrito ao longo de uma tubulação circular (fórmula de Darcy-Weisbach).",
            params: ["f: Fator de atrito", "L: Comprimento (m)", "D: Diâmetro (m)", "v: Velocidade (m/s)"]
        },
        "manning": {
            formula: "Q = (A^(5/3) * S^(1/2)) / (n * P^(2/3))",
            desc: "Determina a vazão em canais abertos pelo método de Manning.",
            params: ["A: Área molhada (m²)", "P: Perímetro molhado (m)", "S: Declividade (m/m)", "n: Rugosidade de Manning"]
        },
        "reynolds": {
            formula: "Re = ρ * v * D / μ",
            desc: "Determina o regime de escoamento (laminar Re < 2000, turbulento Re > 4000).",
            params: ["ρ: Densidade (kg/m³)", "v: Velocidade (m/s)", "D: Diâmetro do tubo (m)", "μ: Viscosidade dinâmica (Pa·s)"]
        },
        "pres-hidro": {
            formula: "P = ρ * g * h",
            desc: "Mede a pressão em um ponto fluido em repouso sob coluna de líquido.",
            params: ["ρ: Densidade (kg/m³)", "h: Profundidade (m)"]
        },
        "vazao-tubo": {
            formula: "Q = A * v = (π * D² / 4) * v",
            desc: "Calcula o volume de fluido transportado por unidade de tempo.",
            params: ["D: Diâmetro interno (m)", "v: Velocidade (m/s)"]
        },
        "pot-bomba": {
            formula: "Pb = (ρ * g * Q * H) / (η * 1000)",
            desc: "Potência elétrica/mecânica exigida para bombear uma vazão com dada perda/altura.",
            params: ["Q: Vazão (m³/s)", "H: Altura manométrica (m)", "ρ: Densidade (kg/m³)", "η: Rendimento da bomba (0 a 1)"]
        },
        "vel-escoa": {
            formula: "v = 4 * Q / (π * D²)",
            desc: "Calcula a velocidade média do escoamento a partir da vazão.",
            params: ["Q: Vazão (m³/s)", "D: Diâmetro (m)"]
        },
        "tijolos": {
            formula: "N = Área * 1.1 / ((Lt + J) * (Ht + J))",
            desc: "Calcula a quantidade necessária de tijolos com margem de 10% de perda.",
            params: ["A: Área da parede (m²)", "Lt, Ht: Dimensões do tijolo (m)", "J: Junta de assentamento (m)"]
        },
        "concreto-vol": {
            formula: "V = Largura * Comprimento * Espessura",
            desc: "Calcula o volume cúbico necessário para preencher lajes ou elementos estruturais.",
            params: ["L, C: Dimensões horizontais (m)", "E: Espessura/Altura (m)"]
        },
        "concreto-traco": {
            formula: "Traco 1:2:3 (Cimento : Areia : Brita)",
            desc: "Determina o consumo de materiais por volume de concreto.",
            params: ["V: Volume total (m³)"]
        },
        "taxa-armadura": {
            formula: "As_taxa = As / (Ac * 100) * 100%",
            desc: "Mede a porcentagem de área de aço em relação à seção total do concreto.",
            params: ["As: Área de aço (cm²)", "Ac: Seção de concreto (cm²)"]
        },
        "inc-telhado": {
            formula: "I = (H / V) * 100%",
            desc: "Mede a inclinação percentual do telhado em relação à horizontal.",
            params: ["H: Altura do topo (m)", "V: Comprimento horizontal (m)"]
        },
        "rup": {
            formula: "RUP = Hh / Qtd",
            desc: "Razão Unitária de Produtividade: mede a eficiência da equipe.",
            params: ["Hh: Horas de trabalho totais", "Qtd: Produção alcançada (m² ou m³)"]
        },
        "escavacao": {
            formula: "V = L * C * H * (1 + Empl/100)",
            desc: "Calcula o volume de terra escavada corrigido pela taxa de empolamento.",
            params: ["L, C: Área de escavação (m)", "H: Profundidade (m)", "Emp: Empolamento do solo (%)"]
        },
        "lei-ohm": {
            formula: "V = R * I | I = V / R | R = V / I",
            desc: "Relação fundamental de circuitos elétricos DC.",
            params: ["V: Tensão (V)", "I: Corrente (A)", "R: Resistência (Ω)"]
        },
        "pot-mono": {
            formula: "P = V * I * FP",
            desc: "Potência ativa monofásica com fator de potência.",
            params: ["V: Tensão (V)", "I: Corrente (A)", "FP: Fator de Potência"]
        },
        "pot-tri": {
            formula: "P = √3 * Vl * Il * FP",
            desc: "Potência ativa trifásica em redes de distribuição equilibradas.",
            params: ["V: Tensão de Linha (V)", "I: Corrente de Linha (A)", "FP: Fator de Potência"]
        },
        "queda-tensao": {
            formula: "ΔV = 2 * L * I * (R * cos(θ) + X * sen(θ)) / 1000",
            desc: "Queda de tensão percentual em condutores elétricos monofásicos.",
            params: ["L: Comprimento (m)", "I: Corrente (A)", "S: Seção transversal (mm²)", "V: Tensão nominal (V)"]
        },
        "dim-condutor": {
            formula: "Ip = In / (Fct * Fca)",
            desc: "Calcula a corrente de projeto corrigida pelos fatores de agrupamento e temperatura.",
            params: ["In: Corrente nominal (A)", "Fct: Fator de temperatura", "Fca: Fator de agrupamento"]
        },
        "imp-rlc": {
            formula: "Z = √[ R² + (XL - XC)² ]",
            desc: "Impedância em circuito RLC em série sob corrente alternada.",
            params: ["R: Resistência (Ω)", "L: Indutância (mH)", "C: Capacitância (μF)", "f: Frequência (Hz)"]
        },
        "ressonancia": {
            formula: "f0 = 1 / (2 * π * √[ L * C ])",
            desc: "Frequência de ressonância natural de malha LC.",
            params: ["L: Indutância (mH)", "C: Capacitância (μF)"]
        },
        "led-res": {
            formula: "R = (Vcc - Vled) / Iled",
            desc: "Resistor limitador de corrente para proteger diodos emissores de luz.",
            params: ["Vcc: Tensão de alimentação (V)", "Vled: Queda no LED (V)", "Iled: Corrente desejada (mA)"]
        },
        "filtro-rc": {
            formula: "fc = 1 / (2 * π * R * C)",
            desc: "Frequência de corte (-3dB) de filtro analógico passa-baixas passivo.",
            params: ["R: Resistência (Ω)", "C: Capacitância (μF)"]
        },
        "div-tensao": {
            formula: "Vout = Vin * [ R2 / (R1 + R2) ]",
            desc: "Redução proporcional da tensão analógica de entrada.",
            params: ["Vin: Tensão de entrada (V)", "R1: Resistor superior (Ω)", "R2: Resistor inferior (Ω)"]
        },
        "timer-555": {
            formula: "f = 1.44 / ((Ra + 2 * Rb) * C)",
            desc: "Frequência de oscilação astável do circuito integrado 555.",
            params: ["Ra: Resistor A (kΩ)", "Rb: Resistor B (kΩ)", "C: Capacitância (μF)"]
        },
        "opamp-gain": {
            formula: "Vout / Vin = 1 + (Rf / R1)",
            desc: "Ganho de tensão para amplificador operacional não-inversor.",
            params: ["R1: Resistor de entrada (kΩ)", "Rf: Resistor de realimentação (kΩ)"]
        },
        "res-cores": {
            formula: "Código de 4 faixas",
            desc: "Determina a resistência e tolerância por meio das cores do componente.",
            params: ["Faixas: 1ª, 2ª, Multiplicador e Tolerância"]
        },
        "db-ratio": {
            formula: "dB = 20 * log10(V2 / V1) ou 10 * log10(P2 / P1)",
            desc: "Converte uma razão linear de tensão/potência para escala logarítmica de Decibéis.",
            params: ["Razão: Relação linear entre grandezas", "Tipo: Tensão ou Potência"]
        },
        "torque": {
            formula: "T = F * d * sen(θ)",
            desc: "Momento rotacional produzido por uma força linear aplicada a uma haste.",
            params: ["F: Força aplicada (N)", "d: Distância (m)", "θ: Ângulo de aplicação (graus)"]
        },
        "torcao-stress": {
            formula: "τ = T * r / J",
            desc: "Tensão de cisalhamento por torção máxima na superfície de eixo cilíndrico.",
            params: ["T: Torque (N·m)", "r: Raio externo (mm)", "J: Momento polar de inércia (cm⁴)"]
        },
        "pot-motor": {
            formula: "P = T * ω = T * (2 * π * N / 60)",
            desc: "Potência rotacional produzida a partir de torque e velocidade angular.",
            params: ["T: Torque (N·m)", "N: Rotação (RPM)"]
        },
        "rel-trans": {
            formula: "i = N1 / N2 = D2 / D1 = Z2 / Z1",
            desc: "Mete a relação de engrenagens ou polias em transmissões rotativas.",
            params: ["D1/Z1: Diâmetro/Dentes motriz", "D2/Z2: Diâmetro/Dentes movida"]
        },
        "pistao-pres": {
            formula: "F = P * A = P * (π * D² / 4)",
            desc: "Força hidráulica/pneumática transmitida por pistão sob pressão.",
            params: ["P: Pressão interna (bar)", "D: Diâmetro do pistão (mm)"]
        },
        "dil-termica": {
            formula: "ΔL = L0 * α * ΔT",
            desc: "Expansão mecânica unidimensional devido à variação de temperatura.",
            params: ["L0: Comprimento original (m)", "α: Coeficiente de dilatação (10⁻⁶/°C)", "ΔT: Variação de temperatura (°C)"]
        },
        "polar-j": {
            formula: "J = π * D⁴ / 32",
            desc: "Momento polar de inércia para seção circular maciça.",
            params: ["D: Diâmetro externo (mm)"]
        },
        "gases-ideais": {
            formula: "n = P * V / (R * T)",
            desc: "Lei geral dos gases ideais: determina o número de mols contido num volume físico.",
            params: ["P: Pressão (atm)", "V: Volume (L)", "T: Temperatura (K)"]
        },
        "molaridade": {
            formula: "M = m / (MM * V)",
            desc: "Concentração em mol/L de uma solução homogênea química.",
            params: ["m: Massa do soluto (g)", "MM: Massa molar (g/mol)", "V: Volume da solução (L)"]
        },
        "diluicao": {
            formula: "C1 * V1 = C2 * V2",
            desc: "Equação de conservação de mols para processos de diluição.",
            params: ["C1, C2: Concentração inicial/final (M)", "V1, V2: Volume inicial/final (L)"]
        },
        "ph": {
            formula: "pH = -log10[H+] | pOH = 14 - pH",
            desc: "Escala logarítmica de acidez ou alcalinidade baseada no log de íons H+.",
            params: ["H+: Concentração de íons de hidrogênio (mol/L)"]
        },
        "calor-reacao": {
            formula: "Q = m * c * ΔT",
            desc: "Mete o calor latente/sensível trocado em processo térmico.",
            params: ["m: Massa da substância (g)", "c: Calor específico (J/g°C)", "ΔT: Diferença térmica (°C)"]
        },
        "arrhenius": {
            formula: "k = A * e^(-Ea / (R * T))",
            desc: "Influência da temperatura na taxa de reação química.",
            params: ["A: Fator de frequência", "Ea: Energia de ativação (kJ/mol)", "T: Temperatura absoluta (K)"]
        },
        "estequiometria": {
            formula: "Massa B = (Massa A / MM_A) * MM_B * Proporção",
            desc: "Cálculo de rendimento de reagente para produto baseado em mol-massa.",
            params: ["Ma, MM_a: Massa e massa molar do reagente (g)", "MM_b: Massa molar do produto (g/mol)", "Ratio: Proporção molar (B/A)"]
        },
        "tempo-ciclo": {
            formula: "TC = Tempo Total / Peças Produzidas",
            desc: "Tempo médio gasto para finalizar uma unidade.",
            params: ["T: Tempo produtivo total (min)", "Q: Volume fabricado"]
        },
        "oee": {
            formula: "OEE = Disponibilidade * Performance * Qualidade",
            desc: "Eficiência Global de Equipamento (Overall Equipment Effectiveness).",
            params: ["Disp: Fração de tempo ativo", "Perf: Fração de velocidade ótima", "Qual: Fração de peças sem defeito"]
        },
        "leo": {
            formula: "LEC = √[ 2 * D * S / H ]",
            desc: "Lote Econômico de Compra/Fabricação minimizando custos totais.",
            params: ["D: Demanda anual", "S: Custo por pedido", "H: Custo de manutenção unitário anual"]
        },
        "capacidade": {
            formula: "Capacidade = Turno * 60 / Tempo de Ciclo",
            desc: "Volume de produção máximo viável dentro de um turno.",
            params: ["Turno: Tempo de turno (h)", "TC: Tempo de ciclo (s)"]
        },
        "ppm-defeitos": {
            formula: "PPM = (Defeitos / Total) * 1,000,000",
            desc: "Métrica de qualidade de defeitos por milhão de oportunidades.",
            params: ["Def: Peças com desvio", "Tot: Amostra total inspecionada"]
        },
        "prod-global": {
            formula: "Prod = Valor da Produção / Custos Operacionais",
            desc: "Índice financeiro/produtivo global de eficiência de fábrica.",
            params: ["Prod: Valor monetário da saída ($)", "Custo: Custo total de insumos ($)"]
        },
        "giro-estoque": {
            formula: "Giro = Custo de Vendas / Estoque Médio",
            desc: "Velocidade em que o inventário é rotacionado durante o ano.",
            params: ["Cmv: Custo de mercadorias vendidas ($)", "Est: Valor médio em inventário ($)"]
        }
    };

    function getFullGuidesList() {
        const list = [...technicalGuides];
        for (const catKey in engCalculations) {
            const cat = engCalculations[catKey];
            for (const calcKey in cat.calcs) {
                const calc = cat.calcs[calcKey];
                const detail = engGuideDetails[calcKey] || {
                    formula: "Fórmula definida no motor",
                    desc: "Cálculo técnico para engenharia.",
                    params: []
                };

                let params = detail.params;
                if (!params || params.length === 0) {
                    params = calc.fields.map(f => `${f.label.split('(')[0].trim()}: Campo de entrada`);
                }

                list.push({
                    id: `eng-${catKey}-${calcKey}`,
                    name: `${calc.name} (${cat.name})`,
                    icon: "⚙️",
                    desc: detail.desc,
                    formula: detail.formula,
                    params: params
                });
            }
        }
        return list;
    }

    function renderTechnicalGuides(query = '') {
        const container = document.getElementById('modalGuideContainer');
        if (!container) return;

        container.innerHTML = '';
        const normQuery = query.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");

        const allGuides = getFullGuidesList();
        const filtered = allGuides.filter(g => {
            if (!normQuery) return true;
            const normName = g.name.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
            const normDesc = g.desc.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
            const normFormula = g.formula.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
            return normName.includes(normQuery) || normDesc.includes(normQuery) || normFormula.includes(normQuery);
        });

        if (filtered.length === 0) {
            container.innerHTML = `<div style="text-align: center; color: var(--text-muted); font-size: 11px; padding: 20px;">Nenhum guia encontrado para "${query}"</div>`;
            return;
        }

        filtered.forEach(g => {
            const card = document.createElement('div');
            card.className = 'guide-card';

            const header = document.createElement('div');
            header.className = 'guide-header';

            const title = document.createElement('div');
            title.className = 'guide-title';
            title.innerHTML = `<span>${g.icon}</span> <span>${g.name}</span>`;

            const chevron = document.createElement('span');
            chevron.className = 'guide-chevron';
            chevron.innerText = '▼';

            header.appendChild(title);
            header.appendChild(chevron);

            const content = document.createElement('div');
            content.className = 'guide-content';

            const body = document.createElement('div');
            body.className = 'guide-body';

            let paramsHtml = '';
            if (g.params && g.params.length > 0) {
                paramsHtml = `
                    <h4 style="margin-top: 8px;">Parâmetros:</h4>
                    <ul class="guide-fields-list">
                        ${g.params.map(p => {
                            const parts = p.split(':');
                            if (parts.length > 1) {
                                return `<li><strong>${parts[0]}:</strong>${parts.slice(1).join(':')}</li>`;
                            }
                            return `<li>${p}</li>`;
                        }).join('')}
                    </ul>
                `;
            }

            body.innerHTML = `
                <p>${g.desc}</p>
                <h4>Fórmula / Conceito:</h4>
                <div class="guide-formula">${g.formula}</div>
                ${paramsHtml}
            `;

            content.appendChild(body);
            card.appendChild(header);
            card.appendChild(content);

            header.addEventListener('click', () => {
                const isActive = card.classList.contains('active');
                
                container.querySelectorAll('.guide-card').forEach(other => {
                    if (other !== card && other.classList.contains('active')) {
                        other.classList.remove('active');
                        other.querySelector('.guide-content').style.maxHeight = null;
                    }
                });

                if (isActive) {
                    card.classList.remove('active');
                    content.style.maxHeight = null;
                } else {
                    card.classList.add('active');
                    content.style.maxHeight = content.scrollHeight + 'px';
                }
            });

            container.appendChild(card);
        });
    }

    function setupModalTabs() {
        const btnTabConfig = document.getElementById('btnTabModalConfig');
        const btnTabGuide = document.getElementById('btnTabModalGuide');
        const panelConfig = document.getElementById('panelModalConfig');
        const panelGuide = document.getElementById('panelModalGuide');
        const searchInput = document.getElementById('modalGuideSearch');

        if (btnTabConfig && btnTabGuide && panelConfig && panelGuide) {
            btnTabConfig.addEventListener('click', () => {
                btnTabConfig.classList.add('active');
                btnTabGuide.classList.remove('active');
                panelConfig.classList.add('active');
                panelGuide.classList.remove('active');
            });

            btnTabGuide.addEventListener('click', () => {
                btnTabGuide.classList.add('active');
                btnTabConfig.classList.remove('active');
                panelGuide.classList.add('active');
                panelConfig.classList.remove('active');
                renderTechnicalGuides(searchInput ? searchInput.value : '');
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                renderTechnicalGuides(e.target.value);
            });
        }
    }

    // === MÓDULO ENGINEERING AI ===
    // Sync with the shared keepai_token from the ecosystem
    let sharedToken = localStorage.getItem('keepai_token');
    let isGuestInit = localStorage.getItem('keep_ai_is_guest') === 'true';

    if (sharedToken) {
        // User is logged in to the portal. Sync to powercalc if different.
        if (localStorage.getItem('keep_ai_token') !== sharedToken || isGuestInit) {
            localStorage.setItem('keep_ai_token', sharedToken);
            localStorage.removeItem('keep_ai_is_guest');
            // Remove old email and credits so that syncAiBalance gets them fresh from auth.php
            localStorage.removeItem('keep_ai_email');
            localStorage.removeItem('keep_ai_credits');
        }
    } else {
        // Portal has no token. If powercalc is logged in (not guest), log out of powercalc too.
        if (localStorage.getItem('keep_ai_token') && !isGuestInit) {
            localStorage.removeItem('keep_ai_token');
            localStorage.removeItem('keep_ai_email');
            localStorage.removeItem('keep_ai_credits');
            localStorage.removeItem('keep_ai_is_guest');
        }
    }

    let aiToken = localStorage.getItem('keep_ai_token') || '';
    let aiEmail = localStorage.getItem('keep_ai_email') || '';
    let aiCredits = parseInt(localStorage.getItem('keep_ai_credits') || '0');
    let pixPollingInterval = null;
    let prePaymentCredits = 0;

    function syncAiBalance() {
        const balanceText = document.getElementById('aiBalanceText');
        const userDisplay = document.getElementById('aiUserDisplay');
        const authContainer = document.getElementById('aiAuthContainer');
        const mainInterface = document.getElementById('aiMainInterface');
        const btnBuyCredits = document.getElementById('btnAiBuyCredits');

        const isGuest = localStorage.getItem('keep_ai_is_guest') === 'true';

        if (!aiToken) {
            if (balanceText) balanceText.innerText = '🪙 10 Créditos Grátis';
            if (userDisplay) userDisplay.innerText = 'Visitante';
            if (authContainer) authContainer.style.display = 'none';
            if (mainInterface) mainInterface.style.display = 'block';
            if (btnBuyCredits) {
                btnBuyCredits.innerText = '👤 ENTRAR';
            }
            return;
        }

        if (userDisplay) {
            userDisplay.innerText = isGuest ? 'Visitante' : aiEmail;
        }
        if (authContainer) authContainer.style.display = 'none';
        if (mainInterface) mainInterface.style.display = 'block';
        if (btnBuyCredits) {
            btnBuyCredits.innerText = isGuest ? '👤 ENTRAR' : '➕ COMPRAR';
        }

        if (balanceText) {
            balanceText.innerText = isGuest 
                ? `🪙 Saldo: ${aiCredits} grátis` 
                : `🪙 Saldo: ${aiCredits} créditos`;
        }

        fetch(`https://4u.ia.br/app/keepai/api/auth.php`, {
            headers: { 'Authorization': `Bearer ${aiToken}` }
        })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.user) {
                    aiCredits = data.user.credits;
                    aiEmail = data.user.email;
                    localStorage.setItem('keep_ai_credits', aiCredits);
                    localStorage.setItem('keep_ai_email', aiEmail);
                    if (userDisplay) {
                        userDisplay.innerText = isGuest ? 'Visitante' : aiEmail;
                    }
                    if (btnBuyCredits) {
                        btnBuyCredits.innerText = isGuest ? '👤 ENTRAR' : '➕ COMPRAR';
                    }
                    if (balanceText) {
                        balanceText.innerText = isGuest 
                            ? `🪙 Saldo: ${aiCredits} grátis` 
                            : `🪙 Saldo: ${aiCredits} créditos`;
                    }
                } else {
                    aiToken = '';
                    localStorage.removeItem('keep_ai_token');
                    localStorage.removeItem('keepai_token');
                    localStorage.removeItem('keep_ai_is_guest');
                    localStorage.removeItem('keep_ai_email');
                    localStorage.removeItem('keep_ai_credits');
                    syncAiBalance();
                }
            })
            .catch(err => {
                console.error("Erro ao sincronizar saldo Keep AI:", err);
                if (balanceText) {
                    balanceText.innerText = isGuest 
                        ? `🪙 Saldo: ${aiCredits} grátis (offline)` 
                        : `🪙 Saldo: ${aiCredits} (offline)`;
                }
            });
    }

    function routeQueryLocally(query) {
        const normQuery = query.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");

        // 1. Detecção de apenas valores (ex: 220V 1500W)
        const cleanText = normQuery.replace(/\d+/g, '').replace(/\b(?:v|w|hz|a|psi|bar|cv|hp|rpm|c|f|kg|lb|m|l)\b/g, '').replace(/[^\w]/g, '').trim();
        if (cleanText.length < 5) {
            const justV = normQuery.match(/(\d+)\s*v/);
            const justW = normQuery.match(/(\d+)\s*w/);
            if (justV && justW) {
                return {
                    type: 'suggestion',
                    response: `Detectamos os valores: Tensão = ${justV[1]}V, Potência = ${justW[1]}W.\n\nPosso calcular:`,
                    actions: [
                        { label: "Calcular Corrente", query: `Corrente de um motor de ${justW[1]}W em ${justV[1]}V` },
                        { label: "Calcular Consumo (8h/dia)", query: `Consumo mensal de equipamento de ${justW[1]}W ligado 8h por dia` }
                    ]
                };
            }
        }

        // 2. Elétrica - Potência Monofásica / Lei de Ohm (Corrente de um motor)
        const hasW = normQuery.match(/(\d+)\s*w/);
        const hasV = normQuery.match(/(\d+)\s*v/);
        if (hasW && hasV) {
            const P = parseFloat(hasW[1]);
            const V = parseFloat(hasV[1]);
            const I = P / V;
            return {
                type: 'local',
                module: 'Elétrica ➔ Potência Monofásica',
                response: `📋 Dados identificados\n\nPotência = ${P} W\nTensão = ${V} V\n\n📐 Fórmula\n\nI = P / V\n\n🧮 Cálculo\n\nI = ${P} / ${V}\nI = ${I.toFixed(2)} A\n\n✅ Resultado\n\nCorrente = ${I.toFixed(2)} A\n\n💡 Explicação\n\nUm equipamento de ${P}W ligado em ${V}V consome aproximadamente ${I.toFixed(2)} amperes.`
            };
        }

        // 3. Construção Civil - Volume de Concreto (Laje)
        const dimMatch = normQuery.match(/(\d+(?:[\.,]\d+)?)\s*x\s*(\d+(?:[\.,]\d+)?)\s*x\s*(\d+(?:[\.,]\d+)?)/);
        if (dimMatch && (normQuery.includes('concreto') || normQuery.includes('laje') || normQuery.includes('volume'))) {
            const d1 = parseFloat(dimMatch[1].replace(',', '.'));
            const d2 = parseFloat(dimMatch[2].replace(',', '.'));
            const d3 = parseFloat(dimMatch[3].replace(',', '.'));
            const Vol = d1 * d2 * d3;
            return {
                type: 'local',
                module: 'Construção Civil ➔ Volume de Concreto',
                response: `📋 Dados identificados\n\nComprimento = ${d1} m\nLargura = ${d2} m\nEspessura/Altura = ${d3} m\n\n📐 Fórmula\n\nV = Largura * Comprimento * Espessura\n\n🧮 Cálculo\n\nV = ${d1} * ${d2} * ${d3}\nV = ${Vol.toFixed(2)} m³\n\n✅ Resultado\n\nVolume = ${Vol.toFixed(2)} m³\n\n💡 Explicação\n\nO volume de concreto necessário para preencher uma laje com as dimensões de ${d1}m por ${d2}m e ${d3}m de espessura é de ${Vol.toFixed(2)} metros cúbicos.`
            };
        }

        // 4. Mecânica - Torque de Motor
        const hasHP = normQuery.match(/(\d+(?:[\.,]\d+)?)\s*(?:cv|hp)/);
        const hasRPM = normQuery.match(/(\d+)\s*rpm/);
        if (hasHP && hasRPM && normQuery.includes('torque')) {
            const powerHP = parseFloat(hasHP[1].replace(',', '.'));
            const rpm = parseFloat(hasRPM[1]);
            const P = powerHP * 735.5;
            const omega = (2 * Math.PI * rpm) / 60;
            const T = P / omega;
            return {
                type: 'local',
                module: 'Mecânica ➔ Potência de Motor (Torque)',
                response: `📋 Dados identificados\n\nPotência = ${powerHP} cv (${P.toFixed(1)} W)\nVelocidade = ${rpm} RPM\n\n📐 Fórmula\n\nT = P / (2 * π * N / 60)\n\n🧮 Cálculo\n\nT = ${P.toFixed(1)} / (2 * 3.14159 * ${rpm} / 60)\nT = ${T.toFixed(2)} N·m\n\n✅ Resultado\n\nTorque = ${T.toFixed(2)} N·m\n\n💡 Explicação\n\nUm motor de ${powerHP}cv operando na velocidade de ${rpm} RPM gera um torque nominal de aproximadamente ${T.toFixed(2)} N·m.`
            };
        }

        // 5. Conversor de Unidades (Psi para bar e temperaturas)
        const hasNum = normQuery.match(/(\d+(?:[\.,]\d+)?)/);
        if (hasNum) {
            const val = parseFloat(hasNum[1].replace(',', '.'));
            if (normQuery.includes('psi') && normQuery.includes('bar')) {
                const barVal = val * 0.0689476;
                return {
                    type: 'local',
                    module: 'Conversor de Unidades ➔ Pressão',
                    response: `📋 Dados identificados\n\nValor de Entrada = ${val} psi\nUnidade de Destino = bar\n\n📐 Fórmula\n\nValor em bar = Valor em psi * 0.0689476\n\n🧮 Cálculo\n\nValor = ${val} * 0.0689476\nValor = ${barVal.toFixed(3)} bar\n\n✅ Resultado\n\nPressão = ${barVal.toFixed(3)} bar\n\n💡 Explicação\n\nA conversão de ${val} psi (libras por polegada quadrada) resulta em aproximadamente ${barVal.toFixed(3)} bar.`
                };
            }
            if ((normQuery.includes('celsius') || normQuery.includes('°c')) && (normQuery.includes('fahrenheit') || normQuery.includes('°f'))) {
                const fVal = (val * 9/5) + 32;
                return {
                    type: 'local',
                    module: 'Conversor de Unidades ➔ Temperatura',
                    response: `📋 Dados identificados\n\nValor de Entrada = ${val} °C\nUnidade de Destino = °F\n\n📐 Fórmula\n\n°F = (°C * 9/5) + 32\n\n🧮 Cálculo\n\nValor = (${val} * 1.8) + 32\nValor = ${fVal.toFixed(1)} °F\n\n✅ Resultado\n\nTemperatura = ${fVal.toFixed(1)} °F\n\n💡 Explicação\n\nA temperatura de ${val}°C equivale a ${fVal.toFixed(1)}°F.`
                };
            }
            if (normQuery.includes('kg') && (normQuery.includes('lb') || normQuery.includes('libra'))) {
                const lbVal = val * 2.20462;
                return {
                    type: 'local',
                    module: 'Conversor de Unidades ➔ Massa',
                    response: `📋 Dados identificados\n\nValor de Entrada = ${val} kg\nUnidade de Destino = lb\n\n📐 Fórmula\n\nValor em lb = Valor em kg * 2.20462\n\n🧮 Cálculo\n\nValor = ${val} * 2.20462\nValor = ${lbVal.toFixed(2)} lb\n\n✅ Resultado\n\nMassa = ${lbVal.toFixed(2)} lb\n\n💡 Explicação\n\nO peso de ${val} kg equivale a aproximadamente ${lbVal.toFixed(2)} libras.`
                };
            }
        }

        // 6. Estatística - Média / Desvio Padrão
        const listMatch = normQuery.match(/(\d+(?:[\.,]\d+)?\s*(?:;|,\s|\s)\s*){2,}/);
        if (listMatch && (normQuery.includes('media') || normQuery.includes('desvio') || normQuery.includes('variancia'))) {
            const cleanText = normQuery.replace(/[^\d\.,; ]/g, '');
            const nums = cleanText.replace(/,/g, '.').split(/[;\s]+/).map(Number).filter(n => !isNaN(n));
            if (nums.length > 1) {
                const sum = nums.reduce((a, b) => a + b, 0);
                const mean = sum / nums.length;
                const variance = nums.reduce((a, b) => a + Math.pow(b - mean, 2), 0) / (nums.length - 1);
                const stdDev = Math.sqrt(variance);
                return {
                    type: 'local',
                    module: 'Estatística ➔ Descritiva',
                    response: `📋 Dados identificados\n\nValores = [${nums.join(', ')}]\nQuantidade N = ${nums.length}\n\n📐 Fórmula\n\nMédia = ∑xi / N\nDesvio Padrão = √[ ∑(xi - Média)² / (N - 1) ]\n\n🧮 Cálculo\n\nMédia = ${sum} / ${nums.length} = ${mean.toFixed(2)}\nVariância = ${variance.toFixed(3)}\nDesvio Padrão = √${variance.toFixed(3)} = ${stdDev.toFixed(2)}\n\n✅ Resultado\n\nMédia = ${mean.toFixed(2)}\nDesvio Padrão = ${stdDev.toFixed(2)}\n\n💡 Explicação\n\nPara a lista de ${nums.length} valores fornecidos, a média aritmética central é ${mean.toFixed(2)} com um desvio padrão de ${stdDev.toFixed(2)} indicando a dispersão dos dados.`
                };
            }
        }

        // 7. Finanças - Juros Compostos
        if (normQuery.includes('juros') && normQuery.includes('composto')) {
            const moneyMatch = normQuery.match(/(?:r\$|\$)\s*(\d+(?:[\.,]\d+)?)/i) || normQuery.match(/(\d{3,})(?:\s+|$)/);
            const rateMatch = normQuery.match(/(\d+(?:[\.,]\d+)?)\s*%/);
            const timeMatch = normQuery.match(/(\d+)\s*(?:mes|mês|ano)/);
            if (moneyMatch && rateMatch && timeMatch) {
                const P = parseFloat(moneyMatch[1].replace(',', '.'));
                const r = parseFloat(rateMatch[1].replace(',', '.')) / 100;
                const t = parseInt(timeMatch[1]);
                const A = P * Math.pow(1 + r, t);
                const J = A - P;
                return {
                    type: 'local',
                    module: 'Finanças ➔ Juros Compostos',
                    response: `📋 Dados identificados\n\nCapital Inicial = R$ ${P.toFixed(2)}\nTaxa de Juros = ${(r*100).toFixed(1)}% ao período\nTempo = ${t} períodos\n\n📐 Fórmula\n\nM = P * (1 + i)^t\nJ = M - P\n\n🧮 Cálculo\n\nM = ${P} * (1 + ${r})^${t}\nM = R$ ${A.toFixed(2)}\nJuros J = R$ ${J.toFixed(2)}\n\n✅ Resultado\n\nMontante Final = R$ ${A.toFixed(2)}\nTotal de Juros = R$ ${J.toFixed(2)}\n\n💡 Explicação\n\nAplicação de R$ ${P.toFixed(2)} sob taxa de ${(r*100).toFixed(1)}% ao período por ${t} períodos gera R$ ${J.toFixed(2)} de juros acumulados, totalizando R$ ${A.toFixed(2)}.`
                };
            }
        }

        return null;
    }

    function setupAiEvents() {
        const btnLogin = document.getElementById('btnAiLogin');
        const btnRegister = document.getElementById('btnAiRegister');
        const btnBuyCredits = document.getElementById('btnAiBuyCredits');
        const pixContainer = document.getElementById('aiPixContainer');
        const mainInterface = document.getElementById('aiMainInterface');
        const btnCancelPix = document.getElementById('btnAiCancelPix');
        const btnAiCopyPix = document.getElementById('btnAiCopyPix');
        const btnSubmit = document.getElementById('btnAiSubmit');
        const promptArea = document.getElementById('aiPromptArea');
        const responsePanel = document.getElementById('aiResponsePanel');
        const responseContent = document.getElementById('aiResponseContent');
        const routerTypeDisplay = document.getElementById('aiRouterTypeDisplay');
        const btnCopyResult = document.getElementById('btnAiCopyResult');
        const btnCopySteps = document.getElementById('btnAiCopySteps');
        
        const historyHeader = document.getElementById('aiHistoryHeader');
        const historyContent = document.getElementById('aiHistoryContent');
        const historyChevron = document.getElementById('aiHistoryChevron');

        const authContainer = document.getElementById('aiAuthContainer');

        function registerSilentGuest() {
            const randomId = Math.random().toString(36).substring(2, 9);
            const email = `visitor_${randomId}@powercalc.xyz`;
            const password = `pass_${randomId}_guest`;

            return fetch('https://4u.ia.br/app/keepai/api/auth.php?action=register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.token) {
                    aiToken = data.token;
                    aiEmail = email;
                    aiCredits = (data.user && data.user.credits) || 10;
                    localStorage.setItem('keep_ai_token', aiToken);
                    localStorage.setItem('keep_ai_email', aiEmail);
                    localStorage.setItem('keep_ai_credits', aiCredits);
                    localStorage.setItem('keep_ai_is_guest', 'true');
                    return data.token;
                }
                throw new Error(data.error || "Failed to register guest");
            });
        }

        // Login
        if (btnLogin) {
            btnLogin.addEventListener('click', () => {
                const email = document.getElementById('aiEmailInput').value.trim();
                const password = document.getElementById('aiPasswordInput').value;
                if (!email || !password) {
                    alert("Por favor, preencha todos os campos.");
                    return;
                }

                btnLogin.innerText = 'Entrando...';
                btnLogin.disabled = true;

                fetch('https://4u.ia.br/app/keepai/api/auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                })
                .then(res => res.json())
                .then(data => {
                    btnLogin.innerText = 'Entrar';
                    btnLogin.disabled = false;

                    if (data.success && data.token) {
                        aiToken = data.token;
                        aiEmail = email;
                        aiCredits = (data.user && data.user.credits) || 0;
                        localStorage.setItem('keep_ai_token', aiToken);
                        localStorage.setItem('keepai_token', aiToken);
                        localStorage.setItem('keep_ai_email', aiEmail);
                        localStorage.setItem('keep_ai_credits', aiCredits);
                        localStorage.removeItem('keep_ai_is_guest');
                        syncAiBalance();
                    } else {
                        alert(data.error || "E-mail ou senha incorretos.");
                    }
                })
                .catch(err => {
                    btnLogin.innerText = 'Entrar';
                    btnLogin.disabled = false;
                    console.error(err);
                    alert("Erro de conexão com o Keep AI.");
                });
            });
        }

        // Cadastro
        if (btnRegister) {
            btnRegister.addEventListener('click', () => {
                const email = document.getElementById('aiEmailInput').value.trim();
                const password = document.getElementById('aiPasswordInput').value;
                if (!email || !password) {
                    alert("Por favor, preencha todos os campos.");
                    return;
                }

                btnRegister.innerText = 'Cadastrando...';
                btnRegister.disabled = true;

                fetch('https://4u.ia.br/app/keepai/api/auth.php?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                })
                .then(res => res.json())
                .then(data => {
                    btnRegister.innerText = 'Cadastrar';
                    btnRegister.disabled = false;

                    if (data.success && data.token) {
                        aiToken = data.token;
                        aiEmail = email;
                        aiCredits = (data.user && data.user.credits) || 10;
                        localStorage.setItem('keep_ai_token', aiToken);
                        localStorage.setItem('keepai_token', aiToken);
                        localStorage.setItem('keep_ai_email', aiEmail);
                        localStorage.setItem('keep_ai_credits', aiCredits);
                        localStorage.removeItem('keep_ai_is_guest');
                        alert("Conta criada com sucesso! Você ganhou 10 créditos de IA gratuitos.");
                        syncAiBalance();
                    } else {
                        alert(data.error || "Erro ao realizar o cadastro.");
                    }
                })
                .catch(err => {
                    btnRegister.innerText = 'Cadastrar';
                    btnRegister.disabled = false;
                    console.error(err);
                    alert("Erro de conexão com o Keep AI.");
                });
            });
        }

        // Compra de Créditos (Pix)
        if (btnBuyCredits && pixContainer && mainInterface) {
            btnBuyCredits.addEventListener('click', () => {
                const isGuest = localStorage.getItem('keep_ai_is_guest') === 'true';
                if (!aiToken || isGuest) {
                    if (authContainer) {
                        const isHidden = authContainer.style.display === 'none' || authContainer.style.display === '';
                        authContainer.style.display = isHidden ? 'flex' : 'none';
                        if (isHidden) {
                            const emailInput = document.getElementById('aiEmailInput');
                            if (emailInput) emailInput.focus();
                        }
                    }
                    return;
                }

                prePaymentCredits = aiCredits;
                mainInterface.style.display = 'none';
                pixContainer.style.display = 'flex';

                const qrWrapper = document.getElementById('aiPixQrWrapper');
                const copyPasteInput = document.getElementById('aiPixCopyPasteInput');

                if (qrWrapper) qrWrapper.innerHTML = '<span style="font-size: 9px; color: black;">Gerando...</span>';
                if (copyPasteInput) copyPasteInput.value = '';

                fetch('https://4u.ia.br/app/keepai/api/mp_create.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${aiToken}`
                    },
                    body: JSON.stringify({ package_index: 1 })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.qr_code && data.qr_code_base64) {
                        if (qrWrapper) {
                            qrWrapper.innerHTML = `<img src="data:image/png;base64,${data.qr_code_base64}" style="width: 100%; height: 100%; object-fit: contain;">`;
                        }
                        if (copyPasteInput) {
                            copyPasteInput.value = data.qr_code;
                        }
                        startPixPolling();
                    } else {
                        alert(data.error || "Erro ao gerar chave Pix.");
                        pixContainer.style.display = 'none';
                        mainInterface.style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Erro ao conectar ao gateway de pagamento.");
                    pixContainer.style.display = 'none';
                    mainInterface.style.display = 'block';
                });
            });
        }

        if (btnCancelPix && pixContainer && mainInterface) {
            btnCancelPix.addEventListener('click', () => {
                stopPixPolling();
                pixContainer.style.display = 'none';
                mainInterface.style.display = 'block';
            });
        }

        function startPixPolling() {
            stopPixPolling();
            pixPollingInterval = setInterval(() => {
                fetch(`https://4u.ia.br/app/keepai/api/credits.php?token=${encodeURIComponent(aiToken)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && typeof data.credits !== 'undefined') {
                            if (data.credits > prePaymentCredits) {
                                stopPixPolling();
                                aiCredits = data.credits;
                                localStorage.setItem('keep_ai_credits', aiCredits);
                                
                                const balanceText = document.getElementById('aiBalanceText');
                                if (balanceText) balanceText.innerText = `🪙 Saldo: ${aiCredits} créditos`;

                                alert(`🎉 Pagamento confirmado! Adicionados 50 créditos ao seu saldo. Novo saldo: ${aiCredits} créditos.`);
                                
                                if (pixContainer) pixContainer.style.display = 'none';
                                if (mainInterface) mainInterface.style.display = 'block';
                            }
                        }
                    })
                    .catch(err => console.error("Erro no polling do Pix:", err));
            }, 3000);
        }

        function stopPixPolling() {
            if (pixPollingInterval) {
                clearInterval(pixPollingInterval);
                pixPollingInterval = null;
            }
        }

        if (btnAiCopyPix) {
            btnAiCopyPix.addEventListener('click', () => {
                const copyPasteInput = document.getElementById('aiPixCopyPasteInput');
                if (copyPasteInput && copyPasteInput.value) {
                    copyPasteInput.select();
                    document.execCommand('copy');
                    alert("Chave Pix Copia e Cola copiada!");
                }
            });
        }

        // Clique nos Chips de Categorias Rápidas
        const chips = document.querySelectorAll('.ai-chip');
        chips.forEach(chip => {
            chip.addEventListener('click', () => {
                const example = chip.getAttribute('data-example');
                if (example && promptArea) {
                    promptArea.value = example;
                    promptArea.focus();
                }
            });
        });

        // Envio do Prompt
        if (btnSubmit && promptArea && responsePanel && responseContent) {
            btnSubmit.addEventListener('click', () => {
                const query = promptArea.value.trim();
                if (!query) {
                    alert("Por favor, digite sua pergunta.");
                    return;
                }

                responsePanel.style.display = 'none';
                responseContent.innerText = '';

                btnSubmit.innerHTML = '<span>⏳</span> PROCESSANDO...';
                btnSubmit.disabled = true;

                // 1. Roteamento Local
                const localResult = routeQueryLocally(query);
                if (localResult) {
                    setTimeout(() => {
                        btnSubmit.innerHTML = '<span>✨</span> PROCESSAR CONSULTA';
                        btnSubmit.disabled = false;

                        responsePanel.style.display = 'flex';
                        if (localResult.type === 'suggestion') {
                            if (routerTypeDisplay) routerTypeDisplay.innerText = '💡 Sugestões';
                            responseContent.innerHTML = formatSuggestions(localResult);
                        } else {
                            if (routerTypeDisplay) routerTypeDisplay.innerText = '✅ Fórmula Local';
                            responseContent.innerText = localResult.response;
                        }
                        saveAiHistory(query, localResult.response || "Sugestões de cálculos.");
                    }, 400);
                    return;
                }

                // 2. IA Remota Keep IA
                const sendRemoteAiQuery = (tokenToUse) => {
                    const prompt = `Você é o assistente virtual da calculadora PowerCalc. O usuário deseja calcular o seguinte problema técnico: "${query}".

Formate sua resposta EXATAMENTE com as seguintes seções estruturadas, incluindo quebras de linha limpas. Se não for possível calcular, explique o motivo na seção Explicação.

Layout da resposta:
📋 Dados identificados
[Lista de variáveis extraídas com suas respectivas unidades físicas]

📐 Fórmula
[Fórmula matemática padrão usada para resolver o problema]

🧮 Cálculo
[Substituição dos valores na fórmula passo a passo]
[Resultado intermediário e final]

✅ Resultado
[Resultado principal formatado de forma clara]

💡 Explicação
[Breve explicação teórica e contextualizada em 2 a 3 frases]`;

                    const requestPayload = {
                        token: tokenToUse,
                        prompt: prompt,
                        image: "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII="
                    };

                    fetch('https://4u.ia.br/app/keepai/api/ai_vision.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(requestPayload)
                    })
                    .then(res => res.json())
                    .then(data => {
                        btnSubmit.innerHTML = '<span>✨</span> PROCESSAR CONSULTA';
                        btnSubmit.disabled = false;

                        if (data.success && (data.text || data.response)) {
                            const aiTextResponse = data.text || data.response;
                            aiCredits = typeof data.credits_remaining !== 'undefined' ? data.credits_remaining : Math.max(0, aiCredits - 1);
                            localStorage.setItem('keep_ai_credits', aiCredits);
                            
                            const isGuest = localStorage.getItem('keep_ai_is_guest') === 'true';
                            const balanceText = document.getElementById('aiBalanceText');
                            if (balanceText) {
                                balanceText.innerText = isGuest 
                                    ? `🪙 Saldo: ${aiCredits} grátis` 
                                    : `🪙 Saldo: ${aiCredits} créditos`;
                            }

                            responsePanel.style.display = 'flex';
                            if (routerTypeDisplay) routerTypeDisplay.innerText = isGuest ? '🧠 Keep AI (Teste)' : '🧠 Keep AI';
                            responseContent.innerText = aiTextResponse;

                            saveAiHistory(query, aiTextResponse);
                        } else {
                            alert(data.error || "Ocorreu um erro no processamento da IA.");
                        }
                    })
                    .catch(err => {
                        btnSubmit.innerHTML = '<span>✨</span> PROCESSAR CONSULTA';
                        btnSubmit.disabled = false;
                        console.error(err);
                        alert("Erro ao se conectar com a Inteligência Artificial.");
                    });
                };

                if (!aiToken) {
                    btnSubmit.innerHTML = '<span>⏳</span> INICIANDO TESTE GRÁTIS...';
                    btnSubmit.disabled = true;

                    registerSilentGuest()
                        .then(token => {
                            btnSubmit.innerHTML = '<span>⏳</span> PROCESSANDO...';
                            sendRemoteAiQuery(token);
                        })
                        .catch(err => {
                            console.error("Silent registration failed:", err);
                            btnSubmit.innerHTML = '<span>✨</span> PROCESSAR CONSULTA';
                            btnSubmit.disabled = false;
                            
                            // Fallback to inline registration display
                            responsePanel.style.display = 'flex';
                            if (routerTypeDisplay) routerTypeDisplay.innerText = '💡 MODO TESTE';
                            responseContent.innerHTML = `
<div style="padding: 6px; border: 1px dashed rgba(0, 210, 255, 0.4); border-radius: 6px; background: rgba(0, 210, 255, 0.05); margin-bottom: 4px;">
    <p style="margin: 0 0 6px 0; font-weight: 700; color: var(--accent-color); font-size: 11px;">🧠 Resolução Avançada na Nuvem</p>
    <p style="margin: 0 0 8px 0; font-size: 10px; color: var(--text-secondary); line-height: 1.4;">
        Esta pergunta requer nossa Inteligência Artificial baseada na nuvem. Crie uma conta gratuita em poucos segundos para receber a resposta completa (você ganha <strong>10 créditos grátis</strong> na hora!).
    </p>
    <button class="submit-btn" id="btnAiPromptRegister" style="padding: 4px 8px; font-size: 10px; font-weight: 700; width: 100%; border-radius: 6px; background: linear-gradient(135deg, #0066FF, #00D2FF); color: white; cursor: pointer; border: none;">
        👤 CRIAR MINHA CONTA / ENTRAR
    </button>
</div>
`;
                            const btnPromptRegister = document.getElementById('btnAiPromptRegister');
                            if (btnPromptRegister) {
                                btnPromptRegister.addEventListener('click', () => {
                                    const authContainer = document.getElementById('aiAuthContainer');
                                    if (authContainer) {
                                        authContainer.style.display = 'flex';
                                        const emailInput = document.getElementById('aiEmailInput');
                                        if (emailInput) emailInput.focus();
                                        authContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                    }
                                });
                            }
                        });
                    return;
                }

                if (aiCredits <= 0) {
                    btnSubmit.innerHTML = '<span>✨</span> PROCESSAR CONSULTA';
                    btnSubmit.disabled = false;
                    alert("Você não possui créditos suficientes. Por favor, adquira mais créditos via Pix.");
                    return;
                }

                sendRemoteAiQuery(aiToken);
            });
        }

        if (responseContent) {
            responseContent.addEventListener('click', (e) => {
                const actionBtn = e.target.closest('.ai-action-btn');
                if (actionBtn) {
                    const targetQuery = actionBtn.getAttribute('data-query');
                    if (targetQuery && promptArea) {
                        promptArea.value = targetQuery;
                        if (btnSubmit) btnSubmit.click();
                    }
                }
            });
        }

        // Toggles do Histórico
        if (historyHeader && historyContent && historyChevron) {
            historyHeader.addEventListener('click', () => {
                const isVisible = historyContent.style.display === 'block';
                if (isVisible) {
                    historyContent.style.display = 'none';
                    historyChevron.innerText = '▶';
                } else {
                    historyContent.style.display = 'block';
                    historyChevron.innerText = '▼';
                    renderAiHistory();
                }
            });
        }

        // Cópia de Respostas
        if (btnCopyResult) {
            btnCopyResult.addEventListener('click', () => {
                const content = responseContent.innerText;
                if (content) {
                    const lines = content.split('\n');
                    let resultText = '';
                    let capturing = false;
                    for (let line of lines) {
                        if (line.includes('✅ Resultado')) {
                            capturing = true;
                            continue;
                        }
                        if (capturing && line.trim() && line.includes('💡 Explicação')) {
                            break;
                        }
                        if (capturing && line.trim()) {
                            resultText += line.trim() + '\n';
                        }
                    }
                    if (!resultText) resultText = content;

                    navigator.clipboard.writeText(resultText.trim()).then(() => {
                        alert("Resultado copiado!");
                    }).catch(err => {
                        const temp = document.createElement('textarea');
                        temp.value = resultText.trim();
                        document.body.appendChild(temp);
                        temp.select();
                        document.execCommand('copy');
                        document.body.removeChild(temp);
                        alert("Resultado copiado!");
                    });
                }
            });
        }

        if (btnCopySteps) {
            btnCopySteps.addEventListener('click', () => {
                const content = responseContent.innerText;
                if (content) {
                    navigator.clipboard.writeText(content).then(() => {
                        alert("Passo a passo completo copiado!");
                    }).catch(err => {
                        const temp = document.createElement('textarea');
                        temp.value = content;
                        document.body.appendChild(temp);
                        temp.select();
                        document.execCommand('copy');
                        document.body.removeChild(temp);
                        alert("Passo a passo completo copiado!");
                    });
                }
            });
        }
    }

    function saveAiHistory(query, response) {
        let history = [];
        try {
            history = JSON.parse(localStorage.getItem('keep_ai_history') || '[]');
        } catch(e) {}

        history = history.filter(item => item.query !== query);
        history.unshift({
            query: query,
            response: response,
            date: new Date().toLocaleDateString('pt-BR')
        });

        if (history.length > 5) history = history.slice(0, 5);
        localStorage.setItem('keep_ai_history', JSON.stringify(history));
        
        const historyContent = document.getElementById('aiHistoryContent');
        if (historyContent && historyContent.style.display === 'block') {
            renderAiHistory();
        }
    }

    function renderAiHistory() {
        const historyContent = document.getElementById('aiHistoryContent');
        const promptArea = document.getElementById('aiPromptArea');
        const responsePanel = document.getElementById('aiResponsePanel');
        const responseContent = document.getElementById('aiResponseContent');
        const routerTypeDisplay = document.getElementById('aiRouterTypeDisplay');
        
        if (!historyContent) return;
        historyContent.innerHTML = '';
        
        let history = [];
        try {
            history = JSON.parse(localStorage.getItem('keep_ai_history') || '[]');
        } catch(e) {}

        if (history.length === 0) {
            historyContent.innerHTML = `<div style="text-align: center; color: var(--text-muted); font-size: 9px; padding: 10px;">Sem buscas recentes</div>`;
            return;
        }

        history.forEach(item => {
            const row = document.createElement('div');
            row.className = 'ai-history-item';
            row.innerHTML = `
                <span class="hist-text" title="${item.query}">🔍 ${item.query}</span>
                <span class="hist-date">${item.date}</span>
            `;

            row.addEventListener('click', () => {
                if (promptArea) {
                    promptArea.value = item.query;
                }
                if (responsePanel && responseContent) {
                    responsePanel.style.display = 'flex';
                    if (routerTypeDisplay) routerTypeDisplay.innerText = '⏳ Histórico';
                    responseContent.innerText = item.response;
                }
            });

            historyContent.appendChild(row);
        });
    }

    function formatSuggestions(result) {
        let html = `<p>${result.response}</p><div style="display: flex; flex-direction: column; gap: 4px; margin-top: 8px;">`;
        result.actions.forEach(act => {
            html += `<button class="submit-btn ai-action-btn" data-query="${act.query}" style="padding: 4px; font-size: 9px; text-align: left; background: rgba(0, 210, 255, 0.1); border-color: rgba(0, 210, 255, 0.35); color: var(--text-primary); cursor: pointer; border-radius: 4px;">👉 ${act.label}</button>`;
        });
        html += `</div>`;
        return html;
    }

    // Inicialização do programador
    setupProgrammerEvents();

    // Inicialização do módulo de matrizes
    generateMatrixGrid('grid-matrix-a', 3);
    generateMatrixGrid('grid-matrix-b', 3);
    setupMatrixEvents();

    // Inicialização do módulo de engenharia
    setupEngineeringEvents();

    // Inicialização da Central de Ajuda / Abas do Modal
    setupModalTabs();

    // Inicialização do módulo AI
    setupAiEvents();

    setupInputFocusTracking();
    setupCompactPads();
});
