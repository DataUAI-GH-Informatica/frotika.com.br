// Escuta o canal privado do usuário e transforma os eventos de conclusão de
// importação em toast. É a ponta cliente do ADR-007: o primeiro caso de uso real
// do websocket (Reverb + Echo). O id do usuário vem de uma <meta> renderizada
// pelo layout autenticado.

function currentUserId() {
    const meta = document.querySelector('meta[name="user-id"]');
    const value = meta?.getAttribute('content');

    return value ? Number(value) : null;
}

// Se o usuário está acompanhando exatamente este lote, atualiza a tela.
function reloadIfWatching(elementId, uuid) {
    const page = document.getElementById(elementId);

    if (page && page.dataset.uuid === uuid) {
        window.setTimeout(() => window.location.reload(), 400);
    }
}

function cteSummary(imported, failed) {
    const importedLabel = imported === 1 ? '1 CT-e importado' : `${imported} CT-es importados`;

    if (failed === 0) {
        return { message: `${importedLabel} com sucesso.`, variant: 'success' };
    }

    if (imported === 0) {
        const noun = failed === 1 ? 'arquivo não pôde ser importado' : 'arquivos não puderam ser importados';

        return { message: `${failed} ${noun}. Veja o detalhe.`, variant: 'danger' };
    }

    return { message: `${importedLabel} · ${failed} com erro. Veja o detalhe.`, variant: 'warning' };
}

function fuelingSummary(imported, ignored, failed) {
    const parts = [imported === 1 ? '1 abastecimento importado' : `${imported} abastecimentos importados`];

    if (ignored > 0) {
        parts.push(ignored === 1 ? '1 linha já existia' : `${ignored} linhas já existiam`);
    }

    if (failed > 0) {
        parts.push(failed === 1 ? '1 linha com erro' : `${failed} linhas com erro`);
    }

    let variant = 'success';

    if (failed > 0) {
        variant = imported === 0 ? 'danger' : 'warning';
    }

    return { message: `${parts.join(' · ')}.`, variant };
}

function init() {
    const userId = currentUserId();

    if (!userId || !window.Echo) {
        return;
    }

    const channel = window.Echo.private(`App.Models.User.${userId}`);

    channel.listen('.cte-import.completed', (payload) => {
        const { message, variant } = cteSummary(Number(payload.imported || 0), Number(payload.failed || 0));

        window.frotikaToast?.({
            title: 'Importação de CT-e concluída',
            message,
            variant,
            href: payload.url,
        });

        reloadIfWatching('cte-import-result', payload.uuid);
    });

    channel.listen('.fueling-import.completed', (payload) => {
        const { message, variant } = fuelingSummary(
            Number(payload.imported || 0),
            Number(payload.ignored || 0),
            Number(payload.failed || 0),
        );

        window.frotikaToast?.({
            title: 'Importação de abastecimentos concluída',
            message,
            variant,
            href: payload.url,
        });

        reloadIfWatching('fueling-import-result', payload.uuid);
    });
}

if (document.readyState !== 'loading') {
    init();
} else {
    document.addEventListener('DOMContentLoaded', init);
}
