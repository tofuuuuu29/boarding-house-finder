<?php 
session_start();
include_once '../config/database.php';

// Get user data
$database = new Database();
$db = $database->getConnection();

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_data = null;

if ($user_id) {
    $query = "SELECT name, email, phone FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>My Profile</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="../css/style.css">
</head>
<body>
   
<header class="header">
   <nav class="navbar nav-1">
      <section class="flex">
         <a href="dashboard.php" class="logo"><i class="fas fa-house"></i>MyHome </a>
      </section>
   </nav>

   <nav class="navbar nav-2">
      <section class="flex">
         <div id="menu-btn" class="fas fa-bars"></div>
         <div class="menu">
            <ul>
               <li><a href="dashboard.php">Dashboard</a></li>
            </ul>
         </div>
         <ul>
            <li><a href="profile.php"><i class="fas fa-user"></i> <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Boarder'; ?></a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out"></i> Logout</a></li>
         </ul>
      </section>
   </nav>
</header>

<section class="form-container">
   <form id="profileForm" method="post" action="">
      <h3>My Profile</h3>
      
      <div class="box">
         <p>Full Name <span>*</span></p>
         <input type="text" name="name" required maxlength="50" value="<?php echo $user_data ? htmlspecialchars($user_data['name']) : ''; ?>" class="input">
      </div>

      <div class="box">
         <p>Email Address <span>*</span></p>
         <input type="email" name="email" required maxlength="50" value="<?php echo $user_data ? htmlspecialchars($user_data['email']) : ''; ?>" class="input">
      </div>

      <div class="box">
         <p>Phone Number <span>*</span></p>
         <input type="tel" name="phone" required maxlength="20" value="<?php echo $user_data ? htmlspecialchars($user_data['phone']) : ''; ?>" class="input">
      </div>

      <h3 style="margin-top: 2rem;">Change Password (Optional)</h3>
      
      <div class="box">
         <p>Current Password</p>
         <input type="password" name="old_pass" maxlength="20" placeholder="Enter current password" class="input">
      </div>

      <div class="box">
         <p>New Password</p>
         <input type="password" name="new_pass" maxlength="20" placeholder="Enter new password" class="input">
      </div>

      <div class="box">
         <p>Confirm New Password</p>
         <input type="password" name="c_pass" maxlength="20" placeholder="Confirm new password" class="input">
      </div>

      <input type="submit" value="Update Profile" name="submit" class="btn">
   </form>
</section>

<script>
document.getElementById('profileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Validate password if changing
    const newPass = formData.get('new_pass');
    const confirmPass = formData.get('c_pass');
    
    if (newPass && newPass !== confirmPass) {
        alert('New passwords do not match!');
        return;
    }
    
    // Send to API
    const data = {
        name: formData.get('name'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        old_password: formData.get('old_pass'),
        new_password: newPass || null
    };
    
    // For now, show a message (API endpoint needs to be created)
    alert('Profile update functionality will be available soon. The API endpoint needs to be created.');
    
    // TODO: Call update profile API when it's ready
    // fetch('../api/boarder/update_profile.php', {
    //     method: 'POST',
    //     headers: { 'Content-Type': 'application/json' },
    //     body: JSON.stringify(data)
    // })
    // .then(response => response.json())
    // .then(data => {
    //     if (data.success) {
    //         alert('Profile updated successfully!');
    //         location.reload();
    //     } else {
    //         alert('Error: ' + data.message);
    //     }
    // });
});
</script>

<script src="../js/script.js"></script>
</body>
</html>

