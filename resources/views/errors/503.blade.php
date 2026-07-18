@include('errors.http', [
    'status' => '503',
    'title' => 'Serviço indisponível',
    'headline' => 'O sistema está em manutenção ou com alta carga.',
    'message' => 'Alguns serviços podem ficar temporariamente indisponíveis durante este período.',
    'tip' => 'Tente novamente em instantes.',
    'tag' => 'Indisponibilidade',
])
