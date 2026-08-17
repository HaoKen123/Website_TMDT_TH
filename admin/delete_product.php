<?php
session_start();
require_once '../db.php';

// Ensure user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    if (isset($_GET['ajax']) || isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Chưa đăng nhập']);
        exit;
    }
    header('Location: login.php');
    exit;
}

// Handle Bulk Delete via POST (Array of IDs)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = array_map('intval', $_POST['ids']);
    $ids = array_filter($ids, function ($id) {
        return $id > 0; });

    if (!empty($ids)) {
        try {
            $pdo->beginTransaction();
            $in = implode(',', array_fill(0, count($ids), '?'));

            // Delete related order items if exists
            $stmtItems = $pdo->prepare("DELETE FROM order_items WHERE product_id IN ($in)");
            $stmtItems->execute($ids);

            // Delete products
            $stmt = $pdo->prepare("DELETE FROM products WHERE id IN ($in)");
            $stmt->execute($ids);

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
        echo json_encode(['status' => 'success', 'count' => count($ids), 'ids' => $ids]);
        exit;
    }

    header('Location: products.php?msg=bulk_deleted');
    exit;
}

// Handle Single Delete via GET
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id > 0) {
    try {
        $pdo->beginTransaction();
        $stmtItems = $pdo->prepare("DELETE FROM order_items WHERE product_id = ?");
        $stmtItems->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
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

header('Location: products.php?msg=deleted');
exit;
