<?php
// database connection
require 'api/db.php';
session_start();

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'] ?? '';

    if (!empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM tb_password WHERE password_name = :password");
            $stmt->execute(['password' => $password]);
            $passwordData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($passwordData) {
                $lockerStatus = (int)$passwordData['locker_status'];
                $passwordId = $passwordData['password_id'];
                $_SESSION['passwordId'] = $passwordId;

                // Determine which locker to update
                $lockerField = $lockerStatus === 1 ? 'locker1' : 'locker2';
                $_SESSION['lockerField'] = $lockerField;

                // Update password status
                $updatePasswordStatusStmt = $pdo->prepare("UPDATE tb_password SET locker_status = 1 WHERE password_id = :password_id");
                $updatePasswordStatusStmt->execute(['password_id' => $passwordId]);

                // Update the specific locker status
                $updateLockerStmt = $pdo->prepare("UPDATE tb_locker SET $lockerField = :locker_value WHERE locker_id = 1");
                $updateLockerStmt->execute(['locker_value' => 1]);

                // Check current locker status
                $lockerCheckStmt = $pdo->query("SELECT locker1, locker2 FROM tb_locker WHERE locker_id = 1");
                $lockerData = $lockerCheckStmt->fetch(PDO::FETCH_ASSOC);

                // Reset the specific locker if both are open
                if ($lockerData['locker1'] == 1 && $lockerData['locker2'] == 1) {
                    $resetLockerStmt = $pdo->prepare("UPDATE tb_locker SET $lockerField = 0 WHERE locker_id = 1");
                    $resetLockerStmt->execute();
                }

                // Reset password status if both lockers are closed
                if ($lockerData['locker1'] == 0 && $lockerData['locker2'] == 0) {
                    $resetPasswordStatusStmt = $pdo->prepare("UPDATE tb_password SET password_status = 0 WHERE password_id = :password_id");
                    $resetPasswordStatusStmt->execute(['password_id' => $passwordId]);
                }

                // Log the time open
                $logTimeOpenStmt = $pdo->prepare("INSERT INTO tb_log (open, quantity, action, locker, close) VALUES (CURRENT_TIME, 0, 0, 0, 0)");
                $logTimeOpenStmt->execute();
                $logId = $pdo->lastInsertId();
                $_SESSION['logId'] = $logId;

                // Redirect to system.php
                header("Location: system.php");
                exit();
            } else {
                $message = 'รหัสผ่านผิด';
                $status = 'error';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $status = 'error';
        }
    } else {
        $message = 'กรุณากรอกรหัสผ่าน';
        $status = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .container {
            text-align: center;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 380px;
            backdrop-filter: blur(10px);
            animation: containerFadeIn 0.5s ease-out;
        }
        @keyframes containerFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        h1 {
            margin-bottom: 25px;
            font-size: 2.2em;
            color: #2d3748;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        .info {
            margin: 15px 0;
            font-size: 1.3em;
            color: #4a5568;
        }
        .keypad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 25px;
            padding: 15px;
        }
        .keypad button {
            font-size: 1.6em;
            padding: 15px;
            border: none;
            background: linear-gradient(145deg, #f0f0f0, #e6e6e6);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 5px 5px 10px #d1d1d1, -5px -5px 10px #ffffff;
            position: relative;
            overflow: hidden;
        }
        .keypad button:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.3s ease-out, height 0.3s ease-out;
        }
        .keypad button:hover:before {
            width: 200%;
            height: 200%;
        }
        .keypad button:hover {
            transform: translateY(-2px);
            box-shadow: 7px 7px 15px #d1d1d1, -7px -7px 15px #ffffff;
        }
        .keypad button:active {
            transform: translateY(1px);
            box-shadow: inset 2px 2px 5px #d1d1d1, inset -2px -2px 5px #ffffff;
        }
        .clear {
            background: linear-gradient(145deg, #ff6b6b, #ff4757) !important;
            color: white !important;
        }
        .submit {
            background: linear-gradient(145deg, #2ecc71, #27ae60) !important;
            color: white !important;
        }
        .display {
            font-size: 2em;
            margin: 20px 0;
            height: 60px;
            line-height: 60px;
            border: none;
            border-radius: 15px;
            background: linear-gradient(145deg, #f0f0f0, #e6e6e6);
            box-shadow: inset 5px 5px 10px #d1d1d1, inset -5px -5px 10px #ffffff;
            color: #2d3748;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 10px;
            background-color: #4299e1;
            color: white;
            font-size: 1.1em;
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        strong {
            color: #4a5568;
            font-weight: 600;
        }
        .popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease-out;
        }
        .popup-content {
            background: white;
            padding: 30px;  
            border-radius: 10px;
            text-align: center;
            animation: popupSlideIn 0.3s ease-out;
            width: 300px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        @keyframes popupSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>ระบบยืม-คืนสินค้า</h1>
    <div class="display" id="password-display">------</div>
    <form id="password-form" method="POST" action="">
        <div class="keypad">
            <button type="button" onclick="addDigit(1)">1</button>
            <button type="button" onclick="addDigit(2)">2</button>
            <button type="button" onclick="addDigit(3)">3</button>
            <button type="button" onclick="addDigit(4)">4</button>
            <button type="button" onclick="addDigit(5)">5</button>
            <button type="button" onclick="addDigit(6)">6</button>
            <button type="button" onclick="addDigit(7)">7</button>
            <button type="button" onclick="addDigit(8)">8</button>
            <button type="button" onclick="addDigit(9)">9</button>
            <button type="button" class="clear" onclick="clearPassword()">Clear</button>
            <button type="button" onclick="addDigit(0)">0</button>
            <button type="submit" class="submit">Submit</button>
        </div>
        <input type="hidden" id="password" name="password" value="">
    </form>
</div>

<?php if (isset($message)): ?>
    <div id="response-message">
        <div class="popup" id="popup">
            <div class="popup-content">
                <p style="color: <?= $status === 'success' ? 'green' : 'red' ?>;"><?= $message ?></p>
                <button onclick="closePopup()">Close</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    function addDigit(digit) {
        let passwordDisplay = document.getElementById("password-display");
        let password = document.getElementById("password");
        let currentPassword = password.value;

        if (currentPassword.length < 6) {
            currentPassword += digit;
            password.value = currentPassword;
            
            // Add animation class
            passwordDisplay.style.transform = "scale(1.1)";
            setTimeout(() => {
                passwordDisplay.style.transform = "scale(1)";
            }, 100);
            
            passwordDisplay.textContent = "•".repeat(currentPassword.length) + "------".slice(currentPassword.length);
        }
    }

    function clearPassword() {
        let passwordDisplay = document.getElementById("password-display");
        let password = document.getElementById("password");
        
        // Add shake animation
        passwordDisplay.style.animation = "shake 0.5s";
        setTimeout(() => {
            passwordDisplay.style.animation = "";
        }, 500);
        
        password.value = "";
        passwordDisplay.textContent = "------";
    }

    function closePopup() {
        const popup = document.getElementById("popup");
        popup.style.opacity = "0";
        setTimeout(() => {
            popup.style.display = "none";
            popup.style.opacity = "1";
        }, 300);
    }

    <?php if (isset($message)): ?>
        document.getElementById("popup").style.display = "flex";
    <?php endif; ?>
</script>

<style>
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
</style>

</body>
</html>