<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Boarder Dashboard</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="../css/style.css">
   <style>
      .dashboard, .quick-actions, .favorites-section {
         max-width: 1200px;
         margin: 2rem auto;
         padding: 0 2rem;
      }

      .heading {
         font-size: 2.5rem;
         color: var(--black);
         margin-bottom: 2rem;
         text-align: left;
         padding-bottom: 1rem;
         border-bottom: var(--border);
      }

      .stats-container {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
         gap: 2rem;
         margin-bottom: 3rem;
      }

      .stat-card {
         background: var(--white);
         padding: 2rem;
         border-radius: 10px;
         box-shadow: var(--box-shadow);
         display: flex;
         align-items: center;
         gap: 2rem;
      }

      .stat-icon {
         width: 6rem;
         height: 6rem;
         background: var(--main-color);
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         color: var(--white);
         font-size: 2.5rem;
      }

      .stat-content h3 {
         font-size: 3rem;
         color: var(--black);
         margin-bottom: 0.5rem;
      }

      .stat-content p {
         color: var(--light-color);
         font-size: 1.6rem;
         margin-bottom: 1rem;
      }

      .stat-btn {
         background: var(--main-color);
         color: var(--white);
         padding: 0.8rem 1.5rem;
         border-radius: 5px;
         font-size: 1.4rem;
         transition: all 0.3s ease;
      }

      .stat-btn:hover {
         background: var(--black);
      }

      .actions-container {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
         gap: 2rem;
         margin-bottom: 3rem;
      }

      .action-card {
         background: var(--white);
         padding: 2rem;
         border-radius: 10px;
         box-shadow: var(--box-shadow);
         display: flex;
         align-items: center;
         gap: 2rem;
      }

      .action-icon {
         width: 6rem;
         height: 6rem;
         background: var(--main-color);
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         color: var(--white);
         font-size: 2.5rem;
      }

      .action-content h3 {
         font-size: 2rem;
         color: var(--black);
         margin-bottom: 0.5rem;
      }

      .action-content p {
         color: var(--light-color);
         font-size: 1.4rem;
         margin-bottom: 1rem;
      }

      .action-btn {
         background: var(--main-color);
         color: var(--white);
         padding: 0.8rem 1.5rem;
         border-radius: 5px;
         font-size: 1.4rem;
         transition: all 0.3s ease;
      }

      .action-btn:hover {
         background: var(--black);
      }

      .properties-container {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
         gap: 2rem;
         margin-bottom: 2rem;
      }

      .property-card {
         background: var(--white);
         border-radius: 10px;
         box-shadow: var(--box-shadow);
         overflow: hidden;
      }

      .property-header {
         padding: 1.5rem;
         display: flex;
         align-items: center;
         gap: 1rem;
         border-bottom: var(--border);
      }

      .property-avatar {
         width: 4rem;
         height: 4rem;
         background: var(--main-color);
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         color: var(--white);
         font-weight: bold;
         font-size: 1.6rem;
      }

      .property-info p {
         font-size: 1.6rem;
         color: var(--black);
         font-weight: bold;
      }

      .property-info span {
         font-size: 1.2rem;
         color: var(--light-color);
      }

      .property-image {
         position: relative;
         height: 20rem;
         overflow: hidden;
      }

      .property-image img {
         width: 100%;
         height: 100%;
         object-fit: cover;
      }

      .property-badges {
         position: absolute;
         top: 1rem;
         left: 1rem;
         display: flex;
         gap: 0.5rem;
      }

      .badge {
         padding: 0.5rem 1rem;
         border-radius: 5px;
         font-size: 1.2rem;
         font-weight: bold;
      }

      .badge.type {
         background: var(--main-color);
         color: var(--white);
      }

      .badge.status {
         background: #27ae60;
         color: var(--white);
      }

      .property-details {
         padding: 1.5rem;
      }

      .property-details h3 {
         font-size: 1.8rem;
         color: var(--black);
         margin-bottom: 1rem;
      }

      .location {
         color: var(--light-color);
         font-size: 1.4rem;
         margin-bottom: 1rem;
         display: flex;
         align-items: center;
         gap: 0.5rem;
      }

      .property-stats {
         display: flex;
         gap: 2rem;
         margin-bottom: 1rem;
      }

      .stat {
         display: flex;
         align-items: center;
         gap: 0.5rem;
         color: var(--light-color);
         font-size: 1.4rem;
      }

      .property-actions {
         padding: 1.5rem;
         border-top: var(--border);
         display: flex;
         gap: 1rem;
      }

      .btn {
         padding: 1rem 2rem;
         border-radius: 5px;
         font-size: 1.4rem;
         font-weight: bold;
         text-align: center;
         flex: 1;
         transition: all 0.3s ease;
      }

      .btn.primary {
         background: var(--main-color);
         color: var(--white);
      }

      .btn.primary:hover {
         background: var(--black);
      }

      .btn.secondary {
         background: var(--white);
         color: var(--black);
         border: var(--border);
      }

      .btn.secondary:hover {
         background: var(--light-bg);
      }

      .view-all-container {
         text-align: center;
      }

      .view-all-btn {
         background: var(--main-color);
         color: var(--white);
         padding: 1.2rem 3rem;
         border-radius: 5px;
         font-size: 1.6rem;
         font-weight: bold;
         transition: all 0.3s ease;
      }

      .view-all-btn:hover {
         background: var(--black);
      }
   </style>
</head>
<body>
   
<header class="header">
   <nav class="navbar nav-1">
      <section class="flex">
         <a href="dashboard.php" class="logo"><i class="fas fa-house"></i>MyHome</a>
      </section>
   </nav>

   <nav class="navbar nav-2">
      <section class="flex">
         <div id="menu-btn" class="fas fa-bars"></div>
         <div class="menu">
            <ul>
               <li><a href="dashboard.php">Dashboard</a></li>
               <li><a href="../search.html">Find Housing</a></li>
               <li><a href="saved-properties.html">Saved Properties</a></li>
            </ul>
         </div>
         <ul>
            <li><a href="profile.php"><i class="fas fa-user"></i> <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User'; ?></a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out"></i> Logout</a></li>
         </ul>
      </section>
   </nav>
</header>

<section class="dashboard">
   <h1 class="heading">Dashboard Overview</h1>
   
   <div class="stats-container">
      <div class="stat-card">
         <div class="stat-icon">
            <i class="fas fa-heart"></i>
         </div>
         <div class="stat-content">
            <h3 id="savedCount">0</h3>
            <p>Saved Properties</p>
            <a href="saved-properties.html" class="stat-btn">View All</a>
         </div>
      </div>

      <div class="stat-card">
         <div class="stat-icon">
            <i class="fas fa-eye"></i>
         </div>
         <div class="stat-content">
            <h3 id="viewedCount">0</h3>
            <p>Properties Viewed</p>
            <a href="../search.html" class="stat-btn">Browse More</a>
         </div>
      </div>
   </div>
</section>

<section class="quick-actions">
   <h1 class="heading">Quick Actions</h1>
   <div class="actions-container">
      <div class="action-card">
         <div class="action-icon">
            <i class="fas fa-search"></i>
         </div>
         <div class="action-content">
            <h3>Find Housing</h3>
            <p>Search for available properties</p>
            <a href="../search.html" class="action-btn">Search now</a>
         </div>
      </div>
      
      <div class="action-card">
         <div class="action-icon">
            <i class="fas fa-heart"></i>
         </div>
         <div class="action-content">
            <h3>Saved Properties</h3>
            <p>View your saved listings</p>
            <a href="saved-properties.html" class="action-btn">View Saved</a>
         </div>
      </div>
   </div>
</section>

<section class="recent-properties">
   <h1 class="heading">Recent Properties</h1>
   <div class="properties-container" id="recentPropertiesContainer">
      <!-- Properties will be loaded here via JavaScript -->
      <p style="text-align: center; padding: 2rem; color: var(--light-color);">Loading properties...</p>
   </div>
   <div class="view-all-container">
      <a href="../search.html" class="view-all-btn">View All Properties</a>
   </div>
</section>

<script src="../js/script.js"></script>
<script src="../js/saved-properties.js"></script>
<script>
// Load saved properties count and recent properties
function loadStats() {
    // Get saved properties count
    fetch('../api/boarder/saved_properties.php')
        .then(response => response.json())
        .then(data => {
            if (data.properties) {
                document.getElementById('savedCount').textContent = data.properties.length;
            }
        })
        .catch(error => {
            console.error('Error loading stats:', error);
        });
    
    // Load recent properties from search API
    loadRecentProperties();
}

function loadRecentProperties() {
    // Fetch recent available properties
    fetch('../api/properties/search.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            location: '',
            room_type: '',
            capacity: '',
            min_price: 0,
            max_price: 100000
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.properties && data.properties.length > 0) {
            displayRecentProperties(data.properties);
        } else {
            document.getElementById('recentPropertiesContainer').innerHTML = 
                '<p style="text-align: center; padding: 2rem; color: var(--light-color);">No properties available yet.</p>';
        }
    })
    .catch(error => {
        console.error('Error loading recent properties:', error);
        document.getElementById('recentPropertiesContainer').innerHTML = 
            '<p style="text-align: center; padding: 2rem; color: var(--light-color);">Unable to load properties.</p>';
    });
}

function displayRecentProperties(properties) {
    const container = document.getElementById('recentPropertiesContainer');
    container.innerHTML = '';
    
    // Show only the 2 most recent properties
    const recentProperties = properties.slice(0, 2);
    
    recentProperties.forEach(property => {
        const initials = property.title ? property.title.split(' ').map(word => word[0]).join('').substring(0, 2).toUpperCase() : 'P';
        
        const propertyCard = `
            <div class="property-card">
               <div class="property-header">
                  <div class="property-avatar">${initials}</div>
                  <div class="property-info">
                     <p>${property.owner_name || 'Landlord'}</p>
                     <span>Posted: ${property.created_at ? new Date(property.created_at).toLocaleDateString() : 'N/A'}</span>
                  </div>
               </div>
               <div class="property-image">
                  <div class="property-badges">
                     <span class="badge type">${property.room_type || 'N/A'}</span>
                     <span class="badge status">Available</span>
                  </div>
                  <img src="../images/house-img-1.webp" alt="${property.title || 'Property'}">
               </div>
               <div class="property-details">
                  <h3>${property.title || 'Untitled Property'}</h3>
                  <p class="location"><i class="fas fa-map-marker-alt"></i> ${property.address || 'Address not provided'}</p>
                  <div class="property-stats">
                     <div class="stat">
                        <i class="fas fa-peso-sign"></i>
                        <span>₱${property.price || '0'}/month</span>
                     </div>
                     <div class="stat">
                        <i class="fas fa-user"></i>
                        <span>Capacity: ${property.capacity || 'N/A'}</span>
                     </div>
                  </div>
               </div>
               <div class="property-actions">
                  <a href="../view_property.html?id=${property.id}" class="btn secondary">View Details</a>
                  <a href="../search.html" class="btn primary">Browse More</a>
               </div>
            </div>
        `;
        container.innerHTML += propertyCard;
    });
}

// Load stats when page loads
document.addEventListener('DOMContentLoaded', loadStats);
</script>
</body>
</html>

