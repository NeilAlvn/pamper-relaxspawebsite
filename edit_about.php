<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "pos_system");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

include 'reference.php';
processLogin($conn);

// Handle About Us Content Update
if (isset($_POST['update_content'])) {
    $section = $_POST['section'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $existing_image = $_POST['existing_image'];
    $image = $existing_image;

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($filetype, $allowed)) {
            $upload_dir = 'uploads/about/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Delete old image
            if (!empty($existing_image) && file_exists($existing_image)) {
                unlink($existing_image);
            }
            
            $new_filename = uniqid() . '.' . $filetype;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $image = $destination;
            }
        }
    }

    $stmt = $conn->prepare("UPDATE about_us SET title = ?, content = ?, image = ? WHERE section = ?");
    $stmt->bind_param("ssss", $title, $content, $image, $section);
    $stmt->execute();
    
    $_SESSION['success_message'] = "Content updated successfully!";
    header("Location: edit_about.php");
    exit();
}

// Handle Add Team Member
if (isset($_POST['add_member'])) {
    $name = $_POST['name'];
    $position = $_POST['position'];
    $bio = $_POST['bio'];
    $display_order = intval($_POST['display_order']);
    $image = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($filetype, $allowed)) {
            $upload_dir = 'uploads/team/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = uniqid() . '.' . $filetype;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $image = $destination;
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO team_members (name, position, bio, image, display_order) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $name, $position, $bio, $image, $display_order);
    $stmt->execute();
    
    $_SESSION['success_message'] = "Team member added successfully!";
    header("Location: edit_about.php");
    exit();
}

// Handle Delete Team Member
if (isset($_GET['delete_member'])) {
    $id = intval($_GET['delete_member']);
    
    // Get image path
    $img_query = $conn->prepare("SELECT image FROM team_members WHERE id = ?");
    $img_query->bind_param("i", $id);
    $img_query->execute();
    $img_result = $img_query->get_result();
    
    if ($img_row = $img_result->fetch_assoc()) {
        if (!empty($img_row['image']) && file_exists($img_row['image'])) {
            unlink($img_row['image']);
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM team_members WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $_SESSION['success_message'] = "Team member deleted successfully!";
    header("Location: edit_about.php");
    exit();
}

// Handle Update Contact Form Settings
if (isset($_POST['update_contact_form'])) {
    $heading = $_POST['contact_heading'];
    $subheading = $_POST['contact_subheading'];
    $description = $_POST['contact_description'];
    $recipient_email = $_POST['recipient_email'];
    $success_message = $_POST['success_message'];

    $stmt = $conn->prepare("UPDATE contact_form_settings SET heading = ?, subheading = ?, description = ?, recipient_email = ?, success_message = ? WHERE id = 1");
    $stmt->bind_param("sssss", $heading, $subheading, $description, $recipient_email, $success_message);
    $stmt->execute();
    
    $_SESSION['success_message'] = "Contact form settings updated successfully!";
    header("Location: edit_about.php");
    exit();
}

// Handle Mark Message as Read
if (isset($_GET['mark_read'])) {
    $id = intval($_GET['mark_read']);
    $conn->query("UPDATE contact_messages SET status = 'read' WHERE id = $id");
    $_SESSION['success_message'] = "Message marked as read!";
    header("Location: edit_about.php");
    exit();
}

// Handle Delete Message
if (isset($_GET['delete_message'])) {
    $id = intval($_GET['delete_message']);
    $conn->query("DELETE FROM contact_messages WHERE id = $id");
    $_SESSION['success_message'] = "Message deleted!";
    header("Location: edit_about.php");
    exit();
}

// Handle Add Location
if (isset($_POST['add_location'])) {
    $name = $_POST['location_name'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $phone = $_POST['location_phone'];
    $email = $_POST['location_email'];
    $hours = $_POST['hours'];
    $map_embed = $_POST['map_embed'];
    $display_order = intval($_POST['location_order']);
    $image = null;

    if (isset($_FILES['location_image']) && $_FILES['location_image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['location_image']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($filetype, $allowed)) {
            $upload_dir = 'uploads/locations/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = uniqid() . '.' . $filetype;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['location_image']['tmp_name'], $destination)) {
                $image = $destination;
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO locations (name, address, city, phone, email, hours, map_embed, image, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssi", $name, $address, $city, $phone, $email, $hours, $map_embed, $image, $display_order);
    $stmt->execute();
    
    $_SESSION['success_message'] = "Location added successfully!";
    header("Location: edit_about.php");
    exit();
}

// Handle Delete Location
if (isset($_GET['delete_location'])) {
    $id = intval($_GET['delete_location']);
    
    $img_query = $conn->prepare("SELECT image FROM locations WHERE id = ?");
    $img_query->bind_param("i", $id);
    $img_query->execute();
    $img_result = $img_query->get_result();
    
    if ($img_row = $img_result->fetch_assoc()) {
        if (!empty($img_row['image']) && file_exists($img_row['image'])) {
            unlink($img_row['image']);
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM locations WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $_SESSION['success_message'] = "Location deleted successfully!";
    header("Location: edit_about.php");
    exit();
}

// Handle Add Contact
if (isset($_POST['add_contact'])) {
    $type = $_POST['contact_type'];
    $label = $_POST['contact_label'];
    $value = $_POST['contact_value'];
    $icon = $_POST['contact_icon'];
    $display_order = intval($_POST['contact_order']);

    $stmt = $conn->prepare("INSERT INTO contact_info (type, label, value, icon, display_order) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $type, $label, $value, $icon, $display_order);
    $stmt->execute();
    
    $_SESSION['success_message'] = "Contact info added successfully!";
    header("Location: edit_about.php");
    exit();
}

// Handle Delete Contact
if (isset($_GET['delete_contact'])) {
    $id = intval($_GET['delete_contact']);
    
    $stmt = $conn->prepare("DELETE FROM contact_info WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $_SESSION['success_message'] = "Contact info deleted successfully!";
    header("Location: edit_about.php");
    exit();
}

// Fetch data
$about_content = [];
$about_query = $conn->query("SELECT * FROM about_us ORDER BY id ASC");
while ($row = $about_query->fetch_assoc()) {
    $about_content[$row['section']] = $row;
}

$team_query = $conn->query("SELECT * FROM team_members ORDER BY display_order ASC, id ASC");
?>
<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Edit About Us — Pamper & Relax</title>

  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            spaGreen: '#0e3f37',
            spaGold: '#d4b26a',
            spaIvory: '#f8f5ef',
            spaWhite: '#ffffff',
            spaAccent: '#e2d4c3'
          },
          fontFamily: {
            heading: ['"Playfair Display"', 'serif'],
            body: ['Inter', 'sans-serif']
          }
        }
      }
    }
  </script>
  <style>
    .line-clamp-3 {
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
  </style>
</head>

<body class="font-body text-gray-800 bg-spaIvory antialiased">
<?php 
login_button();
logout_button(); 
?>

  <header class="nav-blur text-white fixed w-full z-50 border-b border-white/10 bg-spaGreen">
    <nav>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex-shrink-0">
            <a href="index.php" class="flex items-center space-x-2">
              <img src="assets/logo/Pamper Website Logo250x100.png" alt="Pamper & Relax Spa" class="h-8 sm:h-9 md:h-10 lg:h-12 w-auto object-contain transition-all duration-300"/>
            </a>
          </div>

          <?php renderNavigation(); ?>

          <div class="hidden md:block">
            <a href="about_us.php">
              <button class="border bg-spaGold border-spaGold text-white px-5 py-2 rounded-md font-medium hover:bg-white hover:text-spaGold hover:border-spaGold transition duration-300">
                View About Page →
              </button>
            </a>
          </div>
        </div>
      </div>
    </nav>
  </header>

  <main class="pt-24 pb-16 min-h-screen">
    <div class="max-w-7xl mx-auto px-6 lg:px-12">
      
      <!-- Success Message -->
      <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
          <?= htmlspecialchars($_SESSION['success_message']) ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
      <?php endif; ?>

      <!-- Header -->
      <div class="text-center mb-12">
        <p class="text-spaGold uppercase tracking-widest text-sm font-medium mb-3">
          Management Portal
        </p>
        <h1 class="font-heading text-4xl md:text-5xl text-spaGreen mb-4">
          Edit About Us Page
        </h1>
        <p class="text-gray-600 max-w-2xl mx-auto">
          Customize your about us content and manage team members.
        </p>
      </div>

      <!-- About Us Content Sections -->
      <div class="space-y-8 mb-12">
        <?php foreach ($about_content as $section => $data): ?>
          <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30">
            <h2 class="text-2xl font-heading text-spaGreen mb-4 capitalize"><?= $section ?> Section</h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
              <input type="hidden" name="section" value="<?= $section ?>">
              <input type="hidden" name="existing_image" value="<?= $data['image'] ?>">
              
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($data['title']) ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
              </div>
              
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Content</label>
                <textarea name="content" rows="5" required
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold"><?= htmlspecialchars($data['content']) ?></textarea>
              </div>
              
              <?php if ($section === 'story'): ?>
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Image (Optional)</label>
                <?php if (!empty($data['image']) && file_exists($data['image'])): ?>
                  <img src="<?= $data['image'] ?>" alt="Current Image" class="mb-2 w-32 h-32 object-cover rounded-lg border">
                <?php endif; ?>
                <input type="file" name="image" accept="image/*"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
              </div>
              <?php endif; ?>
              
              <div class="flex justify-end">
                <button type="submit" name="update_content"
                        class="px-6 py-2 bg-spaGold text-white rounded-md hover:bg-spaGreen transition">
                  Update <?= ucfirst($section) ?>
                </button>
              </div>
            </form>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Team Members Section -->
      <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30 mb-8">
        <h2 class="text-2xl font-heading text-spaGreen mb-6">Team Members</h2>
        
        <!-- Add New Member Form -->
        <div class="bg-spaIvory/50 rounded-lg p-6 mb-6">
          <h3 class="text-lg font-semibold text-spaGreen mb-4">Add New Team Member</h3>
          <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                <input type="text" name="name" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
              </div>
              
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Position</label>
                <input type="text" name="position" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
              </div>
            </div>
            
            <div>
              <label class="block text-gray-700 text-sm font-bold mb-2">Bio</label>
              <textarea name="bio" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold"></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Photo</label>
                <input type="file" name="image" accept="image/*"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
              </div>
              
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Display Order</label>
                <input type="number" name="display_order" value="0" min="0"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
              </div>
            </div>
            
            <div class="flex justify-end">
              <button type="submit" name="add_member"
                      class="px-6 py-2 bg-spaGold text-white rounded-md hover:bg-spaGreen transition">
                Add Team Member
              </button>
            </div>
          </form>
        </div>

        <!-- Existing Team Members -->
        <h3 class="text-lg font-semibold text-spaGreen mb-4 mt-8">Current Team Members</h3>
        
        <?php if ($team_query->num_rows > 0): ?>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while($member = $team_query->fetch_assoc()): ?>
              <div class="bg-white border border-spaAccent/30 rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300">
                <!-- Member Image -->
                <div class="relative group">
                  <?php if (!empty($member['image']) && file_exists($member['image'])): ?>
                    <img src="<?= $member['image'] ?>" alt="<?= htmlspecialchars($member['name']) ?>" class="w-full h-56 object-cover">
                  <?php else: ?>
                    <div class="w-full h-56 bg-gradient-to-br from-spaIvory to-spaAccent flex items-center justify-center">
                      <svg class="w-20 h-20 text-spaGold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                      </svg>
                    </div>
                  <?php endif; ?>
                  
                  <!-- Delete Overlay -->
                  <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-60 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
                    <a href="?delete_member=<?= $member['id'] ?>" 
                       onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars($member['name']) ?>?')"
                       class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg transition-colors duration-200 flex items-center space-x-2">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                      <span>Delete</span>
                    </a>
                  </div>
                </div>
                
                <!-- Member Info -->
                <div class="p-5">
                  <div class="flex items-start justify-between mb-2">
                    <div class="flex-1">
                      <h4 class="font-heading text-xl text-spaGreen font-semibold">
                        <?= htmlspecialchars($member['name']) ?>
                      </h4>
                      <p class="text-sm text-spaGold font-medium mt-1">
                        <?= htmlspecialchars($member['position']) ?>
                      </p>
                    </div>
                    <span class="bg-spaIvory text-spaGreen text-xs px-2 py-1 rounded-full font-medium">
                      #<?= $member['display_order'] ?>
                    </span>
                  </div>
                  
                  <?php if (!empty($member['bio'])): ?>
                    <p class="text-sm text-gray-600 leading-relaxed mt-3 line-clamp-3">
                      <?= htmlspecialchars($member['bio']) ?>
                    </p>
                  <?php endif; ?>
                  
                  <!-- Status Badge -->
                  <div class="mt-4 pt-3 border-t border-spaAccent/30 flex items-center justify-between">
                    <span class="text-xs text-gray-500">
                      Added <?= date('M d, Y', strtotime($member['created_at'])) ?>
                    </span>
                    <?php if ($member['is_active'] == 1): ?>
                      <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Active
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                        Inactive
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="text-center py-12 bg-spaIvory/30 rounded-lg border-2 border-dashed border-spaAccent">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="text-gray-600 font-medium">No team members added yet</p>
            <p class="text-sm text-gray-500 mt-1">Add your first team member using the form above</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Contact Form Settings -->
      <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30 mb-8">
        <h2 class="text-2xl font-heading text-spaGreen mb-4">Contact Form Settings</h2>
        <?php
        $form_settings_query = $conn->query("SELECT * FROM contact_form_settings LIMIT 1");
        $form_settings = $form_settings_query->fetch_assoc();
        ?>
        <form method="POST" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-gray-700 text-sm font-bold mb-2">Section Heading</label>
              <input type="text" name="contact_heading" value="<?= htmlspecialchars($form_settings['heading']) ?>" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
            </div>
            
            <div>
              <label class="block text-gray-700 text-sm font-bold mb-2">Section Subheading</label>
              <input type="text" name="contact_subheading" value="<?= htmlspecialchars($form_settings['subheading']) ?>" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
            </div>
          </div>
          
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
            <input type="text" name="contact_description" value="<?= htmlspecialchars($form_settings['description']) ?>" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
          </div>
          
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">Recipient Email (where messages are sent)</label>
            <input type="email" name="recipient_email" value="<?= htmlspecialchars($form_settings['recipient_email']) ?>" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
          </div>
          
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">Success Message</label>
            <textarea name="success_message" rows="2" required
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold"><?= htmlspecialchars($form_settings['success_message']) ?></textarea>
          </div>
          
          <div class="flex justify-end">
            <button type="submit" name="update_contact_form"
                    class="px-6 py-2 bg-spaGold text-white rounded-md hover:bg-spaGreen transition">
              Update Contact Form Settings
            </button>
          </div>
        </form>
      </div>

      <!-- Contact Messages Inbox -->
      <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30 mb-8">
        <h2 class="text-2xl font-heading text-spaGreen mb-6">Contact Messages</h2>
        
        <?php 
        $messages_query = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 50");
        if ($messages_query && $messages_query->num_rows > 0): 
        ?>
          <div class="space-y-4">
            <?php while($msg = $messages_query->fetch_assoc()): ?>
              <div class="border border-spaAccent/30 rounded-lg p-4 <?= $msg['status'] === 'unread' ? 'bg-spaIvory/30' : 'bg-white' ?>">
                <div class="flex justify-between items-start mb-2">
                  <div>
                    <h4 class="font-semibold text-spaGreen"><?= htmlspecialchars($msg['name']) ?></h4>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($msg['email']) ?> <?= !empty($msg['phone']) ? '• ' . htmlspecialchars($msg['phone']) : '' ?></p>
                  </div>
                  <div class="flex items-center gap-2">
                    <?php if ($msg['status'] === 'unread'): ?>
                      <span class="px-2 py-1 bg-spaGold text-white text-xs rounded-full">New</span>
                    <?php endif; ?>
                    <span class="text-xs text-gray-500"><?= date('M d, Y g:i A', strtotime($msg['created_at'])) ?></span>
                  </div>
                </div>
                <p class="text-sm font-medium text-gray-700 mb-2">Subject: <?= htmlspecialchars($msg['subject']) ?></p>
                <p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                <div class="mt-3 flex gap-2">
                  <?php if ($msg['status'] === 'unread'): ?>
                    <a href="?mark_read=<?= $msg['id'] ?>" class="text-xs text-spaGold hover:text-spaGreen font-medium">Mark as Read</a>
                  <?php endif; ?>
                  <a href="?delete_message=<?= $msg['id'] ?>" onclick="return confirm('Delete this message?')" class="text-xs text-red-500 hover:text-red-700 font-medium">Delete</a>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <p class="text-gray-500 text-center py-8">No messages yet.</p>
        <?php endif; ?>
      </div>

      <!-- Locations Section -->
      <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30 mb-8">
        <h2 class="text-2xl font-heading text-spaGreen mb-6">Locations</h2>
        
        <!-- Add New Location Form -->
        <div class="bg-spaIvory/50 rounded-lg p-6 mb-6">
          <h3 class="text-lg font-semibold text-spaGreen mb-4">Add New Location</h3>
          <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Location Name</label>
                <input type="text" name="location_name" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
              </div>
              
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">City</label>
                <input type="text" name="city"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
              </div>
            </div>
            
            <div>
              <label class="block text-gray-700 text-sm font-bold mb-2">Address</label>
              <textarea name="address" rows="2" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold"></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Phone</label>
                <input type="text" name="location_phone"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
              </div>
              
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                <input type="email" name="location_email"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
              </div>
            </div>
            
            <div>
              <label class="block text-gray-700 text-sm font-bold mb-2">Business Hours</label>
              <textarea name="hours" rows="2" placeholder="Mon-Fri: 9AM-8PM&#10;Sat-Sun: 10AM-6PM"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold"></textarea>
            </div>
            
            <div>
              <label class="block text-gray-700 text-sm font-bold mb-2">Google Maps Embed Code (Optional)</label>
              <textarea name="map_embed" rows="3" placeholder='<iframe src="..." ...></iframe>'
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold"></textarea>
              <p class="text-xs text-gray-500 mt-1">Paste the full iframe embed code from Google Maps</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Location Photo</label>
                <input type="file" name="location_image" accept="image/*"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
              </div>
              
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Display Order</label>
                <input type="number" name="location_order" value="0" min="0"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
              </div>
            </div>
            
            <div class="flex justify-end">
              <button type="submit" name="add_location"
                      class="px-6 py-2 bg-spaGold text-white rounded-md hover:bg-spaGreen transition">
                Add Location
              </button>
            </div>
          </form>
        </div>

        <!-- Existing Locations -->
        <?php 
        $locations_query = $conn->query("SELECT * FROM locations ORDER BY display_order ASC, id ASC");
        if ($locations_query && $locations_query->num_rows > 0): 
        ?>
          <h3 class="text-lg font-semibold text-spaGreen mb-4 mt-8">Current Locations</h3>
          <div class="space-y-4">
            <?php while($location = $locations_query->fetch_assoc()): ?>
              <div class="bg-white border border-spaAccent/30 rounded-lg p-4 flex items-start justify-between">
                <div class="flex-1">
                  <h4 class="font-semibold text-spaGreen"><?= htmlspecialchars($location['name']) ?></h4>
                  <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($location['address']) ?></p>
                  <div class="flex gap-4 mt-2 text-xs text-gray-500">
                    <?php if (!empty($location['phone'])): ?>
                      <span>📞 <?= htmlspecialchars($location['phone']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($location['email'])): ?>
                      <span>✉️ <?= htmlspecialchars($location['email']) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
                <a href="?delete_location=<?= $location['id'] ?>" 
                   onclick="return confirm('Delete this location?')"
                   class="text-red-500 hover:text-red-700 text-sm font-medium ml-4">
                  Delete
                </a>
              </div>
            <?php endwhile; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Contact Information Section -->
      <div class="bg-white rounded-xl shadow-md p-6 border border-spaAccent/30 mb-8">
        <h2 class="text-2xl font-heading text-spaGreen mb-6">Contact Information</h2>
        
        <!-- Add New Contact Form -->
        <div class="bg-spaIvory/50 rounded-lg p-6 mb-6">
          <h3 class="text-lg font-semibold text-spaGreen mb-4">Add Contact Info</h3>
          <form method="POST" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Type</label>
                <select name="contact_type" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
                  <option value="phone">Phone</option>
                  <option value="email">Email</option>
                  <option value="address">Address</option>
                  <option value="hours">Hours</option>
                  <option value="other">Other</option>
                </select>
              </div>
              
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Label</label>
                <input type="text" name="contact_label" required placeholder="e.g., Main Office"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
              </div>
              
              <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Icon</label>
                <select name="contact_icon" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
                  <option value="phone">Phone</option>
                  <option value="email">Email</option>
                  <option value="location">Location</option>
                  <option value="clock">Clock</option>
                </select>
              </div>
            </div>
            
            <div>
              <label class="block text-gray-700 text-sm font-bold mb-2">Value</label>
              <input type="text" name="contact_value" required placeholder="e.g., +63 123 456 7890"
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
            </div>
            
            <div>
              <label class="block text-gray-700 text-sm font-bold mb-2">Display Order</label>
              <input type="number" name="contact_order" value="0" min="0"
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-spaGold">
            </div>
            
            <div class="flex justify-end">
              <button type="submit" name="add_contact"
                      class="px-6 py-2 bg-spaGold text-white rounded-md hover:bg-spaGreen transition">
                Add Contact Info
              </button>
            </div>
          </form>
        </div>

        <!-- Existing Contacts -->
        <?php 
        $contacts_query = $conn->query("SELECT * FROM contact_info ORDER BY display_order ASC, id ASC");
        if ($contacts_query && $contacts_query->num_rows > 0): 
        ?>
          <h3 class="text-lg font-semibold text-spaGreen mb-4 mt-8">Current Contact Info</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php while($contact = $contacts_query->fetch_assoc()): ?>
              <div class="bg-white border border-spaAccent/30 rounded-lg p-4 flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs px-2 py-1 bg-spaIvory text-spaGreen rounded"><?= htmlspecialchars($contact['type']) ?></span>
                    <h4 class="font-semibold text-spaGreen"><?= htmlspecialchars($contact['label']) ?></h4>
                  </div>
                  <p class="text-sm text-gray-600"><?= htmlspecialchars($contact['value']) ?></p>
                </div>
                <a href="?delete_contact=<?= $contact['id'] ?>" 
                   onclick="return confirm('Delete this contact?')"
                   class="text-red-500 hover:text-red-700 text-sm font-medium ml-4">
                  Delete
                </a>
              </div>
            <?php endwhile; ?>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </main>

  <?php render_footer(); ?>

  <?php login_modal(); ?>
</body>
</html>

<?php $conn->close(); ?>