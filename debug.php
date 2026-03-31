<?php
require_once 'config.php';

// Check what's in admins table
$stmt = $pdo->query("SELECT id, username, email, LEFT(password, 20) as pwd_preview FROM admins");
$admins = $stmt->fetchAll();

echo "<pre>";
print_r($admins);
echo "</pre>";

// Test password verify
$testPassword = 'Admin@1234';
$stmt2 = $pdo->prepare("SELECT password FROM admins WHERE username = 'admin'");
$stmt2->execute();
$row = $stmt2->fetch();

if ($row) {
    echo "Hash found: " . $row['password'] . "<br>";
    echo "Verify result: " . (password_verify($testPassword, $row['password']) ? 'SUCCESS ✅' : 'FAILED ❌');
} else {
    echo "No admin found with username 'admin'";
}
?>
```

Run it at:
```
http://localhost/lending_system/debug.php