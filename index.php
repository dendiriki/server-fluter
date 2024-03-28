<?php
session_start();

class User {
    public function login($userid, $userpass, $mac_addr) {
        $conn = new PDO("mysql:host=localhost;dbname=location", "root", "");

        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $userid);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $return = array(); // Variabel $return untuk menyimpan hasil

        if ($user) {
            // Pemeriksaan kata sandi tanpa hashing
            if ($userpass == $user['password']) {
                // Logika sesuai kebutuhan
                // Misalnya, jika versi aplikasi dan alamat MAC diperlukan untuk log masuk, Anda dapat menambahkannya ke log atau tabel lain.

                // Contoh penggunaan:
             

                $return['status'] = true;
              
            } else {
                $return['status'] = false;
                $return['message'] = 'Invalid username or password';
            }
        } else {
            $return['status'] = false;
            $return['message'] = 'Invalid username or password';
        }

        return $return;
    }

    private function logLogin($mac_addr, $username, $fullname) {
        $conn = new PDO("mysql:host=localhost;dbname=location", "root", "");

        $stmt = $conn->prepare("INSERT INTO login_log (mac_addr, vc_username, vc_name, log_date) VALUES (:mac_addr, :vc_username, :vc_name, NOW())");
        $stmt->bindParam(':mac_addr', $mac_addr);
        $stmt->bindParam(':vc_username', $username);
        $stmt->bindParam(':vc_name', $fullname);
        $stmt->execute();
    }
}

$action = isset($_REQUEST["action"]) ? $_REQUEST["action"] : null;

switch ($action) {
    case "ajax_login":
        $userid = $_REQUEST["userid"];
        $userpass = $_REQUEST["userpass"];
        $mac_addr = $_REQUEST["mac_addr"];

        $user = new User();
        echo json_encode($user->login($userid, $userpass, $mac_addr));
        break;

    default:
        // Tindakan default, jika tidak ada tindakan yang cocok
        echo "Invalid action";
        break;

        case "get_location":
            try {
                $mac_addr = $_REQUEST["mac_addr"];
                $userid = $_REQUEST["userid"];
        
                $conn = new PDO("mysql:host=localhost;dbname=location", "root", "");
        
                if ($conn) {
                    // Gunakan parameter binding untuk menghindari SQL Injection
                    $stmt = $conn->prepare("SELECT DISTINCT main FROM ZPP_WR_LOC");
                    $stmt->execute();
        
                    $mainStorageOptions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
                    // Ambil semua data lokasi untuk setiap nilai main
                    $locationOptions = [];
                    foreach ($mainStorageOptions as $mainStorage) {
                        $stmt = $conn->prepare("SELECT storage FROM ZPP_WR_LOC WHERE main = :main");
                        $stmt->bindParam(':main', $mainStorage);
                        $stmt->execute();
                        $locations = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        $locationOptions[$mainStorage] = $locations;
                    }
        
                    // Simpan mainStorageOptions dan locationOptions ke dalam array untuk digunakan di frontend
                    echo json_encode(["status" => true, "main_storage" => $mainStorageOptions, "storage_locations" => $locationOptions]);
                } else {
                    echo json_encode(["status" => false, "message" => "Connection to database failed"]);
                }
            } catch (PDOException $e) {
                echo json_encode(["status" => false, "message" => "Database error: " . $e->getMessage()]);
            } catch (Exception $e) {
                echo json_encode(["status" => false, "message" => "An unexpected error occurred: " . $e->getMessage()]);
            }
            break;
        
          

      
            case "insert_newonerow":
                $mac_addr = $_REQUEST["mac_addr"];
                $username = $_REQUEST["username"];
                $bundle = $_REQUEST["bundle"];
                $main = $_REQUEST["main"];
                $location = $_REQUEST["location"];
            
                try {
                    $conn = new PDO("mysql:host=localhost;dbname=location", "root", "");

                    $currentDate = date('Y-m-d');
                    $currentTime = date('H-i');


                    
                    // Pertama, ambil data Z_WR_MATNR dan Z_WR_MAKTX dari ZPP_RMS_IB_GR_PR
                    $stmt = $conn->prepare("SELECT Z_WR_MATNR, Z_BL_MAKETX FROM ZPP_RMS_IB_GR_PR WHERE Z_BUNDLE_NO = :bundle");
                    $stmt->bindParam(':bundle', $bundle);
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);

                    $auto = $conn->prepare("SELECT MAX(SRLNO) AS MAX_SRLNO FROM ZPP_WR_SUBLOC_DETAILS");
                    $auto->execute();
                    $maxSrlnoResult = $auto->fetch(PDO::FETCH_ASSOC);
                    $nilai = $maxSrlnoResult['MAX_SRLNO'] + 1 ;
            
                    if ($result) {
                        // Kemudian, insert data ke ZPP_WR_SUBLOC_DETAILS
                        $insertStmt = $conn->prepare("INSERT INTO ZPP_WR_SUBLOC_DETAILS (MANDT, SRLNO, Z_WR_MATNR, Z_WR_MAKTX, Z_BUNDLE_NO, Z_M_SUBLOC, Z_SUBLOC, Z_USR, Z_DATE_TIME, Z_DATE, Z_TIME, Z_DEL_FLAG, Z_NOTE, Z_COLECT_ST) VALUES (600, :nilai, :matnr, :maktx, :bundle, :msubloc, :subloc, :usr, NOW(), DATE(NOW()), TIME(NOW()), 0, 'NO_REL','ONE ROW')");
                        $insertStmt->execute([
                            ':nilai' => $nilai,
                            ':matnr' => $result['Z_WR_MATNR'],
                            ':maktx' => $result['Z_BL_MAKETX'],
                            ':bundle' => $bundle,
                            ':msubloc' => $main,
                            ':subloc' => $location,
                            ':usr' => $username
                        ]);
            
                        echo json_encode(["status" => true, "message" => "Data inserted successfully"]);
                    } else {
                        echo json_encode(["status" => false, "message" => "Bundle not found"]);
                    }
                } catch (PDOException $e) {
                    echo json_encode(["status" => false, "message" => "Database error: " . $e->getMessage()]);
                }
                break;
                
                case "insert_reonerow":
                $mac_addr = $_REQUEST["mac_addr"];
                $username = $_REQUEST["username"];
                $bundle = $_REQUEST["bundle"];
                $main = $_REQUEST["main"];
                $location = $_REQUEST["location"];
            
                try {
                    $conn = new PDO("mysql:host=localhost;dbname=location", "root", "");
                    
                    // Pertama, ambil data Z_WR_MATNR dan Z_WR_MAKTX dari ZPP_RMS_IB_GR_PR
                    $stmt = $conn->prepare("SELECT Z_WR_MATNR, Z_BL_MAKETX FROM ZPP_RMS_IB_GR_PR WHERE Z_BUNDLE_NO = :bundle");
                    $stmt->bindParam(':bundle', $bundle);
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);

                    $auto = $conn->prepare("SELECT MAX(SRLNO) AS MAX_SRLNO FROM ZPP_WR_SUBLOC_DETAILS");
                    $auto->execute();
                    $maxSrlnoResult = $auto->fetch(PDO::FETCH_ASSOC);
                    $nilai = $maxSrlnoResult['MAX_SRLNO'] + 1 ;
            
                    if ($result) {
                        // Kemudian, insert data ke ZPP_WR_SUBLOC_DETAILS
                        $insertStmt = $conn->prepare("INSERT INTO ZPP_WR_SUBLOC_DETAILS (MANDT, SRLNO, Z_WR_MATNR, Z_WR_MAKTX, Z_BUNDLE_NO, Z_M_SUBLOC, Z_SUBLOC, Z_USR, Z_DATE_TIME, Z_DATE, Z_TIME, Z_DEL_FLAG, Z_NOTE, Z_COLECT_ST) VALUES (600, :nilai, :matnr, :maktx, :bundle, :msubloc, :subloc, :usr, NOW(), DATE(NOW()), TIME(NOW()), 0, 'NEW_REL','ONE ROW')");
                        $insertStmt->execute([
                            ':nilai' => $nilai,
                            ':matnr' => $result['Z_WR_MATNR'],
                            ':maktx' => $result['Z_BL_MAKETX'],
                            ':bundle' => $bundle,
                            ':msubloc' => $main,
                            ':subloc' => $location,
                            ':usr' => $username
                        ]);
            
                        echo json_encode(["status" => true, "message" => "Data inserted successfully"]);
                    } else {
                        echo json_encode(["status" => false, "message" => "Bundle not found"]);
                    }
                } catch (PDOException $e) {
                    echo json_encode(["status" => false, "message" => "Database error: " . $e->getMessage()]);
                }
                break;

                case "insert_many_rows":
                    $mac_addr = $_REQUEST["mac_addr"];
                    $username = $_REQUEST["username"];
                    $bundleFrom = $_REQUEST["bundle_from"];
                    $bundleTo = $_REQUEST["bundle_to"];
                    $main = $_REQUEST["main"];
                    $location = $_REQUEST["location"];
                
                    try {
                        $conn = new PDO("mysql:host=localhost;dbname=location", "root", "");
                        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                        $stmt = $conn->prepare("SELECT Z_BUNDLE_NO, Z_WR_MATNR, Z_BL_MAKETX FROM ZPP_RMS_IB_GR_PR WHERE Z_BUNDLE_NO BETWEEN :bundleFrom AND :bundleTo");
                        $stmt->bindParam(':bundleFrom', $bundleFrom, PDO::PARAM_STR);
                        $stmt->bindParam(':bundleTo', $bundleTo, PDO::PARAM_STR);
                        $stmt->execute();
                        $bundles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                        if (!$bundles) {
                            echo json_encode(["status" => false, "message" => "No bundles found within the specified range"]);
                            exit;
                        }
                
                        foreach ($bundles as $bundle) {
                            $auto = $conn->prepare("SELECT MAX(SRLNO) AS MAX_SRLNO FROM ZPP_WR_SUBLOC_DETAILS");
                            $auto->execute();
                            $maxSrlnoResult = $auto->fetch(PDO::FETCH_ASSOC);
                            $nilai = $maxSrlnoResult ? $maxSrlnoResult['MAX_SRLNO'] + 1 : 1;
                
                            $insertStmt = $conn->prepare("INSERT INTO ZPP_WR_SUBLOC_DETAILS (MANDT, SRLNO, Z_WR_MATNR, Z_WR_MAKTX, Z_BUNDLE_NO, Z_M_SUBLOC, Z_SUBLOC, Z_USR, Z_DATE_TIME, Z_DATE, Z_TIME, Z_DEL_FLAG, Z_NOTE, Z_COLECT_ST) VALUES (600, :nilai, :matnr, :maktx, :bundleNo, :msubloc, :subloc, :usr, NOW(), DATE(NOW()), TIME(NOW()), 0, 'NEW_REL', 'MANY ROWS')");
                            $insertStmt->bindParam(':nilai', $nilai);
                            $insertStmt->bindParam(':matnr', $bundle['Z_WR_MATNR'], PDO::PARAM_STR);
                            $insertStmt->bindParam(':maktx', $bundle['Z_BL_MAKETX'], PDO::PARAM_STR);
                            $insertStmt->bindParam(':bundleNo', $bundle['Z_BUNDLE_NO'], PDO::PARAM_STR);
                            $insertStmt->bindParam(':msubloc', $main);
                            $insertStmt->bindParam(':subloc', $location);
                            $insertStmt->bindParam(':usr', $username);
                            $insertStmt->execute();
                        }
                
                        echo json_encode(["status" => true, "message" => "Data inserted successfully for all bundles"]);
                    } catch (PDOException $e) {
                        echo json_encode(["status" => false, "message" => "Database error: " . $e->getMessage()]);
                    }
                    break;   
                    
                    case "insertre_many_rows":
                        $mac_addr = $_REQUEST["mac_addr"];
                        $username = $_REQUEST["username"];
                        $bundleFrom = $_REQUEST["bundle_from"];
                        $bundleTo = $_REQUEST["bundle_to"];
                        $main = $_REQUEST["main"];
                        $location = $_REQUEST["location"];
                    
                        try {
                            $conn = new PDO("mysql:host=localhost;dbname=location", "root", "");
                            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                            $stmt = $conn->prepare("SELECT Z_BUNDLE_NO, Z_WR_MATNR, Z_BL_MAKETX FROM ZPP_RMS_IB_GR_PR WHERE Z_BUNDLE_NO BETWEEN :bundleFrom AND :bundleTo");
                            $stmt->bindParam(':bundleFrom', $bundleFrom, PDO::PARAM_STR);
                            $stmt->bindParam(':bundleTo', $bundleTo, PDO::PARAM_STR);
                            $stmt->execute();
                            $bundles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                            if (!$bundles) {
                                echo json_encode(["status" => false, "message" => "No bundles found within the specified range"]);
                                exit;
                            }
                    
                            foreach ($bundles as $bundle) {
                                $auto = $conn->prepare("SELECT MAX(SRLNO) AS MAX_SRLNO FROM ZPP_WR_SUBLOC_DETAILS");
                                $auto->execute();
                                $maxSrlnoResult = $auto->fetch(PDO::FETCH_ASSOC);
                                $nilai = $maxSrlnoResult ? $maxSrlnoResult['MAX_SRLNO'] + 1 : 1;
                    
                                $insertStmt = $conn->prepare("INSERT INTO ZPP_WR_SUBLOC_DETAILS (MANDT, SRLNO, Z_WR_MATNR, Z_WR_MAKTX, Z_BUNDLE_NO, Z_M_SUBLOC, Z_SUBLOC, Z_USR, Z_DATE_TIME, Z_DATE, Z_TIME, Z_DEL_FLAG, Z_NOTE, Z_COLECT_ST) VALUES (600, :nilai, :matnr, :maktx, :bundleNo, :msubloc, :subloc, :usr, NOW(), DATE(NOW()), TIME(NOW()), 0, 'NEW_REL', 'MANY ROWS')");
                                $insertStmt->bindParam(':nilai', $nilai);
                                $insertStmt->bindParam(':matnr', $bundle['Z_WR_MATNR'], PDO::PARAM_STR);
                                $insertStmt->bindParam(':maktx', $bundle['Z_BL_MAKETX'], PDO::PARAM_STR);
                                $insertStmt->bindParam(':bundleNo', $bundle['Z_BUNDLE_NO'], PDO::PARAM_STR);
                                $insertStmt->bindParam(':msubloc', $main);
                                $insertStmt->bindParam(':subloc', $location);
                                $insertStmt->bindParam(':usr', $username);
                                $insertStmt->execute();
                            }
                    
                            echo json_encode(["status" => true, "message" => "Data inserted successfully for all bundles"]);
                        } catch (PDOException $e) {
                            echo json_encode(["status" => false, "message" => "Database error: " . $e->getMessage()]);
                        }
                        break;        
                
}
?>
