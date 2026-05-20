<?php

function sinelec_order_data(): array
{
    return [
        [
            'order_no' => 'SL-2026-1048',
            'status' => 'pending',
            'status_label' => 'Packed - Awaiting Pickup',
            'date' => 'May 04, 2026',
            'product' => 'STM32F407VGT6 Microcontroller',
            'sku' => 'STM32F407VGT6',
            'qty' => 10,
            'price' => 8.90,
            'total' => 89.00,
            'currency' => 'EUR',
            'tracking' => 'DHL-98324410',
            'carrier' => 'DHL Express',
            'service' => 'Priority Domestic',
            'eta' => 'May 12, 2026',
            'placed_at' => 'May 04, 2026, 10:14 AM',
            'delivery_address' => 'Suite 3, Floor 8, Bldg. 3, Mindspace SEZ, Airoli, Navi Mumbai, Maharashtra 400708',
            'billing_address' => 'Brachvogelweg 9, 85375 Neufahrn, Germany',
            'payment_method' => 'Bank Transfer (SEPA)',
            'image' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=400&h=320&fit=crop',
            'description' => 'High-performance ARM Cortex-M4 MCU with FPU for industrial control, embedded gateways, and advanced automation logic.',
            'timeline' => [
                ['label' => 'Order Placed', 'time' => 'May 04, 2026 - 10:14 AM', 'state' => 'done'],
                ['label' => 'Payment Confirmed', 'time' => 'May 04, 2026 - 10:18 AM', 'state' => 'done'],
                ['label' => 'Packed', 'time' => 'May 05, 2026 - 09:42 AM', 'state' => 'done'],
                ['label' => 'Shipped', 'time' => 'Expected today by 08:00 PM', 'state' => 'active'],
                ['label' => 'Delivered', 'time' => 'Estimated May 12, 2026', 'state' => 'upcoming'],
            ],
        ],
        [
            'order_no' => 'SL-2026-1039',
            'status' => 'pending',
            'status_label' => 'In Transit',
            'date' => 'May 01, 2026',
            'product' => 'MPU-6050 6-Axis Motion Sensor',
            'sku' => 'MPU-6050',
            'qty' => 25,
            'price' => 2.35,
            'total' => 58.75,
            'currency' => 'EUR',
            'tracking' => 'UPS-77194028',
            'carrier' => 'UPS',
            'service' => 'Standard Ground',
            'eta' => 'May 10, 2026',
            'placed_at' => 'May 01, 2026, 03:26 PM',
            'delivery_address' => 'Suite 3, Floor 8, Bldg. 3, Mindspace SEZ, Airoli, Navi Mumbai, Maharashtra 400708',
            'billing_address' => 'Brachvogelweg 9, 85375 Neufahrn, Germany',
            'payment_method' => 'Visa ending 4125',
            'image' => 'https://images.unsplash.com/photo-1581092580497-e0d23cbdf1dc?w=400&h=320&fit=crop',
            'description' => 'Integrated 3-axis gyroscope and accelerometer module used in stabilization, gesture tracking, and IoT sensor systems.',
            'timeline' => [
                ['label' => 'Order Placed', 'time' => 'May 01, 2026 - 03:26 PM', 'state' => 'done'],
                ['label' => 'Payment Confirmed', 'time' => 'May 01, 2026 - 03:28 PM', 'state' => 'done'],
                ['label' => 'Packed', 'time' => 'May 02, 2026 - 11:13 AM', 'state' => 'done'],
                ['label' => 'Shipped', 'time' => 'May 03, 2026 - 08:34 AM', 'state' => 'done'],
                ['label' => 'Delivered', 'time' => 'Estimated May 10, 2026', 'state' => 'active'],
            ],
        ],
        [
            'order_no' => 'SL-2026-0974',
            'status' => 'delivered',
            'status_label' => 'Delivered Successfully',
            'date' => 'Apr 17, 2026',
            'delivered_on' => 'Apr 22, 2026',
            'product' => 'ESP32-WROOM-32 WiFi + BT Module',
            'sku' => 'ESP32-WROOM-32',
            'qty' => 15,
            'price' => 5.60,
            'total' => 84.00,
            'currency' => 'EUR',
            'tracking' => 'DHL-66482217',
            'carrier' => 'DHL Express',
            'service' => 'Priority Domestic',
            'eta' => 'Delivered Apr 22, 2026',
            'placed_at' => 'Apr 17, 2026, 09:48 AM',
            'delivery_address' => 'Suite 3, Floor 8, Bldg. 3, Mindspace SEZ, Airoli, Navi Mumbai, Maharashtra 400708',
            'billing_address' => 'Brachvogelweg 9, 85375 Neufahrn, Germany',
            'payment_method' => 'PayPal',
            'image' => 'https://images.unsplash.com/photo-1553406830-ef2513450d76?w=400&h=320&fit=crop',
            'description' => 'Dual-core Wi-Fi and Bluetooth MCU module ideal for secure cloud-connected products and low-power edge intelligence.',
            'timeline' => [
                ['label' => 'Order Placed', 'time' => 'Apr 17, 2026 - 09:48 AM', 'state' => 'done'],
                ['label' => 'Payment Confirmed', 'time' => 'Apr 17, 2026 - 09:52 AM', 'state' => 'done'],
                ['label' => 'Packed', 'time' => 'Apr 18, 2026 - 10:21 AM', 'state' => 'done'],
                ['label' => 'Shipped', 'time' => 'Apr 19, 2026 - 12:35 PM', 'state' => 'done'],
                ['label' => 'Delivered', 'time' => 'Apr 22, 2026 - 02:12 PM', 'state' => 'done'],
            ],
        ],
        [
            'order_no' => 'SL-2026-0921',
            'status' => 'delivered',
            'status_label' => 'Delivered Successfully',
            'date' => 'Apr 08, 2026',
            'delivered_on' => 'Apr 14, 2026',
            'product' => 'LM2596 Buck Converter Module',
            'sku' => 'LM2596-DC',
            'qty' => 30,
            'price' => 1.95,
            'total' => 58.50,
            'currency' => 'EUR',
            'tracking' => 'UPS-11900761',
            'carrier' => 'UPS',
            'service' => 'Standard Ground',
            'eta' => 'Delivered Apr 14, 2026',
            'placed_at' => 'Apr 08, 2026, 12:40 PM',
            'delivery_address' => 'Suite 3, Floor 8, Bldg. 3, Mindspace SEZ, Airoli, Navi Mumbai, Maharashtra 400708',
            'billing_address' => 'Brachvogelweg 9, 85375 Neufahrn, Germany',
            'payment_method' => 'Mastercard ending 3291',
            'image' => 'https://images.unsplash.com/photo-1593642634443-44adaa06623a?w=400&h=320&fit=crop',
            'description' => 'Step-down DC-DC converter board with adjustable output for embedded power rails and field-ready prototyping.',
            'timeline' => [
                ['label' => 'Order Placed', 'time' => 'Apr 08, 2026 - 12:40 PM', 'state' => 'done'],
                ['label' => 'Payment Confirmed', 'time' => 'Apr 08, 2026 - 12:45 PM', 'state' => 'done'],
                ['label' => 'Packed', 'time' => 'Apr 09, 2026 - 09:09 AM', 'state' => 'done'],
                ['label' => 'Shipped', 'time' => 'Apr 10, 2026 - 05:14 PM', 'state' => 'done'],
                ['label' => 'Delivered', 'time' => 'Apr 14, 2026 - 11:57 AM', 'state' => 'done'],
            ],
        ],
    ];
}

function sinelec_orders_by_status(string $status): array
{
    $status = strtolower(trim($status));
    return array_values(array_filter(sinelec_order_data(), static function (array $order) use ($status): bool {
        return strtolower((string)($order['status'] ?? '')) === $status;
    }));
}

function sinelec_find_order(string $orderNo): ?array
{
    $normalized = strtoupper(trim($orderNo));
    foreach (sinelec_order_data() as $order) {
        if (strtoupper((string)($order['order_no'] ?? '')) === $normalized) {
            return $order;
        }
    }

    return null;
}

function sinelec_returns_data(): array
{
    return [
        [
            'rma_no'       => 'RMA-2026-0041',
            'order_no'     => 'SL-2026-0974',
            'date'         => 'Apr 24, 2026',
            'product'      => 'ESP32-WROOM-32 WiFi + BT Module',
            'sku'          => 'ESP32-WROOM-32',
            'qty'          => 3,
            'reason'       => 'Defective / Not Working',
            'status'       => 'approved',
            'status_label' => 'Return Approved',
            'refund'       => 16.80,
            'currency'     => 'EUR',
            'image'        => 'https://images.unsplash.com/photo-1553406830-ef2513450d76?w=400&h=320&fit=crop',
            'note'         => 'Refund will be processed within 3–5 business days.',
        ],
        [
            'rma_no'       => 'RMA-2026-0038',
            'order_no'     => 'SL-2026-0921',
            'date'         => 'Apr 16, 2026',
            'product'      => 'LM2596 Buck Converter Module',
            'sku'          => 'LM2596-DC',
            'qty'          => 5,
            'reason'       => 'Wrong Item Received',
            'status'       => 'processing',
            'status_label' => 'Under Review',
            'refund'       => 9.75,
            'currency'     => 'EUR',
            'image'        => 'https://images.unsplash.com/photo-1593642634443-44adaa06623a?w=400&h=320&fit=crop',
            'note'         => 'Our team is reviewing your return request.',
        ],
        [
            'rma_no'       => 'RMA-2026-0029',
            'order_no'     => 'SL-2026-0921',
            'date'         => 'Apr 10, 2026',
            'product'      => 'LM2596 Buck Converter Module',
            'sku'          => 'LM2596-DC',
            'qty'          => 2,
            'reason'       => 'Arrived Damaged',
            'status'       => 'completed',
            'status_label' => 'Refund Issued',
            'refund'       => 3.90,
            'currency'     => 'EUR',
            'image'        => 'https://images.unsplash.com/photo-1593642634443-44adaa06623a?w=400&h=320&fit=crop',
            'note'         => 'Refund of €3.90 was issued on Apr 17, 2026.',
        ],
    ];
}
