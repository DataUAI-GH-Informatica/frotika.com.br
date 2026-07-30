// Aprimora o seletor da planilha de abastecimentos: mostra nome e tamanho do
// arquivo escolhido e trava o envio quando a extensão não é .xlsx. Progressive
// enhancement — sem JS o formulário ainda envia e o servidor valida de novo.

const UNITS = ['B', 'KB', 'MB'];

function humanSize(bytes) {
    let value = bytes;
    let unit = 0;

    while (value >= 1024 && unit < UNITS.length - 1) {
        value /= 1024;
        unit += 1;
    }

    return `${value.toFixed(unit === 0 ? 0 : 1)} ${UNITS[unit]}`;
}

function init() {
    const form = document.querySelector('[data-fueling-import]');

    if (!form) {
        return;
    }

    const input = form.querySelector('[data-fueling-import-input]');
    const summary = form.querySelector('[data-fueling-import-summary]');
    const name = form.querySelector('[data-fueling-import-name]');
    const size = form.querySelector('[data-fueling-import-size]');
    const warning = form.querySelector('[data-fueling-import-warning]');
    const submit = form.querySelector('[data-fueling-import-submit]');

    if (!input) {
        return;
    }

    input.addEventListener('change', () => {
        const file = (input.files || [])[0];

        summary.hidden = !file;

        if (!file) {
            if (submit) {
                submit.disabled = false;
            }

            return;
        }

        name.textContent = file.name;
        size.textContent = humanSize(file.size);

        const invalid = !file.name.toLowerCase().endsWith('.xlsx');

        if (warning) {
            warning.hidden = !invalid;
        }

        if (submit) {
            submit.disabled = invalid;
        }
    });
}

if (document.readyState !== 'loading') {
    init();
} else {
    document.addEventListener('DOMContentLoaded', init);
}
