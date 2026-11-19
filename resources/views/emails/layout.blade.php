{{-- Emails always use the light brand theme with inline styles for client compatibility. --}}
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light only"><title>{{ setting('brand.name') }}</title></head>
<body style="margin:0;padding:0;background:#faf7f2;font-family:Helvetica,Arial,sans-serif;color:#221710;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#faf7f2;padding:32px 12px;">
    <tr><td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #e7dfd3;border-radius:12px;overflow:hidden;">
            <tr><td style="padding:28px 36px;border-bottom:2px dashed #e7dfd3;background:#221710;">
                <span style="font-family:Georgia,serif;font-size:24px;letter-spacing:1px;color:#f4ede3;">{{ setting('brand.name') }}</span>
                <span style="display:block;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#c99467;margin-top:4px;">{{ setting('brand.tagline') }}</span>
            </td></tr>
            <tr><td style="padding:32px 36px;font-size:15px;line-height:1.6;">
                @yield('content')
            </td></tr>
            <tr><td style="padding:20px 36px;border-top:1px solid #e7dfd3;font-size:12px;color:#6b5d52;">
                {{ setting('brand.name') }} · {{ setting('brand.email') }} · {{ setting('brand.phone') }}<br>{{ setting('brand.address') }}
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
