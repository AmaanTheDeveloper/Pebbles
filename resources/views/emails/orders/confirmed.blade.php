@extends('emails.layouts.pebbles')

@section('content')
    <h1 style="margin:0 0 12px;font-family:Georgia,'Times New Roman',Times,serif;font-size:28px;line-height:1.25;color:#176b3a;font-weight:700;">
        Your Order Is Confirmed!
    </h1>

    <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#374151;">
        Hi {{ $greeting_name }},
    </p>

    <p style="margin:0 0 28px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#374151;">
        Great news! Your Pebbles order has been confirmed and our team is getting it ready.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;background-color:#fffdf8;border:1px solid #e5e7eb;border-radius:12px;">
        <tr>
            <td style="padding:20px 20px 8px;font-family:Arial,Helvetica,sans-serif;">
                <p style="margin:0 0 4px;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#9ca3af;">
                    Order number
                </p>
                <p style="margin:0 0 16px;font-size:24px;font-weight:700;color:#176b3a;">
                    #{{ $order_number }}
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding:0 20px 20px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#1f2937;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td style="padding:6px 0;color:#6b7280;width:42%;">Status</td>
                        <td style="padding:6px 0;font-weight:600;text-align:right;">{{ $status_label }}</td>
                    </tr>
                    @if (! empty($branch_name))
                        <tr>
                            <td style="padding:6px 0;color:#6b7280;">Branch</td>
                            <td style="padding:6px 0;font-weight:600;text-align:right;">{{ $branch_name }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding:6px 0;color:#6b7280;">Order type</td>
                        <td style="padding:6px 0;font-weight:600;text-align:right;">{{ $order_type_label }}</td>
                    </tr>
                    @if (! empty($estimated_minutes))
                        <tr>
                            <td style="padding:6px 0;color:#6b7280;">Estimated time</td>
                            <td style="padding:6px 0;font-weight:600;text-align:right;">About {{ $estimated_minutes }} minutes</td>
                        </tr>
                    @endif
                    @if (! empty($payment_method_label))
                        <tr>
                            <td style="padding:6px 0;color:#6b7280;">Payment method</td>
                            <td style="padding:6px 0;font-weight:600;text-align:right;">{{ $payment_method_label }}</td>
                        </tr>
                    @endif
                    @if (! empty($payment_status_label))
                        <tr>
                            <td style="padding:6px 0;color:#6b7280;">Payment status</td>
                            <td style="padding:6px 0;font-weight:600;text-align:right;">{{ $payment_status_label }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    @if ($is_delivery && ! empty($delivery_address))
        <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#9ca3af;">
            Delivery address
        </p>
        <p style="margin:0 0 24px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.55;color:#1f2937;">
            {{ $delivery_address }}
        </p>
    @elseif ($is_takeaway && (! empty($branch_name) || ! empty($branch_address)))
        <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#9ca3af;">
            Pickup location
        </p>
        <p style="margin:0 0 24px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.55;color:#1f2937;">
            {{ $branch_name }}
            @if (! empty($branch_address))
                <br>{{ $branch_address }}
            @endif
        </p>
    @endif

    <p style="margin:0 0 12px;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#9ca3af;">
        Items
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#1f2937;">
        @foreach ($items as $item)
            <tr>
                <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;vertical-align:top;">
                    <strong style="color:#111827;">{{ $item['name'] }}</strong>
                    <span style="color:#6b7280;">&nbsp;× {{ $item['quantity'] }}</span>
                    @foreach ($item['add_ons'] as $addOn)
                        <div style="margin-top:4px;padding-left:10px;color:#6b7280;font-size:13px;">
                            + {{ $addOn['name'] }}
                            @if ($addOn['quantity'] > 1)
                                × {{ $addOn['quantity'] }}
                            @endif
                        </div>
                    @endforeach
                </td>
                <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;vertical-align:top;text-align:right;white-space:nowrap;font-weight:600;">
                    {{ $item['line_total'] }}
                </td>
            </tr>
        @endforeach
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 28px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#1f2937;">
        <tr>
            <td style="padding:6px 0;color:#6b7280;">Subtotal</td>
            <td style="padding:6px 0;text-align:right;">{{ $subtotal }}</td>
        </tr>
        @if ($has_discount)
            <tr>
                <td style="padding:6px 0;color:#6b7280;">Discount</td>
                <td style="padding:6px 0;text-align:right;">-{{ $discount_amount }}</td>
            </tr>
        @endif
        @if ($has_delivery_fee)
            <tr>
                <td style="padding:6px 0;color:#6b7280;">Delivery fee</td>
                <td style="padding:6px 0;text-align:right;">{{ $delivery_fee }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding:12px 0 0;font-size:16px;font-weight:700;color:#176b3a;">Total</td>
            <td style="padding:12px 0 0;font-size:16px;font-weight:700;color:#176b3a;text-align:right;">{{ $grand_total }}</td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px;">
        <tr>
            <td align="center">
                <a href="{{ $track_url }}" style="display:inline-block;background-color:#f97316;background:linear-gradient(135deg,#f97316 0%,#ea580c 100%);color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;letter-spacing:0.06em;text-decoration:none;text-transform:uppercase;padding:14px 28px;border-radius:999px;">
                    Track Order
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:16px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;color:#9ca3af;text-align:center;">
        Or open this link:<br>
        <a href="{{ $track_url }}" style="color:#176b3a;text-decoration:underline;word-break:break-all;">{{ $track_url }}</a>
    </p>
@endsection
