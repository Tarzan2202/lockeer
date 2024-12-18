<?php
session_start();
$Id = $_SESSION['passwordId'] ?? null;
$logId = $_SESSION['logId'] ?? null;
include('api/db.php');

// เช็คการกระทำจาก AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && isset($_POST['quantity'])) {
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $action = $_GET['action'];

    if ($quantity === false || $quantity <= 0) {
        echo json_encode(["error" => "จำนวนไม่ถูกต้อง!"]);
        exit();
    }

    try {
        // ดึงจำนวนสินค้าล่าสุด
        $stmt = $pdo->prepare("SELECT total_items FROM inventory WHERE id = :id");
        $stmt->execute(['id' => $Id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $availableItems = $result['total_items'];

        switch ($action) {
            case 'borrow':
                if ($quantity > $availableItems) {
                    echo json_encode(["error" => "จำนวนที่ยืมเกินจำนวนที่มีอยู่!"]);
                } else {
                    $newTotal = $availableItems - $quantity;
                    updateInventory($pdo, $newTotal);
                    logTransaction($pdo, $quantity, 'ยืม', $Id, $logId); // 1 for borrow
                    echo json_encode(["success" => "ยืม " . $quantity . " ชิ้น กรุณาปิดตู้"]);
                }
                break;
            case 'return':
                $newTotal = $availableItems + $quantity;
                updateInventory($pdo, $newTotal);
                logTransaction($pdo, $quantity, 'คืน', $Id, $logId); // 2 for return
                echo json_encode(["success" => "คืน " . $quantity . " ชิ้น กรุณาปิดตู้"]);
                break;
            default:
                echo json_encode(["error" => "การกระทำไม่ถูกต้อง!"]);
                break;
        }
    } catch (PDOException $e) {
        echo json_encode(["error" => "ไม่สามารถดำเนินการได้: " . $e->getMessage()]);
    }
    exit();
}

// ฟังก์ชันอัปเดตข้อมูลฐานข้อมูล
function updateInventory($pdo, $newTotal) {
    global $Id;
    $stmt = $pdo->prepare("UPDATE inventory SET total_items = :total WHERE id = :id");
    $stmt->execute(['total' => $newTotal, 'id' => $Id]);
}

// ฟังก์ชันบันทึกการทำธุรกรรม
function logTransaction($pdo, $quantity, $action, $lockerId, $logId) {
    $stmt = $pdo->prepare("UPDATE tb_log SET quantity = :quantity, action = :action, locker = :locker, close = NOW() WHERE id = :logId");
    $stmt->execute(['quantity' => $quantity, 'action' => $action, 'locker' => $lockerId, 'logId' => $logId]);
}

// ดึงข้อมูลสินค้าปัจจุบัน
$stmt = $pdo->prepare("SELECT total_items FROM inventory WHERE id = :id");
$stmt->execute(['id' => $Id ]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$availableItems = $result['total_items'];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบยืมสินค้า</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
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
            border-radius: 20px;
            text-align: center;
            animation: popupSlideIn 0.3s ease-out;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 90%;
        }
        .popup-content button {
            margin-top: 20px;
            padding: 12px 30px;
            font-size: 1.2em;
            background: linear-gradient(145deg, #2ecc71, #27ae60);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
        }
        .popup-content button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 204, 113, 0.4);
        }
        .popup-content button:active {
            transform: translateY(1px);
        }
        .popup-message {
            font-size: 1.4em;
            color: #2d3748;
            margin-bottom: 20px;
            line-height: 1.5;
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
        <h2> <?php echo ($_GET['action'] === 'return') ? 'คืน' : 'ยืม'; ?></h2>
        <div>สินค้าที่มีอยู่: <strong id="availableItems"><?php echo htmlspecialchars($availableItems); ?></strong> ชิ้น</div>
        <div id="display" class="display">0</div>
        <div class="keypad">
            <button onclick="addDigit(1)">1</button>
            <button onclick="addDigit(2)">2</button>
            <button onclick="addDigit(3)">3</button>
            <button onclick="addDigit(4)">4</button>
            <button onclick="addDigit(5)">5</button>
            <button onclick="addDigit(6)">6</button>
            <button onclick="addDigit(7)">7</button>
            <button onclick="addDigit(8)">8</button>
            <button onclick="addDigit(9)">9</button>
            <button class="clear" onclick="clearDisplay()">Clear</button>
            <button onclick="addDigit(0)">0</button>
            <button class="submit" onclick="submitAction('<?php echo htmlspecialchars($_GET['action'] ?? ''); ?>')">
                <?php echo ($_GET['action'] === 'return') ? 'คืน' : 'ยืม'; ?>
            </button>
        </div>
    </div>

    <div id="popup" class="popup">
        <div class="popup-content">
            <div id="popupMessage" class="popup-message"></div>
            <button onclick="clearSessionAndRedirect()">ปิดตู้</button>
        </div>
    </div>

    <script>
        let quantity = "";

        function addDigit(digit) {
            quantity = parseInt((quantity || "0") + digit, 10).toString();
            updateDisplay();
        }

        function clearDisplay() {
            quantity = "";
            updateDisplay();
        }

        function updateDisplay() {
            document.getElementById("display").innerText = quantity || "0";
        }

        function submitAction(action) {
            const qty = parseInt(quantity) || 0;
            if (qty <= 0) {
                alert("กรุณาใส่จำนวนที่ถูกต้อง");
                return;
            }

            fetch(`?action=${action}`, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `quantity=${qty}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById("popupMessage").innerText = data.success;
                    showPopup();
                    updateAvailableItems();
                } else if (data.error) {
                    alert(data.error);
                }
            });
        }

        function showPopup() {
            document.getElementById("popup").style.display = "flex";
        }

        function clearSessionAndRedirect() {
            fetch('lockert.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=destroy_session'
            })
            .then(() => {
                window.location.href = 'logout.php';
            });
        }

        function updateAvailableItems() {
            fetch(location.href)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, "text/html");
                const newItems = doc.getElementById("availableItems").innerText;
                document.getElementById("availableItems").innerText = newItems;
            });
        }
    </script>
</body>
</html>
