@extends('admin.dashboard_layout')

@section('content')
    <style>
        .scanner-container { position: relative; width: 100%; max-width: 500px; margin: 0 auto; aspect-ratio: 1/1; border-radius: 3rem; overflow: hidden; border: 4px solid #1e293b; box-shadow: 0 0 50px rgba(0,0,0,0.5); }
        #reader { width: 100% !important; border: none !important; }
        #reader__dashboard { background: #1e293b !important; padding: 20px !important; border-radius: 0 0 2rem 2rem !important; }
        #reader__camera_selection { background: white !important; color: black !important; border-radius: 0.5rem !important; padding: 5px !important; }
        .scan-overlay { position: absolute; inset: 0; border: 2px solid rgba(34, 211, 238, 0.3); pointer-events: none; z-index: 10; }
        .scan-line { position: absolute; width: 100%; height: 2px; background: #22d3ee; top: 0; box-shadow: 0 0 15px #22d3ee; animation: scan 2s linear infinite; }
        @keyframes scan { 0% { top: 0%; } 100% { top: 100%; } }
        .glass-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); }
    </style>

    <div class="min-h-screen bg-slate-950 text-white flex flex-col items-center justify-center p-6">
    <div class="w-full max-w-md text-center mb-8">
        <h1 class="text-4xl font-black tracking-tight mb-2 italic">SCANNER <span class="text-indigo-500">ACCESOS</span></h1>
        <p class="text-slate-400 text-sm">Escanea el código QR del pase digital del visitante para validar su entrada a las instalaciones.</p>
    </div>

    <div class="scanner-container relative">
        <div id="reader"></div>
        <div class="scan-overlay">
            <div class="scan-line"></div>
        </div>
    </div>

    <div id="result-card" class="hidden fixed bottom-10 left-6 right-6 p-8 rounded-[2.5rem] glass-card shadow-2xl z-50 animate-bounce-in">
        <div class="flex items-center gap-5">
            <div id="result-icon" class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl shrink-0"></div>
            <div>
                <h3 id="result-title" class="text-xl font-black tracking-tight uppercase"></h3>
                <p id="result-msg" class="text-slate-300 text-sm mt-1"></p>
            </div>
        </div>
    </div>

    <div class="mt-8 flex gap-4">
        <a href="{{ route('admin.dashboard') }}" class="px-6 py-3 bg-slate-800 text-slate-400 rounded-2xl font-bold text-xs hover:bg-slate-700 transition-all">← VOLVER</a>
        <button onclick="location.reload()" class="px-6 py-3 bg-indigo-600 text-white rounded-2xl font-bold text-xs shadow-lg shadow-indigo-500/20">REINICIAR CÁMARA</button>
    </div>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        const html5QrCode = new Html5Qrcode("reader");
        const qrConfig = { fps: 10, qrbox: { width: 250, height: 250 } };

        function onScanSuccess(decodedText, decodedResult) {
            // decodedText debería ser la URL: http://.../reservations/check-in/ID
            console.log("QR Detectado:", decodedText);
            
            // Detener escaneo momentáneamente
            html5QrCode.pause();
            
            // Procesar validación
            validateCheckIn(decodedText);
        }

        async function validateCheckIn(url) {
            const card = document.getElementById('result-card');
            const icon = document.getElementById('result-icon');
            const title = document.getElementById('result-title');
            const msg = document.getElementById('result-msg');

            try {
                // Forzar AJAX para obtener JSON
                const response = await fetch(url, { 
                    headers: { 
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    } 
                });
                
                const data = await response.json();
                card.classList.remove('hidden');
                
                if (data.status === 'success') {
                    icon.className = "w-16 h-16 rounded-2xl flex items-center justify-center text-3xl shrink-0 bg-emerald-500 text-white";
                    icon.innerHTML = "✓";
                    title.innerText = "ACCESO AUTORIZADO";
                    title.className = "text-xl font-black tracking-tight uppercase text-emerald-400";
                    msg.innerText = data.message;
                } else if (data.status === 'error_duplicate') {
                    icon.className = "w-16 h-16 rounded-2xl flex items-center justify-center text-3xl shrink-0 bg-amber-500 text-white";
                    icon.innerHTML = "!";
                    title.innerText = "PASE DUPLICADO";
                    title.className = "text-xl font-black tracking-tight uppercase text-amber-400";
                    msg.innerText = data.message;
                } else if (data.status === 'error_time') {
                    icon.className = "w-16 h-16 rounded-2xl flex items-center justify-center text-3xl shrink-0 bg-indigo-500 text-white";
                    icon.innerHTML = "⌛";
                    title.innerText = "DEMASIADO PRONTO";
                    title.className = "text-xl font-black tracking-tight uppercase text-indigo-400";
                    msg.innerText = data.message;
                } else if (data.status === 'error_expired') {
                    icon.className = "w-16 h-16 rounded-2xl flex items-center justify-center text-3xl shrink-0 bg-slate-500 text-white";
                    icon.innerHTML = "⌛";
                    title.innerText = "RESERVA EXPIRADA";
                    title.className = "text-xl font-black tracking-tight uppercase text-slate-400";
                    msg.innerText = data.message;
                } else {
                    icon.className = "w-16 h-16 rounded-2xl flex items-center justify-center text-3xl shrink-0 bg-rose-500 text-white";
                    icon.innerHTML = "✕";
                    title.innerText = "PASE NO VÁLIDO";
                    title.className = "text-xl font-black tracking-tight uppercase text-rose-400";
                    msg.innerText = "Este código no pertenece a nuestro sistema.";
                }

                // Reiniciar scanner tras 4 segundos
                setTimeout(() => {
                    card.classList.add('hidden');
                    html5QrCode.resume();
                }, 4000);

            } catch (err) {
                console.error(err);
                card.classList.remove('hidden');
                icon.className = "w-16 h-16 rounded-2xl flex items-center justify-center text-3xl shrink-0 bg-rose-500 text-white";
                icon.innerHTML = "✕";
                title.innerText = "ERROR DE SISTEMA";
                title.className = "text-xl font-black tracking-tight uppercase text-rose-400";
                msg.innerText = "No se pudo conectar con el servidor.";
                
                setTimeout(() => {
                    card.classList.add('hidden');
                    html5QrCode.resume();
                }, 4000);
            }
        }

        // Iniciar cámara
        Html5Qrcode.getCameras().then(cameras => {
            if (cameras && cameras.length > 0) {
                // Usar la cámara trasera por defecto si existe
                let cameraId = cameras[0].id;
                const backCamera = cameras.find(c => c.label.toLowerCase().includes('back'));
                if (backCamera) cameraId = backCamera.id;

                html5QrCode.start(cameraId, qrConfig, onScanSuccess);
            }
        }).catch(err => {
            console.error("No se pudo acceder a la cámara", err);
            alert("Error: No se encontró una cámara disponible.");
        });
    </script>
    </div>
@endsection
