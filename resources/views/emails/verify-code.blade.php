<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Verification code') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4efef;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4efef;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:460px;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 4px 24px rgba(20,16,15,0.06);">

                    {{-- Brand header --}}
                    <tr>
                        <td style="background:linear-gradient(135deg,#D91F2C,#951820);padding:28px 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="width:40px;height:40px;background:rgba(255,255,255,0.15);border-radius:11px;text-align:center;vertical-align:middle;">
                                        <span style="color:#ffffff;font-size:17px;font-weight:800;letter-spacing:-1px;">IB</span>
                                    </td>
                                    <td style="padding-left:12px;">
                                        <span style="color:#ffffff;font-size:19px;font-weight:800;letter-spacing:-0.5px;">{{ config('app.name', 'IBSolutions') }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:36px 32px 8px;">
                            <h1 style="margin:0 0 8px;font-size:22px;font-weight:800;color:#14100f;letter-spacing:-0.5px;">{{ __('Verify your email') }}</h1>
                            <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#5e5252;">
                                {{ __('Hi :name, use the code below to verify your email address and activate your account.', ['name' => $name]) }}
                            </p>

                            {{-- The code --}}
                            <div style="background:#faf7f7;border:1px solid #e9e1e1;border-radius:14px;padding:20px;text-align:center;">
                                <p style="margin:0 0 6px;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#a89898;">{{ __('Your code') }}</p>
                                <p style="margin:0;font-family:'SF Mono',ui-monospace,Menlo,monospace;font-size:36px;font-weight:800;letter-spacing:8px;color:#D91F2C;">{{ $code }}</p>
                            </div>

                            <p style="margin:20px 0 0;font-size:13px;line-height:1.6;color:#776a6a;">
                                {{ __('This code expires in 15 minutes. If you didn\'t create an account, you can safely ignore this email.') }}
                            </p>
                        </td>
                    </tr>

                    {{-- Spam notice --}}
                    <tr>
                        <td style="padding:20px 32px 0;">
                            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:14px 16px;">
                                <p style="margin:0;font-size:12.5px;line-height:1.5;color:#92400e;">
                                    <strong>{{ __('Not seeing our emails?') }}</strong> {{ __('Please check your Spam or Junk folder and mark this message as "Not spam" so future codes reach your inbox.') }}
                                </p>
                            </div>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:28px 32px 32px;">
                            <hr style="border:none;border-top:1px solid #f4efef;margin:0 0 16px;">
                            <p style="margin:0;font-size:11.5px;line-height:1.5;color:#a89898;">
                                &copy; {{ date('Y') }} {{ config('app.name', 'IBSolutions') }}. {{ __('All rights reserved.') }}<br>
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