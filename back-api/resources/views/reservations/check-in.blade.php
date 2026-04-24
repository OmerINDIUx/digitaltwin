<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MI PASE DIGITAL | Utopía Japón</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-[#1e293b] min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    <!-- BLUR DECORATIONS -->
    <div class="absolute -top-20 -right-20 w-80 h-80 bg-indigo-500/10 rounded-full blur-[100px]"></div>
    <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-emerald-500/10 rounded-full blur-[100px]"></div>

        <div class="text-center mb-8">
            <h1 class="text-indigo-400 font-black tracking-widest text-xs uppercase">Utopía Japón Experience</h1>
            <p class="text-white text-3xl font-black mt-2 tracking-tight italic">Acceso <span class="text-indigo-500">Digital</span></p>
        </div>

        <!-- PASE CARD (CLON DE LA IMAGEN) -->
        <div class="bg-white rounded-[3.5rem] p-10 shadow-2xl relative overflow-hidden mb-8">
            
            <div class="flex justify-between items-start mb-10">
                <div class="w-16 h-16 {{ $reservation->checked_in_at ? 'bg-indigo-500' : 'bg-emerald-500' }} text-white rounded-3xl flex items-center justify-center text-3xl shadow-xl shadow-emerald-500/20">
                    @if($reservation->checked_in_at) 🔒 @else ✓ @endif
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-slate-300 font-black uppercase tracking-widest">Utopía Japón</p>
                    <p class="text-[10px] text-indigo-600 font-black uppercase tracking-widest">Access Pass</p>
                </div>
            </div>

            <div class="mb-10">
                <h2 class="text-4xl font-black text-slate-900 tracking-tight mb-8">Pase de <span class="text-emerald-500">Acceso</span></h2>
                
                <div class="space-y-5">
                    <div class="bg-slate-50 border border-slate-100 rounded-3xl p-6">
                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">Titular de Reserva</p>
                        <p class="text-xl font-black text-slate-800">{{ $reservation->name }}</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 border border-slate-100 rounded-3xl p-6">
                            <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">Fecha</p>
                            <p class="text-sm font-black text-slate-800">{{ $reservation->reservation_date->format('d M, Y') }}</p>
                        </div>
                        <div class="bg-slate-50 border border-slate-100 rounded-3xl p-6">
                            <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">Horario</p>
                            <p class="text-sm font-black text-slate-800">{{ $reservation->reservation_date->format('H:i') }} hrs</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QR CARD (BLACK) -->
            <div class="flex flex-col items-center justify-center py-10 bg-[#0f172a] rounded-[3rem] shadow-2xl">
                <div id="qrcode" class="p-4 bg-white rounded-3xl shadow-inner border-8 border-slate-800"></div>
                <p class="text-[10px] text-slate-500 font-black mt-6 tracking-[0.4em] uppercase">Validación Biométrica Requerida</p>
            </div>
        </div>

        @if($reservation->checked_in_at)
        <div class="w-full py-6 bg-indigo-600/20 border border-indigo-600/30 rounded-3xl text-center mb-6">
            <p class="text-indigo-400 font-black uppercase tracking-widest text-xs">Pase ya validado el {{ $reservation->checked_in_at->format('H:i') }}</p>
        </div>
        @else
        <div class="w-full py-6 bg-emerald-500/10 border border-emerald-500/20 rounded-3xl text-center mb-6">
            <p class="text-emerald-500 font-black uppercase tracking-widest text-xs">Presenta este QR para ingresar</p>
        </div>
        @endif

        <!-- BOTONES DE ACCIÓN PARA MÓVIL -->
        <div class="grid grid-cols-2 gap-4">
            <button onclick="downloadPass()" id="dl-btn" class="py-5 bg-white text-slate-900 font-black rounded-3xl shadow-xl flex items-center justify-center gap-2 text-sm">
                💾 GUARDAR
            </button>
            <button onclick="sharePass()" id="share-btn" class="py-5 bg-emerald-500 text-white font-black rounded-3xl shadow-xl flex items-center justify-center gap-2 text-sm">
                📱 REENVIAR
            </button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        const qrContainer = document.getElementById("qrcode");
        new QRCode(qrContainer, {
            text: "{{ route('reservations.checkin', $reservation->id) }}",
            width: 200,
            height: 200,
            colorDark : "#0f172a",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });

        async function getPassCanvas() {
            // Seleccionamos solo la tarjeta del pase para la foto
            const card = document.querySelector('.bg-white.rounded-\\[3\\.5rem\\]');
            const blurs = document.querySelectorAll('.blur-\\[100px\\]');
            
            blurs.forEach(b => b.style.opacity = '0');
            
            try {
                return await html2canvas(card, {
                    scale: 2,
                    backgroundColor: "#1e293b",
                    useCORS: true,
                    logging: false
                });
            } finally {
                blurs.forEach(b => b.style.opacity = '1');
            }
        }

        async function downloadPass() {
            const btn = document.getElementById('dl-btn');
            btn.innerHTML = '⌛...';
            try {
                const canvas = await getPassCanvas();
                const link = document.createElement("a");
                link.href = canvas.toDataURL("image/png");
                link.download = "Mi_Pase_Digital_Twin.png";
                link.click();
            } catch (e) {
                console.error(e);
            } finally {
                btn.innerHTML = '💾 GUARDAR';
            }
        }

        async function sharePass() {
            const btn = document.getElementById('share-btn');
            btn.innerHTML = '⌛...';
            try {
                const canvas = await getPassCanvas();
                const blob = await new Promise(r => canvas.toBlob(r, 'image/png'));
                const file = new File([blob], "Pase_Digital_Twin.png", { type: 'image/png' });
                
                if (navigator.share && navigator.canShare({ files: [file] })) {
                    await navigator.share({
                        files: [file],
                        title: 'Mi Pase de Acceso',
                        text: '¡Hola! Mira mi pase para el complejo.'
                    });
                } else {
                    const text = encodeURIComponent("¡Hola! Mira mi pase digital aquí: " + window.location.href);
                    window.open(`https://wa.me/?text=${text}`, '_blank');
                }
            } catch (e) {
                console.error(e);
            } finally {
                btn.innerHTML = '📱 REENVIAR';
            }
        }
    </script>
</body>
</html>
