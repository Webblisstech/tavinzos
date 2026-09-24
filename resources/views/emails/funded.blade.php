<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Wallet funded') }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f3f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f3f5;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:460px;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 4px 24px rgba(17,24,39,0.06);">

                    {{-- Brand header --}}
                    <tr>
                        <td style="background:linear-gradient(135deg,#10b981,#059669);padding:26px 32px;">
                            <span style="color:#ffffff;font-size:18px;font-weight:800;letter-spacing:-0.4px;">{{ config('app.name', 'Tavinzos') }}</span>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:34px 32px 8px;">
                            <div style="width:52px;height:52px;background:#ecfdf5;border-radius:14px;text-align:center;line-height:52px;margin-bottom:16px;">
                                <span style="color:#059669;font-size:26px;">&#10003;</span>
                            </div>
                            <h1 style="margin:0 0 6px;font-size:21px;font-weight:800;color:#111827;letter-spacing:-0.4px;">{{ __('Wallet funded') }}</h1>
                            <p style="margin:0 0 22px;font-size:14px;line-height:1.6;color:#4b5563;">
                                {{ __('Hi :name, your wallet has been credited successfully.', ['name' => $name]) }}
                            </p>

                            {{-- Amount --}}
                            <div style="background:#f8f9fa;border:1px solid #e9ecef;border-radius:14px;padding:20px;">
                                <p style="margin:0 0 4px;font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#9ca3af;">{{ __('Amount added') }}</p>
                                <p style="margin:0 0 14px;font-family:'SF Mono',ui-monospace,Menlo,monospace;font-size:30px;font-weight:800;color:#059669;">{{ $amount }}</p>
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="font-size:13px;color:#6b7280;padding:3px 0;">{{ __('New balance') }}</td>
                                        <td align="right" style="font-family:'SF Mono',ui-monospace,Menlo,monospace;font-size:13px;font-weight:700;color:#111827;padding:3px 0;">{{ $balance }}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size:13px;color:#6b7280;padding:3px 0;">{{ __('Method') }}</td>
                                        <td align="right" style="font-size:13px;font-weight:600;color:#111827;padding:3px 0;">{{ $method }}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size:13px;color:#6b7280;padding:3px 0;">{{ __('Reference') }}</td>
                                        <td align="right" style="font-family:'SF Mono',ui-monospace,Menlo,monospace;font-size:12px;color:#6b7280;padding:3px 0;">{{ $reference }}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size:13px;color:#6b7280;padding:3px 0;">{{ __('Date') }}</td>
                                        <td align="right" style="font-size:13px;color:#6b7280;padding:3px 0;">{{ $date }}</td>
                                    </tr>
                                </table>
                            </div>

                            <p style="margin:22px 0 0;font-size:13px;line-height:1.6;color:#6b7280;">
                                {{ __("You can now buy numbers and accounts from your balance. If you didn't make this deposit, contact support immediately.") }}
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:28px 32px 32px;">
                            <hr style="border:none;border-top:1px solid #f1f3f5;margin:0 0 16px;">
                            <p style="margin:0;font-size:11.5px;line-height:1.5;color:#9ca3af;">
                                &copy; {{ date('Y') }} {{ config('app.name', 'Tavinzos') }}. {{ __('All rights reserved.') }}<br>
                                {{ __('This is an automated message — please do not reply.') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>