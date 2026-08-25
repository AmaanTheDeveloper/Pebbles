Pebbles Order Confirmed — #{{ $order_number }}

Hi {{ $greeting_name }},

Thank you for your order! Your Pebbles order has been confirmed and our team is getting it ready.

Order #{{ $order_number }}
@if (! empty($order_date))
Order date: {{ $order_date }}
@endif
Status: {{ $status_label }}
@if (! empty($branch_name))
Branch: {{ $branch_name }}
@endif
Order type: {{ $order_type_label }}
@if (! empty($table_number))
Table: {{ $table_number }}
@endif
@if (! empty($estimated_minutes))
Estimated time: About {{ $estimated_minutes }} minutes
@endif
@if (! empty($payment_method_label))
Payment method: {{ $payment_method_label }}
@endif
@if (! empty($payment_status_label))
Payment status: {{ $payment_status_label }}
@endif
@if (! empty($customer_email))
Email: {{ $customer_email }}
@endif
@if (! empty($customer_phone))
Phone: {{ $customer_phone }}
@endif

@if ($is_delivery && (! empty($delivery_address) || ! empty($delivery_zone_name)))
Delivery details:
@if (! empty($delivery_zone_name))
Zone: {{ $delivery_zone_name }}
@endif
@if (! empty($delivery_address))
{{ $delivery_address }}
@endif
@elseif ($is_takeaway && (! empty($branch_name) || ! empty($branch_address)))
Pickup location: {{ $branch_name }}@if (! empty($branch_address)) — {{ $branch_address }}@endif
@elseif ($is_dine_in && (! empty($branch_name) || ! empty($table_number)))
Dine-in details:
@if (! empty($branch_name))
{{ $branch_name }}
@endif
@if (! empty($table_number))
Table {{ $table_number }}
@endif
@endif

Order items:
@foreach ($items as $item)
- {{ $item['name'] }} × {{ $item['quantity'] }} — {{ $item['line_total'] }} ({{ $item['unit_price'] }} each)
@foreach ($item['add_ons'] as $addOn)
  • {{ $addOn['name'] }} × {{ $addOn['quantity'] }} — {{ $addOn['line_total'] }}
@endforeach
@endforeach

Subtotal: {{ $subtotal }}
@if ($has_offer_discount ?? false)
Offer discount: -{{ $offer_discount_amount }}
@elseif ($has_discount && ! ($has_coupon ?? false))
Discount: -{{ $discount_amount }}
@endif
@if ($has_coupon ?? false)
Coupon discount{{ ! empty($coupon_code) ? ' ('.$coupon_code.')' : '' }}: -{{ $coupon_discount_amount }}
@endif
@if ($has_delivery_fee)
Delivery fee: {{ $delivery_fee }}
@endif
Grand total: {{ $grand_total }}

View your order: {{ $track_url }}

Thank you for choosing {{ $app_name }}.
@if (! empty($contact_email) || ! empty($contact_phone))
Questions? {{ $contact_email }} {{ $contact_phone }}
@endif

© {{ $year }} {{ $app_name }}
