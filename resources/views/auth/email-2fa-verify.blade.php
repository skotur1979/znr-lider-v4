<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Sigurnosna provjera</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: Arial, sans-serif; background:#f8fafc; margin:0; padding:40px;">
    <div style="max-width:430px; margin:auto; background:white; padding:30px; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,.08);">
        <h1 style="margin-top:0;">Sigurnosna provjera</h1>

        <p>Poslali smo 6-znamenkasti sigurnosni kod na vaš e-mail.</p>

        @if (session('status'))
            <div style="background:#ecfdf5; color:#166534; padding:10px; border-radius:8px; margin-bottom:15px;">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('email-2fa.confirm') }}">
            @csrf

            <label for="code">Unesite kod</label>

            <input
                id="code"
                name="code"
                type="text"
                maxlength="6"
                required
                autofocus
                placeholder="123456"
                style="width:100%; padding:12px; margin-top:8px; border:1px solid #cbd5e1; border-radius:8px;"
            >

            @error('code')
                <div style="color:#dc2626; margin-top:8px;">{{ $message }}</div>
            @enderror

            <button type="submit" style="width:100%; margin-top:20px; padding:12px; background:#f59e0b; border:0; border-radius:8px; font-weight:bold; cursor:pointer;">
                Potvrdi kod
            </button>
        </form>

        <form method="POST" action="{{ route('email-2fa.resend') }}" style="margin-top:12px;">
            @csrf

            <button type="submit" style="width:100%; padding:10px; background:#e5e7eb; border:0; border-radius:8px; cursor:pointer;">
                Pošalji novi kod
            </button>
        </form>
    </div>
</body>
</html>