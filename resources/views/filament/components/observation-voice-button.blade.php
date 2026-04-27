<div
    style="display:flex; align-items:end; height:100%; padding-top:28px;"
    x-data
>
    <button
        type="button"
        x-on:click="
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

            if (! SpeechRecognition) {
                alert('Browser ne podržava glasovni unos. Probaj Chrome ili Edge.');
                return;
            }

            const targetElement = document.querySelector('[data-voice-target=&quot;{{ $target }}&quot;]');

            if (! targetElement) {
                alert('Element nije pronađen: {{ $target }}');
                return;
            }

            let field = targetElement;

            if (! ['TEXTAREA', 'INPUT'].includes(field.tagName)) {
                field = targetElement.querySelector('textarea, input');
            }

            if (! field) {
                alert('Polje za unos nije pronađeno unutar elementa: {{ $target }}');
                return;
            }

            const recognition = new SpeechRecognition();

            recognition.lang = 'hr-HR';
            recognition.interimResults = false;
            recognition.continuous = false;

            recognition.onstart = function () {
                console.log('🎤 Mikrofon pokrenut za: {{ $target }}');
            };

            recognition.onresult = function (event) {
                const text = event.results[0][0].transcript;
                console.log('Prepoznat tekst:', text);

                const currentValue = field.value || '';
                const newValue = currentValue ? currentValue + ' ' + text : text;

                field.focus();
                field.value = newValue;
                field.innerText = newValue;
                field.textContent = newValue;

                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('change', { bubbles: true }));
                field.dispatchEvent(new KeyboardEvent('keyup', { bubbles: true }));

                if (field._x_model && typeof field._x_model.set === 'function') {
                    field._x_model.set(newValue);
                }

                console.log('Upisano u stvarno polje:', field.tagName, newValue);
            };

            recognition.onerror = function (event) {
                console.error('Greška mikrofona:', event.error);
                alert('Greška mikrofona: ' + event.error);
            };

            recognition.start();
        "
        style="
            background:#f59e0b;
            color:#111827;
            font-weight:700;
            padding:10px 14px;
            border-radius:8px;
            border:0;
            cursor:pointer;
        "
    >
        🎙️ {{ $label }}
    </button>
</div>