<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="color-scheme" content="light" />
    <meta name="supported-color-schemes" content="light" />
    <title>{{ $app_name }}</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td { font-family: Arial, Helvetica, sans-serif !important; }
    </style>
    <![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#f3f6f4;font-family:Georgia,'Times New Roman',Times,serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    @isset($preheader)
        <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
            {{ $preheader }}
        </div>
    @endisset

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f3f6f4;margin:0;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 28px rgba(23,107,58,0.08);">
                    <tr>
                        <td style="background:linear-gradient(145deg,#2e8b57 0%,#176b3a 100%);background-color:#176b3a;padding:28px 28px 24px;text-align:center;">
                            @if (! empty($logo_url))
                                <img src="{{ $logo_url }}" alt="{{ $app_name }}" width="180" style="display:inline-block;max-width:180px;height:auto;border:0;outline:none;text-decoration:none;" />
                            @else
                                <div style="display:inline-block;padding:10px 18px;border-radius:999px;background-color:rgba(255,255,255,0.14);color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:700;letter-spacing:0.04em;">
                                    {{ $app_name }}
                                </div>
                            @endif
                            <p style="margin:18px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#fff7ed;">
                                Your order is confirmed
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 28px 8px;color:#1f2937;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 32px;color:#6b7280;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.6;">
                            <p style="margin:0 0 12px;color:#1f2937;font-size:14px;">
                                Thank you for choosing {{ $app_name }}.
                            </p>
                            @if (! empty($contact_email) || ! empty($contact_phone))
                                <p style="margin:0 0 12px;">
                                    Questions about your order?
                                    @if (! empty($contact_email))
                                        <a href="mailto:{{ $contact_email }}" style="color:#176b3a;text-decoration:none;">{{ $contact_email }}</a>
                                    @endif
                                    @if (! empty($contact_email) && ! empty($contact_phone))
                                        &nbsp;·&nbsp;
                                    @endif
                                    @if (! empty($contact_phone))
                                        <a href="tel:{{ preg_replace('/\s+/', '', (string) $contact_phone) }}" style="color:#176b3a;text-decoration:none;">{{ $contact_phone }}</a>
                                    @endif
                                </p>
                            @endif
                            @if (! empty($social))
                                <p style="margin:0 0 16px;">
                                    @foreach ($social as $network => $url)
                                        <a href="{{ $url }}" style="color:#176b3a;text-decoration:none;margin-right:12px;">{{ ucfirst($network) }}</a>
                                    @endforeach
                                </p>
                            @endif
                            <p style="margin:0;font-size:12px;color:#9ca3af;">
                                &copy; {{ $year }} {{ $app_name }}. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
