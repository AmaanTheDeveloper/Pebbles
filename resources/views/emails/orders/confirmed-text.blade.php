Your Pebbles Order #{{ $order_number }} Is Confirmed!

Hi {{ $greeting_name }},

Great news! Your Pebbles order has been confirmed and our team is getting it ready.

Order #{{ $order_number }}
Status: {{ $status_label }}
@if (! empty($branch_name))
Branch: {{ $branch_name }}
@endif
Order type: {{ $order_type_label }}
@if (! empty($estimated_minutes))
Estimated time: About {{ $estimated_minutes }} minutes
@endif
@if (! empty($payment_method_label))
Payment method: {{ $payment_method_label }}
@endif
@if (! empty($payment_status_label))
Payment status: {{ $payment_status_label }}
@endif

@if ($is_delivery && ! empty($delivery_address))
Delivery address: {{ $delivery_address }}
@elseif ($is_takeaway && (! empty($branch_name) || ! empty($branch_address)))
Pickup location: {{ $branch_name }}@if (! empty($branch_address)) — {{ $branch_address }}@endif
@endif

Items:
@foreach ($items as $item)
- {{ $item['name'] }} × {{ $item['quantity'] }} — {{ $item['line_total'] }}
@foreach ($item['add_ons'] as $addOn)
  + {{ $addOn['name'] }}@if ($addOn['quantity'] > 1) × {{ $addOn['quantity'] }}@endif
@endforeach
@endforeach

Subtotal: {{ $subtotal }}
@if ($has_discount)
Discount: -{{ $discount_amount }}
@endif
@if ($has_delivery_fee)
Delivery fee: {{ $delivery_fee }}
@endif
Total: {{ $grand_total }}

Track your order: {{ $track_url }}

Thank you for choosing {{ $app_name }}.
@if (! empty($contact_email) || ! empty($contact_phone))
Questions? {{ $contact_email }} {{ $contact_phone }}
@endif

© {{ $year }} {{ $app_name }}
