<?php
/**
 * SIMPLE DELETE TEST
 * Upload this as test_delete_simple.php
 * Visit: http://yoursite.com/test_delete_simple.php
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli("localhost", "root", "", "pos_system");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Delete Test</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { background: #f0f0f0; padding: 10px; margin: 10px 0; }
        button { padding: 10px 20px; font-size: 16px; cursor: pointer; margin: 5px; }
        .delete-btn { background: #ff4444; color: white; border: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔧 Simple Delete Test</h1>

    <?php
    // Check database connection
    if ($conn->connect_error) {
        echo "<p class='error'>❌ Database connection failed: " . $conn->connect_error . "</p>";
        exit;
    }
    echo "<p class='success'>✓ Database connected successfully</p>";

    // Get all products
    $products = $conn->query("SELECT id, name, stock FROM products ORDER BY id DESC LIMIT 5");
    
    if ($products->num_rows === 0) {
        echo "<p class='error'>No products found in database</p>";
        exit;
    }

    echo "<h2>Recent Products:</h2>";
    echo "<div class='info'>";
    
    while ($product = $products->fetch_assoc()) {
        echo "<div style='padding: 10px; border-bottom: 1px solid #ccc;'>";
        echo "<strong>ID: {$product['id']}</strong> - {$product['name']} (Stock: {$product['stock']})<br>";
        echo "<form method='POST' style='display: inline;'>";
        echo "<input type='hidden' name='delete_id' value='{$product['id']}'>";
        echo "<button type='submit' class='delete-btn' onclick='return confirm(\"Delete {$product['name']}?\")'>🗑️ Delete</button>";
        echo "</form>";
        echo "</div>";
    }
    echo "</div>";

    // Handle delete
    if (isset($_POST['delete_id'])) {
        $delete_id = intval($_POST['delete_id']);
        
        echo "<hr><h2>Delete Attempt:</h2>";
        echo "<div class='info'>";
        echo "<p>Attempting to delete product ID: $delete_id</p>";
        
        // Check if exists
        $check = $conn->prepare("SELECT name FROM products WHERE id = ?");
        $check->bind_param("i", $delete_id);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows === 0) {
            echo "<p class='error'>❌ Product not found!</p>";
        } else {
            $product = $result->fetch_assoc();
            echo "<p>Product found: <strong>{$product['name']}</strong></p>";
            
            // Try delete
            $delete = $conn->prepare("DELETE FROM products WHERE id = ?");
            $delete->bind_param("i", $delete_id);
            
            if ($delete->execute()) {
                if ($delete->affected_rows > 0) {
                    echo "<p class='success'>✅ DELETE SUCCESSFUL!</p>";
                    echo "<p>Affected rows: {$delete->affected_rows}</p>";
                    echo "<p><a href='test_delete_simple.php'>Refresh to see updated list</a></p>";
                } else {
                    echo "<p class='error'>⚠️ Delete executed but 0 rows affected</p>";
                }
            } else {
                echo "<p class='error'>❌ Delete failed: {$delete->error}</p>";
            }
        }
        echo "</div>";
    }

    $conn->close();
    ?>

    <hr>
    <h3>Instructions:</h3>
    <ol>
        <li>Click any "Delete" button above</li>
        <li>Confirm the deletion</li>
        <li>Check if you see "✅ DELETE SUCCESSFUL!"</li>
        <li>Refresh to verify the product is gone</li>
    </ol>

    <p><strong>If this works:</strong> The problem is in your main products_dashboard.php file</p>
    <p><strong>If this doesn't work:</strong> The problem is database permissions or connection</p>

</body>
</html>