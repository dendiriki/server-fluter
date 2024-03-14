<?php
// ...

$action = isset($_REQUEST["action"]) ? $_REQUEST["action"] : null;

switch ($action) {
    case "check_database_connection":
        // Memeriksa koneksi database
        try {
            $conn = new PDO("mysql:host=localhost;dbname=location", "root", "");
            echo "Database Connection Status: Connection successful!";
            
            // Menampilkan data dari tabel user
            $stmt = $conn->prepare("SELECT * FROM users");
            $stmt->execute();
            
            echo "<h2>Data from 'users' table:</h2>";
            echo "<ul>";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<li>Username: " . $row['username'] . ", Password: " . $row['password'] . "</li>";
            }
            echo "</ul>";
            
            $conn = null; // Menutup koneksi
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
        break;

    default:
        // Tindakan default, jika tidak ada tindakan yang cocok
        echo "Invalid action";
        break;
}
?>
