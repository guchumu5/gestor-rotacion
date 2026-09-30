<?php
declare(strict_types=1);

/**
 * Estadísticas del dashboard.
 * - price = precio unitario (NULL si vacío).
 * - Dinero = quantity * price solo cuando hay precio; los sin precio no cuentan en medias €.
 * - Unidades siempre cuentan.
 * - Bloques principales por producto.
 */
function compute_dashboard_stats(PDO $pdo): array
{
    $movements = $pdo->query(
        'SELECT m.id, m.client_id, m.product_id, m.quantity, m.price, m.note, m.movement_date, m.created_at,
                c.name AS client_name, p.name AS product_name
         FROM movements m
         INNER JOIN clients c ON c.id = m.client_id
         INNER JOIN products p ON p.id = m.product_id
         ORDER BY m.movement_date DESC, m.created_at DESC'
    )->fetchAll();

    foreach ($movements as &$mv) {
        $priceRaw = $mv['price'];
        $mv['unit_price'] = $priceRaw === null ? null : (float) $priceRaw;
        $mv['line_total'] = line_total($mv['quantity'], $mv['unit_price']);
    }
    unset($mv);

    $totalQuantity = 0.0;
    $totalAmount = 0.0;
    $pricedCount = 0;
    $months = [];
    $dates = [];

    foreach ($movements as $mv) {
        $totalQuantity += (float) $mv['quantity'];
        $months[substr($mv['movement_date'], 0, 7)] = true;
        $dates[] = $mv['movement_date'];
        if ($mv['line_total'] !== null) {
            $totalAmount += $mv['line_total'];
            $pricedCount++;
        }
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
    $thisMonth = date('Y-m');
    $prevMonth = (new DateTimeImmutable('first day of this month'))->modify('-1 month')->format('Y-m');

    $last30 = ['quantity' => 0.0, 'amount' => 0.0, 'count' => 0, 'priced_count' => 0];
    $prev30 = ['quantity' => 0.0, 'amount' => 0.0, 'count' => 0, 'priced_count' => 0];

    $clientMap = [];
    $productMap = [];

    $emptyProduct = static function (int $pid, string $name): array {
        return [
            'id' => $pid,
            'name' => $name,
            'quantity' => 0.0,
            'amount' => 0.0,
            'priced_count' => 0,
            'this_month_qty' => 0.0,
            'this_month_amount' => 0.0,
            'this_month_priced' => 0,
            'prev_month_qty' => 0.0,
            'prev_month_amount' => 0.0,
            'prev_month_priced' => 0,
            'months' => [],
            'dates' => [],
        ];
    };

    foreach ($movements as $mv) {
        $d = $mv['movement_date'];
        $qty = (float) $mv['quantity'];
        $line = $mv['line_total'];
        $monthKey = substr($d, 0, 7);

        if ($d >= $last30From && $d <= $today) {
            $last30['quantity'] += $qty;
            $last30['count']++;
            if ($line !== null) {
                $last30['amount'] += $line;
                $last30['priced_count']++;
            }
        } elseif ($d >= $prev30From && $d <= $prev30To) {
            $prev30['quantity'] += $qty;
            $prev30['count']++;
            if ($line !== null) {
                $prev30['amount'] += $line;
                $prev30['priced_count']++;
            }
        }

        $cid = (int) $mv['client_id'];
        if (!isset($clientMap[$cid])) {
            $clientMap[$cid] = [
                'id' => $cid,
                'name' => $mv['client_name'],
                'quantity' => 0.0,
                'amount' => 0.0,
                'count' => 0,
                'priced_count' => 0,
                'by_product' => [],
            ];
        }
        $clientMap[$cid]['quantity'] += $qty;
        $clientMap[$cid]['count']++;
        if ($line !== null) {
            $clientMap[$cid]['amount'] += $line;
            $clientMap[$cid]['priced_count']++;
        }

        $pid = (int) $mv['product_id'];
        $pname = (string) $mv['product_name'];
        if (!isset($clientMap[$cid]['by_product'][$pid])) {
            $clientMap[$cid]['by_product'][$pid] = [
                'id' => $pid,
                'name' => $pname,
                'quantity' => 0.0,
                'amount' => 0.0,
                'priced_count' => 0,
            ];
        }
        $clientMap[$cid]['by_product'][$pid]['quantity'] += $qty;
        if ($line !== null) {
            $clientMap[$cid]['by_product'][$pid]['amount'] += $line;
            $clientMap[$cid]['by_product'][$pid]['priced_count']++;
        }

        if (!isset($productMap[$pid])) {
            $productMap[$pid] = $emptyProduct($pid, $pname);
        }
        $productMap[$pid]['quantity'] += $qty;
        $productMap[$pid]['months'][$monthKey] = true;
        $productMap[$pid]['dates'][] = $d;
        if ($line !== null) {
            $productMap[$pid]['amount'] += $line;
            $productMap[$pid]['priced_count']++;
        }
        if ($monthKey === $thisMonth) {
            $productMap[$pid]['this_month_qty'] += $qty;
            if ($line !== null) {
                $productMap[$pid]['this_month_amount'] += $line;
                $productMap[$pid]['this_month_priced']++;
            }
        } elseif ($monthKey === $prevMonth) {
            $productMap[$pid]['prev_month_qty'] += $qty;
            if ($line !== null) {
                $productMap[$pid]['prev_month_amount'] += $line;
                $productMap[$pid]['prev_month_priced']++;
            }
        }
    }

    $clientStats = [];
    foreach ($clientMap as $row) {
        $row['avg_quantity'] = $row['count'] ? $row['quantity'] / $row['count'] : 0.0;
        $row['avg_amount'] = $row['priced_count'] > 0 ? $row['amount'] / $row['priced_count'] : null;
        $byProduct = array_values($row['by_product']);
        usort($byProduct, static fn($a, $b) => $b['quantity'] <=> $a['quantity']);
        $row['by_product'] = $byProduct;
        $clientStats[] = $row;
    }

    $bestByAmount = array_values(array_filter($clientStats, static fn($r) => $r['priced_count'] > 0));
    usort($bestByAmount, static fn($a, $b) => $b['amount'] <=> $a['amount']);
    $bestByAmount = array_slice($bestByAmount, 0, 5);

    $bestByQuantity = $clientStats;
    usort($bestByQuantity, static fn($a, $b) => $b['quantity'] <=> $a['quantity']);
    $bestByQuantity = array_slice($bestByQuantity, 0, 5);

    $clientAverages = $clientStats;
    usort($clientAverages, static function ($a, $b) {
        $aa = $a['avg_amount'] ?? -1.0;
        $bb = $b['avg_amount'] ?? -1.0;
        if ($aa === $bb) {
            return $b['avg_quantity'] <=> $a['avg_quantity'];
        }
        return $bb <=> $aa;
    });

    $allProducts = products_all($pdo, false);
    $productStats = [];
    foreach ($allProducts as $product) {
        $pid = (int) $product['id'];
        $row = $productMap[$pid] ?? $emptyProduct($pid, (string) $product['name']);
        $pMonthCount = max(count($row['months']), 1);
        $pSpanDays = 1;
        if ($row['dates']) {
            $pDates = $row['dates'];
            sort($pDates);
            $pf = new DateTimeImmutable($pDates[0]);
            $pl = new DateTimeImmutable($pDates[count($pDates) - 1]);
            $pSpanDays = max(1, (int) $pf->diff($pl)->days + 1);
        }
        $pWeekCount = max($pSpanDays / 7, 1);

        $row['monthly_avg_quantity'] = $row['quantity'] / $pMonthCount;
        $row['weekly_avg_quantity'] = $row['quantity'] / $pWeekCount;
        $row['monthly_avg_amount'] = $row['priced_count'] > 0 ? $row['amount'] / $pMonthCount : null;
        $row['weekly_avg_amount'] = $row['priced_count'] > 0 ? $row['amount'] / $pWeekCount : null;
        $row['stock_hint'] = $row['monthly_avg_quantity'];
        $row['share'] = $totalQuantity > 0 ? $row['quantity'] / $totalQuantity : 0.0;
        $row['month_count'] = count($row['months']);
        $row['this_month_amount_display'] = $row['this_month_priced'] > 0 ? $row['this_month_amount'] : null;
        $row['prev_month_amount_display'] = $row['prev_month_priced'] > 0 ? $row['prev_month_amount'] : null;
        unset($row['months'], $row['dates']);
        $productStats[] = $row;
    }
    usort($productStats, static fn($a, $b) => $b['quantity'] <=> $a['quantity']);

    $monthCompare = [
        'this' => ['quantity' => 0.0, 'amount' => 0.0, 'count' => 0, 'priced_count' => 0, 'label' => $thisMonth],
        'prev' => ['quantity' => 0.0, 'amount' => 0.0, 'count' => 0, 'priced_count' => 0, 'label' => $prevMonth],
    ];
    foreach ($movements as $mv) {
        $key = substr($mv['movement_date'], 0, 7);
        $bucket = null;
        if ($key === $thisMonth) {
            $bucket = 'this';
        } elseif ($key === $prevMonth) {
            $bucket = 'prev';
        }
        if ($bucket === null) {
            continue;
        }
        $monthCompare[$bucket]['quantity'] += (float) $mv['quantity'];
        $monthCompare[$bucket]['count']++;
        if ($mv['line_total'] !== null) {
            $monthCompare[$bucket]['amount'] += $mv['line_total'];
            $monthCompare[$bucket]['priced_count']++;
        }
    }

    return [
        'empty' => count($movements) === 0,
        'total_quantity' => $totalQuantity,
        'total_amount' => $pricedCount > 0 ? $totalAmount : null,
        'priced_count' => $pricedCount,
        'movement_count' => count($movements),
        'month_count' => count($months),
        'monthly_avg_quantity' => $totalQuantity / $monthCount,
        'monthly_avg_amount' => $pricedCount > 0 ? $totalAmount / $monthCount : null,
        'weekly_avg_quantity' => $totalQuantity / $weekCount,
        'weekly_avg_amount' => $pricedCount > 0 ? $totalAmount / $weekCount : null,
        'last30' => $last30,
        'prev30' => $prev30,
        'amount_delta_pct' => ($last30['priced_count'] || $prev30['priced_count'])
            ? pct_delta($last30['amount'], $prev30['amount'])
            : null,
        'quantity_delta_pct' => ($last30['count'] || $prev30['count'])
            ? pct_delta($last30['quantity'], $prev30['quantity'])
            : null,
        'best_by_amount' => $bestByAmount,
        'best_by_quantity' => $bestByQuantity,
        'client_averages' => $clientAverages,
        'product_stats' => $productStats,
        'product_mix' => $productStats,
        'recent' => array_slice($movements, 0, 8),
        'stock_hint' => $totalQuantity / $monthCount,
        'month_compare' => $monthCompare,
        'month_amount_delta' => ($monthCompare['this']['priced_count'] || $monthCompare['prev']['priced_count'])
            ? pct_delta($monthCompare['this']['amount'], $monthCompare['prev']['amount'])
            : null,
        'month_qty_delta' => pct_delta($monthCompare['this']['quantity'], $monthCompare['prev']['quantity']),
    ];
}

/**
 * Historial de un cliente con huecos entre pedidos (días).
 * Lista más reciente primero; los huecos se calculan en orden cronológico.
 */
function compute_client_history(PDO $pdo, int $clientId): array
{
    $stmt = $pdo->prepare(
        'SELECT m.id, m.quantity, m.price, m.note, m.movement_date, m.created_at,
                p.name AS product_name, m.product_id
         FROM movements m
         INNER JOIN products p ON p.id = m.product_id
         WHERE m.client_id = ?
         ORDER BY m.movement_date ASC, m.created_at ASC, m.id ASC'
    );
    $stmt->execute([$clientId]);
    $chrono = $stmt->fetchAll();

    $gapById = [];
    $gapValues = [];
    $prevDate = null;

    foreach ($chrono as $mv) {
        $id = (int) $mv['id'];
        if ($prevDate === null) {
            $gapById[$id] = null;
        } else {
            $d1 = new DateTimeImmutable($prevDate);
            $d2 = new DateTimeImmutable($mv['movement_date']);
            $days = (int) $d1->diff($d2)->days;
            $gapById[$id] = $days;
            $gapValues[] = $days;
        }
        $prevDate = $mv['movement_date'];
    }

    $rows = [];
    foreach ($chrono as $mv) {
        $priceRaw = $mv['price'];
        $unitPrice = $priceRaw === null ? null : (float) $priceRaw;
        $rows[] = [
            'id' => (int) $mv['id'],
            'product_id' => (int) $mv['product_id'],
            'product_name' => $mv['product_name'],
            'quantity' => (float) $mv['quantity'],
            'unit_price' => $unitPrice,
            'line_total' => line_total($mv['quantity'], $unitPrice),
            'note' => $mv['note'],
            'movement_date' => $mv['movement_date'],
            'gap_days' => $gapById[(int) $mv['id']],
        ];
    }

    // Mostrar más reciente primero
    $display = array_reverse($rows);

    $avgGap = null;
    if (count($gapValues) >= 1 && count($chrono) >= 2) {
        $avgGap = array_sum($gapValues) / count($gapValues);
    }

    return [
        'empty' => count($chrono) === 0,
        'movements' => $display,
        'count' => count($chrono),
        'avg_gap_days' => $avgGap,
    ];
}
