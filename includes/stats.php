<?php
declare(strict_types=1);

function compute_dashboard_stats(PDO $pdo): array
{
    $movements = $pdo->query(
        'SELECT m.id, m.client_id, m.product_id, m.quantity, m.amount, m.note, m.movement_date, m.created_at,
                c.name AS client_name, p.name AS product_name
         FROM movements m
         INNER JOIN clients c ON c.id = m.client_id
         INNER JOIN products p ON p.id = m.product_id
         ORDER BY m.movement_date DESC, m.created_at DESC'
    )->fetchAll();

    $totalQuantity = 0.0;
    $totalAmount = 0.0;
    $months = [];
    $dates = [];

    foreach ($movements as $mv) {
        $totalQuantity += (float) $mv['quantity'];
        $totalAmount += (float) $mv['amount'];
        $months[substr($mv['movement_date'], 0, 7)] = true;
        $dates[] = $mv['movement_date'];
    }

    $monthCount = max(count($months), 1);
    $spanDays = 1;
    if ($dates) {
        sort($dates);
        $first = new DateTimeImmutable($dates[0]);
        $last = new DateTimeImmutable($dates[count($dates) - 1]);
        $spanDays = max(1, (int) $first->diff($last)->days + 1);
    }
    $weekCount = max($spanDays / 7, 1);

    $today = today_iso();
    $last30From = (new DateTimeImmutable($today))->modify('-29 days')->format('Y-m-d');
    $prev30To = (new DateTimeImmutable($last30From))->modify('-1 day')->format('Y-m-d');
    $prev30From = (new DateTimeImmutable($prev30To))->modify('-29 days')->format('Y-m-d');

    $last30 = ['quantity' => 0.0, 'amount' => 0.0, 'count' => 0];
    $prev30 = ['quantity' => 0.0, 'amount' => 0.0, 'count' => 0];

    $clientMap = [];
    $productMap = [];

    foreach ($movements as $mv) {
        $d = $mv['movement_date'];
        $qty = (float) $mv['quantity'];
        $amt = (float) $mv['amount'];

        if ($d >= $last30From && $d <= $today) {
            $last30['quantity'] += $qty;
            $last30['amount'] += $amt;
            $last30['count']++;
        } elseif ($d >= $prev30From && $d <= $prev30To) {
            $prev30['quantity'] += $qty;
            $prev30['amount'] += $amt;
            $prev30['count']++;
        }

        $cid = (int) $mv['client_id'];
        if (!isset($clientMap[$cid])) {
            $clientMap[$cid] = [
                'id' => $cid,
                'name' => $mv['client_name'],
                'quantity' => 0.0,
                'amount' => 0.0,
                'count' => 0,
            ];
        }
        $clientMap[$cid]['quantity'] += $qty;
        $clientMap[$cid]['amount'] += $amt;
        $clientMap[$cid]['count']++;

        $pid = (int) $mv['product_id'];
        if (!isset($productMap[$pid])) {
            $productMap[$pid] = [
                'id' => $pid,
                'name' => $mv['product_name'],
                'quantity' => 0.0,
                'amount' => 0.0,
            ];
        }
        $productMap[$pid]['quantity'] += $qty;
        $productMap[$pid]['amount'] += $amt;
    }

    $clientStats = [];
    foreach ($clientMap as $row) {
        $row['avg_quantity'] = $row['count'] ? $row['quantity'] / $row['count'] : 0.0;
        $row['avg_amount'] = $row['count'] ? $row['amount'] / $row['count'] : 0.0;
        $clientStats[] = $row;
    }

    $bestByAmount = $clientStats;
    usort($bestByAmount, static fn($a, $b) => $b['amount'] <=> $a['amount']);
    $bestByAmount = array_slice($bestByAmount, 0, 5);

    $bestByQuantity = $clientStats;
    usort($bestByQuantity, static fn($a, $b) => $b['quantity'] <=> $a['quantity']);
    $bestByQuantity = array_slice($bestByQuantity, 0, 5);

    $clientAverages = $clientStats;
    usort($clientAverages, static fn($a, $b) => $b['avg_amount'] <=> $a['avg_amount']);

    // Asegurar productos seed aunque no tengan movimientos
    $allProducts = products_all($pdo, false);
    $productMix = [];
    foreach ($allProducts as $product) {
        $pid = (int) $product['id'];
        $row = $productMap[$pid] ?? [
            'id' => $pid,
            'name' => $product['name'],
            'quantity' => 0.0,
            'amount' => 0.0,
        ];
        $row['share'] = $totalQuantity > 0 ? $row['quantity'] / $totalQuantity : 0.0;
        $productMix[] = $row;
    }
    usort($productMix, static fn($a, $b) => $b['quantity'] <=> $a['quantity']);

    $thisMonth = date('Y-m');
    $prevMonth = (new DateTimeImmutable('first day of this month'))->modify('-1 month')->format('Y-m');
    $monthCompare = [
        'this' => ['quantity' => 0.0, 'amount' => 0.0, 'count' => 0, 'label' => $thisMonth],
        'prev' => ['quantity' => 0.0, 'amount' => 0.0, 'count' => 0, 'label' => $prevMonth],
    ];
    foreach ($movements as $mv) {
        $key = substr($mv['movement_date'], 0, 7);
        if ($key === $thisMonth) {
            $monthCompare['this']['quantity'] += (float) $mv['quantity'];
            $monthCompare['this']['amount'] += (float) $mv['amount'];
            $monthCompare['this']['count']++;
        } elseif ($key === $prevMonth) {
            $monthCompare['prev']['quantity'] += (float) $mv['quantity'];
            $monthCompare['prev']['amount'] += (float) $mv['amount'];
            $monthCompare['prev']['count']++;
        }
    }

    $monthlyAvgQuantity = $totalQuantity / $monthCount;
    $monthlyAvgAmount = $totalAmount / $monthCount;

    return [
        'empty' => count($movements) === 0,
        'total_quantity' => $totalQuantity,
        'total_amount' => $totalAmount,
        'movement_count' => count($movements),
        'month_count' => count($months),
        'monthly_avg_quantity' => $monthlyAvgQuantity,
        'monthly_avg_amount' => $monthlyAvgAmount,
        'weekly_avg_quantity' => $totalQuantity / $weekCount,
        'weekly_avg_amount' => $totalAmount / $weekCount,
        'last30' => $last30,
        'prev30' => $prev30,
        'amount_delta_pct' => ($last30['count'] || $prev30['count'])
            ? pct_delta($last30['amount'], $prev30['amount'])
            : null,
        'quantity_delta_pct' => ($last30['count'] || $prev30['count'])
            ? pct_delta($last30['quantity'], $prev30['quantity'])
            : null,
        'best_by_amount' => $bestByAmount,
        'best_by_quantity' => $bestByQuantity,
        'client_averages' => $clientAverages,
        'product_mix' => $productMix,
        'recent' => array_slice($movements, 0, 8),
        'stock_hint' => $monthlyAvgQuantity,
        'month_compare' => $monthCompare,
        'month_amount_delta' => pct_delta($monthCompare['this']['amount'], $monthCompare['prev']['amount']),
        'month_qty_delta' => pct_delta($monthCompare['this']['quantity'], $monthCompare['prev']['quantity']),
    ];
}
