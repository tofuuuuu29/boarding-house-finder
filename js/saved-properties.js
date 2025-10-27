// Saved Properties Handler
class SavedPropertiesHandler {
    constructor() {
        this.apiUrl = '../api/boarder/saved_properties.php';
        this.init();
    }

    init() {
        this.loadSavedProperties();
        this.setupEventListeners();
    }

    async loadSavedProperties() {
        try {
            const response = await fetch(this.apiUrl);
            const data = await response.json();
            
            if (response.ok) {
                this.updatePropertiesDisplay(data.properties);
                this.updateSavedCount(data.properties.length);
            } else {
                console.error('Error:', data.message);
            }
        } catch (error) {
            console.error('Failed to load saved properties:', error);
        }
    }

    updatePropertiesDisplay(properties) {
        const container = document.querySelector('.properties-container');
        if (!container) return;

        container.innerHTML = properties.map(property => this.createPropertyCard(property)).join('');
    }

    updateSavedCount(count) {
        const savedCountElement = document.querySelector('.stat-content h3');
        if (savedCountElement) {
            savedCountElement.textContent = count;
        }
    }

    createPropertyCard(property) {
        const initials = property.title
            .split(' ')
            .map(word => word[0])
            .join('')
            .substring(0, 2)
            .toUpperCase();

        return `
            <div class="property-card" data-id="${property.id}">
                <div class="property-header">
                    <div class="property-avatar">${initials}</div>
                    <div class="property-info">
                        <p>${property.title}</p>
                        <span>Saved: ${new Date(property.saved_at).toLocaleDateString()}</span>
                    </div>
                </div>
                <div class="property-image">
                    <div class="property-badges">
                        <span class="badge type">${property.room_type}</span>
                        <span class="badge status">Available</span>
                    </div>
                    <img src="../images/house-img-1.webp" alt="${property.title}">
                </div>
                <div class="property-details">
                    <h3>${property.title}</h3>
                    <p class="location"><i class="fas fa-map-marker-alt"></i> ${property.address}</p>
                    <div class="property-stats">
                        <div class="stat">
                            <i class="fas fa-peso-sign"></i>
                            <span>₱${property.price}/month</span>
                        </div>
                        <div class="stat">
                            <i class="fas fa-user"></i>
                            <span>Capacity: ${property.capacity}</span>
                        </div>
                    </div>
                </div>
                <div class="property-actions">
                    <a href="#" class="btn secondary remove-property">Remove</a>
                    <a href="../view_property.html?id=${property.id}" class="btn primary">View Details</a>
                </div>
            </div>
        `;
    }

    setupEventListeners() {
        document.addEventListener('click', async (e) => {
            if (e.target.classList.contains('remove-property')) {
                e.preventDefault();
                const propertyCard = e.target.closest('.property-card');
                if (propertyCard) {
                    const propertyId = propertyCard.dataset.id;
                    await this.removeProperty(propertyId, propertyCard);
                }
            }
        });
    }

    async removeProperty(propertyId, cardElement) {
        if (!confirm('Are you sure you want to remove this property from your saved list?')) {
            return;
        }

        try {
            const response = await fetch(`${this.apiUrl}?property_id=${propertyId}`, {
                method: 'DELETE'
            });

            const data = await response.json();
            
            if (response.ok) {
                cardElement.remove();
                const currentCount = parseInt(document.querySelector('.stat-content h3').textContent);
                this.updateSavedCount(currentCount - 1);
            } else {
                console.error('Error:', data.message);
            }
        } catch (error) {
            console.error('Failed to remove property:', error);
        }
    }
}

// Initialize when the document is ready
document.addEventListener('DOMContentLoaded', () => {
    new SavedPropertiesHandler();
});