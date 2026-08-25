<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $receipt['app_name'] }} — {{ $receipt['order_number'] }}</title>
    <style>
        @page {
            margin: 28px 32px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #1f2937;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.45;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #176b3a;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }

        .logo {
            max-height: 52px;
            max-width: 180px;
        }

        .brand-mark {
            display: inline-block;
            width: 42px;
            height: 42px;
            border-radius: 21px;
            background: #176b3a;
            color: #ffffff;
            text-align: center;
            line-height: 42px;
            font-size: 18px;
            font-weight: bold;
        }

        .brand-name {
            margin: 6px 0 0;
            color: #176b3a;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .doc-title {
            margin: 2px 0 0;
            color: #6b7280;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .meta-table,
        .info-table,
        .items-table,
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            vertical-align: top;
            padding: 0;
        }

        .meta-right {
            text-align: right;
            white-space: nowrap;
        }

        .order-number {
            font-size: 15px;
            font-weight: bold;
            color: #176b3a;
        }

        .badge {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 8px;
            border-radius: 999px;
            background: #ecfdf5;
            color: #176b3a;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .badge-cancelled {
            background: #fef2f2;
            color: #b91c1c;
        }

        .section {
            margin-top: 16px;
        }

        .section-title {
            margin: 0 0 8px;
            color: #176b3a;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 4px;
        }

        .info-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 10px 0 0;
        }

        .info-table td:last-child {
            padding-right: 0;
            padding-left: 10px;
        }

        .label {
            color: #6b7280;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 2px;
        }

        .value {
            color: #111827;
            font-size: 11px;
            margin-bottom: 8px;
        }

        .items-table thead {
            display: table-header-group;
        }

        .items-table tbody {
            display: table-row-group;
        }

        .items-table tr {
            page-break-inside: avoid;
        }

        .items-table thead th {
            background: #f3f4f6;
            color: #374151;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            text-align: left;
            padding: 8px 6px;
            border-bottom: 1px solid #d1d5db;
        }

        .items-table thead th.num,
        .items-table tbody td.num {
            text-align: right;
            white-space: nowrap;
        }

        .items-table thead th.qty,
        .items-table tbody td.qty {
            text-align: center;
            width: 48px;
        }

        .items-table tbody td {
            padding: 8px 6px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .item-name {
            font-weight: bold;
            color: #111827;
        }

        .addon {
            color: #6b7280;
            font-size: 10px;
            padding-top: 2px;
        }

        .totals-wrap {
            margin-top: 12px;
            width: 100%;
        }

        .totals-table {
            width: 260px;
            margin-left: auto;
        }

        .totals-table td {
            padding: 4px 0;
        }

        .totals-table .label-cell {
            color: #6b7280;
        }

        .totals-table .amount-cell {
            text-align: right;
            white-space: nowrap;
        }

        .totals-table .total-row td {
            padding-top: 8px;
            border-top: 2px solid #176b3a;
            font-size: 13px;
            font-weight: bold;
            color: #176b3a;
        }

        .footer {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
        }

        .footer strong {
            color: #176b3a;
            font-size: 11px;
        }

        .muted {
            color: #6b7280;
        }

        .track {
            margin-top: 10px;
            font-size: 10px;
            color: #176b3a;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 55%; vertical-align: middle;">
                @if ($receipt['has_logo'])
                    <img class="logo" src="{{ $receipt['logo_src'] }}" alt="{{ $receipt['app_name'] }}">
                @else
                    <div class="brand-mark">{{ strtoupper(substr($receipt['app_name'], 0, 1)) }}</div>
                    <div class="brand-name">{{ $receipt['app_name'] }}</div>
                @endif
                <div class="doc-title">{{ $receipt['document_title'] }}</div>
            </td>
            <td class="meta-right" style="vertical-align: middle;">
                <div class="order-number">{{ $receipt['order_number'] }}</div>
                <div class="muted">{{ $receipt['ordered_at_label'] }}</div>
                <div class="badge {{ $receipt['is_cancelled'] ? 'badge-cancelled' : '' }}">
                    {{ $receipt['status_label'] }}
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <table class="info-table">
            <tr>
                <td>
                    <div class="section-title">Order</div>
                    <div class="label">Order Number</div>
                    <div class="value">{{ $receipt['order_number'] }}</div>

                    <div class="label">Order Date</div>
                    <div class="value">{{ $receipt['ordered_at_label'] }}</div>

                    <div class="label">Order Type</div>
                    <div class="value">{{ $receipt['order_type_label'] }}</div>

                    <div class="label">Status</div>
                    <div class="value">{{ $receipt['status_label'] }}</div>
                </td>
                <td>
                    <div class="section-title">
                        @if ($receipt['is_delivery'])
                            Customer &amp; Delivery
                        @elseif ($receipt['is_takeaway'])
                            Customer &amp; Pickup
                        @else
                            Customer
                        @endif
                    </div>

                    @if (! empty($receipt['customer']['name']))
                        <div class="label">Customer</div>
                        <div class="value">{{ $receipt['customer']['name'] }}</div>
                    @endif

                    @if (! empty($receipt['customer']['phone']))
                        <div class="label">Phone</div>
                        <div class="value">{{ $receipt['customer']['phone'] }}</div>
                    @endif

                    @if (! empty($receipt['customer']['email']))
                        <div class="label">Email</div>
                        <div class="value">{{ $receipt['customer']['email'] }}</div>
                    @endif

                    @if ($receipt['is_delivery'])
                        @if (! empty($receipt['delivery_zone_name']))
                            <div class="label">Delivery Zone</div>
                            <div class="value">{{ $receipt['delivery_zone_name'] }}</div>
                        @endif

                        @if (! empty($receipt['delivery_address_lines']))
                            <div class="label">Delivery Address</div>
                            <div class="value">
                                @foreach ($receipt['delivery_address_lines'] as $line)
                                    {{ $line }}@if (! $loop->last)<br>@endif
                                @endforeach
                            </div>
                        @endif
                    @endif

                    @if ($receipt['is_takeaway'])
                        <div class="label">Pickup Branch</div>
                        <div class="value">
                            {{ $receipt['branch_name'] ?? '—' }}
                            @if (! empty($receipt['branch_address']))
                                <br>{{ $receipt['branch_address'] }}
                            @endif
                        </div>
                        @if (! empty($receipt['pickup_notes']))
                            <div class="label">Pickup Notes</div>
                            <div class="value">{{ $receipt['pickup_notes'] }}</div>
                        @endif
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Branch</div>
        <div class="value" style="margin-bottom: 0;">
            <strong>{{ $receipt['branch_name'] ?? 'Pebbles' }}</strong>
            @if (! empty($receipt['branch_address']))
                <br>{{ $receipt['branch_address'] }}
            @endif
            @if (! empty($receipt['branch_phone']) || ! empty($receipt['branch_whatsapp']))
                <br>
                @if (! empty($receipt['branch_phone']))
                    Phone: {{ $receipt['branch_phone'] }}
                @endif
                @if (! empty($receipt['branch_phone']) && ! empty($receipt['branch_whatsapp']))
                    ·
                @endif
                @if (! empty($receipt['branch_whatsapp']))
                    WhatsApp: {{ $receipt['branch_whatsapp'] }}
                @endif
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title">Items</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="qty">Qty</th>
                    <th class="num">Price</th>
                    <th class="num">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($receipt['items'] as $item)
                    <tr>
                        <td>
                            <div class="item-name">{{ $item['name'] }}</div>
                            @foreach ($item['add_ons'] as $addOn)
                                <div class="addon">
                                    + {{ $addOn['name'] }}
                                    @if ($addOn['quantity'] > 1)
                                        × {{ $addOn['quantity'] }}
                                    @endif
                                    ({{ $addOn['line_total'] }})
                                </div>
                            @endforeach
                        </td>
                        <td class="qty">{{ $item['quantity'] }}</td>
                        <td class="num">{{ $item['unit_price'] }}</td>
                        <td class="num">{{ $item['line_total'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted">No items recorded for this order.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="totals-wrap">
            <table class="totals-table">
                <tr>
                    <td class="label-cell">Subtotal</td>
                    <td class="amount-cell">{{ $receipt['subtotal'] }}</td>
                </tr>
                @if ($receipt['has_offer_discount'] ?? false)
                    <tr>
                        <td class="label-cell">Discount</td>
                        <td class="amount-cell">- {{ $receipt['offer_discount_amount'] }}</td>
                    </tr>
                @elseif ($receipt['has_discount'] && ! ($receipt['has_coupon'] ?? false))
                    <tr>
                        <td class="label-cell">Discount</td>
                        <td class="amount-cell">- {{ $receipt['discount_amount'] }}</td>
                    </tr>
                @endif
                @if ($receipt['has_coupon'] ?? false)
                    <tr>
                        <td class="label-cell">Coupon ({{ $receipt['coupon_code'] }})</td>
                        <td class="amount-cell">- {{ $receipt['coupon_discount_amount'] }}</td>
                    </tr>
                @endif
                @if ($receipt['has_delivery_fee'])
                    <tr>
                        <td class="label-cell">Delivery Fee</td>
                        <td class="amount-cell">{{ $receipt['delivery_fee'] }}</td>
                    </tr>
                @endif
                @if ($receipt['has_tax'])
                    <tr>
                        <td class="label-cell">Tax</td>
                        <td class="amount-cell">{{ $receipt['tax_amount'] }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td>Total</td>
                    <td class="amount-cell">{{ $receipt['grand_total'] }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Payment</div>
        <table class="info-table">
            <tr>
                <td>
                    <div class="label">Payment Method</div>
                    <div class="value">{{ $receipt['payment_method_label'] ?? 'Not selected' }}</div>
                </td>
                <td>
                    <div class="label">Payment Status</div>
                    <div class="value">{{ $receipt['payment_status_label'] ?? '—' }}</div>
                </td>
            </tr>
        </table>
        @if (! empty($receipt['customer_notes']))
            <div class="label">Customer Notes</div>
            <div class="value">{{ $receipt['customer_notes'] }}</div>
        @endif
        @if ($receipt['show_track_link'])
            <div class="track">Track order: {{ $receipt['track_url'] }}</div>
        @endif
    </div>

    <div class="footer">
        <strong>Thank you for choosing {{ $receipt['app_name'] }}.</strong><br>
        Freshly prepared. Made for you.<br><br>
        {{ $receipt['app_name'] }}
        @if (! empty($receipt['contact_phone']) || ! empty($receipt['contact_whatsapp']) || ! empty($receipt['contact_email']))
            <br>
            @if (! empty($receipt['contact_phone']))
                {{ $receipt['contact_phone'] }}
            @endif
            @if (! empty($receipt['contact_phone']) && ! empty($receipt['contact_whatsapp']))
                ·
            @endif
            @if (! empty($receipt['contact_whatsapp']))
                WhatsApp {{ $receipt['contact_whatsapp'] }}
            @endif
            @if ((! empty($receipt['contact_phone']) || ! empty($receipt['contact_whatsapp'])) && ! empty($receipt['contact_email']))
                ·
            @endif
            @if (! empty($receipt['contact_email']))
                {{ $receipt['contact_email'] }}
            @endif
        @endif
        @if (! empty($receipt['website_url']))
            <br>{{ $receipt['website_url'] }}
        @endif
        <br><br>
        <span class="muted">Generated {{ $receipt['generated_at_label'] }}</span>
    </div>
</body>
</html>
