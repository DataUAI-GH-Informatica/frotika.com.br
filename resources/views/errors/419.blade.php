@include('errors.http', [
    'status' => '419',
    'title' => 'Sessão expirada',
    'headline' => 'Seu tempo de uso desta tela terminou.',
    'message' => 'Para manter a segurança, a sessão foi encerrada e este formulário não pode mais ser enviado.',
    'tip' => 'Atualize a página e tente novamente.',
    'tag' => 'Sessão',
])
