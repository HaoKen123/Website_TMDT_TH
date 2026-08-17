<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_id'])) {
    if (isset($_GET['ajax']) || isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Chưa đăng nhập']);
        exit;
    }
    header('Location: login.php');
    exit;
}

// Bulk Delete Users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = array_map('intval', $_POST['ids']);
    $ids = array_filter($ids, function ($id) {
        return $id > 0; });

    if (!empty($ids)) {
        try {
            $pdo->beginTransaction();
            $in = implode(',', array_fill(0, count($ids), '?'));

            // 1. Get emails of users to delete from subscribers
            $stmtEmails = $pdo->prepare("SELECT email FROM users WHERE id IN ($in) AND email IS NOT NULL AND email != ''");
            $stmtEmails->execute($ids);
            $emails = $stmtEmails->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($emails)) {
                $inEmails = implode(',', array_fill(0, count($emails), '?'));
                try {
                    $stmtDelSub = $pdo->prepare("DELETE FROM subscribers WHERE email IN ($inEmails)");
                    $stmtDelSub->execute($emails);
                } catch (Exception $ex) {
                }
            }

            // 2. Delete user's order items & orders
            $stmtOrderIds = $pdo->prepare("SELECT id FROM orders WHERE user_id IN ($in)");
            $stmtOrderIds->execute($ids);
            $orderIds = $stmtOrderIds->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($orderIds)) {
                $inOrders = implode(',', array_fill(0, count($orderIds), '?'));
                $stmtDelItems = $pdo->prepare("DELETE FROM order_items WHERE order_id IN ($inOrders)");
                $stmtDelItems->execute($orderIds);

                $stmtDelOrders = $pdo->prepare("DELETE FROM orders WHERE user_id IN ($in)");
                $stmtDelOrders->execute($ids);
            }

            // 3. Delete user's comments
            try {
                $stmtDelComments = $pdo->prepare("DELETE FROM comments WHERE user_id IN ($in)");
                $stmtDelComments->execute($ids);
            } catch (Exception $ex) {
            }

            // 4. Delete users
            $stmtDelUsers = $pdo->prepare("DELETE FROM users WHERE id IN ($in)");
            $stmtDelUsers->execute($ids);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }
        }
    }

    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'count' => count($ids)]);
        exit;
    }

    header('Location: users.php?msg=deleted');
    exit;
}

// Single Delete User
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id > 0) {
    try {
        $pdo->beginTransaction();

        // 1. Get user email to delete from subscribers table
        $stmtEmail = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmtEmail->execute([$id]);
        $uEmail = $stmtEmail->fetchColumn();

        if (!empty($uEmail)) {
            try {
                $stmtDelSub = $pdo->prepare("DELETE FROM subscribers WHERE email = ?");
                $stmtDelSub->execute([$uEmail]);
            } catch (Exception $ex) {
            }
        }

        // 2. Delete user's orders & order items
        $stmtOrderIds = $pdo->prepare("SELECT id FROM orders WHERE user_id = ?");
        $stmtOrderIds->execute([$id]);
        $orderIds = $stmtOrderIds->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($orderIds)) {
            $inOrders = implode(',', array_fill(0, count($orderIds), '?'));
            $stmtDelItems = $pdo->prepare("DELETE FROM order_items WHERE order_id IN ($inOrders)");
            $stmtDelItems->execute($orderIds);

            $stmtDelOrders = $pdo->prepare("DELETE FROM orders WHERE user_id = ?");
            $stmtDelOrders->execute([$id]);
        }

        // 3. Delete user's comments
        try {
            $stmtDelComments = $pdo->prepare("DELETE FROM comments WHERE user_id = ?");
            $stmtDelComments->execute([$id]);
        } catch (Exception $ex) {
        }

        // 4. Delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();

        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'id' => $id]);
            exit;
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
}

header('Location: users.php?msg=deleted');
exit;
