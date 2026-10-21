{{-- Emails always use the light brand theme with inline styles for client compatibility. --}}
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light only"><title>{{ setting('brand.name') }}</title></head>
<body style="margin:0;padding:0;background:#f6f0e6;font-family:Helvetica,Arial,sans-serif;color:#2b1e16;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f0e6;padding:32px 12px;">
    <tr><td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fffcf6;border:1px solid #ddd0bc;">
            <tr><td style="padding:28px 36px;border-bottom:1px solid #ddd0bc;">
                <span style="font-family:Georgia,serif;font-size:24px;letter-spacing:1px;color:#2b1e16;">{{ setting('brand.name') }}</span>
                <span style="display:block;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#7a5a1f;margin-top:4px;">{{ setting('brand.tagline') }}</span>
            </td></tr>
            <tr><td style="padding:32px 36px;font-size:15px;line-height:1.6;">
                @yield('content')
            </td></tr>
            <tr><td style="padding:20px 36px;border-top:1px solid #ddd0bc;font-size:12px;color:#66533f;">
                {{ setting('brand.name') }} · {{ setting('brand.email') }} · {{ setting('brand.phone') }}<br>{{ setting('brand.address') }}
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
