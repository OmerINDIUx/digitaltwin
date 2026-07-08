<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Pase Digital | UTOPÍA Japon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap');

        body {
            font-family: 'Space Grotesk', sans-serif;
            background: radial-gradient(circle at top, rgba(16, 185, 129, 0.14), transparent 28%), #0f172a;
        }
    </style>
</head>
<body class="min-h-screen text-white">
    <main class="mx-auto flex min-h-screen max-w-md items-center px-4 py-8">
        <section class="w-full rounded-[2rem] border border-white/10 bg-slate-900/95 p-5 shadow-2xl shadow-black/30 sm:p-6">
            <div id="pass-card" class="rounded-[1.75rem] bg-white p-5 text-slate-900 sm:p-6">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-slate-400">Pase digital</p>
                        <h1 class="mt-1 text-2xl font-bold text-slate-900">Acceso QR</h1>
                    </div>
                    <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">
                        #{{ $reservation->id }}
                    </div>
                </div>

                <div class="rounded-[1.5rem] bg-slate-950 p-4 text-center text-white">
                    <div class="rounded-[1.25rem] bg-white p-3">
                        <div id="qrcode" class="flex min-h-[240px] items-center justify-center"></div>
                    </div>
                    <p class="mt-4 text-sm font-medium text-slate-300">Muestralo al entrar</p>
                </div>

                <div class="mt-5 space-y-3">
                    <div class="rounded-2xl bg-slate-100 px-4 py-3">
                        <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-slate-400">Nombre</p>
                        <p class="mt-1 text-lg font-bold text-slate-900">{{ $reservation->name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-2xl border border-slate-200 px-4 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-slate-400">Fecha</p>
                            <p class="mt-1 text-sm font-bold text-slate-900">{{ $reservation->reservation_date->format('d M Y') }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 px-4 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-slate-400">Hora</p>
                            <p class="mt-1 text-sm font-bold text-slate-900">{{ $reservation->reservation_date->format('H:i') }} hrs</p>
                        </div>
                    </div>

                    @if($reservation->checked_in_at)
                        <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
                            Validado a las {{ $reservation->checked_in_at->format('H:i') }} hrs
                        </div>
                    @else
                        <div class="rounded-2xl bg-amber-50 px-4 py-3 text-sm font-bold text-amber-700">
                            Listo para escanear
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3">
                <button onclick="downloadPass()" id="dl-btn" class="rounded-[1.2rem] bg-white px-4 py-3 text-sm font-bold uppercase tracking-[0.16em] text-slate-900">
                    Guardar
                </button>
                <button onclick="sharePass()" id="share-btn" class="rounded-[1.2rem] bg-emerald-500 px-4 py-3 text-sm font-bold uppercase tracking-[0.16em] text-slate-950">
                    Compartir
                </button>
            </div>
        </section>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        const qrContainer = document.getElementById('qrcode');
        new QRCode(qrContainer, {
            text: "{{ route('reservations.checkin', $reservation->id) }}",
            width: 240,
            height: 240,
            colorDark: '#020617',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });

        async function getPassCanvas() {
            const card = document.getElementById('pass-card');

            return html2canvas(card, {
                scale: 2,
                backgroundColor: null,
                useCORS: true,
                logging: false
            });
        }

        async function downloadPass() {
            const btn = document.getElementById('dl-btn');
            const originalText = btn.textContent;
            btn.textContent = '...';
            btn.disabled = true;

            try {
                const canvas = await getPassCanvas();
                const link = document.createElement('a');
                link.href = canvas.toDataURL('image/png');
                link.download = 'Mi_Pase_Digital_Twin.png';
                link.click();
            } catch (error) {
                console.error(error);
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        }

        async function sharePass() {
            const btn = document.getElementById('share-btn');
            const originalText = btn.textContent;
            btn.textContent = '...';
            btn.disabled = true;

            try {
                const canvas = await getPassCanvas();
                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
                const file = new File([blob], 'Pase_Digital_Twin.png', { type: 'image/png' });

                if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({
                        files: [file],
                        title: 'Mi Pase de Acceso',
                        text: 'Te comparto mi pase digital.'
                    });
                } else {
                    const text = encodeURIComponent('Te comparto mi pase digital: ' + window.location.href);
                    window.open(`https://wa.me/?text=${text}`, '_blank');
                }
            } catch (error) {
                console.error(error);
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        }
    </script>
    @include('partials.indi-footer', ['overlay' => true, 'theme' => 'dark'])
</body>
</html>
