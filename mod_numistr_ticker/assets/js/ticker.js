/**
 * NumisTR Ticker Module - Flexible Fade Transition JavaScript
 * @package     mod_numistr_ticker
 * @version     2.1.0 (Supports optional navigation and pause on hover)
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initFadeTickers();
    });

    /**
     * Initialize all fade ticker instances
     */
    function initFadeTickers() {
        const tickers = document.querySelectorAll('.numistr-ticker-fade');

        tickers.forEach(function(ticker) {
            new FadeTicker(ticker);
        });
    }

    /**
     * Fade Ticker Class (Flexible with optional features)
     */
    function FadeTicker(container) {
        this.container = container;
        this.items = container.querySelectorAll('.ticker-item-fade');
        this.dots = container.querySelectorAll('.ticker-dot');
        this.prevBtn = container.querySelector('.ticker-prev');
        this.nextBtn = container.querySelector('.ticker-next');
        
        this.currentIndex = 0;
        this.interval = parseInt(container.dataset.interval || 15000);
        this.timer = null;
        this.isPaused = false;
        
        // Check if navigation is enabled
        this.hasNavigation = this.prevBtn || this.nextBtn;
        
        if (this.items.length === 0) return;

        this.init();
    }

    FadeTicker.prototype = {
        /**
         * Initialize the ticker
         */
        init: function() {
            this.setupNavigationHandlers();
            this.setupVisibilityControl();
            this.startAutoAdvance();
        },

        /**
         * Setup navigation event handlers (if navigation is enabled)
         */
        setupNavigationHandlers: function() {
            var self = this;

            // Previous button
            if (this.prevBtn) {
                this.prevBtn.addEventListener('click', function() {
                    self.goToPrevious();
                    self.restartAutoAdvance();
                });
            }

            // Next button
            if (this.nextBtn) {
                this.nextBtn.addEventListener('click', function() {
                    self.goToNext();
                    self.restartAutoAdvance();
                });
            }

            // Dot navigation
            this.dots.forEach(function(dot, index) {
                dot.addEventListener('click', function() {
                    self.goToSlide(index);
                    self.restartAutoAdvance();
                });
            });

            // Keyboard navigation (only if navigation is enabled)
            if (this.hasNavigation) {
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'ArrowLeft') {
                        self.goToPrevious();
                        self.restartAutoAdvance();
                    } else if (e.key === 'ArrowRight') {
                        self.goToNext();
                        self.restartAutoAdvance();
                    }
                });
            }
        },

        /**
         * Go to specific slide
         */
        goToSlide: function(index) {
            // Remove active class from current item
            this.items[this.currentIndex].classList.remove('active');
            if (this.dots[this.currentIndex]) {
                this.dots[this.currentIndex].classList.remove('active');
            }

            // Update index
            this.currentIndex = index;

            // Add active class to new item
            this.items[this.currentIndex].classList.add('active');
            if (this.dots[this.currentIndex]) {
                this.dots[this.currentIndex].classList.add('active');
            }
        },

        /**
         * Go to next slide
         */
        goToNext: function() {
            var nextIndex = (this.currentIndex + 1) % this.items.length;
            this.goToSlide(nextIndex);
        },

        /**
         * Go to previous slide
         */
        goToPrevious: function() {
            var prevIndex = (this.currentIndex - 1 + this.items.length) % this.items.length;
            this.goToSlide(prevIndex);
        },

        /**
         * Start auto-advance timer
         */
        startAutoAdvance: function() {
            var self = this;

            if (this.timer) {
                clearInterval(this.timer);
            }

            this.timer = setInterval(function() {
                if (!self.isPaused) {
                    self.goToNext();
                }
            }, this.interval);
        },

        /**
         * Restart auto-advance (after manual navigation)
         */
        restartAutoAdvance: function() {
            this.startAutoAdvance();
        },

        /**
         * Pause auto-advance
         */
        pause: function() {
            this.isPaused = true;
        },

        /**
         * Resume auto-advance
         */
        resume: function() {
            this.isPaused = false;
        },

        /**
         * Setup visibility control and optional pause on hover
         */
        setupVisibilityControl: function() {
            var self = this;

            // Check if pause on hover is enabled (data attribute from module params)
            var pauseOnHover = this.container.dataset.pauseOnHover === '1';

            if (pauseOnHover) {
                this.container.addEventListener('mouseenter', function() {
                    self.pause();
                });

                this.container.addEventListener('mouseleave', function() {
                    self.resume();
                });
            }

            // Pause when page is hidden
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    self.pause();
                } else {
                    self.resume();
                }
            });

            // Use IntersectionObserver to detect if ticker is visible
            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            self.resume();
                        } else {
                            self.pause();
                        }
                    });
                }, {
                    threshold: 0.1
                });

                observer.observe(this.container);
            }

            // Respect user's reduced motion preference
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.pause();
            }
        }
    };

})();
