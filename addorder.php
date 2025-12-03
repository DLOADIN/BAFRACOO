<?php
  require "connection.php";
  if(!empty($_SESSION["id"])){
  $id = $_SESSION["id"];
  $check = mysqli_query($con,"SELECT * FROM `admin` WHERE id=$id ");
  $row = mysqli_fetch_array($check);
  }
  else{
  header('location:loginadmin.php');
  exit();
  } 
  
  // Create uploads directory if it doesn't exist
  $upload_dir = 'uploads/tools/';
  if (!file_exists($upload_dir)) {
      mkdir($upload_dir, 0777, true);
  }
  
  // Check if editing existing tool
  $is_edit = false;
  $tool_data = null;
  if(isset($_GET['id']) && !empty($_GET['id'])){
    $is_edit = true;
    $tool_id = mysqli_real_escape_string($con, $_GET['id']);
    $tool_query = mysqli_query($con, "SELECT * FROM `tool` WHERE id='$tool_id'");
    if($tool_query && mysqli_num_rows($tool_query) > 0){
      $tool_data = mysqli_fetch_assoc($tool_query);
    } else {
      echo "<script>alert('Tool not found!'); window.location.href='stock.php';</script>";
      exit();
    }
  }
  
  // Handle form submission
  if(isset($_POST['submit'])){
    $toolname = mysqli_real_escape_string($con, $_POST['u_toolname']);
    $itemsnumber = mysqli_real_escape_string($con, $_POST['u_itemsnumber']);
    $type = mysqli_real_escape_string($con, $_POST['u_type']);
    $tooldescription = mysqli_real_escape_string($con, $_POST['u_tooldescription']);
    $price = mysqli_real_escape_string($con, $_POST['u_price']);
    
    // Handle image upload
    $image_url = null;
    if(isset($_FILES['tool_image']) && $_FILES['tool_image']['error'] == 0){
      $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
      $max_size = 5 * 1024 * 1024; // 5MB
      
      if(in_array($_FILES['tool_image']['type'], $allowed_types) && $_FILES['tool_image']['size'] <= $max_size){
        $file_extension = pathinfo($_FILES['tool_image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'tool_' . time() . '_' . uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . $new_filename;
        
        if(move_uploaded_file($_FILES['tool_image']['tmp_name'], $target_path)){
          $image_url = $target_path;
        }
      }
    }
    
    if($is_edit && isset($_POST['tool_id'])){
      // Update existing tool
      $tool_id = mysqli_real_escape_string($con, $_POST['tool_id']);
      
      // Keep existing image if no new one uploaded
      if($image_url === null && isset($_POST['existing_image']) && !empty($_POST['existing_image'])){
        $image_url = mysqli_real_escape_string($con, $_POST['existing_image']);
      }
      
      $image_sql = $image_url ? ", image_url='$image_url'" : "";
      $sql = mysqli_query($con, "UPDATE `tool` SET u_toolname='$toolname', u_itemsnumber='$itemsnumber', u_type='$type', u_tooldescription='$tooldescription', u_price='$price'$image_sql WHERE id='$tool_id'");
      
      if($sql){
        header('Location: stock.php');
        exit();
      } else {
        echo "<script>alert('Error updating tool. Please try again.');</script>";
      }
    } else {
      // Insert new tool
      $date = date('Y-m-d');
      $image_url_escaped = $image_url ? "'$image_url'" : "NULL";
      $sql = mysqli_query($con, "INSERT INTO `tool` (u_toolname, u_itemsnumber, u_type, u_tooldescription, u_date, u_price, image_url) VALUES ('$toolname', '$itemsnumber', '$type', '$tooldescription', '$date', '$price', $image_url_escaped)");
      
      if($sql){
        header('Location: stock.php');
        exit();
      } else {
        echo "<script>alert('Error adding tool. Please try again.');</script>";
      }
    }
  }
  
  $current_page = 'addtool';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./CSS/modern-dashboard.css">
  <link rel="stylesheet" href="./CSS/modern-forms.css">
  <link rel="shortcut icon" href="./images/Capture.JPG" type="image/x-icon">
  <script src="https://kit.fontawesome.com/14ff3ea278.js" crossorigin="anonymous"></script>
  <title>BAFRACOO - <?php echo $is_edit ? 'Edit Tool' : 'Add Tool'; ?></title>
  <style>
    .image-upload-container {
      border: 2px dashed var(--gray-300);
      border-radius: var(--radius-lg);
      padding: var(--spacing-xl);
      text-align: center;
      transition: all 0.3s ease;
      cursor: pointer;
      background: var(--gray-50);
    }
    .image-upload-container:hover {
      border-color: var(--primary-color);
      background: rgba(59, 130, 246, 0.05);
    }
    .image-upload-container.has-image {
      border-style: solid;
      border-color: var(--success-color);
    }
    .image-preview {
      max-width: 200px;
      max-height: 200px;
      border-radius: var(--radius-md);
      margin-top: var(--spacing-md);
      display: none;
    }
    .image-preview.show {
      display: block;
      margin: var(--spacing-md) auto 0;
    }
    .upload-icon {
      font-size: 3rem;
      color: var(--gray-400);
      margin-bottom: var(--spacing-md);
    }
    .upload-text {
      color: var(--gray-600);
      font-size: 0.875rem;
    }
    .upload-hint {
      color: var(--gray-500);
      font-size: 0.75rem;
      margin-top: var(--spacing-sm);
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <?php include 'includes/admin_sidebar.php'; ?>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay"></div>

    <!-- Main Content -->
    <main class="main-content">
      <header class="header">
        <div class="header-left">
          <button class="mobile-menu-btn">
            <ion-icon name="menu-outline"></ion-icon>
          </button>
          <button class="sidebar-toggle">
            <ion-icon name="chevron-back-outline"></ion-icon>
          </button>
          <h1 class="page-title"><?php echo $is_edit ? 'Edit Tool' : 'Add New Tool'; ?></h1>
        </div>
        <div class="header-right">
          <a href="logout.php" class="logout-btn">
            <ion-icon name="log-out-outline"></ion-icon>
            <span>Logout</span>
          </a>
        </div>
      </header>

      <div class="content-area">
        <!-- Breadcrumb -->
        <div style="margin-bottom: var(--spacing-xl); display: flex; align-items: center; gap: var(--spacing-sm); color: var(--gray-600); font-size: 0.875rem;">
          <a href="stock.php" style="color: var(--primary-color); text-decoration: none;">
            <ion-icon name="cube-outline"></ion-icon> Inventory
          </a>
          <span>/</span>
          <span style="color: var(--gray-900); font-weight: 500;"><?php echo $is_edit ? 'Edit Tool' : 'Add New Tool'; ?></span>
        </div>

        <!-- Add Tool Form Card -->
        <div class="dashboard-card" style="max-width: 900px; margin: 0 auto;">
          <div class="card-header">
            <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--gray-900); margin: 0;">
              <ion-icon name="<?php echo $is_edit ? 'create-outline' : 'add-circle-outline'; ?>" style="margin-right: var(--spacing-sm);"></ion-icon>
              Tool Information
            </h3>
            <p style="margin: var(--spacing-sm) 0 0 0; color: var(--gray-600); font-size: 0.875rem;">
              <?php echo $is_edit ? 'Update the tool details below' : 'Fill in the details below to add a new tool to your inventory'; ?>
            </p>
          </div>

          <div style="padding: var(--spacing-xl);">
            <form method="POST" action="" id="addToolForm" enctype="multipart/form-data">
              <?php if($is_edit): ?>
                <input type="hidden" name="tool_id" value="<?php echo $tool_data['id']; ?>">
                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($tool_data['image_url'] ?? ''); ?>">
              <?php endif; ?>

              <!-- Image Upload Section -->
              <div class="form-group" style="margin-bottom: var(--spacing-xl);">
                <label class="form-label">
                  <ion-icon name="image-outline" style="margin-right: 4px;"></ion-icon>
                  Tool Image
                </label>
                <div class="image-upload-container <?php echo ($is_edit && !empty($tool_data['image_url'])) ? 'has-image' : ''; ?>" onclick="document.getElementById('tool_image').click();">
                  <ion-icon name="cloud-upload-outline" class="upload-icon"></ion-icon>
                  <div class="upload-text">Click to upload an image</div>
                  <div class="upload-hint">Supported formats: JPG, PNG, GIF, WebP (Max 5MB)</div>
                  <img id="imagePreview" class="image-preview <?php echo ($is_edit && !empty($tool_data['image_url'])) ? 'show' : ''; ?>" 
                       src="<?php echo ($is_edit && !empty($tool_data['image_url'])) ? htmlspecialchars($tool_data['image_url']) : ''; ?>" 
                       alt="Preview">
                </div>
                <input type="file" id="tool_image" name="tool_image" accept="image/*" style="display: none;" onchange="previewImage(this);">
              </div>

              <!-- Tool Name and Type Row -->
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-lg);">
                <div class="form-group">
                  <label for="u_toolname" class="form-label">
                    <ion-icon name="construct-outline" style="margin-right: 4px;"></ion-icon>
                    Tool Name *
                  </label>
                  <input 
                    type="text" 
                    id="u_toolname" 
                    name="u_toolname" 
                    class="form-control" 
                    placeholder="e.g., APPLES, Silicone 500mg" 
                    value="<?php echo $is_edit && $tool_data ? htmlspecialchars($tool_data['u_toolname']) : ''; ?>"
                    required
                    style="width: 100%; padding: var(--spacing-md); border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-size: 1rem;"
                  >
                </div>

                <div class="form-group">
                  <label for="u_type" class="form-label">
                    <ion-icon name="pricetag-outline" style="margin-right: 4px;"></ion-icon>
                    Type/Category *
                  </label>
                  <input 
                    type="text" 
                    id="u_type" 
                    name="u_type" 
                    class="form-control" 
                    placeholder="e.g., Very Good, Not Good" 
                    value="<?php echo $is_edit && $tool_data ? htmlspecialchars($tool_data['u_type']) : ''; ?>"
                    required
                    style="width: 100%; padding: var(--spacing-md); border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-size: 1rem;"
                  >
                </div>
              </div>

              <!-- Quantity and Price Row -->
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-lg);">
                <div class="form-group">
                  <label for="u_itemsnumber" class="form-label">
                    <ion-icon name="layers-outline" style="margin-right: 4px;"></ion-icon>
                    Quantity *
                  </label>
                  <input 
                    type="number" 
                    id="u_itemsnumber" 
                    name="u_itemsnumber" 
                    class="form-control" 
                    placeholder="Enter quantity"
                    value="<?php echo $is_edit && $tool_data ? $tool_data['u_itemsnumber'] : ''; ?>"
                    min="0"
                    required
                    style="width: 100%; padding: var(--spacing-md); border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-size: 1rem;"
                  >
                </div>

                <div class="form-group">
                  <label for="u_price" class="form-label">
                    <ion-icon name="cash-outline" style="margin-right: 4px;"></ion-icon>
                    Unit Price (RWF) *
                  </label>
                  <input 
                    type="number" 
                    id="u_price" 
                    name="u_price" 
                    class="form-control" 
                    placeholder="Enter price in RWF"
                    value="<?php echo $is_edit && $tool_data ? $tool_data['u_price'] : ''; ?>"
                    min="0"
                    required
                    style="width: 100%; padding: var(--spacing-md); border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-size: 1rem;"
                  >
                </div>
              </div>

              <!-- Description -->
              <div class="form-group" style="margin-bottom: var(--spacing-xl);">
                <label for="u_tooldescription" class="form-label">
                  <ion-icon name="document-text-outline" style="margin-right: 4px;"></ion-icon>
                  Description *
                </label>
                <textarea 
                  id="u_tooldescription" 
                  name="u_tooldescription" 
                  class="form-control" 
                  placeholder="Please provide more details about the tool/product"
                  rows="4"
                  required
                  style="width: 100%; padding: var(--spacing-md); border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-size: 1rem; resize: vertical;"
                ><?php echo $is_edit && $tool_data ? htmlspecialchars($tool_data['u_tooldescription']) : ''; ?></textarea>
              </div>

              <!-- Terms and Conditions -->
              <div style="margin-bottom: var(--spacing-xl); padding: var(--spacing-md); background: var(--gray-50); border-radius: var(--radius-md); border: 1px solid var(--gray-200);">
                <label style="display: flex; align-items: start; gap: var(--spacing-sm); cursor: pointer;">
                  <input 
                    type="checkbox" 
                    id="terms" 
                    name="terms" 
                    required
                    style="margin-top: 4px; width: 18px; height: 18px; cursor: pointer;"
                  >
                  <span style="font-size: 0.875rem; color: var(--gray-700); line-height: 1.5;">
                    I confirm that I have read and accepted the <a href="#" style="color: var(--primary-color); text-decoration: none;">terms and conditions</a> and <a href="#" style="color: var(--primary-color); text-decoration: none;">privacy policy</a>
                  </span>
                </label>
              </div>

              <!-- Action Buttons -->
              <div style="display: flex; gap: var(--spacing-md); justify-content: flex-end; padding-top: var(--spacing-lg); border-top: 1px solid var(--gray-200);">
                <a href="stock.php" class="btn-secondary" style="text-decoration: none; display: inline-flex; align-items: center; gap: var(--spacing-sm); padding: var(--spacing-md) var(--spacing-xl); border-radius: var(--radius-md);">
                  <ion-icon name="close-outline"></ion-icon>
                  Cancel
                </a>
                <button 
                  type="submit" 
                  name="submit" 
                  class="btn-primary"
                  style="display: inline-flex; align-items: center; gap: var(--spacing-sm); padding: var(--spacing-md) var(--spacing-xl); border-radius: var(--radius-md);"
                >
                  <ion-icon name="<?php echo $is_edit ? 'save-outline' : 'checkmark-circle-outline'; ?>"></ion-icon>
                  <?php echo $is_edit ? 'Update Tool' : 'Add Tool to Inventory'; ?>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Help Card -->
        <div class="dashboard-card" style="max-width: 900px; margin: var(--spacing-xl) auto 0;">
          <div style="padding: var(--spacing-lg); background: linear-gradient(135deg, var(--primary-light) 0%, var(--info-light) 100%); border-radius: var(--radius-lg);">
            <div style="display: flex; align-items: start; gap: var(--spacing-md);">
              <div style="width: 40px; height: 40px; background: var(--primary-color); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                <ion-icon name="information-circle-outline" style="font-size: 1.5rem;"></ion-icon>
              </div>
              <div>
                <h4 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-900); font-weight: 600;">Need Help?</h4>
                <p style="margin: 0; color: var(--gray-700); font-size: 0.875rem; line-height: 1.6;">
                  Make sure to fill in all required fields marked with an asterisk (*). The date will be automatically set to today. 
                  Once added, the tool will appear in your inventory list where you can edit or delete it if needed.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
  
  <script>
    function previewImage(input) {
      const preview = document.getElementById('imagePreview');
      const container = input.closest('.form-group').querySelector('.image-upload-container');
      
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
          preview.src = e.target.result;
          preview.classList.add('show');
          container.classList.add('has-image');
        }
        
        reader.readAsDataURL(input.files[0]);
      }
    }

    // Initialize dashboard
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof window.Dashboard !== 'undefined') {
        new window.Dashboard();
      }
      
      // Form validation
      const form = document.getElementById('addToolForm');
      form.addEventListener('submit', function(e) {
        const quantity = document.getElementById('u_itemsnumber').value;
        const price = document.getElementById('u_price').value;
        
        if (quantity < 0 || price < 0) {
          e.preventDefault();
          alert('Quantity and Price must be positive numbers');
          return false;
        }
      });
    });
  </script>
</body>
</html>
