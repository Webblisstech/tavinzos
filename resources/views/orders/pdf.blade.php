<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        /* dompdf works best with a plain, self-contained stylesheet and a
           unicode-capable font. DejaVu ships with dompdf. */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #14100f; font-size: 11px; line-height: 1.5; }

        .head { border-bottom: 2px solid #d91f2c; padding-bottom: 14px; margin-bottom: 18px; }
        .brand { font-size: 18px; font-weight: bold; color: #d91f2c; }
        .meta { color: #776a6a; font-size: 10px; margin-top: 2px; }

        h1 { font-size: 13px; margin: 14px 0 4px; }
        .sub { color: #776a6a; font-size: 10px; margin-bottom: 16px; }

        .item { border: 1px solid #e9e1e1; border-radius: 6px; margin-bottom: 10px; page-break-inside: avoid; }
        .item-head { background: #faf7f7; border-bottom: 1px solid #e9e1e1; padding: 6px 10px; }
        .item-no { font-size: 9px; font-weight: bold; letter-spacing: 0.06em; color: #776a6a; }
        .item-label { font-weight: bold; font-size: 11px; }
        .item-url { color: #776a6a; font-size: 9px; }
        .content { font-family: DejaVu Sans Mono, monospace; font-size: 10px; padding: 10px; white-space: pre-wrap; word-break: break-all; }

        .foot { margin-top: 22px; padding-top: 10px; border-top: 1px solid #e9e1e1; color: #a89898; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    <div class="head">
        <div class="brand">{{ $siteName }}</div>
        <div class="meta">Order receipt · {{ $order->reference }} · {{ \Carbon\Carbon::parse($order->created_at)->format('j M Y, H:i') }}</div>
    </div>

    <h1>{{ $order->product_name }}</h1>
    <div class="sub">{{ $order->quantity }} account(s) · {{ $total['formatted'] }}</div>

    @foreach ($items as $i => $item)
        <div class="item">
            <div class="item-head">
                <span class="item-no">ACCOUNT {{ $i + 1 }}</span>
                @if ($item->label)
                    &nbsp; <span class="item-label">{{ $item->label }}</span>
                @endif
                @if ($item->preview_url)
                    <div class="item-url">{{ $item->preview_url }}</div>
                @endif
            </div>
            <div class="content">{{ $item->content }}</div>
        </div>
    @endforeach

    <div class="foot">
        Keep this file safe — it contains your account credentials.
        Generated {{ now()->format('j M Y, H:i') }}.
    </div>
</body>
</html>