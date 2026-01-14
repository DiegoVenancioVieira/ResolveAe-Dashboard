/**
 * Sistema de Rotação Inteligente de Slides
 * Gerencia a rotação automática baseada na configuração de slides visíveis
 */

class SlideRotation {
    constructor() {
        this.slides = [];
        this.currentIndex = 0;
        this.isPaused = false;
        this.config = null;
        this.rotationTimer = null;
        this.defaultDuration = 10000; // 10 segundos padrão

        this.init();
    }

    /**
     * Inicializar sistema de rotação
     */
    async init() {
        try {
            // Carregar configuração de slides visíveis
            await this.loadSlidesConfig();

            // Identificar slides disponíveis no DOM
            this.identifySlides();

            // Aplicar configuração
            this.applyConfiguration();

            // Iniciar rotação
            this.startRotation();

            // Setup de controles
            this.setupControls();

            console.log('Sistema de rotação inicializado:', {
                total: this.slides.length,
                config: this.config
            });
        } catch (error) {
            console.error('Erro ao inicializar rotação:', error);
            // Fallback para rotação padrão
            this.fallbackRotation();
        }
    }

    /**
     * Carregar configuração de slides da API
     */
    async loadSlidesConfig() {
        try {
            const response = await fetch('api/get-visible-slides.php');
            const data = await response.json();

            if (data.success) {
                this.config = data.slides;
            } else {
                throw new Error('Failed to load slides configuration');
            }
        } catch (error) {
            console.error('Erro ao carregar configuração:', error);
            this.config = null;
        }
    }

    /**
     * Identificar slides presentes no DOM
     */
    identifySlides() {
        const slideElements = document.querySelectorAll('.slide');

        this.slides = Array.from(slideElements).map((element, index) => {
            const slideId = element.id || `slide-${index}`;

            // Tentar mapear com configuração
            let slideKey = this.getSlideKeyFromId(slideId);
            let config = this.config ? this.config[slideKey] : null;

            return {
                element: element,
                id: slideId,
                key: slideKey,
                visible: config ? config.visible : true,
                duration: config ? config.duration * 1000 : this.defaultDuration,
                order: config ? config.order : index,
                name: config ? config.name : element.querySelector('.slide-title')?.textContent || slideId
            };
        });

        // Filtrar apenas slides visíveis
        this.slides = this.slides.filter(slide => slide.visible);

        // Ordenar por ordem configurada
        this.slides.sort((a, b) => a.order - b.order);
    }

    /**
     * Mapear ID do slide para chave de configuração
     */
    getSlideKeyFromId(slideId) {
        const mapping = {
            'slide-overview': 'ticket_status',
            'slide-ranking': 'technician_performance',
            'slide-sla': 'sla_compliance',
            'slide-categorias': 'category_distribution',
            'slide-satisfacao': 'satisfaction_metrics',
            'slide-tendencias': 'trends_analysis',
            'slide-heatmap': 'heat_map',
            'slide-executive': 'executive_dashboard',
            'slide-prioridades': 'priority_analysis',
            'slide-setores': 'entity_comparison'
        };

        return mapping[slideId] || slideId;
    }

    /**
     * Aplicar configuração aos slides
     */
    applyConfiguration() {
        // Esconder todos os slides primeiro
        document.querySelectorAll('.slide').forEach(slide => {
            slide.classList.remove('active');
            slide.style.display = 'none';
        });

        // Mostrar apenas slides visíveis
        this.slides.forEach((slide, index) => {
            slide.element.style.display = 'block';
            if (index === 0) {
                slide.element.classList.add('active');
            }
        });
    }

    /**
     * Iniciar rotação automática
     */
    startRotation() {
        if (this.slides.length === 0) {
            console.warn('Nenhum slide visível para rotação');
            return;
        }

        // Mostrar primeiro slide
        this.showSlide(0);

        // Agendar próximo slide
        this.scheduleNextSlide();
    }

    /**
     * Agendar próximo slide
     */
    scheduleNextSlide() {
        if (this.rotationTimer) {
            clearTimeout(this.rotationTimer);
        }

        if (this.isPaused || this.slides.length <= 1) {
            return;
        }

        const currentSlide = this.slides[this.currentIndex];
        const duration = currentSlide.duration;

        this.rotationTimer = setTimeout(() => {
            this.nextSlide();
        }, duration);
    }

    /**
     * Mostrar slide específico
     */
    showSlide(index) {
        if (index < 0 || index >= this.slides.length) {
            return;
        }

        // Esconder slide atual
        if (this.slides[this.currentIndex]) {
            this.slides[this.currentIndex].element.classList.remove('active');
        }

        // Mostrar novo slide
        this.currentIndex = index;
        this.slides[this.currentIndex].element.classList.add('active');

        // Atualizar indicadores
        this.updateIndicators();

        // Emitir evento customizado
        this.emitSlideChangeEvent();
    }

    /**
     * Próximo slide
     */
    nextSlide() {
        const nextIndex = (this.currentIndex + 1) % this.slides.length;
        this.showSlide(nextIndex);
        this.scheduleNextSlide();
    }

    /**
     * Slide anterior
     */
    previousSlide() {
        const prevIndex = (this.currentIndex - 1 + this.slides.length) % this.slides.length;
        this.showSlide(prevIndex);

        // Resetar timer
        if (!this.isPaused) {
            this.scheduleNextSlide();
        }
    }

    /**
     * Ir para slide específico
     */
    goToSlide(index) {
        this.showSlide(index);

        // Resetar timer
        if (!this.isPaused) {
            this.scheduleNextSlide();
        }
    }

    /**
     * Pausar rotação
     */
    pause() {
        this.isPaused = true;
        if (this.rotationTimer) {
            clearTimeout(this.rotationTimer);
        }
        this.updatePlayPauseButton();
    }

    /**
     * Retomar rotação
     */
    resume() {
        this.isPaused = false;
        this.scheduleNextSlide();
        this.updatePlayPauseButton();
    }

    /**
     * Alternar pause/play
     */
    togglePause() {
        if (this.isPaused) {
            this.resume();
        } else {
            this.pause();
        }
    }

    /**
     * Configurar controles de navegação
     */
    setupControls() {
        // Criar barra de controle se não existir
        if (!document.getElementById('slide-controls')) {
            this.createControlBar();
        }

        // Botões de navegação
        const prevBtn = document.getElementById('slide-prev');
        const nextBtn = document.getElementById('slide-next');
        const playPauseBtn = document.getElementById('slide-play-pause');

        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.previousSlide());
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.nextSlide());
        }

        if (playPauseBtn) {
            playPauseBtn.addEventListener('click', () => this.togglePause());
        }

        // Controles de teclado
        document.addEventListener('keydown', (e) => {
            switch(e.key) {
                case 'ArrowLeft':
                    this.previousSlide();
                    break;
                case 'ArrowRight':
                    this.nextSlide();
                    break;
                case ' ':
                    e.preventDefault();
                    this.togglePause();
                    break;
            }
        });

        // Pausar quando mouse sobre slide
        this.slides.forEach(slide => {
            slide.element.addEventListener('mouseenter', () => this.pause());
            slide.element.addEventListener('mouseleave', () => this.resume());
        });
    }

    /**
     * Criar barra de controle
     */
    createControlBar() {
        const controlBar = document.createElement('div');
        controlBar.id = 'slide-controls';
        controlBar.className = 'slide-controls';
        controlBar.innerHTML = `
            <button id="slide-prev" class="control-btn" title="Slide Anterior (←)">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button id="slide-play-pause" class="control-btn" title="Pausar/Retomar (Espaço)">
                <i class="fas fa-pause"></i>
            </button>
            <button id="slide-next" class="control-btn" title="Próximo Slide (→)">
                <i class="fas fa-chevron-right"></i>
            </button>
            <div class="slide-indicator" id="slide-indicator">
                <span id="current-slide-num">1</span> / <span id="total-slides-num">1</span>
            </div>
            <div class="slide-progress-bar">
                <div class="slide-progress-fill" id="slide-progress"></div>
            </div>
        `;

        document.body.appendChild(controlBar);

        // Adicionar estilos
        const style = document.createElement('style');
        style.textContent = `
            .slide-controls {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: rgba(0, 0, 0, 0.8);
                padding: 15px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                gap: 10px;
                z-index: 1000;
            }

            .control-btn {
                background: transparent;
                border: 2px solid white;
                color: white;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                cursor: pointer;
                transition: all 0.3s;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .control-btn:hover {
                background: white;
                color: black;
                transform: scale(1.1);
            }

            .slide-indicator {
                color: white;
                font-weight: bold;
                margin: 0 10px;
                min-width: 60px;
                text-align: center;
            }

            .slide-progress-bar {
                width: 150px;
                height: 6px;
                background: rgba(255, 255, 255, 0.3);
                border-radius: 3px;
                overflow: hidden;
            }

            .slide-progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
                width: 0%;
                transition: width 0.3s linear;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Atualizar indicadores
     */
    updateIndicators() {
        const currentNum = document.getElementById('current-slide-num');
        const totalNum = document.getElementById('total-slides-num');

        if (currentNum) {
            currentNum.textContent = this.currentIndex + 1;
        }

        if (totalNum) {
            totalNum.textContent = this.slides.length;
        }

        // Atualizar barra de progresso
        this.updateProgressBar();
    }

    /**
     * Atualizar barra de progresso
     */
    updateProgressBar() {
        const progressBar = document.getElementById('slide-progress');
        if (!progressBar) return;

        const currentSlide = this.slides[this.currentIndex];
        const duration = currentSlide.duration;

        // Reset
        progressBar.style.transition = 'none';
        progressBar.style.width = '0%';

        // Animar
        setTimeout(() => {
            progressBar.style.transition = `width ${duration}ms linear`;
            progressBar.style.width = '100%';
        }, 50);
    }

    /**
     * Atualizar botão play/pause
     */
    updatePlayPauseButton() {
        const btn = document.getElementById('slide-play-pause');
        if (btn) {
            const icon = btn.querySelector('i');
            if (this.isPaused) {
                icon.className = 'fas fa-play';
                btn.title = 'Retomar (Espaço)';
            } else {
                icon.className = 'fas fa-pause';
                btn.title = 'Pausar (Espaço)';
            }
        }
    }

    /**
     * Emitir evento de mudança de slide
     */
    emitSlideChangeEvent() {
        const event = new CustomEvent('slidechange', {
            detail: {
                currentIndex: this.currentIndex,
                currentSlide: this.slides[this.currentIndex],
                totalSlides: this.slides.length
            }
        });
        document.dispatchEvent(event);
    }

    /**
     * Rotação fallback (caso configuração falhe)
     */
    fallbackRotation() {
        console.log('Usando rotação padrão (fallback)');

        const slideElements = document.querySelectorAll('.slide');
        this.slides = Array.from(slideElements).map((element, index) => ({
            element: element,
            id: element.id || `slide-${index}`,
            visible: true,
            duration: this.defaultDuration,
            order: index
        }));

        this.applyConfiguration();
        this.startRotation();
        this.setupControls();
    }

    /**
     * Recarregar configuração
     */
    async reload() {
        if (this.rotationTimer) {
            clearTimeout(this.rotationTimer);
        }

        await this.init();
    }
}

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.slideRotation = new SlideRotation();

    // Listener para eventos de mudança de slide
    document.addEventListener('slidechange', (e) => {
        console.log('Slide mudado:', e.detail.currentSlide.name);

        // Atualizar seletor de slides
        updateSlideSelector(e.detail.currentIndex);
    });

    // Setup do seletor de slides
    setupSlideSelector();
});

/**
 * Configurar seletor de slides no header
 */
function setupSlideSelector() {
    const btn = document.getElementById('slide-selector-btn');
    const menu = document.getElementById('slide-selector-menu');

    if (!btn || !menu) return;

    // Toggle menu
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.classList.toggle('show');
        populateSlideMenu();
    });

    // Fechar ao clicar fora
    document.addEventListener('click', (e) => {
        if (!menu.contains(e.target) && e.target !== btn) {
            menu.classList.remove('show');
        }
    });
}

/**
 * Preencher menu com slides disponíveis
 */
function populateSlideMenu() {
    const menu = document.getElementById('slide-selector-menu');
    if (!menu || !window.slideRotation) return;

    const slides = window.slideRotation.slides;
    const currentIndex = window.slideRotation.currentIndex;

    // Definir ícones e cores para cada tipo de slide
    const slideIcons = {
        'slide-overview': { icon: 'fa-chart-pie', color: '#3498db' },
        'slide-ranking': { icon: 'fa-users', color: '#2ecc71' },
        'slide-sla': { icon: 'fa-clock', color: '#e74c3c' },
        'slide-categorias': { icon: 'fa-folder-tree', color: '#9b59b6' },
        'slide-satisfacao': { icon: 'fa-star', color: '#f39c12' },
        'slide-tendencias': { icon: 'fa-chart-line', color: '#1abc9c' },
        'slide-heatmap': { icon: 'fa-fire', color: '#e67e22' },
        'slide-executive': { icon: 'fa-chart-bar', color: '#34495e' },
        'slide-prioridades': { icon: 'fa-exclamation-triangle', color: '#c0392b' },
        'slide-setores': { icon: 'fa-building', color: '#16a085' }
    };

    let html = '<div class="slide-selector-header">';
    html += '<i class="fas fa-th-large"></i> Selecionar Slide';
    html += '</div>';

    slides.forEach((slide, index) => {
        const iconData = slideIcons[slide.id] || { icon: 'fa-circle', color: '#95a5a6' };
        const isActive = index === currentIndex ? 'active' : '';

        html += `
            <div class="slide-menu-item ${isActive}" data-slide-index="${index}">
                <div class="slide-menu-icon" style="background: ${iconData.color}">
                    <i class="fas ${iconData.icon}"></i>
                </div>
                <div class="slide-menu-label">${slide.name}</div>
                ${isActive ? '<span class="slide-menu-badge">Atual</span>' : ''}
            </div>
        `;
    });

    menu.innerHTML = html;

    // Adicionar event listeners
    menu.querySelectorAll('.slide-menu-item').forEach(item => {
        item.addEventListener('click', () => {
            const index = parseInt(item.dataset.slideIndex);
            window.slideRotation.goToSlide(index);
            menu.classList.remove('show');
        });
    });
}

/**
 * Atualizar indicador do slide atual no menu
 */
function updateSlideSelector(currentIndex) {
    const menu = document.getElementById('slide-selector-menu');
    if (!menu) return;

    // Remover active de todos
    menu.querySelectorAll('.slide-menu-item').forEach(item => {
        item.classList.remove('active');
        const badge = item.querySelector('.slide-menu-badge');
        if (badge) badge.remove();
    });

    // Adicionar active ao atual
    const currentItem = menu.querySelector(`[data-slide-index="${currentIndex}"]`);
    if (currentItem) {
        currentItem.classList.add('active');
        currentItem.insertAdjacentHTML('beforeend', '<span class="slide-menu-badge">Atual</span>');
    }
}
