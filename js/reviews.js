class ReviewManager {
    constructor() {
        this.propertyId = new URLSearchParams(window.location.search).get('id');
        this.initializeReviews();
        this.setupReviewForm();
    }

    async initializeReviews() {
        try {
            const response = await fetch(`../api/boarder/reviews.php?property_id=${this.propertyId}`);
            const data = await response.json();
            
            this.updateReviewSummary(data);
            this.displayReviews(data.reviews);
        } catch (error) {
            console.error('Error fetching reviews:', error);
        }
    }

    updateReviewSummary(data) {
        document.getElementById('averageRating').textContent = data.average_rating || '0.0';
        document.getElementById('reviewCount').textContent = `${data.total} review${data.total !== 1 ? 's' : ''}`;
        this.updateStarRating(data.average_rating || 0);
    }

    updateStarRating(rating) {
        const starsElement = document.getElementById('ratingStars');
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        
        let starsHTML = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= fullStars) {
                starsHTML += '<i class="fas fa-star"></i>';
            } else if (i === fullStars + 1 && hasHalfStar) {
                starsHTML += '<i class="fas fa-star-half-alt"></i>';
            } else {
                starsHTML += '<i class="far fa-star"></i>';
            }
        }
        starsElement.innerHTML = starsHTML;
    }

    displayReviews(reviews) {
        const reviewsList = document.getElementById('reviewsList');
        reviewsList.innerHTML = '';

        if (reviews.length === 0) {
            reviewsList.innerHTML = '<p class="no-reviews">No reviews yet. Be the first to review!</p>';
            return;
        }

        reviews.forEach(review => {
            const reviewElement = this.createReviewElement(review);
            reviewsList.appendChild(reviewElement);
        });
    }

    createReviewElement(review) {
        const reviewCard = document.createElement('div');
        reviewCard.className = 'review-card';
        
        const starsHTML = this.getStarRating(review.rating);
        
        reviewCard.innerHTML = `
            <div class="review-header">
                <div class="reviewer-avatar">
                    <img src="${review.reviewer_image || '../images/default-avatar.png'}" alt="${review.reviewer_name}">
                </div>
                <div class="reviewer-info">
                    <h3 class="reviewer-name">${review.reviewer_name}</h3>
                    <div class="review-date">${this.formatDate(review.created_at)}</div>
                </div>
                <div class="review-rating">
                    ${starsHTML}
                </div>
            </div>
            <div class="review-content">
                ${review.comment}
            </div>
            ${this.getReviewImages(review.images)}
        `;

        return reviewCard;
    }

    getStarRating(rating) {
        return Array(5).fill(0)
            .map((_, index) => index < rating ? 
                '<i class="fas fa-star"></i>' : 
                '<i class="far fa-star"></i>')
            .join('');
    }

    getReviewImages(images) {
        if (!images || images.length === 0) return '';

        return `
            <div class="review-images">
                ${images.map(image => `
                    <img src="${image}" alt="Review image" class="review-image">
                `).join('')}
            </div>
        `;
    }

    formatDate(dateString) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    }

    setupReviewForm() {
        // Create and append review form
        const formHTML = `
            <div class="review-form-container">
                <h3>Write a Review</h3>
                <form id="reviewForm" class="review-form">
                    <div class="rating-input">
                        <label>Your Rating:</label>
                        <div class="star-rating">
                            <i class="far fa-star" data-rating="1"></i>
                            <i class="far fa-star" data-rating="2"></i>
                            <i class="far fa-star" data-rating="3"></i>
                            <i class="far fa-star" data-rating="4"></i>
                            <i class="far fa-star" data-rating="5"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="reviewComment">Your Review:</label>
                        <textarea id="reviewComment" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="reviewImages">Add Images (optional):</label>
                        <input type="file" id="reviewImages" multiple accept="image/*">
                    </div>
                    <button type="submit" class="submit-review">Submit Review</button>
                </form>
            </div>
        `;

        const reviewsSection = document.querySelector('.reviews-section');
        reviewsSection.insertAdjacentHTML('beforeend', formHTML);

        // Setup star rating interaction
        const starRating = document.querySelector('.star-rating');
        let selectedRating = 0;

        starRating.addEventListener('mouseover', (e) => {
            if (e.target.tagName === 'I') {
                const rating = parseInt(e.target.dataset.rating);
                this.updateStarSelection(rating);
            }
        });

        starRating.addEventListener('mouseout', () => {
            this.updateStarSelection(selectedRating);
        });

        starRating.addEventListener('click', (e) => {
            if (e.target.tagName === 'I') {
                selectedRating = parseInt(e.target.dataset.rating);
                this.updateStarSelection(selectedRating);
            }
        });

        // Handle form submission
        const form = document.getElementById('reviewForm');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!selectedRating) {
                alert('Please select a rating');
                return;
            }

            const comment = document.getElementById('reviewComment').value;
            const imageFiles = document.getElementById('reviewImages').files;
            const images = [];

            // Handle image uploads if any
            if (imageFiles.length > 0) {
                for (const file of imageFiles) {
                    const base64 = await this.convertToBase64(file);
                    images.push(base64);
                }
            }

            try {
                const response = await fetch('../api/boarder/reviews.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        property_id: this.propertyId,
                        rating: selectedRating,
                        comment,
                        images
                    })
                });

                const data = await response.json();
                if (response.ok) {
                    alert('Review submitted successfully!');
                    form.reset();
                    selectedRating = 0;
                    this.updateStarSelection(0);
                    this.initializeReviews();
                } else {
                    alert(data.message || 'Error submitting review');
                }
            } catch (error) {
                console.error('Error submitting review:', error);
                alert('Error submitting review. Please try again.');
            }
        });
    }

    updateStarSelection(rating) {
        const stars = document.querySelectorAll('.star-rating i');
        stars.forEach((star, index) => {
            if (index < rating) {
                star.className = 'fas fa-star';
            } else {
                star.className = 'far fa-star';
            }
        });
    }

    async convertToBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = () => resolve(reader.result);
            reader.onerror = error => reject(error);
        });
    }
}

// Initialize the review manager
document.addEventListener('DOMContentLoaded', () => {
    new ReviewManager();
});