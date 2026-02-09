<?php
session_start();

// Ensure both users exist in global_users
if (!isset($_SESSION['global_users'])) {
    $_SESSION['global_users'] = [];
}

// Add Omer if not exists
if (!isset($_SESSION['global_users']['omersr12@gmail.com'])) {
    $_SESSION['global_users']['omersr12@gmail.com'] = [
        'id' => 'omersr12@gmail.com',
        'username' => 'Omer Shalom Rimon',
        'email' => 'omersr12@gmail.com',
        'password' => password_hash('1234567890', PASSWORD_DEFAULT),
        'avatar' => 'https://ui-avatars.com/api/?name=Omer+Shalom+Rimon&background=4f46e5&color=fff',
        'created_at' => time(),
        'registered_at' => time(),
        'last_active' => time()
    ];
}

// Add/Update Testy
$_SESSION['global_users']['testy@osrg.lol'] = [
    'id' => 'testy@osrg.lol',
    'username' => 'testy mesty',
    'email' => 'testy@osrg.lol',
    'password' => password_hash('1234567890', PASSWORD_DEFAULT),
    'avatar' => 'https://ui-avatars.com/api/?name=testy+mesty&background=4f46e5&color=fff',
    'created_at' => time(),
    'registered_at' => time(),
    'last_active' => time()
];

echo "✅ Both users restored!<br><br>";
echo "<h3>All Users:</h3>";
echo "<pre>";
print_r($_SESSION['global_users']);
echo "</pre>";

echo "<br><a href='login.php'>Go to Login</a> | <a href='debug_users.php'>Check Debug</a> | <a href='friends.php'>Go to Friends</a>";
?>

