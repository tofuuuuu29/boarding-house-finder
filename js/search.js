class PropertySearch {
    constructor() {
        this.apiUrl = 'http://localhost/boarding%20house%20finder/design/api/properties/search.php';
        this.currentFilters = {
            keyword: '',
            min_price: '',
            max_price: '',
            room_type: '',
            city: '',
            capacity: ''
        };
        this.init();
    }

    init() {
        this.setupSearchForm();
        this.setupFilterListeners();
        this.performSearch(); // Initial search
    }

    setupSearchForm() {
        const searchForm = document.getElementById('searchForm');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.performSearch();
            });
        }
    }

    setupFilterListeners() {
        // Price range inputs
        const priceInputs = document.querySelectorAll('input[type="range"]');
        priceInputs.forEach(input => {
            input.addEventListener('input', () => {
                this.updatePriceLabels();
                this.debounceSearch();
            });
        });

        // Other filters
        const filters = document.querySelectorAll('.filter-input');
        filters.forEach(filter => {
            filter.addEventListener('change', () => this.debounceSearch());
        });

        // Search input
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', () => this.debounceSearch());
        }
    }

    debounceSearch() {
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }
        this.debounceTimer = setTimeout(() => this.performSearch(), 500);
    }

    updatePriceLabels() {
        const minPrice = document.getElementById('minPrice');
        const maxPrice = document.getElementById('maxPrice');
        const minLabel = document.getElementById('minPriceLabel');
        const maxLabel = document.getElementById('maxPriceLabel');

        if (minPrice && minLabel) {
            minLabel.textContent = `₱${parseInt(minPrice.value).toLocaleString()}`;
        }
        if (maxPrice && maxLabel) {
            maxLabel.textContent = `₱${parseInt(maxPrice.value).toLocaleString()}`;
        }
    }

    async performSearch() {
        const searchInput = document.getElementById('searchInput');
        const minPrice = document.getElementById('minPrice');
        const maxPrice = document.getElementById('maxPrice');
        const roomType = document.getElementById('roomType');
        const city = document.getElementById('city');
        const capacity = document.getElementById('capacity');

        this.currentFilters = {
            keyword: searchInput ? searchInput.value : '',
            min_price: minPrice ? minPrice.value : '',
            max_price: maxPrice ? maxPrice.value : '',
            room_type: roomType ? roomType.value : '',
            city: city ? city.value : '',
            capacity: capacity ? capacity.value : ''
        };

        try {
            const queryString = new URLSearchParams(this.currentFilters).toString();
            const response = await fetch(`${this.apiUrl}?${queryString}`);
            const data = await response.json();

            if (response.ok) {
                this.updateResults(data);
                this.updateFilterOptions(data.filters);
            } else {
                console.error('Search failed:', data.message);
            }
        } catch (error) {
            console.error('Error performing search:', error);
        }
    }

    updateResults(data) {
        const resultsContainer = document.querySelector('.properties-container');
        const totalResults = document.getElementById('totalResults');

        if (totalResults) {
            totalResults.textContent = `${data.total} properties found`;
        }

        if (resultsContainer) {
            if (data.properties.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>No properties found</h3>
                        <p>Try adjusting your search criteria</p>
                    </div>
                `;
                return;
            }

            resultsContainer.innerHTML = data.properties.map(property => this.createPropertyCard(property)).join('');
        }
    }

    createPropertyCard(property) {
        const initials = property.title
            .split(' ')
            .map(word => word[0])
            .join('')
            .substring(0, 2)
            .toUpperCase();

        const images = property.images && property.images.length > 0 
            ? property.images[0] 
            : '../images/house-img-1.webp';

        return `
            <div class="property-card" data-id="${property.id}">
                <div class="property-header">
                    <div class="property-avatar">${initials}</div>
                    <div class="property-info">
                        <p>${property.title}</p>
                        <span>Listed by: ${property.owner_name}</span>
                    </div>
                </div>
                <div class="property-image">
                    <div class="property-badges">
                        <span class="badge type">${property.room_type}</span>
                        <span class="badge status">Available</span>
                    </div>
                    <img src="${images}" alt="${property.title}">
                </div>
                <div class="property-details">
                    <h3>${property.title}</h3>
                    <p class="location"><i class="fas fa-map-marker-alt"></i> ${property.address}, ${property.city}</p>
                    <div class="property-stats">
                        <div class="stat">
                            <i class="fas fa-peso-sign"></i>
                            <span>₱${parseInt(property.price).toLocaleString()}/month</span>
                        </div>
                        <div class="stat">
                            <i class="fas fa-user"></i>
                            <span>Capacity: ${property.capacity}</span>
                        </div>
                    </div>
                </div>
                <div class="property-actions">
                    ${property.is_saved 
                        ? '<button class="btn secondary remove-property">Remove from Saved</button>'
                        : '<button class="btn secondary save-property">Save Property</button>'
                    }
                    <a href="../view_property.html?id=${property.id}" class="btn primary">View Details</a>
                </div>
            </div>
        `;
    }

    updateFilterOptions(filters) {
        // Update room type options
        const roomTypeSelect = document.getElementById('roomType');
        if (roomTypeSelect && filters.room_types) {
            const currentValue = roomTypeSelect.value;
            roomTypeSelect.innerHTML = `
                <option value="">All Room Types</option>
                ${filters.room_types.map(type => `
                    <option value="${type}" ${type === currentValue ? 'selected' : ''}>
                        ${type}
                    </option>
                `).join('')}
            `;
        }

        // Update city options
        const citySelect = document.getElementById('city');
        if (citySelect && filters.cities) {
            const currentValue = citySelect.value;
            citySelect.innerHTML = `
                <option value="">All Cities</option>
                ${filters.cities.map(city => `
                    <option value="${city}" ${city === currentValue ? 'selected' : ''}>
                        ${city}
                    </option>
                `).join('')}
            `;
        }

        // Update price range
        if (filters.price_range) {
            const minPrice = document.getElementById('minPrice');
            const maxPrice = document.getElementById('maxPrice');
            if (minPrice && maxPrice) {
                minPrice.min = filters.price_range.min;
                minPrice.max = filters.price_range.max;
                maxPrice.min = filters.price_range.min;
                maxPrice.max = filters.price_range.max;

                if (!minPrice.value) minPrice.value = filters.price_range.min;
                if (!maxPrice.value) maxPrice.value = filters.price_range.max;
                
                this.updatePriceLabels();
            }
        }

        // Update capacity options
        const capacitySelect = document.getElementById('capacity');
        if (capacitySelect && filters.max_capacity) {
            const currentValue = capacitySelect.value;
            capacitySelect.innerHTML = `
                <option value="">Any Capacity</option>
                ${Array.from({length: filters.max_capacity}, (_, i) => i + 1).map(num => `
                    <option value="${num}" ${num.toString() === currentValue ? 'selected' : ''}>
                        ${num}+ People
                    </option>
                `).join('')}
            `;
        }
    }
}

// Initialize when the document is ready
document.addEventListener('DOMContentLoaded', () => {
    new PropertySearch();
});