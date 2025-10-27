class ProfileManager {
    constructor() {
        this.initializeTabs();
        this.loadProfileData();
        this.setupImageUpload();
        this.setupFormSubmission();
        this.loadReviews();
        this.loadSavedProperties();
    }

    initializeTabs() {
        const tabs = document.querySelectorAll('.tab-button');
        const contents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs and contents
                tabs.forEach(t => t.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));

                // Add active class to clicked tab and corresponding content
                tab.classList.add('active');
                document.getElementById(`${tab.dataset.tab}Tab`).classList.add('active');
            });
        });
    }

    async loadProfileData() {
        try {
            const response = await fetch('../api/boarder/profile.php');
            const data = await response.json();

            if (response.ok) {
                document.getElementById('userName').textContent = data.name;
                document.getElementById('profileImage').src = data.profile_image || '../images/default-avatar.png';
                
                // Fill form fields
                document.getElementById('name').value = data.name;
                document.getElementById('email').value = data.email;
                document.getElementById('phone').value = data.phone;

                // Update stats
                document.getElementById('savedCount').textContent = data.saved_count;
                document.getElementById('reviewCount').textContent = data.review_count;
            }
        } catch (error) {
            console.error('Error loading profile:', error);
            alert('Error loading profile data');
        }
    }

    setupImageUpload() {
        const imageInput = document.getElementById('imageInput');
        imageInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                alert('Please upload an image file');
                return;
            }

            const formData = new FormData();
            formData.append('profile_image', file);

            try {
                const response = await fetch('../api/boarder/update_profile_image.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (response.ok) {
                    document.getElementById('profileImage').src = data.image_url;
                } else {
                    alert(data.message || 'Error updating profile image');
                }
            } catch (error) {
                console.error('Error uploading image:', error);
                alert('Error uploading image');
            }
        });
    }

    setupFormSubmission() {
        const form = document.getElementById('profileForm');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                current_password: document.getElementById('currentPassword').value,
                new_password: document.getElementById('newPassword').value || null
            };

            try {
                const response = await fetch('../api/boarder/update_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                if (response.ok) {
                    alert('Profile updated successfully');
                    document.getElementById('userName').textContent = formData.name;
                    document.getElementById('currentPassword').value = '';
                    document.getElementById('newPassword').value = '';
                } else {
                    alert(data.message || 'Error updating profile');
                }
            } catch (error) {
                console.error('Error updating profile:', error);
                alert('Error updating profile');
            }
        });
    }

    async loadReviews() {
        try {
            const response = await fetch('../api/boarder/my_reviews.php');
            const data = await response.json();

            const reviewsList = document.getElementById('reviewsList');
            reviewsList.innerHTML = '';

            if (data.reviews.length === 0) {
                reviewsList.innerHTML = '<p class="no-data">You haven\'t written any reviews yet.</p>';
                return;
            }

            data.reviews.forEach(review => {
                const reviewElement = this.createReviewElement(review);
                reviewsList.appendChild(reviewElement);
            });
        } catch (error) {
            console.error('Error loading reviews:', error);
        }
    }

    createReviewElement(review) {
        const div = document.createElement('div');
        div.className = 'review-card';
        
        const stars = '★'.repeat(review.rating) + '☆'.repeat(5 - review.rating);
        
        div.innerHTML = `
            <div class="review-property">
                <img src="${review.property_image}" alt="${review.property_name}" class="property-thumbnail">
                <div class="property-info">
                    <h3>${review.property_name}</h3>
                    <p>${review.property_location}</p>
                </div>
            </div>
            <div class="review-rating">${stars}</div>
            <div class="review-text">${review.comment}</div>
            <div class="review-date">${new Date(review.created_at).toLocaleDateString()}</div>
            <button class="action-button remove-saved" onclick="profileManager.deleteReview(${review.id})">
                Delete Review
            </button>
        `;

        return div;
    }

    async deleteReview(reviewId) {
        if (!confirm('Are you sure you want to delete this review?')) return;

        try {
            const response = await fetch(`../api/boarder/reviews.php?id=${reviewId}`, {
                method: 'DELETE'
            });

            if (response.ok) {
                await this.loadReviews();
                await this.loadProfileData(); // Update review count
            } else {
                const data = await response.json();
                alert(data.message || 'Error deleting review');
            }
        } catch (error) {
            console.error('Error deleting review:', error);
            alert('Error deleting review');
        }
    }

    async loadSavedProperties() {
        try {
            const response = await fetch('../api/boarder/saved_properties.php');
            const data = await response.json();

            const savedList = document.getElementById('savedList');
            savedList.innerHTML = '';

            if (data.properties.length === 0) {
                savedList.innerHTML = '<p class="no-data">You haven\'t saved any properties yet.</p>';
                return;
            }

            data.properties.forEach(property => {
                const propertyElement = this.createPropertyElement(property);
                savedList.appendChild(propertyElement);
            });
        } catch (error) {
            console.error('Error loading saved properties:', error);
        }
    }

    createPropertyElement(property) {
        const div = document.createElement('div');
        div.className = 'property-card';
        
        div.innerHTML = `
            <img src="${property.image}" alt="${property.title}" class="property-image">
            <div class="property-details">
                <h3>${property.title}</h3>
                <p><i class="fas fa-map-marker-alt"></i> ${property.location}</p>
                <p><i class="fas fa-home"></i> ${property.type}</p>
                <div class="property-price">₱${property.price}/month</div>
                <div class="property-actions">
                    <button class="action-button view-property" onclick="location.href='view_property.html?id=${property.id}'">
                        View Property
                    </button>
                    <button class="action-button remove-saved" onclick="profileManager.unsaveProperty(${property.id})">
                        Remove from Saved
                    </button>
                </div>
            </div>
        `;

        return div;
    }

    async unsaveProperty(propertyId) {
        try {
            const response = await fetch(`../api/boarder/saved_properties.php?property_id=${propertyId}`, {
                method: 'DELETE'
            });

            if (response.ok) {
                await this.loadSavedProperties();
                await this.loadProfileData(); // Update saved count
            } else {
                const data = await response.json();
                alert(data.message || 'Error removing property from saved');
            }
        } catch (error) {
            console.error('Error removing property:', error);
            alert('Error removing property from saved');
        }
    }
}

// Initialize the profile manager
const profileManager = new ProfileManager();