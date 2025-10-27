class PropertyDetails {
    constructor() {
        this.apiUrl = '../api/properties/details.php';
        this.propertyId = new URLSearchParams(window.location.search).get('id');
        this.currentImageIndex = 0;
        this.images = [];
        this.init();
    }

    async init() {
        if (!this.propertyId) {
            this.showError('Property ID is missing');
            return;
        }

        await this.loadPropertyDetails();
        this.setupEventListeners();
    }

    async loadPropertyDetails() {
        try {
            const response = await fetch(`${this.apiUrl}?id=${this.propertyId}`);
            const data = await response.json();

            if (response.ok) {
                this.updatePropertyDetails(data);
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            console.error('Error loading property details:', error);
            this.showError('Failed to load property details');
        }
    }

    updatePropertyDetails(property) {
        // Update title and location
        document.getElementById('propertyTitle').textContent = property.title;
        document.getElementById('propertyLocation').textContent = `${property.address}, ${property.city}`;

        // Update meta information
        document.getElementById('propertyPrice').textContent = `₱${parseInt(property.price).toLocaleString()}/month`;
        document.getElementById('propertyType').textContent = property.room_type;
        document.getElementById('propertyCapacity').textContent = `${property.capacity} People`;
        document.getElementById('propertyRating').textContent = 
            `${property.reviews.average_rating} (${property.reviews.total_reviews} reviews)`;

        // Update description
        document.getElementById('propertyDescription').textContent = property.description;

        // Update amenities
        const amenitiesList = document.getElementById('amenitiesList');
        amenitiesList.innerHTML = property.amenities.map(amenity => `
            <div class="amenity-item">
                <i class="fas fa-check"></i>
                <span>${amenity}</span>
            </div>
        `).join('');

        // Update rules
        const rulesList = document.getElementById('rulesList');
        rulesList.innerHTML = property.rules.map(rule => `
            <li>${rule}</li>
        `).join('');

        // Update landlord information
        document.getElementById('landlordName').textContent = property.owner.name;
        document.getElementById('landlordPhone').textContent = property.owner.phone;
        document.getElementById('landlordEmail').textContent = property.owner.email;
        document.getElementById('landlordImage').src = property.owner.image || '../images/default-avatar.png';

        // Update save button
        const saveButton = document.getElementById('saveButton');
        if (property.is_saved) {
            saveButton.innerHTML = '<i class="fas fa-heart"></i> Saved';
            saveButton.classList.add('saved');
        }

        // Update reviews
        this.updateReviews(property.reviews);

        // Update image gallery
        this.images = property.images;
        this.updateImageGallery();
    }

    updateReviews(reviews) {
        document.getElementById('averageRating').textContent = reviews.average_rating;
        document.getElementById('reviewCount').textContent = `${reviews.total_reviews} reviews`;
        
        // Update rating stars
        const starsHtml = this.getStarRating(reviews.average_rating);
        document.getElementById('ratingStars').innerHTML = starsHtml;

        // Update reviews list
        const reviewsList = document.getElementById('reviewsList');
        reviewsList.innerHTML = reviews.items.map(review => `
            <div class="review-card">
                <div class="review-header">
                    <div class="reviewer-avatar">
                        <img src="${review.reviewer_image || '../images/default-avatar.png'}" alt="${review.reviewer_name}">
                    </div>
                    <div class="reviewer-info">
                        <div class="reviewer-name">${review.reviewer_name}</div>
                        <div class="review-date">${new Date(review.created_at).toLocaleDateString()}</div>
                    </div>
                    <div class="review-rating">
                        ${this.getStarRating(review.rating)}
                    </div>
                </div>
                <div class="review-content">
                    ${review.comment}
                </div>
            </div>
        `).join('');
    }

    getStarRating(rating) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        let stars = '';
        
        for (let i = 0; i < 5; i++) {
            if (i < fullStars) {
                stars += '<i class="fas fa-star"></i>';
            } else if (i === fullStars && hasHalfStar) {
                stars += '<i class="fas fa-star-half-alt"></i>';
            } else {
                stars += '<i class="far fa-star"></i>';
            }
        }
        
        return stars;
    }

    updateImageGallery() {
        if (!this.images || this.images.length === 0) {
            this.images = ['../images/default-property.jpg'];
        }

        // Update main image
        document.getElementById('mainImage').src = this.images[this.currentImageIndex];
        document.getElementById('mainImage').alt = `Image ${this.currentImageIndex + 1}`;

        // Update thumbnails
        const thumbnailsContainer = document.getElementById('thumbnails');
        thumbnailsContainer.innerHTML = this.images.map((image, index) => `
            <div class="gallery-thumbnail ${index === this.currentImageIndex ? 'active' : ''}" data-index="${index}">
                <img src="${image}" alt="Thumbnail ${index + 1}">
            </div>
        `).join('');
    }

    setupEventListeners() {
        // Image gallery navigation
        document.querySelector('.gallery-prev').addEventListener('click', () => this.changeImage(-1));
        document.querySelector('.gallery-next').addEventListener('click', () => this.changeImage(1));

        // Thumbnail clicks
        document.getElementById('thumbnails').addEventListener('click', (e) => {
            const thumbnail = e.target.closest('.gallery-thumbnail');
            if (thumbnail) {
                this.currentImageIndex = parseInt(thumbnail.dataset.index);
                this.updateImageGallery();
            }
        });

        // Save button
        document.getElementById('saveButton').addEventListener('click', () => this.toggleSave());

        // Contact button
        document.getElementById('contactButton').addEventListener('click', () => this.contactLandlord());
    }

    changeImage(direction) {
        this.currentImageIndex = (this.currentImageIndex + direction + this.images.length) % this.images.length;
        this.updateImageGallery();
    }

    async toggleSave() {
        try {
            const response = await fetch('../api/boarder/saved_properties.php', {
                method: this.isSaved ? 'DELETE' : 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    property_id: this.propertyId
                })
            });

            const data = await response.json();

            if (response.ok) {
                const saveButton = document.getElementById('saveButton');
                this.isSaved = !this.isSaved;
                
                if (this.isSaved) {
                    saveButton.innerHTML = '<i class="fas fa-heart"></i> Saved';
                    saveButton.classList.add('saved');
                } else {
                    saveButton.innerHTML = '<i class="far fa-heart"></i> Save';
                    saveButton.classList.remove('saved');
                }
            } else {
                console.error('Failed to toggle save:', data.message);
            }
        } catch (error) {
            console.error('Error toggling save:', error);
        }
    }

    contactLandlord() {
        const landlordEmail = document.getElementById('landlordEmail').textContent;
        const subject = encodeURIComponent(`Inquiry about: ${document.getElementById('propertyTitle').textContent}`);
        window.location.href = `mailto:${landlordEmail}?subject=${subject}`;
    }

    showError(message) {
        const container = document.querySelector('.property-details-container');
        container.innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <h2>Error</h2>
                <p>${message}</p>
                <a href="../search.html" class="action-btn primary">Back to Search</a>
            </div>
        `;
    }
}

// Initialize when the document is ready
document.addEventListener('DOMContentLoaded', () => {
    new PropertyDetails();
});