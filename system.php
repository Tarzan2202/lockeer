<?php
session_start();
$Id = $_SESSION['passwordId'] ?? null;
$logId = $_SESSION['logId'] ?? null;
include('api/db.php');

// Check inventory for the session id
$inventoryQuery = $pdo->prepare("SELECT total_items FROM inventory WHERE id = :id");
$inventoryQuery->execute(['id' => $Id]);
$inventoryResult = $inventoryQuery->fetch(PDO::FETCH_ASSOC);
$availableItems = $inventoryResult['total_items'] ?? 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบยืม-คืน</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .container {
            text-align: center;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 400px;
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
            margin-bottom: 30px;
            color: #2d3748;
            font-size: 2.5em;
            font-weight: 600;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .button-container {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        button {
            flex: 1;
            min-width: 150px;
            padding: 15px 30px;
            font-size: 1.3em;
            font-weight: 500;
            cursor: pointer;
            border: none;
            border-radius: 12px;
            color: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        button[value="borrow"] {
            background: linear-gradient(145deg, #2ecc71, #27ae60);
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
        }

        button[value="return"] {
            background: linear-gradient(145deg, #3498db, #2980b9);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        button:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 55%;
            transform: translate(-50%, -50%);
            transition: width 0.3s ease-out, height 0.3s ease-out;
        }

        button:hover:before {
            width: 200%;
            height: 200%;
        }

        button:hover {
            transform: translateY(-3px);
        }

        button:active {
            transform: translateY(1px);
        }

        @media screen and (max-width: 600px) {
            .container {
                width: 90%;
                padding: 30px 20px;
                margin: 20px;
            }

            h1 {
                font-size: 2em;
            }

            button {
                width: 100%;
                min-width: unset;
            }

            .button-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>ระบบยืม-คืน</h1>
        <form action="lockert.php" method="get">
            <div class="button-container">
                <?php if ($availableItems > 0): ?>
                    <button type="submit" name="action" value="borrow">ยืม</button>
                <?php else: ?>
                    <button type="button" disabled style="background-color: #000; color: #fff;">ของหมด</button>
                <?php endif; ?>
                <button type="submit" name="action" value="return">คืน</button>
            </div>
        </form>
    </div>
</body>
</html>
