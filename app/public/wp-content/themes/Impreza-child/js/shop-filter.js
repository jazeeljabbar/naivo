document.addEventListener('DOMContentLoaded', function() {
    const activeFiltersContainer = document.querySelector('.nv-active-filters');
    const productsContainer = document.querySelector('.nv-product-grid');
    const productCount = document.querySelector('.nv-product-count');
    
    // UI Elements
    const dropdowns = document.querySelectorAll('.nv-custom-dropdown');
    const catItems = document.querySelectorAll('.nv-cat-item');
    
    // State – Default sort is price-desc (HIGH TO LOW) matching Figma
    let filterState = {
        category: '',
        orderby: 'price-desc',
        attributes: {}
    };

    // ==========================================
    // TICKER CLOSE BUTTON
    // ==========================================
    const tickerClose = document.querySelector('.nv-ticker-close');
    if (tickerClose) {
        tickerClose.addEventListener('click', () => {
            const ticker = document.querySelector('.nv-shop-ticker');
            if (ticker) ticker.classList.add('nv-hidden');
        });
    }

    // ==========================================
    // MOBILE FILTER DRAWER CONTROLS
    // ==========================================
    const mobileFilterBtn = document.getElementById('nv-mobile-filters-btn');
    const mobileSortBtn = document.getElementById('nv-mobile-sort-btn');
    const filterSection = document.getElementById('nv-filter-section');
    const mobileFilterClose = document.getElementById('nv-mobile-filter-close');
    const mobileFilterBackdrop = document.getElementById('nv-mobile-filter-backdrop');
    const applyFiltersBtn = document.getElementById('nv-mobile-filter-apply');
    const resetFiltersBtn = document.getElementById('nv-mobile-filter-reset');

    function openMobileFilters() {
        if (filterSection) {
            filterSection.classList.add('nv-mobile-open');
            if (mobileFilterBackdrop) {
                mobileFilterBackdrop.classList.add('active');
            }
            document.body.classList.add('nv-filter-active');
        }
    }

    function closeMobileFilters() {
        if (filterSection) {
            filterSection.classList.remove('nv-mobile-open');
            if (mobileFilterBackdrop) {
                mobileFilterBackdrop.classList.remove('active');
            }
            document.body.classList.remove('nv-filter-active');
        }
    }

    if (mobileFilterBtn) {
        mobileFilterBtn.addEventListener('click', openMobileFilters);
    }

    if (mobileFilterClose) {
        mobileFilterClose.addEventListener('click', closeMobileFilters);
    }

    if (mobileFilterBackdrop) {
        mobileFilterBackdrop.addEventListener('click', closeMobileFilters);
    }

    if (mobileSortBtn) {
        mobileSortBtn.addEventListener('click', () => {
            const sortDropdown = document.querySelector('.nv-sort-dropdown');
            if (sortDropdown) {
                openMobileFilters();
                // Close other dropdowns first, then open sort
                dropdowns.forEach(d => { if(d !== sortDropdown) d.classList.remove('open'); });
                setTimeout(() => {
                    sortDropdown.classList.add('open');
                }, 200);
            }
        });
    }

    // Apply Filters button click
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', () => {
            updatePills();
            triggerAJAX();
            closeMobileFilters();
        });
    }

    // Reset Filters button click
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', () => {
            const checkboxes = document.querySelectorAll('.nv-dropdown-menu input[type="checkbox"]');
            checkboxes.forEach(cb => cb.checked = false);
            updateStateFromDOM();
            updateBadges();
            updateMobileFilterCount();
            updatePills();
            triggerAJAX();
            closeMobileFilters();
        });
    }

    // Swipe to Close gesture
    if (filterSection) {
        let touchStartX = 0;
        let touchEndX = 0;

        filterSection.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        filterSection.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            // If swiped right by more than 80px (towards right edge)
            if (touchEndX - touchStartX > 80) {
                closeMobileFilters();
            }
        }, { passive: true });
    }

    // ==========================================
    // DROPDOWN TOGGLE
    // ==========================================
    dropdowns.forEach(dropdown => {
        const btn = dropdown.querySelector('.nv-dropdown-btn');
        if(btn) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                // Close others
                dropdowns.forEach(d => { if(d !== dropdown) d.classList.remove('open'); });
                dropdown.classList.toggle('open');
            });
        }
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.nv-custom-dropdown')) {
            dropdowns.forEach(d => d.classList.remove('open'));
        }
    });

    // ==========================================
    // CATEGORY CLICK
    // ==========================================
    catItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const cat = item.getAttribute('data-category');
            if (filterState.category === cat) {
                filterState.category = '';
                item.classList.remove('active');
            } else {
                catItems.forEach(c => c.classList.remove('active'));
                filterState.category = cat;
                item.classList.add('active');
            }
            triggerAJAX();
            updatePills();
        });
    });

    // ==========================================
    // CHECKBOX FILTER CHANGE
    // ==========================================
    const checkboxes = document.querySelectorAll('.nv-dropdown-menu input[type="checkbox"]');
    checkboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            updateStateFromDOM();
            updateBadges();
            updateMobileFilterCount();
            
            // Only update pills and trigger AJAX immediately on desktop screen widths
            if (window.innerWidth > 768) {
                updatePills();
                triggerAJAX();
            }
        });
    });

    // ==========================================
    // RADIO SORT CHANGE
    // ==========================================
    const radios = document.querySelectorAll('.nv-dropdown-menu input[type="radio"]');
    radios.forEach(radio => {
        radio.addEventListener('change', () => {
            const oldSortLabel = document.querySelector('.nv-sort-label');
            // Map values to short labels matching Figma style
            const labelMap = {
                'price-desc': 'HIGH TO LOW',
                'price': 'LOW TO HIGH',
                'alphabetical': 'A - Z',
                'date': 'NEWEST'
            };
            const newLabel = labelMap[radio.value] || radio.nextElementSibling.innerText;
            if (oldSortLabel) oldSortLabel.innerText = newLabel;
            
            filterState.orderby = radio.value;
            triggerAJAX();
            
            // close dropdowns on radio click
            dropdowns.forEach(d => d.classList.remove('open'));
        });
    });

    // ==========================================
    // STATE HELPERS
    // ==========================================
    function updateStateFromDOM() {
        filterState.attributes = {};
        dropdowns.forEach(dropdown => {
            const taxonomy = dropdown.getAttribute('data-filter');
            if (taxonomy) {
                const checked = dropdown.querySelectorAll('input[type="checkbox"]:checked');
                if (checked.length > 0) {
                    filterState.attributes[taxonomy] = Array.from(checked).map(cb => cb.value);
                }
            }
        });
    }

    function updateBadges() {
        dropdowns.forEach(dropdown => {
            const taxonomy = dropdown.getAttribute('data-filter');
            if (taxonomy) {
                const count = dropdown.querySelectorAll('input[type="checkbox"]:checked').length;
                const badge = dropdown.querySelector('.nv-badge-count');
                if (badge) {
                    if (count > 0) {
                        badge.style.display = 'inline-flex';
                        badge.innerText = count;
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        });
    }

    function updateMobileFilterCount() {
        const mobileCount = document.querySelector('.nv-mobile-filter-count');
        if (mobileCount) {
            let totalChecked = 0;
            dropdowns.forEach(dropdown => {
                const taxonomy = dropdown.getAttribute('data-filter');
                if (taxonomy) {
                    totalChecked += dropdown.querySelectorAll('input[type="checkbox"]:checked').length;
                }
            });
            if (filterState.category) totalChecked++;
            
            if (totalChecked > 0) {
                mobileCount.style.display = 'inline-flex';
                mobileCount.innerText = totalChecked;
            } else {
                mobileCount.style.display = 'none';
            }
        }
    }

    function updatePills() {
        activeFiltersContainer.innerHTML = '';
        
        // Category Pill
        if (filterState.category) {
            const activeCat = document.querySelector('.nv-cat-item.active span');
            if (activeCat) {
                createPill(activeCat.innerText, () => {
                    filterState.category = '';
                    document.querySelectorAll('.nv-cat-item').forEach(c => c.classList.remove('active'));
                    updateMobileFilterCount();
                    triggerAJAX();
                });
            }
        }

        // Attribute Pills
        dropdowns.forEach(dropdown => {
            const taxonomy = dropdown.getAttribute('data-filter');
            if (taxonomy) {
                const checked = dropdown.querySelectorAll('input[type="checkbox"]:checked');
                checked.forEach(cb => {
                    const name = cb.getAttribute('data-name');
                    createPill(name, () => {
                        cb.checked = false;
                        updateStateFromDOM();
                        updateBadges();
                        updateMobileFilterCount();
                        updatePills();
                        triggerAJAX();
                    });
                });
            }
        });
    }

    function createPill(text, onRemove) {
        const pill = document.createElement('div');
        pill.className = 'nv-filter-pill';
        pill.innerHTML = `<span>${text}</span> <span>&times;</span>`;
        pill.addEventListener('click', onRemove);
        activeFiltersContainer.appendChild(pill);
    }

    // ==========================================
    // AJAX FILTER REQUEST
    // ==========================================
    let ajaxTimeout = null;
    let requestCount = 0;

    function triggerAJAX() {
        if (ajaxTimeout) {
            clearTimeout(ajaxTimeout);
        }

        ajaxTimeout = setTimeout(() => {
            if (!productsContainer) return;

            // Provide immediate visual feedback
            productsContainer.classList.add('loading');
            productsContainer.style.opacity = '0.5';
            
            const currentRequest = ++requestCount;
            
            const params = new URLSearchParams();
            params.append('action', 'filter_shop_products');
            params.append('category', filterState.category);
            params.append('orderby', filterState.orderby);
            
            for (let tax in filterState.attributes) {
                if (filterState.attributes[tax]) {
                    filterState.attributes[tax].forEach(val => {
                        params.append(tax + '[]', val);
                    });
                }
            }

            fetch(shop_ajax.ajax_url + '?' + params.toString())
                .then(res => {
                    if (!res.ok) throw new Error("HTTP Status " + res.status);
                    return res.json();
                })
                .then(data => {
                    // Only process if this is still the latest request
                    if (currentRequest !== requestCount) {
                        console.log('Stale filter query result ignored.');
                        return;
                    }

                    if (data && data.html) {
                        productsContainer.innerHTML = data.html;
                        if (productCount) {
                            productCount.innerText = 'Showing 1 to ' + Math.min(24, data.count) + ' of ' + data.count + ' Products';
                        }
                        
                        // Scroll to top of grid after filter (with slight offset)
                        const gridTop = productsContainer.getBoundingClientRect().top + window.pageYOffset - 150;
                        window.scrollTo({ top: gridTop, behavior: 'smooth' });
                    }
                })
                .catch(err => {
                    console.error('Filter AJAX error:', err);
                })
                .finally(() => {
                    // Only clear loading state if this was the latest request
                    if (currentRequest === requestCount) {
                        productsContainer.classList.remove('loading');
                        productsContainer.style.opacity = '1';
                    }
                });
        }, 200); 
    }

    // Mobile tap details toggle (Event Delegation)
    document.addEventListener('click', (e) => {
        const toggleBtn = e.target.closest('.nv-mobile-details-toggle');
        if (toggleBtn) {
            e.preventDefault();
            e.stopPropagation();
            const card = toggleBtn.closest('.nv-product-card');
            if (card) {
                card.classList.toggle('nv-show-details');
                
                const infoIcon = toggleBtn.querySelector('.nv-info-icon');
                const closeIcon = toggleBtn.querySelector('.nv-close-icon');
                if (infoIcon && closeIcon) {
                    if (card.classList.contains('nv-show-details')) {
                        infoIcon.style.display = 'none';
                        closeIcon.style.display = 'block';
                    } else {
                        infoIcon.style.display = 'block';
                        closeIcon.style.display = 'none';
                    }
                }
            }
        }
    });
});
