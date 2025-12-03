<?php
  require "connection.php";
  if(!empty($_SESSION["id"])){
  $id = $_SESSION["id"];
  $check = mysqli_query($con,"SELECT * FROM `admin` WHERE id=$id ");
  $row = mysqli_fetch_array($check);
  }
  else{
  header('location:loginadmin.php');
  } 
  
  // Check if editing existing order
  $is_edit = false;
  $order_data = null;
  if(isset($_GET['id']) && !empty($_GET['id'])){
    $is_edit = true;
    $order_id = mysqli_real_escape_string($con, $_GET['id']);
    $order_query = mysqli_query($con, "SELECT * FROM `order` WHERE id='$order_id'");
    if($order_query && mysqli_num_rows($order_query) > 0){
      $order_data = mysqli_fetch_assoc($order_query);
    } else {
      echo "<script>alert('Order not found!'); window.location.href='orders.php';</script>";
      exit();
    }
  }
  
  // Handle form submission
  if(isset($_POST['submit'])){
    $user_id = mysqli_real_escape_string($con, $_POST['user_id']);
    $toolname = mysqli_real_escape_string($con, $_POST['u_toolname']);
    $itemsnumber = mysqli_real_escape_string($con, $_POST['u_itemsnumber']);
    $type = mysqli_real_escape_string($con, $_POST['u_type']);
    $tooldescription = mysqli_real_escape_string($con, $_POST['u_tooldescription']);
    $price = mysqli_real_escape_string($con, $_POST['u_price']);
    $totalprice = $itemsnumber * $price;
    
    if($is_edit && isset($_POST['order_id'])){
      // Update existing order
      $order_id = mysqli_real_escape_string($con, $_POST['order_id']);
      $sql = mysqli_query($con, "UPDATE `order` SET user_id='$user_id', u_toolname='$toolname', u_itemsnumber='$itemsnumber', u_type='$type', u_tooldescription='$tooldescription', u_price='$price', u_totalprice='$totalprice' WHERE id='$order_id'");
      
      if($sql){
        header('Location: orders.php');
        exit();
      } else {
        echo "<script>alert('Error updating order. Please try again.');</script>";
      }
    } else {
      // Insert new order
      $date = date('Y-m-d');
      $sql = mysqli_query($con, "INSERT INTO `order` (user_id, u_toolname, u_itemsnumber, u_type, u_tooldescription, u_date, u_price, u_totalprice) VALUES ('$user_id', '$toolname', '$itemsnumber', '$type', '$tooldescription', '$date', '$price', '$totalprice')");
      
      if($sql){
        header('Location: orders.php');
        exit();
      } else {
        echo "<script>alert('Error adding order. Please try again.');</script>";
      }
    }
  }
  
  // Get list of users
  $users_query = mysqli_query($con, "SELECT id, u_name, u_email FROM `user` ORDER BY u_name ASC");
  $current_page = 'addorder';
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
  <title>BAFRACOO - Add Order</title>
  <!-- <script src="./JS/file.js"></script> -->
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
          <h1 class="page-title"><?php echo $is_edit ? 'Edit Order' : 'Add New Order'; ?></h1>
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
          <a href="orders.php" style="color: var(--primary-color); text-decoration: none;">
            <ion-icon name="bag-handle-outline"></ion-icon> Orders
          </a>
          <span>/</span>
          <span style="color: var(--gray-900); font-weight: 500;"><?php echo $is_edit ? 'Edit Order' : 'Add New Order'; ?></span>
        </div>

        <!-- Add Order Form Card -->
        <div class="dashboard-card" style="max-width: 900px; margin: 0 auto;">
          <div class="card-header">
            <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--gray-900); margin: 0;">
              <ion-icon name="<?php echo $is_edit ? 'create-outline' : 'add-circle-outline'; ?>" style="margin-right: var(--spacing-sm);"></ion-icon>
              Order Information
            </h3>
            <p style="margin: var(--spacing-sm) 0 0 0; color: var(--gray-600); font-size: 0.875rem;">
              <?php echo $is_edit ? 'Update the order details below' : 'Fill in the details below to create a new order for a customer'; ?>
            </p>
          </div>

          <div style="padding: var(--spacing-xl);">
            <form method="POST" action="" id="addOrderForm">
              <?php if($is_edit): ?>
                <input type="hidden" name="order_id" value="<?php echo $order_data['id']; ?>">
              <?php endif; ?>
              <!-- Customer Selection -->
              <div class="form-group" style="margin-bottom: var(--spacing-lg);">
                <label for="user_id" class="form-label">
                  <ion-icon name="person-outline" style="margin-right: 4px;"></ion-icon>
                  Select Customer *
                </label>
                <select 
                  id="user_id" 
                  name="user_id" 
                  class="form-control" 
                  required
                  style="width: 100%; padding: var(--spacing-md); border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-size: 1rem; background: white;"
                >
                  <option value="">-- Select a customer --</option>
                  <?php 
                  if($users_query && mysqli_num_rows($users_query) > 0){
                    while($user = mysqli_fetch_assoc($users_query)){
                      $selected = ($is_edit && $order_data && $order_data['user_id'] == $user['id']) ? 'selected' : '';
                      echo '<option value="'.$user['id'].'" '.$selected.'>'.$user['u_name'].' ('.$user['u_email'].')</option>';
                    }
                  }
                  ?>
                </select>
              </div>

              <!-- Product Name and Type Row -->
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-lg);">
                <div class="form-group">
                  <label for="u_toolname" class="form-label">
                    <ion-icon name="cube-outline" style="margin-right: 4px;"></ion-icon>
                    Product/Tool Name *
                  </label>
                  <input 
                    type="text" 
                    id="u_toolname" 
                    name="u_toolname" 
                    class="form-control" 
                    placeholder="e.g., APPLES, Mangos, Silicone 500mg" 
                    value="<?php echo $is_edit && $order_data ? htmlspecialchars($order_data['u_toolname']) : ''; ?>"
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
                    value="<?php echo $is_edit && $order_data ? htmlspecialchars($order_data['u_type']) : ''; ?>"
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
                    value="<?php echo $is_edit && $order_data ? $order_data['u_itemsnumber'] : ''; ?>"
                    min="1"
                    required
                    oninput="calculateTotal()"
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
                    placeholder="Enter price per unit"
                    value="<?php echo $is_edit && $order_data ? $order_data['u_price'] : ''; ?>"
                    min="0"
                    required
                    oninput="calculateTotal()"
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
                  placeholder="Please provide order details or special instructions"
                  rows="4"
                  required
                  style="width: 100%; padding: var(--spacing-md); border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-size: 1rem; resize: vertical;"
                ><?php echo $is_edit && $order_data ? htmlspecialchars($order_data['u_tooldescription']) : ''; ?></textarea>
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
                    I confirm that I have verified the order details and accepted the <a href="#" style="color: var(--primary-color); text-decoration: none;">terms and conditions</a> and <a href="#" style="color: var(--primary-color); text-decoration: none;">privacy policy</a>
                  </span>
                </label>
              </div>

              <!-- Action Buttons -->
              <div style="display: flex; gap: var(--spacing-md); justify-content: flex-end; padding-top: var(--spacing-lg); border-top: 1px solid var(--gray-200);">
                <a href="orders.php" class="btn-secondary" style="text-decoration: none; display: inline-flex; align-items: center; gap: var(--spacing-sm); padding: var(--spacing-md) var(--spacing-xl); border-radius: var(--radius-md);">
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
                  <?php echo $is_edit ? 'Update Order' : 'Create Order'; ?>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Help Card -->
        <div class="dashboard-card" style="max-width: 900px; margin: var(--spacing-xl) auto 0;">
          <div style="padding: var(--spacing-lg); background: linear-gradient(135deg, var(--success-light) 0%, var(--info-light) 100%); border-radius: var(--radius-lg);">
            <div style="display: flex; align-items: start; gap: var(--spacing-md);">
              <div style="width: 40px; height: 40px; background: var(--success-color); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                <ion-icon name="information-circle-outline" style="font-size: 1.5rem;"></ion-icon>
              </div>
              <div>
                <h4 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-900); font-weight: 600;">Order Information</h4>
                <p style="margin: 0; color: var(--gray-700); font-size: 0.875rem; line-height: 1.6;">
                  <?php echo $is_edit ? 'Update the order details and click "Update Order" to save your changes. The total price will be automatically recalculated based on quantity and unit price.' : 'Select a customer from the dropdown to create an order for them. The total price will be automatically calculated based on quantity and unit price. The order will be set to "Pending" status by default and the date will be automatically recorded.'; ?>
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
    // Calculate total price
    function calculateTotal() {
      const quantity = document.getElementById('u_itemsnumber').value || 0;
      const price = document.getElementById('u_price').value || 0;
      const total = quantity * price;
      
      document.getElementById('totalPriceDisplay').textContent = new Intl.NumberFormat().format(total) + ' RWF';
    }
    
    // Initialize dashboard
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof window.Dashboard !== 'undefined') {
        new window.Dashboard();
      }
      
      // Calculate total on page load (for edit mode)
      calculateTotal();
      
      // Form validation
      const form = document.getElementById('addOrderForm');
      form.addEventListener('submit', function(e) {
        const quantity = document.getElementById('u_itemsnumber').value;
        const price = document.getElementById('u_price').value;
        const userId = document.getElementById('user_id').value;
        
        if (!userId) {
          e.preventDefault();
          alert('Please select a customer');
          return false;
        }
        
        if (quantity <= 0 || price < 0) {
          e.preventDefault();
          alert('Quantity must be greater than 0 and Price must be positive');
          return false;
        }
      });
    });
  </script>
</body>
</html>