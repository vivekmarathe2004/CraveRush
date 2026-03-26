<?php
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    header('Location: ' . $path);
    exit;
}

function is_post() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function cart_count() {
    if (empty($_SESSION['cart'])) {
        return 0;
    }
    return array_sum($_SESSION['cart']);
}

function status_label($value) {
    return ucwords(str_replace('_', ' ', (string)$value));
}

function restaurant_rating($id) {
    $rating = 3.8 + ((int)$id * 7 % 12) / 10;
    return number_format($rating, 1);
}

function restaurant_eta($id) {
    return 20 + ((int)$id * 5 % 25);
}

function restaurant_price_for_two($id) {
    return 200 + ((int)$id * 37 % 350);
}

function display_rating($rating, $id) {
    if ($rating !== null && $rating > 0) {
        return number_format((float)$rating, 1);
    }
    return restaurant_rating($id);
}

function display_eta($eta, $id) {
    if ($eta !== null && $eta > 0) {
        return (int)$eta;
    }
    return restaurant_eta($id);
}

function display_price_for_two($price, $id) {
    if ($price !== null && $price > 0) {
        return (int)$price;
    }
    return restaurant_price_for_two($id);
}

function display_cuisine($cuisine) {
    $cuisine = trim((string)$cuisine);
    return $cuisine !== '' ? $cuisine : 'Multi-cuisine';
}

function fallback_image($seed) {
    $images = [
        'assets/images/dishes/misal.jpg',
        'assets/images/dishes/thali.jpg',
        'assets/images/dishes/biryani.jpg',
        'assets/images/dishes/dosa.jpg',
        'assets/images/dishes/chaat.jpg',
        'assets/images/dishes/vada-pav.jpg',
        'assets/images/dishes/kebab.jpg',
        'assets/images/dishes/gulab-jamun.jpg',
        'assets/images/dishes/coffee.jpg',
    ];
    $index = ((int)$seed) % count($images);
    return $images[$index];
}

function compute_order_totals($subtotal, $promoCode = null) {
    $subtotal = (float)$subtotal;
    $deliveryFee = $subtotal >= 499 ? 0 : 25;
    $packagingFee = max(5, round($subtotal * 0.02, 2));
    $taxes = round(($subtotal + $packagingFee) * 0.05, 2);

    $discount = 0.0;
    $promoValid = false;
    $promoNote = null;
    $code = strtoupper(trim((string)$promoCode));

    if ($code !== '') {
        if ($code === 'SAVE50' && $subtotal >= 299) {
            $discount = 50;
            $promoValid = true;
            $promoNote = 'SAVE50 applied';
        } elseif ($code === 'SAVE100' && $subtotal >= 499) {
            $discount = 100;
            $promoValid = true;
            $promoNote = 'SAVE100 applied';
        } elseif ($code === 'TASTY75' && $subtotal >= 349) {
            $discount = 75;
            $promoValid = true;
            $promoNote = 'TASTY75 applied';
        } elseif ($code === 'FREESHIP') {
            $discount = $deliveryFee;
            $promoValid = true;
            $promoNote = 'FREESHIP applied';
        } else {
            $promoNote = 'Promo code not applicable.';
        }
    }

    $grandTotal = max(0, $subtotal + $deliveryFee + $packagingFee + $taxes - $discount);

    return [
        'subtotal' => $subtotal,
        'delivery_fee' => $deliveryFee,
        'packaging_fee' => $packagingFee,
        'taxes' => $taxes,
        'discount' => $discount,
        'grand_total' => $grandTotal,
        'promo_valid' => $promoValid,
        'promo_note' => $promoNote,
        'promo_code' => $code,
    ];
}

function add_order_event($pdo, $orderId, $status, $note = null) {
    if (!$pdo || $orderId <= 0) {
        return;
    }
    $stmt = $pdo->prepare('INSERT INTO order_tracking_events (order_id, status, note) VALUES (?, ?, ?)');
    $stmt->execute([$orderId, $status, $note]);
}

function add_notification($pdo, $userId, $title, $message, $link = null) {
    if (!$pdo || $userId <= 0) {
        return;
    }
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $title, $message, $link]);
}
?>
