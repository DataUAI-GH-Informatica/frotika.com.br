@include('errors.http', [
    'status' => '404',
    'title' => 'Página não encontrada',
    'headline' => 'Não encontramos este endereço no Frotika.',
    'message' => 'A URL pode ter mudado ou o link pode estar incompleto.',
    'tip' => 'Use o menu principal para voltar ao fluxo e localizar a tela correta.',
    'tag' => 'Rota inválida',
])
